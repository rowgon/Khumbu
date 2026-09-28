<?php

declare( strict_types=1 );

/**
 * Database Gateway
 *
 * Responsibilities:
 * 1. Manages the raw mysqli connection.
 * 2. Provides client-side value quoting for safe SQL interpolation.
 * 3. Executes raw SQL queries and maps results to associative arrays.
 * 4. Packages query and transaction outcomes into Revalt DTOs.
 * 5. Manages database transactions.
 *
 * Side Effects:
 * - Instances manage a live TCP socket connection to a MySQL server.
 * - Query methods modify the state of the connected database.
 * - wrap() mutates the provided connection by setting its charset to utf8mb4.
 *
 * Environment contract: assumes MYSQLI_REPORT_OFF is set globally.
 *
 * Warning: If MYSQLI_REPORT_STRICT is enabled by external code,
 * mysqli will throw exceptions instead of returning false, bypassing Revalt and
 * propagating to callers. Reliable operation is not guaranteed in that mode.
 *
 * @version 4.1
 */
class CRB_Database {
	/**
	 * LIKE escape character. The query builder must use the same character when emitting ESCAPE clauses for LIKE and NOT LIKE conditions.
	 */
	public const LIKE_ESCAPE_CHAR = '|';

	/**
	 * Fetch mode for query(): indexed array of row objects (stdClass).
	 */
	public const FETCH_OBJECTS = 5;

	/**
	 * Fetch mode for query(): row objects keyed by the value of each row's first column.
	 * Duplicate first-column values overwrite earlier rows.
	 */
	public const FETCH_OBJECTS_KEY = 6;

	/**
	 * @var mysqli The active database connection instance.
	 */
	private mysqli $connection;

	/**
	 * Private constructor to enforce dependency injection via named constructors.
	 *
	 * @param mysqli $connection An active mysqli connection instance.
	 */
	private function __construct( mysqli $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Builds a structured error context array from the current mysqli connection state.
	 *
	 * Assumption: Revalt::add_error() accepts a mixed value as its third argument
	 * (verified in Revalt.php), so passing an array here is safe. If that ever
	 * changes to a scalar-only contract, fall back to passing $this->connection->errno
	 * and fold $this->connection->error into the message string.
	 *
	 * @return array{errno: int, error: string, sqlstate: string}
	 */
	private function collect_mysqli_error(): array {
		return array(
			'errno'    => $this->connection->errno,
			'error'    => $this->connection->error,
			'sqlstate' => $this->connection->sqlstate,
		);
	}

	/**
	 * Attempts to enforce the connection charset without emitting web server warnings.
	 *
	 * mysqli::set_charset() can emit a PHP warning before returning false when
	 * the connection is not ready for another command, for example after a stale
	 * socket or an unconsumed result set. The adapter reports setup failures via
	 * Revalt in connect(), while wrap() keeps its existing best-effort contract.
	 *
	 * @param mysqli $connection The connection whose charset should be set.
	 *
	 * @return bool True when mysqli accepted the requested charset.
	 */
	private static function set_utf8mb4_charset( mysqli $connection ): bool {
		return @$connection->set_charset( 'utf8mb4' );
	}

	/**
	 * Factory method to establish a completely new database connection.
	 *
	 * Expected successful data format:
	 * - Contains a new, initialized instance of CRB_Database.
	 *
	 * Possible errors:
	 * - 'db_connection_error': Triggered if the connection fails.
	 *
	 * Side Effects:
	 * - Opens a new network socket connection to the specified database host.
	 *
	 * @param string $host The database host.
	 * @param string $user The database user.
	 * @param string $password The database password.
	 * @param string $database The database name.
	 * @param int $port The database port (default 3306).
	 *
	 * @return Revalt<self> The initialized database adapter instance wrapped in a Revalt DTO.
	 */
	public static function connect( string $host, string $user, string $password, string $database, int $port = 3306 ): Revalt {
		$result = new Revalt();

		// Suppress the PHP warning at construction; connect_error already carries the same information.
		$connection = @new mysqli( $host, $user, $password, $database, $port );

		// Detect connection failures explicitly and avoid false positives from empty connect_error values.
		if ( $connection->connect_errno !== 0 || ! empty( $connection->connect_error ) ) {
			$result->add_error( 'db_connection_error', 'Failed to connect to MySQL database: ' . $connection->connect_error, $connection->connect_errno );

			return $result;
		}

		// Enforce charset to prevent SQL injection via multibyte characters.
		if ( self::set_utf8mb4_charset( $connection ) === false ) {
			// From the caller's perspective, connection setup has failed; reuse the connection error code.
			$result->add_error( 'db_connection_error', 'Failed to set MySQL connection charset to utf8mb4: ' . $connection->error, $connection->errno );
			$connection->close();

			return $result;
		}

		$result->success( new self( $connection ) );

		return $result;
	}

	/**
	 * Factory method to wrap an existing database connection.
	 *
	 * Designed to implement Dependency Injection and reuse existing resources.
	 *
	 * Side Effects:
	 * - Does not open new ports. Mutates the provided mysqli connection by enforcing the utf8mb4 charset.
	 *
	 * @param mysqli $existing_connection An already established mysqli connection.
	 *
	 * @return self The initialized database adapter instance.
	 */
	public static function wrap( mysqli $existing_connection ): self {
		// Enforce charset to prevent SQL injection via multibyte characters. The return value is
		// intentionally ignored: callers of wrap() are providing their own connection and accept
		// responsibility for its state. If charset enforcement fails, the issue will surface on
		// the next query() call rather than altering this method's public signature.
		self::set_utf8mb4_charset( $existing_connection );

		return new self( $existing_connection );
	}

	/**
	 * Smart quote and escape function to protect against SQL injection.
	 *
	 * Applies context-aware escaping logic to different scalar types.
	 *
	 * @param null|bool|int|float|string $value The value to escape and quote.
	 *
	 * @return string|int A safe SQL literal: numeric and boolean values as bare int,
	 *                    null as the NULL keyword, strings escaped and wrapped in single quotes.
	 *
	 * @throws RuntimeException
	 */
	public function quote( $value ) {
		// Handle explicit null values or empty data logic.
		if ( $value === null ) {
			return 'NULL';
		}

		// Convert booleans to standard integer representations.
		if ( is_bool( $value ) ) {
			return $value ? 1 : 0;
		}

		// Trust integers as they are natively safe.
		if ( is_int( $value ) ) {
			return $value;
		}

		// Ensure float is formatted consistently without locale comma issues.
		if ( is_float( $value ) ) {
			return sprintf( '%F', $value );
		}

		if ( is_string( $value ) ) {

			// Apply real escape string and wrap in single quotes for strings.
			// A dead connection yields an empty escaped string, but the resulting query is expected to fail downstream in query()
			$escaped = $this->connection->real_escape_string( $value );
		}
		else {
			throw new RuntimeException( 'Cannot quote non-scalar value of type ' . gettype( $value ) . '.' );
		}

		return '\'' . (string) $escaped . '\'';
	}

	/**
	 * Quotes an array of values for safe use in IN or NOT IN clauses.
	 *
	 * Side Effects:
	 * - Pure interpolation logic.
	 *
	 * @param array<int|string, mixed> $values The list of values to quote.
	 *
	 * @return string The comma-separated string wrapped in parentheses.
	 */
	public function quote_array( array $values ): string {
		// Provide a safe fallback to prevent syntax errors on empty arrays.
		if ( empty( $values ) ) {
			return '(NULL)';
		}

		$quoted_values = array();

		// Safely quote each individual item.
		foreach ( $values as $value ) {
			$quoted_values[] = $this->quote( $value );
		}

		return '(' . implode( ', ', $quoted_values ) . ')';
	}

	/**
	 * Escapes LIKE wildcard characters in a user-provided literal.
	 *
	 * This method preserves the input text while neutralizing LIKE pattern
	 * metacharacters. It does not quote the value as an SQL string literal.
	 *
	 * Example usage:
	 *
	 * $search_pattern = '%' . CRB_Database::escape_like( $search_term ) . '%';
	 * $result = $db->table( 'users' )->where( 'username', 'LIKE', $search_pattern )->get_query_results();
	 *
	 * @param string $literal Raw user input to use as literal LIKE text.
	 *
	 * @return string Literal text escaped for use inside a LIKE pattern.
	 */
	public static function escape_like( string $literal ): string {
		$escape_char = self::LIKE_ESCAPE_CHAR;

		return strtr( $literal, array(
			$escape_char => $escape_char . $escape_char,
			'%'          => $escape_char . '%',
			'_'          => $escape_char . '_',
		) );
	}

	/**
	 * Executes a raw SQL query directly against the database connection.
	 *
	 * For SELECT-style queries the success payload shape depends on $fetch_mode:
	 * - MYSQLI_ASSOC (default): indexed array of associative row arrays keyed by column name.
	 * - MYSQLI_NUM: indexed array of numerically indexed row arrays.
	 * - CRB_Database::FETCH_OBJECTS: indexed array of row objects (stdClass).
	 * - CRB_Database::FETCH_OBJECTS_KEY: array of row objects keyed by the value of each row's first column.
	 *   Duplicate first-column values overwrite earlier rows.
	 *
	 * For write queries the payload is always an associative array containing 'affected_rows'
	 * and 'insert_id'. The $fetch_mode argument does not apply to writes and is ignored.
	 *
	 * Possible errors:
	 * - 'db_query_error': the query failed to execute.
	 * - 'db_unsupported_fetch_mode': $fetch_mode is not one of the supported constants.
	 *
	 * @param string $sql The fully interpolated and safe SQL string to execute.
	 * @param int $fetch_mode One of MYSQLI_ASSOC, MYSQLI_NUM, CRB_Database::FETCH_OBJECTS, CRB_Database::FETCH_OBJECTS_KEY.
	 *
	 * @return Revalt<array<int, array<string, mixed>>|array<int, array<int, mixed>>|array<int, object>|array<string, object>|array<string, int>> Result object containing the query outcome.
	 */
	public function query( string $sql, int $fetch_mode = MYSQLI_ASSOC ): Revalt {
		$query_result = new Revalt();

		// Execute the raw query through the connection.
		$mysqli_result = $this->connection->query( $sql );

		// Detect query failure explicitly; mysqli no longer throws because we do not toggle
		// MYSQLI_REPORT_STRICT globally. The SQL string is intentionally NOT included in the
		// error payload to avoid leaking query data (with interpolated user values) into logs;
		// structured mysqli error context is passed instead.
		if ( $mysqli_result === false ) {
			$query_result->add_error( 'db_query_error', 'Failed to execute MySQL query: ' . $this->connection->error, $this->collect_mysqli_error() );

			return $query_result;
		}

		// Handle write queries that return a boolean success state. Fetch mode does not apply here.
		if ( $mysqli_result === true ) {
			$query_result->success( array(
				'affected_rows' => $this->connection->affected_rows,
				'insert_id'     => $this->connection->insert_id,
			) );

			return $query_result;
		}

		// Read query: map the open result set into the requested row shape.
		return $this->fetch_result_set( $mysqli_result, $fetch_mode );
	}

	/**
	 * Maps an open mysqli result set into the requested row shape and frees it.
	 *
	 * The result set is freed in every branch, including the unsupported-mode error path,
	 * so the caller never needs to free it.
	 *
	 * @param mysqli_result $mysqli_result Open result set produced by a SELECT-style query.
	 * @param int $fetch_mode One of MYSQLI_ASSOC, MYSQLI_NUM, CRB_Database::FETCH_OBJECTS, CRB_Database::FETCH_OBJECTS_KEY.
	 *
	 * @return Revalt<array<int, array<string, mixed>>|array<int, array<int, mixed>>|array<int, object>|array<string, object>> Result object containing the mapped rows.
	 */
	private function fetch_result_set( mysqli_result $mysqli_result, int $fetch_mode ): Revalt {
		$result = new Revalt();

		$supported_modes = array( MYSQLI_ASSOC, MYSQLI_NUM, self::FETCH_OBJECTS, self::FETCH_OBJECTS_KEY );

		// Reject unsupported modes up front, freeing the set so it is never leaked.
		if ( ! in_array( $fetch_mode, $supported_modes, true ) ) {
			$mysqli_result->free();
			$result->add_error( 'db_unsupported_fetch_mode', 'Unsupported fetch mode specified.', $fetch_mode );

			return $result;
		}

		// Fast path: scalar-indexed shapes are pulled in a single mysqlnd call, when available.
		if ( $fetch_mode === MYSQLI_ASSOC || $fetch_mode === MYSQLI_NUM ) {
			if ( function_exists( 'mysqli_fetch_all' ) ) {
				$rows = $mysqli_result->fetch_all( $fetch_mode );
			}
			else {
				// mysqli_result::fetch_all() is a mysqlnd-only method; hosts running the
				// older libmysqlclient driver do not expose it, so fetch row by row instead.
				$rows = array();

				while ( $row = $mysqli_result->fetch_array( $fetch_mode ) ) {
					$rows[] = $row;
				}
			}

			$mysqli_result->free();
			$result->success( $rows );

			return $result;
		}

		// Object shapes require row-by-row fetching because fetch_all() cannot produce objects.
		$rows = array();

		while ( $row = $mysqli_result->fetch_object() ) {
			if ( $fetch_mode === self::FETCH_OBJECTS_KEY ) {
				// Key each row by the value of its first column; duplicates overwrite earlier rows.
				// Iterate once to get the first property value without allocating a full array copy.
				foreach ( $row as $first_column_value ) {
					break;
				}
				$rows[ $first_column_value ] = $row;
				continue;
			}

			$rows[] = $row;
		}

		$mysqli_result->free();
		$result->success( $rows );

		return $result;
	}

	/**
	 * Executes a SELECT-style query using an unbuffered result set and returns a row stream.
	 *
	 * The returned Revalt reports stream initialization errors. On success, its payload is a Generator that owns the open mysqli_result.
	 *
	 * Usage constraints:
	 * - While the stream is active, do not execute any other query on the same mysqli connection.
	 * - The caller must either iterate the stream to completion or explicitly close
	 *    the Generator when stopping early. Closing the stream is required to free
	 *    the underlying mysqli_result and unlock the connection for subsequent queries.
	 * - Unconsumed stream must be closed: do not hold an unconsumed stream open across other queries.
	 *
	 * Supported fetch modes:
	 * - MYSQLI_ASSOC: yields associative row arrays.
	 * - MYSQLI_NUM: yields numeric row arrays.
	 * - CRB_Database::FETCH_OBJECTS: yields stdClass row objects.
	 *
	 * Usage example:
	 *
	 *  $stream_result = $db->query_stream( $sql, CRB_Database::FETCH_OBJECTS );
	 *
	 *  if ( $stream_result->has_errors() ) {
	 *      return $stream_result;
	 *  }
	 *
	 *  $stream = $stream_result->get_results();
	 *
	 *  try {
	 *      foreach ( $stream as $row ) {
	 *          yield $row;
	 *      }
	 *  }
	 *  finally {
	 *      $stream = null;
	 *      $stream_result = null;
	 * }
	 *
	 *
	 * @param string $sql Fully interpolated and safe SELECT-style SQL string to execute.
	 * @param int $fetch_mode One of MYSQLI_ASSOC, MYSQLI_NUM, CRB_Database::FETCH_OBJECTS.
	 *
	 * @return Revalt<Generator<int, array<string, mixed>|array<int, mixed>|stdClass>> Result object containing the row stream.
	 */
	public function query_stream( string $sql, int $fetch_mode = MYSQLI_ASSOC ): Revalt {
		$stream_result = new Revalt();

		$supported_modes = array( MYSQLI_ASSOC, MYSQLI_NUM, self::FETCH_OBJECTS );

		// Reject unsupported modes before opening an unbuffered result set.
		if ( ! in_array( $fetch_mode, $supported_modes, true ) ) {
			$stream_result->add_error( 'db_unsupported_fetch_mode', 'Unsupported streaming fetch mode specified.', $fetch_mode );

			return $stream_result;
		}

		// Open an unbuffered result set. This locks the connection until freed.
		$mysqli_result = $this->connection->query( $sql, MYSQLI_USE_RESULT );

		if ( $mysqli_result === false ) {
			$stream_result->add_error( 'db_query_error', 'Failed to execute MySQL streaming query: ' . $this->connection->error, $this->collect_mysqli_error() );

			return $stream_result;
		}

		if ( $mysqli_result === true ) {
			$stream_result->add_error( 'db_streaming_query_error', 'Streaming query did not produce a result set.' );

			return $stream_result;
		}

		// Keep streaming logic local to this method without adding a private helper.
		$stream = ( static function () use ( $mysqli_result, $fetch_mode ): Generator {
			try {
				while ( true ) {
					if ( $fetch_mode === self::FETCH_OBJECTS ) {
						$row = $mysqli_result->fetch_object();
					}
					else {
						$row = $mysqli_result->fetch_array( $fetch_mode );
					}

					if ( $row === null || $row === false ) {
						break;
					}

					yield $row;
				}
			} finally {
				$mysqli_result->free();
			}
		} )();

		$stream_result->success( $stream );

		return $stream_result;
	}

	/**
	 * Starts a new database transaction.
	 *
	 * Side Effects:
	 * - Modifies the connection state to begin a transactional block.
	 *
	 * @return Revalt<bool> Result object indicating success.
	 */
	public function begin_transaction(): Revalt {
		$result = new Revalt();

		// Instruct the connection to start a transaction. mysqli returns false on failure.
		if ( $this->connection->begin_transaction() === false ) {
			$result->add_error( 'db_transaction_error', 'Failed to begin transaction: ' . $this->connection->error, $this->collect_mysqli_error() );

			return $result;
		}

		$result->success( true );

		return $result;
	}

	/**
	 * Commits the current database transaction.
	 *
	 * Side Effects:
	 * - Applies all pending transactional operations permanently to the database.
	 *
	 * @return Revalt<bool> Result object indicating success.
	 */
	public function commit(): Revalt {
		$result = new Revalt();

		// Instruct the connection to commit. mysqli returns false on failure.
		if ( $this->connection->commit() === false ) {
			$result->add_error( 'db_transaction_error', 'Failed to commit transaction: ' . $this->connection->error, $this->collect_mysqli_error() );

			return $result;
		}

		$result->success( true );

		return $result;
	}

	/**
	 * Rolls back the current database transaction.
	 *
	 * Side Effects:
	 * - Reverts all pending transactional operations.
	 *
	 * @return Revalt<bool> Result object indicating success.
	 */
	public function rollback(): Revalt {
		$result = new Revalt();

		// Instruct the connection to rollback. mysqli returns false on failure.
		if ( $this->connection->rollback() === false ) {
			$result->add_error( 'db_transaction_error', 'Failed to rollback transaction: ' . $this->connection->error, $this->collect_mysqli_error() );

			return $result;
		}

		$result->success( true );

		return $result;
	}

	/**
	 * Factory method to start a new query builder chain.
	 *
	 * Side Effects:
	 * - Creates and returns a new CRB_Query_Builder instance.
	 *
	 * @param string $table_name The name of the database table to operate on.
	 *
	 * @return CRB_Query_Builder The fluent query builder instance.
	 */
	public function table( string $table_name ): CRB_Query_Builder {
		return new CRB_Query_Builder( $this, $table_name );
	}

	/**
	 * Retrieves the underlying raw mysqli connection.
	 *
	 * @return mysqli The raw mysqli connection object.
	 */
	public function get_connection(): mysqli {
		return $this->connection;
	}

	/**
	 * Explicitly closes the active database connection.
	 *
	 * Side Effects:
	 * - Terminates the TCP connection to the MySQL server.
	 *
	 * @return void
	 */
	public function close(): void {
		$this->connection->close();
	}
}
