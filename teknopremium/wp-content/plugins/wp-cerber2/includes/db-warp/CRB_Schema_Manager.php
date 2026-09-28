<?php
/*
	Copyright (C) 2025-26 CERBER TECH INC., https://wpcerber.com

    Licensed under the GNU GPL.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

declare( strict_types=1 );

/**
 * Database Schema Manager
 *
 * CRB_Schema_Manager is a focused schema-maintenance service built on top of CRB_Database
 * for operations that are intentionally outside CRB_Query_Builder: whitelisted MySQL metadata
 * inspection via SHOW statements and controlled DDL maintenance such as ensuring index definitions.
 * Its responsibility is to validate schema identifiers, compile only supported schema-level SQL,
 * execute it through the injected database gateway, and return deterministic Revalt outcomes
 * without exposing a generic raw-SQL interface or expanding the CRUD query builder's scope.
 *
 * Responsibilities:
 * 1. Compiles whitelisted MySQL schema metadata SHOW statements.
 * 2. Validates table and metadata identifiers locally without depending on CRB_Query_Builder internals.
 * 3. Executes metadata queries through CRB_Database while preserving Revalt-based control flow.
 *
 * Side Effects:
 * - Executes read-only MySQL metadata statements through the provided CRB_Database instance.
 * - Does not mutate CRB_Database, CRB_Query_Builder, or any global state.
 *
 * Design constraints:
 * - This class is intentionally self-contained and does not modify or call private helpers from other DB classes.
 * - get_metadata() is whitelisted by metadata type and must not become a generic SHOW executor.
 *
 * @version 2.7
 */
final class CRB_Schema_Manager {
	/**
	 * Metadata type for SHOW FIELDS FROM table.
	 */
	public const META_FIELDS = 'fields';

	/**
	 * Metadata type for SHOW FULL COLUMNS FROM table.
	 */
	public const META_FULL_COLUMNS = 'full_columns';

	/**
	 * Metadata type for SHOW INDEX FROM table.
	 */
	public const META_INDEXES = 'indexes';

	/**
	 * Metadata type for SHOW VARIABLES.
	 */
	public const META_VARIABLES = 'variables';

	/**
	 * Metadata type for SHOW TABLE STATUS.
	 */
	public const META_TABLE_STATUS = 'table_status';

	/**
	 * @var CRB_Database Database adapter used for quoting and query execution.
	 */
	private CRB_Database $db;

	/**
	 * Initializes the schema manager with an existing database adapter.
	 *
	 * @param CRB_Database $db Database adapter used for quoting and query execution.
	 */
	public function __construct( CRB_Database $db ) {
		$this->db = $db;
	}

	/**
	 * Retrieves whitelisted MySQL metadata using SHOW statements.
	 *
	 * Supported metadata types and arguments:
	 * - self::META_FIELDS: SHOW FIELDS FROM table, required string 'table', optional string 'field'.
	 * - self::META_FULL_COLUMNS: SHOW FULL COLUMNS FROM table, required string 'table', optional string 'field'.
	 * - self::META_INDEXES: SHOW INDEX FROM table, required string 'table', optional string 'key_name'.
	 * - self::META_VARIABLES: SHOW VARIABLES, optional string 'like'.
	 * - self::META_TABLE_STATUS: SHOW TABLE STATUS, optional string 'name'.
	 *
	 * Return payload:
	 * - On success: indexed list of associative metadata rows.
	 * - On validation failure: Revalt error with no query execution.
	 * - On database failure: propagated CRB_Database::query() Revalt error.
	 *
	 * @param string $metadata_type One of the self::META_* constants.
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<array<int, array<string, mixed>>> Result object containing metadata rows.
	 */
	public function get_metadata( string $metadata_type, array $args = array() ): Revalt {
		// Compile only whitelisted metadata statements before touching the database.
		$sql_result = $this->build_metadata_sql( $metadata_type, $args );
		if ( $sql_result->has_errors() ) {
			return $sql_result;
		}

		return $this->db->query( $sql_result->get_results( '' ) );
	}

	/**
	 * Builds a whitelisted MySQL metadata SQL statement.
	 *
	 * @param string $metadata_type One of the self::META_* constants.
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_metadata_sql( string $metadata_type, array $args ): Revalt {
		// Route by fixed metadata operation instead of accepting arbitrary SQL fragments.
		switch ( $metadata_type ) {
			case self::META_FIELDS:
				return $this->build_show_fields_sql( $args );

			case self::META_FULL_COLUMNS:
				return $this->build_show_full_columns_sql( $args );

			case self::META_INDEXES:
				return $this->build_show_indexes_sql( $args );

			case self::META_VARIABLES:
				return $this->build_show_variables_sql( $args );

			case self::META_TABLE_STATUS:
				return $this->build_show_table_status_sql( $args );
		}

		return new Revalt(
			null,
			'invalid_metadata_type',
			'Unsupported metadata type.',
			$metadata_type
		);
	}

	/**
	 * Builds SHOW FIELDS FROM table SQL.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_show_fields_sql( array $args ): Revalt {
		$table_result = $this->get_required_table_reference( $args );
		if ( $table_result->has_errors() ) {
			return $table_result;
		}

		$sql = 'SHOW FIELDS FROM ' . $table_result->get_results( '' );

		// Apply optional field filter using the SHOW result column name.
		if ( array_key_exists( 'field', $args ) ) {
			$field_result = $this->get_non_empty_string_argument( $args, 'field' );
			if ( $field_result->has_errors() ) {
				return $field_result;
			}

			$sql .= ' WHERE Field = ' . $this->db->quote( $field_result->get_results( '' ) );
		}

		return new Revalt( $sql );
	}

	/**
	 * Builds SHOW FULL COLUMNS FROM table SQL.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_show_full_columns_sql( array $args ): Revalt {
		$table_result = $this->get_required_table_reference( $args );
		if ( $table_result->has_errors() ) {
			return $table_result;
		}

		$sql = 'SHOW FULL COLUMNS FROM ' . $table_result->get_results( '' );

		// Apply optional field filter using the SHOW result column name.
		if ( array_key_exists( 'field', $args ) ) {
			$field_result = $this->get_non_empty_string_argument( $args, 'field' );
			if ( $field_result->has_errors() ) {
				return $field_result;
			}

			$sql .= ' WHERE Field = ' . $this->db->quote( $field_result->get_results( '' ) );
		}

		return new Revalt( $sql );
	}

	/**
	 * Builds SHOW INDEX FROM table SQL.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_show_indexes_sql( array $args ): Revalt {
		$table_result = $this->get_required_table_reference( $args );
		if ( $table_result->has_errors() ) {
			return $table_result;
		}

		$sql = 'SHOW INDEX FROM ' . $table_result->get_results( '' );

		// Apply optional index-name filter using the SHOW result column name.
		if ( array_key_exists( 'key_name', $args ) ) {
			$key_result = $this->get_non_empty_string_argument( $args, 'key_name' );
			if ( $key_result->has_errors() ) {
				return $key_result;
			}

			$sql .= ' WHERE Key_name = ' . $this->db->quote( $key_result->get_results( '' ) );
		}

		return new Revalt( $sql );
	}

	/**
	 * Builds SHOW VARIABLES SQL.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_show_variables_sql( array $args ): Revalt {
		$sql = 'SHOW VARIABLES';

		// Apply optional LIKE pattern as a string literal, not as an identifier.
		if ( array_key_exists( 'like', $args ) ) {
			$like_result = $this->get_non_empty_string_argument( $args, 'like' );
			if ( $like_result->has_errors() ) {
				return $like_result;
			}

			$sql .= ' LIKE ' . $this->db->quote( $like_result->get_results( '' ) );
		}

		return new Revalt( $sql );
	}

	/**
	 * Builds SHOW TABLE STATUS SQL.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a compiled SQL statement.
	 */
	private function build_show_table_status_sql( array $args ): Revalt {
		$sql = 'SHOW TABLE STATUS';

		// Apply optional table-name filter using the SHOW result column name.
		if ( array_key_exists( 'name', $args ) ) {
			$name_result = $this->get_non_empty_string_argument( $args, 'name' );
			if ( $name_result->has_errors() ) {
				return $name_result;
			}

			$sql .= ' WHERE Name = ' . $this->db->quote( $name_result->get_results( '' ) );
		}

		return new Revalt( $sql );
	}

	/**
	 * Retrieves and validates a required table reference argument.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 *
	 * @return Revalt<string> Result object containing a validated table reference.
	 */
	private function get_required_table_reference( array $args ): Revalt {
		$argument_result = $this->get_non_empty_string_argument( $args, 'table' );
		if ( $argument_result->has_errors() ) {
			return $argument_result;
		}

		$table_reference = $this->safe_ident( $argument_result->get_results( '' ) );
		if ( $table_reference === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid metadata table identifier.',
				$argument_result->get_results( '' )
			);
		}

		return new Revalt( $table_reference );
	}

	/**
	 * Retrieves a required or optional non-empty string argument from metadata args.
	 *
	 * @param array<string, mixed> $args Metadata query arguments.
	 * @param string $argument_name Argument key to read.
	 *
	 * @return Revalt<string> Result object containing the normalized argument string.
	 */
	private function get_non_empty_string_argument( array $args, string $argument_name ): Revalt {
		if ( ! array_key_exists( $argument_name, $args ) ) {
			return new Revalt(
				null,
				'missing_argument',
				'Metadata argument is required.',
				$argument_name
			);
		}

		$argument_value = $args[ $argument_name ];

		if ( ! is_string( $argument_value ) ) {
			return new Revalt(
				null,
				'invalid_argument',
				'Metadata argument must be a string.',
				array(
					'argument' => $argument_name,
					'type'     => gettype( $argument_value ),
				)
			);
		}

		$argument_value = trim( $argument_value );

		if ( $argument_value === '' ) {
			return new Revalt(
				null,
				'invalid_argument',
				'Metadata argument must be a non-empty string.',
				$argument_name
			);
		}

		return new Revalt( $argument_value );
	}

	/**
	 * Validates a MySQL identifier and returns it unquoted, or an empty string when invalid.
	 *
	 * Accepts a single identifier or a two-segment qualified name (segment.segment).
	 * Each segment may contain ASCII letters, digits, and underscores, and is limited
	 * to 64 characters, matching the MySQL identifier length limit. Numeric-only
	 * segments such as 1024 are accepted.
	 *
	 * The returned value is not quoted. Most identifiers are valid unquoted, but
	 * reserved words and numeric-only names are not, so callers interpolating the
	 * result into SQL should backtick-quote it by default to stay safe. When quoting
	 * a qualified name, quote each segment separately rather than the dotted string
	 * as a whole.
	 *
	 * @param string $ident Raw identifier name. Surrounding whitespace is trimmed.
	 *
	 * @return string Validated unquoted identifier, or an empty string when the input fails validation.
	 */
	private function safe_ident( string $ident ): string {
		// Maximum length MySQL allows for a single identifier.
		$max_length = 64;

		$ident = trim( $ident );

		if ( $ident === '' ) {
			return '';
		}

		// Accept a plain identifier or a two-segment qualified name.
		if ( ! preg_match( '/\A[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?\z/', $ident ) ) {
			return '';
		}

		// Reject any segment that exceeds the MySQL length limit.
		foreach ( explode( '.', $ident ) as $segment ) {
			if ( strlen( $segment ) > $max_length ) {
				return '';
			}
		}
		return $ident;
	}

	/**
	 * Ensures that a MySQL index exists with the expected column order and uniqueness.
	 *
	 * Contract:
	 * - Adds the index when it is missing.
	 * - Rebuilds a secondary index (DROP then ADD) when its uniqueness, indexed columns,
	 *   or column order do not match, or when it is a prefix or non-BTREE index.
	 * - Treats prefix indexes as mismatched because they are not equivalent to full-column indexes.
	 * - Supports BTREE indexes only for secondary indexes.
	 * - Adds the PRIMARY KEY when no primary key currently exists on the table.
	 * - Does not rebuild an existing primary key automatically. An existing primary key that
	 *   does not match is reported as the error 'primary_key_mismatch' and no DDL is executed,
	 *   because dropping a primary key is unsafe when a key column is AUTO_INCREMENT and a
	 *   DROP then ADD pair would leave the table without a primary key in between.
	 * - Does not support functional indexes, expression indexes, index prefix lengths, or custom index types.
	 *
	 * Successful data format:
	 * - array{ddl_executed: array<int, string>, ddl_message: string}
	 *   - 'ddl_executed': zero-indexed list of the DDL statements actually executed, in execution order.
	 *     Empty when the existing index already matched; otherwise holds the DROP INDEX and/or
	 *     ADD INDEX or ADD PRIMARY KEY statements that were applied.
	 *   - 'ddl_message': human-readable summary of the outcome.
	 *
	 * Possible errors:
	 * - 'invalid_identifier': the table, index, or a column identifier failed validation.
	 * - 'invalid_argument': a column is not a string, a duplicate column was given, or no columns were given.
	 * - 'primary_key_mismatch': an existing primary key does not match the expected definition.
	 *   The error data is array{expected: array<int, string>, actual: array<int, string>}.
	 * - 'db_query_error': propagated from CRB_Database::query() when a DROP or ADD statement fails.
	 *
	 * Side Effects:
	 * - A secondary index rebuild runs DROP INDEX then ADD INDEX as separate non-transactional statements; if ADD fails after a successful DROP, the index is left absent.
	 *
	 * @param string $table_name Table name. Must be a plain or single-qualified identifier.
	 * @param string $index_name Index name. Must be a simple identifier, or 'PRIMARY' (case-insensitive) to target the primary key.
	 * @param array<int, string> $columns Ordered list of indexed columns. Each column must be a simple identifier.
	 * @param bool $unique Whether the index must be UNIQUE. Always treated as true when $index_name is 'PRIMARY'.
	 *
	 * @return Revalt<array{ddl_executed: array<int, string>, ddl_message: string}> Result object describing the index-maintenance outcome.
	 */
	public function ensure_index( string $table_name, string $index_name, array $columns, bool $unique = false ): Revalt {
		$result = new Revalt();

		// Validate the table identifier.
		$safe_table = $this->safe_ident( $table_name );

		if ( $safe_table === '' || $safe_table !== $table_name ) {
			$result->add_error( 'invalid_identifier', 'Invalid table identifier for index maintenance.', $table_name );

			return $result;
		}

		// The reserved name PRIMARY targets the primary key; any other name is a secondary index.
		$is_primary = strtoupper( $index_name ) === 'PRIMARY';

		// Validate the index identifier. 'PRIMARY' (case-insensitive) is the reserved name for the primary key.
		$safe_index = $is_primary ? 'PRIMARY' : $this->safe_simple_ident( $index_name );

		if ( $safe_index === '' || ( ! $is_primary && $safe_index !== $index_name ) ) {
			$result->add_error( 'invalid_identifier', 'Invalid index identifier for index maintenance.', $index_name );

			return $result;
		}

		// A primary key is always unique; normalize the flag so downstream comparisons are honest.
		if ( $is_primary ) {
			$unique = true;
		}

		// Validate and normalize the expected indexed columns.
		$expected_columns = array();
		$known_columns = array();

		foreach ( $columns as $column_name ) {
			if ( ! is_string( $column_name ) ) {
				$result->add_error( 'invalid_argument', 'Index column must be a string.', array( 'type' => gettype( $column_name ) ) );

				return $result;
			}

			$safe_column = $this->safe_simple_ident( $column_name );

			if ( $safe_column === '' || $safe_column !== $column_name ) {
				$result->add_error( 'invalid_identifier', 'Invalid column identifier for index maintenance.', $column_name );

				return $result;
			}

			$column_key = strtolower( $safe_column );

			if ( isset( $known_columns[ $column_key ] ) ) {
				$result->add_error( 'invalid_argument', 'Duplicate column identifier in index definition.', $safe_column );

				return $result;
			}

			$known_columns[ $column_key ] = true;
			$expected_columns[] = $safe_column;
		}

		if ( empty( $expected_columns ) ) {
			$result->add_error( 'invalid_argument', 'Index definition requires at least one column.' );

			return $result;
		}

		// Read the current index definition.
		$index_result = $this->get_metadata(
			self::META_INDEXES,
			array(
				'table'    => $safe_table,
				'key_name' => $safe_index,
			)
		);

		if ( $index_result->has_errors() ) {
			return $index_result;
		}

		$index_rows = $index_result->get_results( array() );

		// Normalize the current index definition by Seq_in_index.
		$current_columns = array();
		$current_is_unique = false;
		$has_prefix_columns = false;
		$has_unsupported_index_type = false;

		// These fields are mandatory.
		$required_index_fields = array(
			'Seq_in_index',
			'Column_name',
			'Non_unique',
			'Sub_part',
			'Index_type',
		);

		foreach ( $index_rows as $index_row ) {

			$validate_row = $this->validate_metadata_row(
				$index_row,
				$required_index_fields,
				'SHOW INDEX',
				array(
					'table'      => $safe_table,
					'index_name' => $safe_index,
				)
			);

			if ( $validate_row->has_errors() ) {
				return $validate_row;
			}

			$seq_in_index = (int) $index_row['Seq_in_index'];

			if ( $seq_in_index <= 0 ) {
				continue;
			}

			$current_columns[ $seq_in_index ] = (string) ( $index_row['Column_name'] ?? '' );

			if ( $index_row['Sub_part'] !== null ) {
				$has_prefix_columns = true;
			}

			$index_type = strtoupper( trim( (string) ( $index_row['Index_type'] ?? '' ) ) );

			if ( $index_type !== '' && $index_type !== 'BTREE' ) {
				$has_unsupported_index_type = true;
			}
		}

		if ( ! empty( $index_rows ) ) {
			$current_is_unique = 0 === (int) $index_rows[0]['Non_unique'];
		}

		ksort( $current_columns );
		$current_columns = array_values( $current_columns );

		$current_columns_normalized = array_map( 'strtolower', $current_columns );
		$expected_columns_normalized = array_map( 'strtolower', $expected_columns );

		$index_exists = ! empty( $index_rows );

		$matches = $index_exists
		           && $current_columns_normalized === $expected_columns_normalized
		           && $current_is_unique === $unique
		           && ! $has_prefix_columns
		           && ! $has_unsupported_index_type;

		// Already correct: nothing to do for both index kinds.
		if ( $matches ) {
			$result->success( array(
				'ddl_executed' => array(),
				'ddl_message'  => 'Index already matches the expected definition. Table: ' . $safe_table . ', Index: ' . $safe_index . ', Columns: ' . implode( ', ', $expected_columns ) . ', Unique: ' . ( $unique ? 'true' : 'false' ) . '.',
			) );

			return $result;
		}

		// Primary key that exists but does not match: report, never rebuild automatically.
		if ( $is_primary && $index_exists ) {
			$result->add_error(
				'primary_key_mismatch',
				'Existing primary key does not match the expected definition and was not modified. Table: ' . $safe_table . ', Expected: ' . implode( ', ', $expected_columns ) . ', Actual: ' . implode( ', ', $current_columns ) . '.',
				array(
					'expected' => $expected_columns,
					'actual'   => $current_columns,
				)
			);

			return $result;
		}

		$ddl_executed = array();

		// Drop the incompatible secondary index before recreating it. Never reached for a primary key.
		if ( $index_exists ) {
			$drop_sql = 'ALTER TABLE ' . $safe_table . ' DROP INDEX ' . $safe_index;
			$drop_result = $this->db->query( $drop_sql );
			$ddl_executed[] = $drop_sql;

			if ( $drop_result->has_errors() ) {
				$drop_result->add_error_data( array( 'ddl_executed' => $ddl_executed ) );

				return $drop_result;
			}
		}

		// Build the ADD clause: primary key or secondary index.
		if ( $is_primary ) {
			$create_sql = 'ALTER TABLE ' . $safe_table . ' ADD PRIMARY KEY (' . implode( ', ', $expected_columns ) . ')';
		}
		else {
			$index_type_sql = $unique ? 'UNIQUE INDEX' : 'INDEX';
			$create_sql = 'ALTER TABLE ' . $safe_table
			              . ' ADD ' . $index_type_sql . ' ' . $safe_index
			              . ' (' . implode( ', ', $expected_columns ) . ') USING BTREE';
		}

		$create_result = $this->db->query( $create_sql );
		$ddl_executed[] = $create_sql;

		if ( $create_result->has_errors() ) {
			$create_result->add_error_data( array( 'ddl_executed' => $ddl_executed ) );

			return $create_result;
		}

		$result->success( array(
			'ddl_executed' => $ddl_executed,
			'ddl_message'  => 'Index created successfully. Table: ' . $safe_table . ', Index: ' . $safe_index . ', Columns: ' . implode( ', ', $expected_columns ) . ', Unique: ' . ( $unique ? 'true' : 'false' ) . '.',
		) );

		return $result;
	}

	/**
	 * Drops an index if it exists, treating an already-absent index as success.
	 *
	 * Best-effort by design: it validates the runtime identifiers and enforces the existence
	 * contract, then lets the database reject anything exotic (for example, an index a foreign
	 * key still depends on, or a primary key on an AUTO_INCREMENT column). It does not inspect
	 * such constraints itself.
	 *
	 * A bare primary-key drop leaves the table without a primary key; re-adding one, if needed,
	 * is the caller's responsibility.
	 *
	 * Successful data format:
	 * - array{ddl_executed: array<int, string>, ddl_message: string}
	 *   - 'ddl_executed': the single DROP statement that was executed, or an empty array when
	 *     the index was already absent.
	 *   - 'ddl_message': human-readable summary of the outcome.
	 *
	 * Possible errors:
	 * - 'invalid_identifier': the table or index identifier failed validation; no work is performed.
	 * - propagated from get_metadata() when the existence read fails.
	 * - 'db_query_error': propagated from CRB_Database::query() when the DROP statement fails.
	 *
	 * @param string $table_name Table name. Must be a plain or single-qualified identifier.
	 * @param string $index_name Index name. Must be a simple identifier, or 'PRIMARY' (case-insensitive) to target the primary key.
	 *
	 * @return Revalt<array{ddl_executed: array<int, string>, ddl_message: string}> Result object describing the drop outcome.
	 *
	 * @since 2.7
	 */
	public function drop_index_if_exists( string $table_name, string $index_name ): Revalt {
		$result = new Revalt();

		// Validate the table identifier.
		$safe_table = $this->safe_ident( $table_name );

		if ( $safe_table === '' || $safe_table !== $table_name ) {
			$result->add_error( 'invalid_identifier', 'Invalid table identifier for index drop.', $table_name );

			return $result;
		}

		// The reserved name PRIMARY targets the primary key; any other name is a secondary index.
		$is_primary = strtoupper( $index_name ) === 'PRIMARY';

		// Validate the index identifier. 'PRIMARY' (case-insensitive) is the reserved name for the primary key.
		$safe_index = $is_primary ? 'PRIMARY' : $this->safe_simple_ident( $index_name );

		if ( $safe_index === '' || ( ! $is_primary && $safe_index !== $index_name ) ) {
			$result->add_error( 'invalid_identifier', 'Invalid index identifier for index drop.', $index_name );

			return $result;
		}

		// Resolve existence through the same whitelisted SHOW INDEX path ensure_index() uses.
		// IF EXISTS is not portable, so the "absent counts as success" contract is enforced here.
		$index_result = $this->get_metadata(
			self::META_INDEXES,
			array(
				'table'    => $safe_table,
				'key_name' => $safe_index,
			)
		);

		if ( $index_result->has_errors() ) {
			return $index_result;
		}

		$index_rows = $index_result->get_results( array() );

		// Idempotent no-op: nothing to drop.
		if ( empty( $index_rows ) ) {
			$result->success( array(
				'ddl_executed' => array(),
				'ddl_message'  => 'Index was already absent; nothing to drop. Table: ' . $safe_table . ', Index: ' . $safe_index . '.',
			) );

			return $result;
		}

		// Primary keys and secondary indexes use different DROP syntax.
		if ( $is_primary ) {
			$drop_sql = 'ALTER TABLE ' . $safe_table . ' DROP PRIMARY KEY';
		}
		else {
			$drop_sql = 'ALTER TABLE ' . $safe_table . ' DROP INDEX ' . $safe_index;
		}

		$drop_result = $this->db->query( $drop_sql );
		$ddl_executed = array( $drop_sql );

		if ( $drop_result->has_errors() ) {
			$drop_result->add_error_data( array( 'ddl_executed' => $ddl_executed ) );

			return $drop_result;
		}

		$result->success( array(
			'ddl_executed' => $ddl_executed,
			'ddl_message'  => 'Index dropped successfully. Table: ' . $safe_table . ', Index: ' . $safe_index . '.',
		) );

		return $result;
	}

	/**
	 * Validates and normalizes a simple MySQL identifier.
	 *
	 * A simple identifier may contain ASCII letters, digits, and underscores,
	 * and must not contain dots. It is suitable for index and column names.
	 *
	 * @param string $identifier Raw identifier.
	 *
	 * @return string Validated identifier, or an empty string when invalid.
	 */
	private function safe_simple_ident( string $identifier ): string {
		$identifier = trim( $identifier );

		if ( $identifier === '' ) {
			return '';
		}

		if ( ! preg_match( '/\A[A-Za-z_][A-Za-z0-9_]*\z/', $identifier ) ) {
			return '';
		}

		return $identifier;
	}

	/**
	 * Validates that a metadata row is an associative array with required fields.
	 *
	 * Expected successful data format:
	 * - Associative metadata row.
	 *
	 * Possible errors:
	 * - 'invalid_metadata_shape': Row is not an array or required fields are missing.
	 *
	 * @param mixed $metadata_row Metadata row returned by a SHOW statement.
	 * @param array<int, string> $required_fields Required field names.
	 * @param string $source Metadata source name used in diagnostics.
	 * @param array<string, mixed> $context Additional diagnostic context.
	 *
	 * @return Revalt<array<string, mixed>> Result object containing the validated metadata row.
	 */
	private function validate_metadata_row( $metadata_row, array $required_fields, string $source, array $context = array() ): Revalt {
		if ( ! is_array( $metadata_row ) ) {
			return new Revalt(
				null,
				'invalid_metadata_shape',
				$source . ' returned a metadata row with an unsupported shape.',
				array_merge(
					$context,
					array( 'row_type' => gettype( $metadata_row ) )
				)
			);
		}

		foreach ( $required_fields as $required_field ) {
			if ( ! array_key_exists( $required_field, $metadata_row ) ) {
				return new Revalt(
					null,
					'invalid_metadata_shape',
					$source . ' metadata row is missing a required field.',
					array_merge(
						$context,
						array(
							'field' => $required_field,
							'row'   => $metadata_row,
						)
					)
				);
			}
		}

		return new Revalt( $metadata_row );
	}

	/**
	 * Ensures that a table exists and has the expected declared schema.
	 *
	 * This method trusts the internal table definition registry and delegates
	 * declaration validation to CRB_Schema_Compiler and ensure_index().
	 *
	 * It performs best-effort runtime orchestration and does not stop at the first problem:
	 * - creates a missing table;
	 * - adds missing columns;
	 * - ensures declared indexes, including the primary key;
	 * - reports column and primary-key drift without modifying existing definitions.
	 *
	 * Policy: this method detects and reports; it never decides how severe a drift is.
	 * Classifying drift and acting on it is left to the caller.
	 *
	 * Result and partial-success contract:
	 * - The maintenance report is ALWAYS available through get_results(), even when
	 *   has_errors() is true. This producer documents partial-success: read the report
	 *   regardless of the error state.
	 * - has_errors() is true when any drift was detected OR any operation failed. Use the
	 *   error codes and the report buckets to tell them apart; they require different handling.
	 * - report['status'] summarizes the outcome:
	 *   - 'failed': at least one metadata read, compilation, or DDL operation failed.
	 *   - 'diagnosed': no failure, but at least one column or primary-key drift was detected.
	 *     Outranks 'changed' so the caller cannot miss drift.
	 *   - 'changed': schema was modified and nothing drifted or failed.
	 *   - 'ok': schema already matched; nothing was executed.
	 *
	 * Return payload shape:
	 * array(
	 *     'table' => string,
	 *     'status' => string,
	 *     'created' => bool,
	 *     'columns_added' => string[],
	 *     'indexes_checked' => array<int, array<string, string>>,
	 *     'column_mismatches' => array<string, array<int, array<string, mixed>>>,
	 *     'index_mismatches' => array<string, array{expected: string[], actual: string[]}>,
	 *     'ddl_executed' => array<int, array<string, string>>,
	 *     'ddl_failed' => array<int, array<string, string>>,
	 * )
	 *
	 * Possible errors:
	 * - 'invalid_identifier': table identifier is invalid; no work is performed.
	 * - 'schema_metadata_failed': metadata inspection failed.
	 * - 'schema_ddl_failed': CREATE TABLE, ADD COLUMN, or index execution or validation failed.
	 * - 'schema_column_mismatch': an existing column does not match the expected definition.
	 * - 'schema_primary_key_mismatch': an existing primary key does not match the expected definition.
	 *
	 * @param string $table_name Table name.
	 * @param array<string, mixed> $table_definition Table definition array shape.
	 *
	 * @return Revalt<array<string,mixed>> Result object containing a concise schema maintenance report.
	 */
	public function ensure_table_schema( string $table_name, array $table_definition ): Revalt {
		$result = new Revalt();

		// Validate only runtime input. The table definition itself is trusted internal code.
		$safe_table = $this->safe_ident( $table_name );

		if ( $safe_table === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid table identifier for schema maintenance.',
				$table_name
			);
		}

		$compiler = new CRB_Schema_Compiler();
		$report   = $this->create_ensure_table_schema_report( $safe_table );

		// Create the table when it does not exist.
		$table_exists_result = $this->table_exists( $safe_table );

		if ( $table_exists_result->has_errors() ) {
			$this->record_schema_metadata_failure( $result, $report, $safe_table, $table_exists_result );

			return $this->finalize_ensure_table_schema_result( $result, $report );
		}

		if ( ! $table_exists_result->get_results( false ) ) {
			$this->create_schema_table( $safe_table, $table_definition, $compiler, $result, $report );

			return $this->finalize_ensure_table_schema_result( $result, $report );
		}

		// Inspect actual columns only for existing tables.
		$actual_columns_result = $this->get_schema_columns_by_name( $safe_table );

		if ( $actual_columns_result->has_errors() ) {
			$this->record_schema_metadata_failure( $result, $report, $safe_table, $actual_columns_result );

			return $this->finalize_ensure_table_schema_result( $result, $report );
		}

		$actual_columns = $actual_columns_result->get_results( array() );

		// Add missing columns; report drift for existing columns as an error without stopping.
		foreach ( $table_definition['columns'] as $column_name => $column_definition ) {
			$column_key = strtolower( (string) $column_name );

			if ( ! isset( $actual_columns[ $column_key ] ) ) {
				$this->add_schema_column(
					$safe_table,
					(string) $column_name,
					$column_definition,
					$compiler,
					$result,
					$report
				);

				continue;
			}

			$mismatches = $this->get_schema_column_mismatches(
				$column_definition,
				$actual_columns[ $column_key ]
			);

			if ( ! empty( $mismatches ) ) {
				$this->record_schema_column_mismatch( $result, $report, $safe_table, (string) $column_name, $mismatches );
			}
		}

		// Ensure declared indexes, including the primary key. Drift is reported, not repaired.
		foreach ( $table_definition['indexes'] ?? array() as $index_name => $index_definition ) {
			$index_result = $this->ensure_index(
				$safe_table,
				(string) $index_name,
				$index_definition['columns'],
				(bool) ( $index_definition['unique'] ?? false )
			);

			if ( $index_result->has_errors() ) {
				// A primary-key drift is a finding, not an execution failure: route it to the
				// mismatch channel so the caller can distinguish it from a real DDL failure.
				if ( $index_result->get_error_code() === 'primary_key_mismatch' ) {
					$this->record_schema_index_mismatch( $result, $report, $safe_table, (string) $index_name, $index_result );

					continue;
				}

				$this->record_schema_ddl_failure( $result, $report, 'ensure_index', (string) $index_name, '', $index_result );

				continue;
			}

			$payload = $index_result->get_results( array() );

			$report['indexes_checked'][] = array(
				'index'   => (string) $index_name,
				'message' => (string) ( $payload['ddl_message'] ?? '' ),
			);

			// Record executed DDL from structured data, not by sniffing the message text.
			if ( ! empty( $payload['ddl_executed'] ) ) {
				$report['ddl_executed'][] = array(
					'operation' => 'ensure_index',
					'target'    => (string) $index_name,
					'sql'       => implode( '; ', $payload['ddl_executed'] ),
				);
			}
		}

		return $this->finalize_ensure_table_schema_result( $result, $report );
	}

	/**
	 * Determines whether a table exists in the current database.
	 *
	 * Validates the table identifier, then performs an exact existence check
	 * against information_schema.tables.
	 *
	 * Scope and matching:
	 * - The lookup is scoped to the current connection database via DATABASE().
	 *   A database-qualified name has its schema segment ignored; only the bare
	 *   table name is matched, so cross-database checks are not supported.
	 * - Name matching follows the information_schema column collation, which is
	 *   case-insensitive on typical MySQL and MariaDB configurations.
	 *
	 * Possible errors:
	 * - 'invalid_identifier': the table name failed identifier validation; no query is executed.
	 * - 'db_query_error': propagated from CRB_Database::query() when execution fails.
	 *
	 * @param string $table_name Plain or database-qualified table name.
	 *
	 * @return Revalt<bool> Result object whose payload is true when the table exists.
	 */
	public function table_exists( string $table_name ): Revalt {
		$safe_table = $this->safe_ident( $table_name );

		if ( $safe_table === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid table identifier for existence check.',
				$table_name
			);
		}

		// Extracting the simple table name
		$segments = explode( '.', $safe_table );
		$simple_name = (string) end( $segments );

		// Exact existence check against the data dictionary, scoped to the current database.
		$sql = 'SELECT 1 FROM information_schema.tables'
		       . ' WHERE table_schema = DATABASE()'
		       . ' AND table_name = ' . $this->db->quote( $simple_name );

		$rows_result = $this->db->query( $sql );

		if ( $rows_result->has_errors() ) {
			return $rows_result;
		}

		return new Revalt( ! empty( $rows_result->get_results( array() ) ) );
	}

	/**
	 * Creates a missing table using CRB_Schema_Compiler.
	 *
	 * @param string $safe_table Validated table identifier.
	 * @param array<string, mixed> $table_definition Table definition array shape.
	 * @param CRB_Schema_Compiler $compiler Schema compiler.
	 * @param Revalt $result Aggregate result object.
	 * @param array<string, mixed> $report Schema report.
	 *
	 * @return void
	 */
	private function create_schema_table( string $safe_table, array $table_definition, CRB_Schema_Compiler $compiler, Revalt $result, array &$report ): void {
		$compile_result = $compiler->compile_create_table_sql( $safe_table, $table_definition );

		if ( $compile_result->has_errors() ) {
			$this->record_schema_ddl_failure(
				$result,
				$report,
				'create_table',
				$safe_table,
				'',
				$compile_result
			);

			return;
		}

		$sql = $compile_result->get_results( '' );
		$query_result = $this->db->query( $sql );

		if ( $query_result->has_errors() ) {
			$this->record_schema_ddl_failure(
				$result,
				$report,
				'create_table',
				$safe_table,
				$sql,
				$query_result
			);

			return;
		}

		$report['created'] = true;
		$report['ddl_executed'][] = array(
			'operation' => 'create_table',
			'target'    => $safe_table,
			'sql'       => $sql,
		);
	}

	/**
	 * Adds a missing column using CRB_Schema_Compiler.
	 *
	 * @param string $safe_table Validated table identifier.
	 * @param string $column_name Column name.
	 * @param array<string, mixed> $column_definition Column definition array shape.
	 * @param CRB_Schema_Compiler $compiler Schema compiler.
	 * @param Revalt $result Aggregate result object.
	 * @param array<string, mixed> $report Schema report.
	 *
	 * @return void
	 */
	private function add_schema_column( string $safe_table, string $column_name, array $column_definition, CRB_Schema_Compiler $compiler, Revalt $result, array &$report ): void {
		$compile_result = $compiler->compile_add_column_sql( $safe_table, $column_name, $column_definition );

		if ( $compile_result->has_errors() ) {
			$this->record_schema_ddl_failure(
				$result,
				$report,
				'add_column',
				$column_name,
				'',
				$compile_result
			);

			return;
		}

		$sql = $compile_result->get_results( '' );
		$query_result = $this->db->query( $sql );

		if ( $query_result->has_errors() ) {
			$this->record_schema_ddl_failure(
				$result,
				$report,
				'add_column',
				$column_name,
				$sql,
				$query_result
			);

			return;
		}

		$report['columns_added'][] = $column_name;
		$report['ddl_executed'][] = array(
			'operation' => 'add_column',
			'target'    => $column_name,
			'sql'       => $sql,
		);
	}

	/**
	 * Reads and normalizes actual table columns by lower-case column name.
	 *
	 * @param string $safe_table Validated table identifier.
	 *
	 * @return Revalt<array<string,array<string,mixed>>> Result object containing actual column facts.
	 */
	private function get_schema_columns_by_name( string $safe_table ): Revalt {
		$columns_result = $this->get_metadata(
			self::META_FULL_COLUMNS,
			array( 'table' => $safe_table )
		);

		if ( $columns_result->has_errors() ) {
			return $columns_result;
		}

		$columns = array();

		foreach ( $columns_result->get_results( array() ) as $column_row ) {
			$row_result = $this->validate_metadata_row(
				$column_row,
				array( 'Field', 'Type', 'Collation', 'Null', 'Default' ),
				'SHOW FULL COLUMNS',
				array( 'table' => $safe_table )
			);

			if ( $row_result->has_errors() ) {
				return $row_result;
			}

			$row = $row_result->get_results( array() );
			$name = (string) $row['Field'];

			$columns[ strtolower( $name ) ] = array(
				'type'     => $this->normalize_schema_column_type( (string) $row['Type'] ),
				'charset'  => $this->get_schema_charset_from_collation( $row['Collation'] ),
				'nullable' => strtoupper( (string) $row['Null'] ) === 'YES',
				'default'  => $this->normalize_schema_default( $row['Default'] ),
			);
		}

		return new Revalt( $columns );
	}

	/**
	 * Returns mismatches between expected and actual column facts.
	 *
	 * @param array<string, mixed> $expected_column Expected column declaration.
	 * @param array<string, mixed> $actual_column Actual column facts.
	 *
	 * @return array<int,array<string,mixed>> Column mismatches.
	 */
	private function get_schema_column_mismatches( array $expected_column, array $actual_column ): array {
		$mismatches = array();

		$expected_type = $this->normalize_schema_column_type( (string) $expected_column['type'] );

		if ( $expected_type !== $actual_column['type'] ) {
			$mismatches[] = array(
				'fact'     => 'type',
				'expected' => $expected_type,
				'actual'   => $actual_column['type'],
			);
		}

		if ( array_key_exists( 'charset', $expected_column ) ) {
			$expected_charset = strtolower( trim( (string) $expected_column['charset'] ) );

			if ( $expected_charset !== $actual_column['charset'] ) {
				$mismatches[] = array(
					'fact'     => 'charset',
					'expected' => $expected_charset,
					'actual'   => $actual_column['charset'],
				);
			}
		}

		if ( (bool) $expected_column['nullable'] !== $actual_column['nullable'] ) {
			$mismatches[] = array(
				'fact'     => 'nullable',
				'expected' => (bool) $expected_column['nullable'],
				'actual'   => $actual_column['nullable'],
			);
		}

		$expected_default = null;

		if ( array_key_exists( 'default', $expected_column ) ) {
			$expected_default = $this->normalize_schema_default( $expected_column['default'] );
		}

		if ( $expected_default !== $actual_column['default'] ) {
			$mismatches[] = array(
				'fact'     => 'default',
				'expected' => $expected_default,
				'actual'   => $actual_column['default'],
			);
		}

		return $mismatches;
	}

	/**
	 * Records a column drift as an error and a structured report entry.
	 *
	 * A drift is a finding, not an execution failure: it is reported through its own
	 * error code and the column_mismatches bucket, never through ddl_failed.
	 *
	 * @param Revalt $result Aggregate result object.
	 * @param array<string, mixed> $report Schema report.
	 * @param string $safe_table Validated table identifier.
	 * @param string $column_name Column name.
	 * @param array<int, array<string, mixed>> $mismatches Detected column-fact mismatches.
	 *
	 * @return void
	 *
	 * @since 2.6
	 */
	private function record_schema_column_mismatch( Revalt $result, array &$report, string $safe_table, string $column_name, array $mismatches ): void {
		$report['column_mismatches'][ $column_name ] = $mismatches;

		$result->add_error(
			'schema_column_mismatch',
			'Column does not match the expected definition: ' . $column_name . ' on ' . $safe_table . '.',
			array(
				'column'     => $column_name,
				'mismatches' => $mismatches,
			)
		);
	}

	/**
	 * Records a primary-key drift as an error and a structured report entry.
	 *
	 * A drift is a finding, not an execution failure: it is reported through its own
	 * error code and the index_mismatches bucket, never through ddl_failed.
	 *
	 * @param Revalt $result Aggregate result object.
	 * @param array<string, mixed> $report Schema report.
	 * @param string $safe_table Validated table identifier.
	 * @param string $index_name Table index name
	 * @param Revalt $index_result Failed ensure_index() result carrying primary_key_mismatch.
	 *
	 * @return void
	 *
	 * @since 2.6
	 */
	private function record_schema_index_mismatch( Revalt $result, array &$report, string $safe_table, string $index_name, Revalt $index_result ): void {
		$mismatch = $index_result->get_error_data( 'primary_key_mismatch' );

		if ( ! is_array( $mismatch ) ) {
			$mismatch = array();
		}

		$report['index_mismatches'][ $index_name ] = $mismatch;

		$result->add_error(
			'schema_primary_key_mismatch',
			'Primary key does not match the expected definition: ' . $index_name . ' on ' . $safe_table . '.',
			array(
				'index'    => $index_name,
				'mismatch' => $mismatch,
			)
		);
	}

	/**
	 * Creates a concise schema maintenance report.
	 *
	 * @param string $safe_table Validated table identifier.
	 *
	 * @return array<string,mixed> Report payload.
	 */
	private function create_ensure_table_schema_report( string $safe_table ): array {
		return array(
			'table'             => $safe_table,
			'status'            => 'pending',
			'created'           => false,
			'columns_added'     => array(),
			'indexes_checked'   => array(),
			'column_mismatches' => array(),
			'index_mismatches'  => array(),
			'ddl_executed'      => array(),
			'ddl_failed'        => array(),
		);
	}

	/**
	 * Finalizes the schema maintenance result.
	 *
	 * The status label is a summary only. Whether the Revalt carries errors is decided by
	 * what was accumulated during the run, not by the label. The report is attached in both
	 * cases because ensure_table_schema() documents partial-success.
	 *
	 * @param Revalt $result Aggregate result object.
	 * @param array<string, mixed> $report Report payload.
	 *
	 * @return Revalt<array<string, mixed>> Result object containing the finalized report.
	 */
	private function finalize_ensure_table_schema_result( Revalt $result, array $report ): Revalt {
		$report['status'] = $this->resolve_schema_status( $report );

		// Drift and execution failures both accumulate errors. Keep the report readable in
		// both cases: attach it without clearing the accumulated error state.
		if ( $result->has_errors() ) {
			$result->put_results( $report );

			return $result;
		}

		$result->success( $report );

		return $result;
	}

	/**
	 * Resolves the summary status label from the report buckets.
	 *
	 * Detected drift outranks a successful change so a caller cannot miss it; a real
	 * failure outranks everything because it is the most actionable outcome.
	 *
	 * @param array<string, mixed> $report Report payload.
	 *
	 * @return string One of 'failed', 'diagnosed', 'changed', or 'ok'.
	 *
	 * @since 2.6
	 */
	private function resolve_schema_status( array $report ): string {
		// A real metadata, compilation, or DDL failure is the most actionable outcome.
		if ( ! empty( $report['ddl_failed'] ) ) {
			return 'failed';
		}

		// Detected drift must not be hidden behind a successful change.
		if ( ! empty( $report['column_mismatches'] ) || ! empty( $report['index_mismatches'] ) ) {
			return 'diagnosed';
		}

		// Schema was modified and nothing drifted or failed.
		if ( ! empty( $report['ddl_executed'] ) ) {
			return 'changed';
		}

		return 'ok';
	}

	/**
	 * Records a failed DDL operation in the report and aggregate Revalt.
	 *
	 * @param Revalt $result Aggregate result object.
	 * @param array<string,mixed> $report Report payload.
	 * @param string $operation Operation name.
	 * @param string $target Operation target.
	 * @param string $sql SQL statement, or an empty string when SQL was not compiled or is hidden inside another method.
	 * @param Revalt $operation_result Failed lower-level result.
	 *
	 * @return void
	 */
	private function record_schema_ddl_failure( Revalt $result, array &$report, string $operation, string $target, string $sql, Revalt $operation_result ): void {
		$failure = array(
			'operation'     => $operation,
			'target'        => $target,
			'sql'           => $sql,
			'error_code'    => $operation_result->get_error_code(),
			'error_message' => $operation_result->get_error_message(),
		);

		$report['ddl_failed'][] = $failure;

		$result->add_error(
			'schema_ddl_failed',
			'Schema DDL operation failed: ' . $operation . ' on ' . $target . '.',
			$failure
		);
	}

	/**
	 * Records a metadata inspection failure.
	 *
	 * @param Revalt $result Aggregate result object.
	 * @param array<string,mixed> $report Report payload.
	 * @param string $safe_table Validated table identifier.
	 * @param Revalt $metadata_result Failed metadata result.
	 *
	 * @return void
	 */
	private function record_schema_metadata_failure( Revalt $result, array &$report, string $safe_table, Revalt $metadata_result ): void {
		$failure = array(
			'operation'     => 'metadata',
			'target'        => $safe_table,
			'sql'           => '',
			'error_code'    => $metadata_result->get_error_code(),
			'error_message' => $metadata_result->get_error_message(),
		);

		$report['ddl_failed'][] = $failure;

		$result->add_error(
			'schema_metadata_failed',
			'Schema metadata inspection failed for table ' . $safe_table . '.',
			$failure
		);
	}

	/**
	 * Extracts charset name from a MySQL collation value.
	 *
	 * @param mixed $collation Collation metadata value.
	 *
	 * @return string Charset name, or empty string for non-character columns.
	 */
	private function get_schema_charset_from_collation( $collation ): string {
		if ( ! is_string( $collation ) || $collation === '' ) {
			return '';
		}

		$parts = explode( '_', $collation );

		return strtolower( (string) ( $parts[0] ?? '' ) );
	}

	/**
	 * Normalizes a column type for schema diff.
	 *
	 * @param string $column_type Column type.
	 *
	 * @return string Normalized column type.
	 */
	private function normalize_schema_column_type( string $column_type ): string {
		$column_type = strtolower( trim( $column_type ) );
		$column_type = (string) preg_replace( '/\s+/', ' ', $column_type );
		$column_type = str_replace( 'integer', 'int', $column_type );

		// Ignore integer display width for compatibility across MySQL and MariaDB variants.
		return (string) preg_replace( '/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $column_type );
	}

	/**
	 * Normalizes a default value for schema diff.
	 *
	 * @param mixed $default_value Default value.
	 *
	 * @return string|null Normalized default value.
	 */
	private function normalize_schema_default( $default_value ) {
		if ( $default_value === null ) {
			return null;
		}

		if ( is_bool( $default_value ) ) {
			return $default_value ? '1' : '0';
		}

		if ( is_int( $default_value ) ) {
			return (string) $default_value;
		}

		if ( is_float( $default_value ) ) {
			$normalized_default = rtrim( rtrim( sprintf( '%F', $default_value ), '0' ), '.' );

			return $normalized_default === '' ? '0' : $normalized_default;
		}

		return (string) $default_value;
	}
}
