<?php

declare( strict_types=1 );

/**
 * Pragmatic SQL Query Builder with a fluent API.
 *
 * Responsibilities:
 * 1. Construct valid SQL syntax for SELECT, INSERT, UPDATE, and DELETE operations.
 * 2. Execute a compiled query via terminal methods.
 * 3. Enforce client-side validation to prevent invalid identifiers or injection.
 * 4. Delegate quoting to the CRB_Database adapter.
 * 5. Provide a clean, semantic fluent-based API for data manipulation and retrieval.
 * 6. Accumulate validation errors internally and defer compilation until terminal execution methods are called.
 * 7. Integrate with Revalt DTO for error accumulation and deterministic return.
 *
 * Requires: CRB_Database class
 *
 * Error handling contract:
 * - In the expected CRB_Database environment (for example, when mysqli is configured not to throw,
 *   such as MYSQLI_REPORT_OFF), public methods accumulate validation failures and normal execution
 *   failures in the internal Revalt state and surface them either through terminal methods
 *   (insert, update, delete, get_row, etc.) or via get_state(). Callers should check
 *   $builder->get_state()->has_errors() when consuming non-terminal outputs such as to_sql().
 * - If the underlying database driver is configured to throw exceptions, terminal execution methods
 *   may also propagate those exceptions according to the CRB_Database environment contract.
 *
 * Side Effects:
 * - Instances of this class are mutable. Method calls (e.g., where, limit) modify the internal state.
 *
 * @version 4.6
 */
class CRB_Query_Builder {
	/**
	 * @var CRB_Database The database adapter instance used for escaping and execution.
	 */
	private CRB_Database $db;

	/**
	 * @var Revalt Internal state to accumulate validation errors during method chaining.
	 */
	private Revalt $state;

	/**
	 * @var string The validated table name.
	 */
	private string $table = '';

	/**
	 * @var string The validated table alias.
	 */
	private string $table_alias = '';

	/**
	 * @var array<int, string> Contains validated identifiers and wildcard projections accepted by select(), such as column, table.column, *, and table.*, plus aliased projections (column AS alias) accepted by select_as().
	 */
	private array $select_columns = array();

	/**
	 * @var array<int, string> List of validated aggregate SELECT expressions (e.g. COUNT(...) AS alias).
	 */
	private array $select_aggregates = array();

	/**
	 * @var bool Whether a projection method was called explicitly (select() with args or select_count()).
	 *          Distinguishes the default "no projection requested" state from an explicit projection
	 *          whose identifiers were all rejected by validation.
	 */
	private bool $select_explicit = false;

	/**
	 * @var bool Whether to emit the SQL_CALC_FOUND_ROWS modifier in the next compiled SELECT.
	 */
	private bool $calc_found_rows = false;

	/**
	 * @var array<int, string> List of safe JOIN condition fragments.
	 */
	private array $joins = array();

	/**
	 * @var array<int, string> List of safe WHERE condition fragments.
	 */
	private array $wheres = array();

	/**
	 * @var array<int, string> List of safe GROUP BY instruction fragments.
	 */
	private array $groups = array();

	/**
	 * @var array<int, string> List of safe ORDER BY instruction fragments.
	 */
	private array $orders = array();

	/**
	 * @var int The maximum number of rows to return. Zero means no limit.
	 */
	private int $limit = 0;

	/**
	 * @var int The number of rows to skip before starting to return data.
	 */
	private int $offset = 0;

	/**
	 * Initializes the builder with a database instance and table name.
	 *
	 * Expected behavior:
	 * - Validates the table name strictly.
	 * - Prepares the internal Revalt state.
	 *
	 * Side Effects:
	 * - Modifies the internal $table property.
	 * - Adds an error to the internal $state if validation fails.
	 *
	 * @param CRB_Database $db The database adapter instance.
	 * @param string $table The raw table name to query.
	 */
	public function __construct( CRB_Database $db, string $table ) {
		$this->db = $db;
		$this->state = new Revalt();

		// Validate and secure the table name identifier.
		$safe_table = $this->safe_ident( $table );

		if ( $safe_table === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid table name provided', $table );

			return;
		}

		$this->table = $safe_table;
	}

	/**
	 * Sets an alias for the main table in the FROM clause.
	 *
	 * Error handling:
	 * - Invalid alias identifiers are recorded as 'invalid_identifier' in the builder's internal Revalt error state
	 *   and the alias is not changed.
	 *
	 * @param string $alias The alias for the main table.
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function alias( string $alias ): self {
		$safe_alias = $this->safe_simple_ident( $alias );

		if ( $safe_alias === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid table alias provided', $alias );

			return $this;
		}

		$this->table_alias = $safe_alias;

		return $this;
	}

	/**
	 * Specify columns to retrieve in a SELECT query.
	 *
	 * Accepted formats:
	 * - No arguments: no-op; the default projection ('*') is preserved when no other
	 *   columns or aggregates are accumulated.
	 * - Single or multiple string arguments representing column names.
	 * - Wildcards allowed ('*', 'table.*').
	 *
	 * Usage constraints:
	 * - Repeated calls accumulate columns; identifiers are appended, not replaced.
	 * - May be combined with select_count() in any order; the final SELECT list is
	 *   assembled at compile time.
	 * - Complex expressions (e.g., COUNT(*), functions) are not supported here; use
	 *   select_count() for aggregates.
	 *
	 * Error handling:
	 * - Invalid identifiers are skipped and recorded in the builder's internal Revalt error state.
	 *
	 *  Examples:
	 *
	 *  // Select specific columns
	 *  $builder->select('id', 'username', 'email');
	 *
	 *  // Select all columns (default; no-op call)
	 *  $builder->select();
	 *
	 *  // Explicit scoped wildcard
	 *  $builder->select('u.*', 'p.id');
	 *
	 *  // Accumulate across calls
	 *  $builder->select('id')->select('username');
	 *
	 * @param string ...$columns Column names to select.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see select_count()
	 */
	public function select( string ...$columns ): self {
		// No arguments: leave accumulated state untouched and rely on the default
		// '*' projection emitted at compile time when nothing else is set.
		if ( empty( $columns ) ) {
			return $this;
		}

		// Mark the projection as explicitly requested so the compiler does not
		// silently fall back to '*' if every identifier here is rejected.
		$this->select_explicit = true;

		// Validate and append each column name securely, allowing wildcards.
		foreach ( $columns as $col ) {
			$safe_col = $this->safe_ident( $col, true );

			if ( $safe_col === '' ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in select', $col );
				continue;
			}

			$this->select_columns[] = $safe_col;
		}

		return $this;
	}

	/**
	 * Enables the MySQL SQL_CALC_FOUND_ROWS SELECT modifier.
	 *
	 * This is a compatibility bridge for queries that rely on
	 * SQL_CALC_FOUND_ROWS followed by FOUND_ROWS().
	 *
	 * Usage constraints:
	 * - get_found_rows() must be called immediately after executing the SELECT query
	 *    on the same database connection.
	 * - Applies only to SELECT queries compiled by to_sql() and terminal read methods.
	 * - Once enabled, the modifier stays active for every SELECT compiled by this
	 *   builder instance until explicitly disabled with calc_found_rows(false),
	 *   consistent with how other builder settings such as where() and limit() persist.
	 *
	 * @param bool $enabled Whether to emit SQL_CALC_FOUND_ROWS.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see get_found_rows()
	 */
	public function calc_found_rows( bool $enabled = true ): self {
		$this->calc_found_rows = $enabled;

		return $this;
	}

	/**
	 * Adds an aliased column projection to the SELECT list.
	 *
	 * The column must be a plain or table-qualified identifier. Wildcards are not
	 * accepted. The alias must be a simple identifier without dots. Invalid column
	 * or alias identifiers are recorded as invalid_identifier and the projection is
	 * not added. A failed validation does not restore the default SELECT * projection.
	 *
	 * @param string $column Column to alias (plain or table-qualified, no wildcard).
	 * @param string $alias Result alias for the selected column.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 */
	public function select_as( string $column, string $alias ): self {
		// Mark the projection as explicitly requested so the compiler does not fall back to '*'.
		$this->select_explicit = true;

		$safe_alias = $this->safe_simple_ident( $alias );

		if ( $safe_alias === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid alias identifier in select_as', $alias );

			return $this;
		}

		// Validate without wildcard authorization, so '*' and 'table.*' are rejected: an aliased wildcard is meaningless.
		$safe_column = $this->safe_ident( $column );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in select_as', $column );

			return $this;
		}

		$this->select_columns[] = $safe_column . ' AS ' . $safe_alias;

		return $this;
	}

	/**
	 * Adds a COUNT aggregate projection to the SELECT list.
	 *
	 * Use '*' to count all rows, or pass a plain or table-qualified column identifier.
	 * The alias must be a simple identifier. Invalid column or alias identifiers are recorded
	 * in the builder state and the projection is not added. A failed validation does not
	 * restore the default SELECT * projection.
	 *
	 * Intended usage:
	 * - select_count() alone for pure count queries.
	 * - select(...) combined with select_count(...) for grouped aggregate queries.
	 *
	 * @param string $column Column to count, or '*' for all rows.
	 * @param string $alias Result alias for the count value.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see select()
	 */
	public function select_count( string $column = '*', string $alias = 'count_value' ): self {
		return $this->select_aggregate( 'COUNT', $column, $alias, true );
	}

	/**
	 * Specifies a SUM aggregate projection for a SELECT query.
	 *
	 * @param string $column Column to sum. The '*' form is not accepted.
	 * @param string $alias  Result alias for the sum value.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see select_aggregate()
	 */
	public function select_sum( string $column, string $alias = 'sum_value' ): self {
		return $this->select_aggregate( 'SUM', $column, $alias );
	}

	/**
	 * Specifies a MAX aggregate projection for a SELECT query.
	 *
	 * @param string $column Column to take the maximum of. The '*' form is not accepted.
	 * @param string $alias  Result alias for the maximum value.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see select_aggregate()
	 */
	public function select_max( string $column, string $alias = 'max_value' ): self {
		return $this->select_aggregate( 'MAX', $column, $alias );
	}

	/**
	 * Specifies a MIN aggregate projection for a SELECT query.
	 *
	 * @param string $column Column to take the minimum of. The '*' form is not accepted.
	 * @param string $alias  Result alias for the minimum value.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see select_aggregate()
	 */
	public function select_min( string $column, string $alias = 'min_value' ): self {
		return $this->select_aggregate( 'MIN', $column, $alias );
	}

	/**
	 * Adds a JOIN clause to the query.
	 *
	 * Identifier policy:
	 * - $table accepts a plain or qualified identifier (e.g. 'orders' or 'schema.orders').
	 * - $left_column and $right_column accept plain or qualified identifiers (e.g. 'orders.user_id').
	 * - $alias accepts only a simple identifier (no dots).
	 *
	 * Error handling:
	 * - Invalid identifiers are recorded as 'invalid_identifier' in the builder's internal Revalt error state.
	 * - Unsupported join types are recorded as 'invalid_join_type'.
	 * - On any failure, the JOIN clause is not appended.
	 *
	 * Examples:
	 *
	 * // INNER join without alias
	 * $builder->join('orders', 'users.id', 'orders.user_id');
	 *
	 * // LEFT join with alias
	 * $builder->join('posts', 'users.id', 'posts.user_id', 'LEFT', 'p');
	 *
	 * @param string $table Table name to join (plain or schema-qualified).
	 * @param string $left_column Column from the left side of the join (plain or table-qualified).
	 * @param string $right_column Column from the right side of the join (plain or table-qualified).
	 * @param string $type Join type ('INNER', 'LEFT', 'RIGHT'). Defaults to 'INNER'.
	 * @param string $alias Optional alias for the joined table (simple identifier only).
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function join( string $table, string $left_column, string $right_column, string $type = 'INNER', string $alias = '' ): self {
		$safe_table = $this->safe_ident( $table );

		if ( $safe_table === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid table identifier in join', $table );

			return $this;
		}

		$join_table = $safe_table;

		if ( $alias !== '' ) {
			$safe_alias = $this->safe_simple_ident( $alias );

			if ( $safe_alias === '' ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid join table alias provided', $alias );

				return $this;
			}

			$join_table .= ' ' . $safe_alias;
		}

		$safe_left_column = $this->safe_ident( $left_column );

		if ( $safe_left_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid left column identifier in join', $left_column );

			return $this;
		}

		$safe_right_column = $this->safe_ident( $right_column );

		if ( $safe_right_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid right column identifier in join', $right_column );

			return $this;
		}

		$safe_join_type = $this->safe_join_type( $type );

		if ( $safe_join_type === '' ) {
			$this->state->add_error( 'invalid_join_type', 'Invalid join type', $type );

			return $this;
		}

		$this->joins[] = $safe_join_type . ' JOIN ' . $join_table . ' ON ' . $safe_left_column . ' = ' . $safe_right_column;

		return $this;
	}

	/**
	 * Adds a WHERE condition to the query.
	 *
	 * Supported operators:
	 * - Comparison: '=', '<', '>', '<=', '>=', '<>', '!='
	 * - Pattern matching: 'LIKE', 'NOT LIKE'. Do not pass raw user input directly to LIKE or NOT LIKE. Use CRB_Database::escape_like() before composing a LIKE pattern from user input.
	 * - List membership: 'IN', 'NOT IN' (require a non-empty array $value)
	 * - Null comparison: 'IS', 'IS NOT' (require $value === null)
	 *
	 * NULL normalization:
	 * - When $value is null and $operator is '=' or 'IS', the fragment becomes "<column> IS NULL".
	 * - When $value is null and $operator is '!=', '<>', or 'IS NOT', the fragment becomes "<column> IS NOT NULL".
	 * - Any other operator combined with null is rejected with 'invalid_argument'.
	 *
	 * Error handling:
	 * - Invalid column identifiers are recorded as 'invalid_identifier'.
	 * - Unsupported operators are recorded as 'invalid_operator'.
	 * - 'IN'/'NOT IN' with a non-array, empty array, or array containing elements that are neither scalar nor null is recorded as 'invalid_argument'.
	 * - Non-scalar (and non-null) scalar-operator values are recorded as 'invalid_argument'.
	 * - On any failure, the WHERE condition is not appended.
	 *
	 * @param string $column_name The column name to filter by.
	 * @param string $operator The operator (see supported operators above).
	 * @param mixed $value The target value to compare against (scalar, null, or array for IN/NOT IN).
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function where( string $column_name, string $operator, $value ): self {

		// Delegate compilation and validation to the centralized helper method.
		$fragment = $this->build_where_fragment( $column_name, $operator, $value, 'where clause' );

		// If validation failed, the error is already appended to the internal state.
		if ( $fragment === '' ) {
			return $this;
		}

		// Store the safe SQL fragment.
		$this->wheres[] = $fragment;

		return $this;
	}

	/**
	 * Adds an IS NULL condition to the query safely.
	 *
	 * @param string $column The column name to check.
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function where_null( string $column ): self {
		$safe_column = $this->safe_ident( $column );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in where_null clause', $column );

			return $this;
		}

		$this->wheres[] = $safe_column . ' IS NULL';

		return $this;
	}

	/**
	 * Adds an IS NOT NULL condition to the query safely.
	 *
	 * @param string $column The column name to check.
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function where_not_null( string $column ): self {
		$safe_column = $this->safe_ident( $column );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in where_not_null clause', $column );

			return $this;
		}

		$this->wheres[] = $safe_column . ' IS NOT NULL';

		return $this;
	}

	/**
	 * Adds a grouped OR condition to the query.
	 *
	 * Contract:
	 * - Produces a grouped fragment like: (status = 'active' OR status = 'pending').
	 * - The whole group is later combined with other accumulated WHERE fragments using AND.
	 * - Each condition REQUIRES the three-item positional format: array('column', 'operator', 'value').
	 * - Supported operators, NULL normalization, and IN/NOT IN semantics match where().
	 * - Pattern matching: 'LIKE', 'NOT LIKE'. Do not pass raw user input directly to LIKE or NOT LIKE. Use CRB_Database::escape_like() before composing a LIKE pattern from user input.
	 *
	 * Group behavior (all-or-nothing):
	 * - If $conditions is empty, an 'invalid_argument' error is recorded and no group is appended.
	 * - If any single condition fails validation, the ENTIRE group is dropped (no partial OR groups
	 *   are emitted). Per-condition errors are still recorded in the builder's internal Revalt state.
	 *
	 * @param array<int, array{0:string,1:string,2:mixed}> $conditions Non-empty list of OR conditions in three-item format.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see where()
	 */
	public function where_any( array $conditions ): self {
		if ( empty( $conditions ) ) {
			$this->state->add_error( 'invalid_argument', 'No conditions provided for where_any' );

			return $this;
		}

		$fragments = array();
		$has_group_errors = false;

		// Build each condition independently but append the group only if all conditions are valid.
		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				$this->state->add_error( 'invalid_argument', 'Invalid condition format in where_any (OR clause)', $condition );
				$has_group_errors = true;
				continue;
			}

			$condition = array_values( $condition );
			$condition_count = count( $condition );

			if ( $condition_count !== 3 ) {
				$this->state->add_error( 'invalid_argument', 'Invalid condition item count in where_any (OR clause)', $condition );

				$has_group_errors = true;
				continue;
			}

			if ( ! is_string( $condition[0] ) ) {
				$this->state->add_error( 'invalid_argument', 'Condition column must be a string in where_any (OR clause)', $condition[0] );

				$has_group_errors = true;
				continue;
			}

			if ( ! is_string( $condition[1] ) ) {
				$this->state->add_error( 'invalid_argument', 'Condition operator must be a string in where_any (OR clause)', $condition[1] );

				$has_group_errors = true;
				continue;
			}

			$fragment = $this->build_where_fragment( $condition[0], $condition[1], $condition[2], 'where_any clause' );

			if ( $fragment === '' ) {
				$has_group_errors = true;
				continue;
			}

			$fragments[] = $fragment;
		}

		if ( $has_group_errors || empty( $fragments ) ) {
			return $this;
		}

		$this->wheres[] = '(' . implode( ' OR ', $fragments ) . ')';

		return $this;
	}

	/**
	 * Adds one or more columns to the GROUP BY clause of the current query.
	 *
	 * Contract:
	 * - Appends to the builder's internal GROUP BY state; multiple calls preserve call order.
	 * - Applies to SELECT queries, including those using select_count().
	 * - If validation fails for an individual column, it is skipped and an 'invalid_identifier'
	 *   error is recorded in the builder's internal Revalt state; remaining columns are still added.
	 *
	 * @param string ...$columns Variable number of column names to group by.
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function group_by( string ...$columns ): self {
		// Validate and append each column name securely, preserving previously accumulated columns.
		foreach ( $columns as $col ) {
			$safe_col = $this->safe_ident( $col );

			if ( $safe_col === '' ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in group_by', $col );
				continue;
			}

			$this->groups[] = $safe_col;
		}

		return $this;
	}

	/**
	 * Adds an ORDER BY instruction to the query.
	 *
	 * Contract:
	 * - Appends to the existing ORDER BY state; multiple calls preserve call order.
	 * - If validation fails, records an error in the builder state and does not add the expression.
	 * - Treats any direction other than 'DESC' as 'ASC'.
	 *
	 * @param string $column The column name to sort by.
	 * @param string $direction The sorting direction (ASC or DESC).
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function order_by( string $column, string $direction = 'ASC' ): self {
		// Validate the column name.
		$safe_column = $this->safe_ident( $column );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in ORDER BY', $column );

			return $this;
		}

		// Normalize and validate the sorting direction.
		$direction = strtoupper( trim( $direction ) ) === 'DESC' ? 'DESC' : 'ASC';

		$this->orders[] = $safe_column . ' ' . $direction;

		return $this;
	}

	/**
	 * Sets the query limit for pagination or bounding.
	 *
	 * A limit of 0 means "no limit" and will automatically reset any previously
	 * set offset to 0 to prevent invalid SQL queries.
	 *
	 * Error handling:
	 * - Negative values are recorded as 'invalid_argument' and the limit is not changed.
	 *
	 * @param int $limit The maximum number of rows to return. Zero means no limit.
	 *
	 * @return self Current builder instance for method chaining.
	 */
	public function limit( int $limit ): self {
		if ( $limit < 0 ) {
			$this->state->add_error( 'invalid_argument', 'Limit must be a non-negative integer', $limit );

			return $this;
		}

		$this->limit = $limit;

		// Reset offset when limit is disabled to prevent invalid SQL (OFFSET without LIMIT).
		if ( $limit === 0 ) {
			$this->offset = 0;
		}

		return $this;
	}

	/**
	 * Sets the query offset for pagination.
	 *
	 * Note: an offset without a limit is invalid SQL. When offset > 0 and limit == 0,
	 * to_sql() records a 'missing_limit' error and emits SQL without OFFSET.
	 *
	 * Error handling:
	 * - Negative values are recorded as 'invalid_argument' and the offset is not changed.
	 *
	 * @param int $offset The number of rows to skip.
	 *
	 * @return self Current builder instance for method chaining.
	 *
	 * @see limit()
	 * @see to_sql()
	 */
	public function offset( int $offset ): self {
		if ( $offset < 0 ) {
			$this->state->add_error( 'invalid_argument', 'Offset must be a non-negative integer', $offset );

			return $this;
		}

		$this->offset = $offset;

		return $this;
	}

	/**
	 * Compiles and executes the query to return a list of rows.
	 *
	 * Important notes:
	 * - Validation or database execution errors are propagated as-is via Revalt.
	 * - Use '$result->has_errors()' to check for failure before consuming the payload.
	 * - If the query succeeds but no rows match, the success payload is an empty array.
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * The success payload shape depends on $fetch_mode, which is forwarded to CRB_Database::query():
	 * - MYSQLI_ASSOC (default): indexed array of associative row arrays keyed by column name.
	 * - MYSQLI_NUM: indexed array of numerically indexed row arrays.
	 * - CRB_Database::FETCH_OBJECTS: indexed array of row objects (stdClass).
	 * - CRB_Database::FETCH_OBJECTS_KEY: array of row objects keyed by the value of each row's first column (int|string).

	 *
	 * @param int $fetch_mode One of MYSQLI_ASSOC, MYSQLI_NUM, CRB_Database::FETCH_OBJECTS, CRB_Database::FETCH_OBJECTS_KEY.
	 *
	 * @return Revalt<array<int, array<string, mixed>>|array<int, array<int, mixed>>|array<int, object>|array<int|string, object>> Result object containing the fetched rows.

	 */
	public function get_query_results( int $fetch_mode = MYSQLI_ASSOC ): Revalt {

		$sql = $this->to_sql();

		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		$query_result = $this->db->query( $sql, $fetch_mode );

		if ( $query_result->has_errors() ) {
			return $query_result;
		}

		$this->state->success( $query_result->get_results( array() ) );

		return clone $this->state;
	}

	/**
	 * Returns the row count recorded by the previous SQL_CALC_FOUND_ROWS SELECT.
	 *
	 * This method wraps SELECT FOUND_ROWS() AS total. The result is connection-state
	 * dependent and only refers to the most recent relevant SELECT statement executed
	 * on the same database connection.
	 *
	 * Usage constraints:
	 * - Requires calc_found_rows() to have been enabled on this builder instance.
	 * - Call immediately after get_query_results().
	 * - Do not execute another SELECT on the same connection between the original query and this method.
	 *
	 * @return Revalt<int> Result object containing the FOUND_ROWS() integer value.
	 *
	 * @see calc_found_rows()
	 */
	public function get_found_rows(): Revalt {
		if ( ! $this->calc_found_rows ) {
			return new Revalt( null, 'missing_calc_found_rows', 'FOUND_ROWS() requires a previous SELECT compiled with calc_found_rows() on the same builder instance.' );
		}

		// Read the connection-level FOUND_ROWS() counter set by the previous SELECT.
		$query_result = $this->db->query( 'SELECT FOUND_ROWS() AS total' );

		if ( $query_result->has_errors() ) {
			return $query_result;
		}

		// Extract the single scalar total from the first (and only) result row.
		$rows = $query_result->get_results( array() );
		$row  = $rows[0] ?? array();

		$result = new Revalt();
		$result->success( (int) ( $row['total'] ?? 0 ) );

		return $result;
	}

	/**
	 * Retrieves a single row from the database matching the current query conditions.
	 *
	 * This method temporarily forces a query limit of 1 to ensure only a single row
	 * is fetched, restoring the original limit state afterward.
	 *
	 * Important notes:
	 * - Errors from validation or database execution are propagated as-is via Revalt.
	 * Use '$result->has_errors()' to check for validation or fetch errors.
	 * - If the query succeeds but no rows match, the success payload is an empty array.
	 *
	 * Example:
	 * $result = $builder->where('id', '=', 42)->get_row();
	 * if ($result->has_errors()) {
	 * $errors = $result->get_error_messages();
	 * } else {
	 * $row = $result->get_results(array());
	 * }
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @return Revalt<array<string, mixed>> Result object containing the row data or an empty array.
	 */
	public function get_row(): Revalt {
		// Store the original limit to restore it later.
		$original_limit = $this->limit;

		// Force limit to 1 for this execution.
		$this->limit( 1 );

		$result = $this->get_query_results();

		// Restore the original limit state.
		$this->limit = $original_limit;

		if ( $result->has_errors() ) {
			return $result;
		}

		$rows = $result->get_results_list();
		$row = $rows[0] ?? array();

		$result->success( $row );

		return $result;
	}

	/**
	 * Retrieves a single scalar value from the first row of the query result.
	 *
	 * Returns a Revalt DTO containing the requested value. If a specific column
	 * is requested but missing, or if no rows match the query, the success
	 * payload will be an empty string ('').
	 *
	 * Important notes:
	 * - Validation or database execution errors are propagated as-is via Revalt.
	 * - Use '$result->has_errors()' to check for failure before accessing the value.
	 * - If $column is omitted, the method returns the first value of the fetched row
	 *    in projection compile order (plain columns precede aggregates). With more than
	 *    one projected column, pass $column explicitly to target the intended value.
	 *
	 * Example:
	 * $result = $builder->where('email', '=', 'test@example.com')->get_value('id');
	 * if ($result->has_errors()) {
	 * $errors = $result->get_error_messages();
	 * } else {
	 * $user_id = $result->get_results();
	 * }
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @param string $column Optional. The name of the specific column to retrieve.
	 *
	 * @return Revalt<mixed> Result object containing the scalar value or an empty string.
	 */
	public function get_value( string $column = '' ): Revalt {
		$result = $this->get_row();

		if ( $result->has_errors() ) {
			return $result;
		}

		$row = $result->get_results( array() );

		// Return empty string instead of null if record does not exist.
		if ( empty( $row ) ) {
			$result->success( '' );

			return $result;
		}

		// Return the specific column if explicitly requested.
		if ( $column !== '' ) {
			$result->success( array_key_exists( $column, $row ) ? $row[ $column ] : '' );

			return $result;
		}

		// Otherwise return the first value in the array.
		$result->success( reset( $row ) );

		return $result;
	}

	/**
	 * Retrieves a single column from every row matched by the current query as a flat list.
	 *
	 * Returns a Revalt DTO containing a zero-indexed array of scalar values, one per matched row.
	 *
	 * Important notes:
	 * - Validation or database execution errors are propagated as-is via Revalt.
	 *   Use '$result->has_errors()' to check for failure before consuming the payload.
	 * - If the query succeeds but no rows match, the success payload is an empty array.
	 * - If $column is omitted, the first value of each fetched row is returned in projection
	 *   compile order (plain columns precede aggregates). With more than one projected column,
	 *   pass $column explicitly to target the intended value.
	 * - If $column is provided but missing from a given row, an empty string ('') is used
	 *   for that entry (the builder never returns null here).
	 *
	 * Example:
	 * $result = $builder->select('email')->where('status', '=', 'active')->get_column();
	 * if ($result->has_errors()) {
	 * $errors = $result->get_error_messages();
	 * } else {
	 * $emails = $result->get_results(array());
	 * }
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @param string $column Optional. The name of the specific column to extract from each row.
	 *
	 * @return Revalt<array<int, mixed>> Result object containing the list of column values.
	 */
	public function get_column( string $column = '' ): Revalt {
		$result = $this->get_query_results();

		if ( $result->has_errors() ) {
			return $result;
		}

		$rows = $result->get_results( array() );
		$values = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row ) ) {
				$values[] = '';
				continue;
			}

			if ( $column !== '' ) {
				$values[] = array_key_exists( $column, $row ) ? $row[ $column ] : '';
				continue;
			}

			$values[] = reset( $row );
		}

		$result->success( $values );

		return $result;
	}

	/**
	 * Inserts one row into the current table using simple column-value assignments.
	 *
	 * This method compiles a single-table INSERT statement only:
	 * - Table aliases are not compiled into the INSERT target.
	 * - JOIN clauses are not compiled into the INSERT statement.
	 * - WHERE, GROUP BY, ORDER BY, LIMIT, and OFFSET state is not consumed by this method.
	 * - The INSERT payload must use simple column identifiers only, such as 'status' or 'created_at'.
	 * - Qualified identifiers such as 'users.status' or 'u.status' are intentionally rejected in the INSERT payload.
	 *
	 * Important:
	 * - The method requires a non-empty $data array.
	 * - The method inserts exactly one row.
	 * - Alias-aware and join-aware INSERT compilation is not supported by the current version of the method.
	 * - If the builder already contains accumulated validation errors, no query is executed.
	 *
	 * Return payload:
	 * - On success, returns Revalt<int> containing the database insert ID.
	 * - The returned insert ID may be 0 when the table has no AUTO_INCREMENT column or no generated ID is reported.
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @param array<string, mixed> $data Non-empty associative array of simple column names and inserted values.
	 *
	 * @return Revalt<int> Result object containing the inserted row ID.
	 */
	public function insert( array $data ): Revalt {
		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		if ( empty( $data ) ) {
			$this->state->add_error( 'invalid_argument', 'INSERT operation requires at least one column-value pair' );

			return clone $this->state;
		}

		// Normalize write data through the shared validation path used by UPDATE.
		$normalized_data = $this->normalize_write_data( $data, 'insert' );

		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		$columns = array_keys( $normalized_data );
		$values = array();

		// Quote each payload value after identifiers have been validated.
		foreach ( $normalized_data as $column_value ) {
			$values[] = $this->db->quote( $column_value );
		}

		$sql = 'INSERT INTO ' . $this->table
		       . ' (' . implode( ', ', $columns ) . ')'
		       . ' VALUES (' . implode( ', ', $values ) . ')';

		return $this->execute_write( $sql, 'insert_id' );
	}

	/**
	 * Inserts multiple rows into the current table using a single bulk INSERT query.
	 *
	 * The column schema is derived exclusively from the first row. Its keys define
	 * the inserted column list, column order, and value extraction map for every
	 * subsequent row.
	 *
	 * Subsequent rows are expected to follow the same logical schema as the first
	 * row. Keys that are not present in the first row are ignored and will not be
	 * inserted. Missing keys from subsequent rows cause a missing_column_data
	 * validation error.
	 *
	 * Important:
	 * - The first row defines the entire insert schema.
	 * - First-row column keys must already be canonical simple identifiers: keys with leading or
	 *   trailing whitespace are rejected with 'invalid_identifier' even though they would otherwise
	 *   pass simple-identifier validation after trimming. This enforces deterministic key mapping
	 *   across all rows.
	 * - Duplicate column keys in the first row are rejected with 'duplicate_column'.
	 * - Extra keys in later rows are silently ignored.
	 * - Missing keys in later rows cause a 'missing_column_data' error.
	 * - Invalid column identifiers cause the operation to fail through Revalt.
	 * - An empty row list or an invalid first row causes the operation to fail.
	 * - No automatic chunking; caller owns batch sizing against max_allowed_packet and memory limits.
	 *
	 * Example:
	 * $builder->insert_batch(array(
	 *     array('device_id' => 1, 'cpu' => 45),
	 *     array('device_id' => 2, 'cpu' => 88),
	 * ));
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @param array<int, array<string, mixed>> $data_list List of associative rows to insert.
	 *
	 * @return Revalt<int> Result object containing the number of affected rows on success.
	 */
	public function insert_batch( array $data_list ): Revalt {
		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		if ( empty( $data_list ) ) {
			$this->state->add_error( 'invalid_argument', 'No data provided for batch insert' );

			return clone $this->state;
		}

		$first_row = reset( $data_list );

		// Ensure the first row is actually an array

		if ( ! is_array( $first_row ) ) {
			$this->state->add_error( 'invalid_argument', 'First row in batch insert must be an array' );

			return clone $this->state;
		}

		if ( empty( $first_row ) ) {
			$this->state->add_error( 'invalid_argument', 'First row in batch insert must contain at least one column' );

			return clone $this->state;
		}

		$column_map = array();
		$sql_columns = array();
		$known_columns = array();

		// Extract and strictly validate column names from the very first row.
		// Maintain a mapping of original keys to sanitized columns to ensure accurate row data extraction.

		foreach ( array_keys( $first_row ) as $original_key ) {
			$string_key = (string) $original_key;
			$safe_col = $this->safe_simple_ident( $string_key );

			if ( $safe_col === '' ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in batch insert', $string_key );

				return clone $this->state;
			}

			// Reject keys with leading or trailing whitespace to ensure deterministic mapping across all rows.
			if ( $safe_col !== $string_key ) {
				$this->state->add_error( 'invalid_identifier', 'Column identifier contains leading or trailing whitespace in batch insert', $string_key );

				return clone $this->state;
			}

			// Prevent duplicate columns in the INSERT statement.
			$lower_safe_col = strtolower( $safe_col );
			if ( isset( $known_columns[ $lower_safe_col ] ) ) {
				$this->state->add_error( 'duplicate_column', 'Duplicate column identifier detected in batch insert', $safe_col );

				return clone $this->state;
			}
			$known_columns[ $lower_safe_col ] = true;

			$column_map[ $original_key ] = $safe_col;
			$sql_columns[] = $safe_col;
		}

		$values_blocks = array();

		// Process each row to ensure it provides all columns established by the first row.
		// Any additional keys in a row are ignored because INSERT values are built only from $column_map.
		foreach ( $data_list as $index => $row ) {
			// Ensure every item in the list is an array to prevent fatal runtime errors during schema checking.
			if ( ! is_array( $row ) ) {
				$this->state->add_error( 'invalid_argument', 'Batch insert row must be an array', $index );

				return clone $this->state;
			}

			$row_values = array();

			foreach ( $column_map as $original_key => $safe_col ) {
				if ( ! array_key_exists( $original_key, $row ) ) {
					$this->state->add_error( 'missing_column_data', 'Missing data for expected column in batch insert', (string) $original_key );

					return clone $this->state;
				}

				$row_value = $row[ $original_key ];

				if ( ! $this->is_quotable_value( $row_value ) ) {
					$this->state->add_error( 'invalid_argument', 'Unsupported value type for column in batch insert', $safe_col . ' at row ' . $index . ': ' . gettype( $row_value ) );

					return clone $this->state;
				}

				$row_values[] = $this->db->quote( $row_value );
			}

			$values_blocks[] = '(' . implode( ', ', $row_values ) . ')';
		}

		// Compile the massive batch insert query block.
		$sql = 'INSERT INTO ' . $this->table . ' (' . implode( ', ', $sql_columns ) . ') VALUES ' . implode( ', ', $values_blocks );

		return $this->execute_write( $sql, 'affected_rows' );
	}

	/**
	 * Updates existing rows in the current table using simple column-value assignments.
	 *
	 * This method compiles a single-table UPDATE statement only:
	 * - alias() is compiled into the UPDATE target.
	 * - join() clauses ARE NOT supported and cause an error.
	 * - SET payload keys must use simple column identifiers only, such as 'status' or 'updated_at'.
	 * - Qualified identifiers such as 'users.status' or 'u.status' are intentionally rejected in the SET payload.
	 *
	 * Important:
	 * - The method requires at least one WHERE condition to prevent accidental full-table updates.
	 * - WHERE and ORDER BY clauses may use qualified identifiers accepted earlier by where() and order_by().
	 * - When alias() is used, qualified WHERE and ORDER BY identifiers should use that alias.
	 * - This method does not apply builder state created by select(), select_count(), group_by(), or offset().
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @param array<string, mixed> $data Associative array of simple column names and their new values. Must be non-empty; an empty array produces `invalid_argument`.
	 *
	 * @return Revalt<int> Result object containing the number of affected rows.
	 */
	public function update( array $data ): Revalt {
		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		if ( ! $this->validate_no_joins_for_write( 'UPDATE' ) ) {
			return clone $this->state;
		}

		if ( empty( $data ) ) {
			$this->state->add_error( 'invalid_argument', 'UPDATE operation requires at least one column-value pair' );

			return clone $this->state;
		}

		// Guard against accidental full-table updates.
		if ( empty( $this->wheres ) ) {
			$this->state->add_error( 'missing_where_condition', 'UPDATE operation requires at least one WHERE condition to prevent accidental mass modification' );

			return clone $this->state;
		}

		// Normalize write data through the shared validation path used by INSERT.
		$normalized_data = $this->normalize_write_data( $data, 'update' );

		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		$sets = array();

		// Build the SET clause after identifiers have been validated.
		foreach ( $normalized_data as $safe_column => $column_value ) {
			$sets[] = $safe_column . ' = ' . $this->db->quote( $column_value );
		}

		$sql = 'UPDATE ' . $this->compile_main_table_reference() . ' SET ' . implode( ', ', $sets );

		if ( ! empty( $this->wheres ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $this->wheres );
		}

		if ( ! empty( $this->orders ) ) {
			$sql .= ' ORDER BY ' . implode( ', ', $this->orders );
		}

		if ( $this->limit > 0 ) {
			$sql .= ' LIMIT ' . $this->limit;
		}

		return $this->execute_write( $sql, 'affected_rows' );
	}

	/**
	 * Deletes rows from the current table matching the current query conditions.
	 *
	 * This method compiles a single-table DELETE statement only:
	 * - alias() is compiled into the DELETE target.
	 * - join() clauses are not supported and cause an error.
	 * - Only rows from the main table are deleted.
	 *
	 * Important:
	 * - The method requires at least one WHERE condition to prevent accidental full-table deletion.
	 * - WHERE and ORDER BY clauses may use qualified identifiers accepted earlier by where() and order_by().
	 * - When alias() is used, qualified WHERE and ORDER BY identifiers should use that alias.
	 * - This method does not apply builder state created by select(), select_count(), group_by(), or offset().
	 *
	 * Usage: Terminal method; executes the compiled query and ends the fluent builder chain.
	 *
	 * @return Revalt<int> Result object containing the number of affected rows.
	 */
	public function delete(): Revalt {
		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		if ( ! $this->validate_no_joins_for_write( 'DELETE' ) ) {
			return clone $this->state;
		}

		// Guard against accidental full-table deletions.
		if ( empty( $this->wheres ) ) {
			$this->state->add_error( 'missing_where_condition', 'DELETE operation requires at least one WHERE condition to prevent accidental mass deletion' );

			return clone $this->state;
		}

		// Construct the base delete statement.
		$sql = 'DELETE FROM ' . $this->compile_main_table_reference();

		// Append operational conditions and limits.
		if ( ! empty( $this->wheres ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $this->wheres );
		}

		if ( ! empty( $this->orders ) ) {
			$sql .= ' ORDER BY ' . implode( ', ', $this->orders );
		}

		if ( $this->limit > 0 ) {
			$sql .= ' LIMIT ' . $this->limit;
		}

		return $this->execute_write( $sql, 'affected_rows' );
	}

	/**
	 * Executes a write query and normalizes the return payload into the internal state.
	 *
	 * @param string $sql The constructed SQL query to execute.
	 * @param string $result_key The key to extract from the query data.
	 *
	 * @return Revalt<int> Result object containing the extracted integer value.
	 */
	private function execute_write( string $sql, string $result_key = 'affected_rows' ): Revalt {
		// Return early if the builder already accumulated validation errors.
		if ( $this->state->has_errors() ) {
			return clone $this->state;
		}

		// Execute the query via the database adapter.
		$query_result = $this->db->query( $sql );

		// Return early if the database execution failed.
		if ( $query_result->has_errors() ) {
			return $query_result;
		}

		// Extract the requested integer value securely.
		$query_data = $query_result->get_results( array() );
		$this->state->success( (int) ( $query_data[ $result_key ] ?? 0 ) );

		return clone $this->state;
	}

	/**
	 * Compiles the accumulated query clauses into a raw SELECT SQL string.
	 *
	 * The returned string is fully escaped and ready for execution. All
	 * accumulated clauses (SELECT, JOIN, WHERE, GROUP BY, ORDER BY, LIMIT, OFFSET) are included if set.
	 *
	 * This method only reflects SELECT-style read queries, write class methods build their own SQL and are not represented here.
	 *
	 * Side effect:
	 * - When offset > 0 and limit == 0, this method records a 'missing_limit' error in the
	 *   builder's internal Revalt state and returns the SQL without the OFFSET clause.
	 *
	 * Important: Always check $this->get_state()->has_errors() before using the returned string to run a query.
	 *
	 * @return string Compiled SQL string. May be invalid SQL if the builder has accumulated errors.
	 */
	public function to_sql(): string {

		// Start the basic select structure. The optional SQL_CALC_FOUND_ROWS modifier is
		// emitted immediately after SELECT and before the projection list (it is a modifier,
		// not a selected column, so no comma separates it from the projection).
		$sql = 'SELECT '
		       . ( $this->calc_found_rows ? 'SQL_CALC_FOUND_ROWS ' : '' )
		       . $this->compile_select_list()
		       . ' FROM ' . $this->table;

		// Append table alias if it exists.
		if ( $this->table_alias !== '' ) {
			$sql .= ' ' . $this->table_alias;
		}

		// Append join clauses if they exist.
		if ( ! empty( $this->joins ) ) {
			$sql .= ' ' . implode( ' ', $this->joins );
		}

		// Append where conditions if they exist.
		if ( ! empty( $this->wheres ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $this->wheres );
		}

		// Append group by instructions if they exist.
		if ( ! empty( $this->groups ) ) {
			$sql .= ' GROUP BY ' . implode( ', ', $this->groups );
		}

		// Append sorting instructions if they exist.
		if ( ! empty( $this->orders ) ) {
			$sql .= ' ORDER BY ' . implode( ', ', $this->orders );
		}

		// Append limit and offset instruction if they exist

		if ( $this->offset > 0
		     && $this->limit === 0 ) {
			$this->state->add_error( 'missing_limit', 'Offset requires a limit' );

			return $sql;
		}

		if ( $this->limit > 0 ) {
			$sql .= ' LIMIT ' . $this->limit;
		}

		if ( $this->offset > 0 ) {
			$sql .= ' OFFSET ' . $this->offset;
		}

		return $sql;
	}

	/**
	 * Compiles the final SELECT projection list from accumulated columns and aggregates.
	 *
	 * The two internal buckets ($select_columns and $select_aggregates) are merged
	 * with columns first, aggregates second, producing a stable order that does not
	 * depend on the original call sequence of select() and select_count().
	 *
	 * Behavior:
	 * - Both buckets empty and no explicit select()/select_count() call: returns '*' (default projection).
	 * - At least one bucket non-empty: returns the merged, comma-separated list.
	 * - Explicit projection reduced to empty by validation errors: returns the empty string, which yields
	 *   syntactically broken SQL on purpose so callers that skip the get_state()->has_errors() check fail loudly rather than
	 *   silently running a full-table scan.
	 *
	 * @return string The compiled SELECT projection list (without the leading 'SELECT ').
	 */
	private function compile_select_list(): string {
		$parts = array_merge( $this->select_columns, $this->select_aggregates );

		// Default projection only when nothing has been requested at all.
		if ( empty( $parts ) && ! $this->select_explicit ) {
			return '*';
		}

		return implode( ', ', $parts );
	}

	/**
	 * Builds one aggregate SELECT projection (COUNT(col), SUM(col), MAX(col), ...) and
	 * appends it to the aggregate bucket. Shared implementation behind the select_* aggregate API.
	 *
	 * SECURITY: $function is concatenated verbatim and is NOT validated. It MUST be a
	 * hard-coded keyword supplied by the calling method, never user input. Keep this
	 * method private.
	 *
	 * @param string $function   Aggregate keyword, e.g. 'COUNT' or 'SUM'. Caller-controlled, not validated.
	 * @param string $column     Column to aggregate, or '*' only when $allow_star is true.
	 * @param string $alias      Result alias.
	 * @param bool   $allow_star Whether the bare '*' form is accepted (COUNT only; SUM/MAX/MIN(*) are invalid SQL).
	 *
	 * @return self Current builder instance for method chaining.
	 */
	private function select_aggregate( string $function, string $column, string $alias, bool $allow_star = false ): self {
		$this->select_explicit = true;

		$safe_alias = $this->safe_simple_ident( $alias );

		if ( $safe_alias === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid alias identifier in ' . $function . ' select', $alias );

			return $this;
		}

		if ( $allow_star && $column === '*' ) {
			$this->select_aggregates[] = $function . '(*) AS ' . $safe_alias;

			return $this;
		}

		$safe_column = $this->safe_ident( $column );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in ' . $function . ' select', $column );

			return $this;
		}

		$this->select_aggregates[] = $function . '(' . $safe_column . ') AS ' . $safe_alias;

		return $this;
	}

	/**
	 * Validates a MySQL identifier and returns it unquoted, or an empty string when invalid.
	 *
	 * Accepts a single identifier or a two-segment qualified name (segment.segment).
	 * Each segment may contain ASCII letters, digits, and underscores, and is limited
	 * to 64 characters, matching the MySQL identifier length limit. Numeric-only
	 * segments such as 1024 are accepted. With wildcard authorization the standalone
	 * asterisk and the table.* form are also accepted.
	 *
	 * The returned value is not quoted. Most identifiers are valid unquoted, but
	 * reserved words and numeric-only names are not, so callers interpolating the
	 * result into SQL should backtick-quote it by default to stay safe. When quoting
	 * a qualified name, quote each segment separately rather than the dotted string
	 * as a whole, and leave the wildcard asterisk unquoted.
	 *
	 * @param string $ident Raw identifier name. Surrounding whitespace is trimmed.
	 * @param bool $allow_wildcard Whether to accept the standalone asterisk and the table.* form.
	 *
	 * @return string Validated unquoted identifier, or an empty string when the input fails validation.
	 */
	private function safe_ident( string $ident, bool $allow_wildcard = false ): string {
		// Maximum length MySQL allows for a single identifier.
		$max_length = 64;

		$ident = trim( $ident );

		if ( $ident === '' ) {
			return '';
		}

		// A bare asterisk is authorized on its own and needs no further checks.
		if ( $allow_wildcard && $ident === '*' ) {
			return '*';
		}

		// Select the accepted structure: a plain identifier, a qualified name, or the table.* form.
		$pattern = $allow_wildcard
			? '/\A[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+|\.\*)?\z/'
			: '/\A[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?\z/';

		if ( ! preg_match( $pattern, $ident ) ) {
			return '';
		}

		// Reject any named segment that exceeds the MySQL length limit. The asterisk is exempt.
		foreach ( explode( '.', $ident ) as $segment ) {
			if ( $segment !== '*' && strlen( $segment ) > $max_length ) {
				return '';
			}
		}

		return $ident;
	}

	/**
	 * Validates and normalizes a simple MySQL identifier (e.g., an alias).
	 *
	 * @param string $ident Raw identifier name.
	 *
	 * @return string Validated identifier or an empty string if invalid.
	 */
	private function safe_simple_ident( string $ident ): string {
		// Strip leading and trailing whitespace.
		$ident = trim( $ident );

		// Reject empty strings early.
		if ( $ident === '' ) {
			return '';
		}

		// Apply strict validation for alphanumeric and underscore patterns only (no dots).
		if ( ! preg_match( '/\\A[A-Za-z_][A-Za-z0-9_]*\\z/', $ident ) ) {
			return '';
		}

		return $ident;
	}

	/**
	 * Validates the provided SQL comparison operator against a list of allowed operators.
	 *
	 * @param string $operator Raw operator.
	 *
	 * @return string Validated operator or an empty string the operator is not invalid.
	 *
	 * @see where()
	 */
	private function safe_operator( string $operator ): string {
		$operator = strtoupper( trim( $operator ) );

		$allowed_operators = array(
			'=',
			'<',
			'>',
			'<=',
			'>=',
			'<>',
			'!=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'IS',
			'IS NOT'
		);

		if ( in_array( $operator, $allowed_operators, true ) ) {
			return $operator;
		}

		return '';
	}

	/**
	 * Validates a SQL JOIN type against a strict whitelist.
	 *
	 * @param string $type Raw join type.
	 *
	 * @return string Validated join type or an empty string if invalid.
	 */
	private function safe_join_type( string $type ): string {
		$type = strtoupper( trim( $type ) );

		$allowed_types = array(
			'INNER',
			'LEFT',
			'RIGHT'
		);

		if ( in_array( $type, $allowed_types, true ) ) {
			return $type;
		}

		return '';
	}

	/**
	 * Builds a safe SQL WHERE fragment from a normalized condition array.
	 *
	 * @param string $column_name The column name to filter by.
	 * @param mixed $operator The operator (e.g., '>', '=', 'IN').
	 * @param mixed $value The target value to compare against.
	 * @param string $context The error context used in diagnostic messages.
	 *
	 * @return string Safe SQL fragment, or an empty string if validation failed.
	 */
	private function build_where_fragment( string $column_name, string $operator, $value, string $context ): string {

		$safe_column = $this->safe_ident( $column_name );

		if ( $safe_column === '' ) {
			$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in ' . $context, $column_name );

			return '';
		}

		$safe_operator = $this->safe_operator( $operator );

		if ( $safe_operator === '' ) {
			$this->state->add_error( 'invalid_operator', 'Invalid SQL operator in ' . $context, $operator );

			return '';
		}

		// Handle NULL comparisons, normalizing =/!=/<> to IS NULL/IS NOT NULL.
		if ( $value === null ) {
			if ( $safe_operator === '=' || $safe_operator === 'IS' ) {
				return $safe_column . ' IS NULL';
			}

			if ( $safe_operator === '!=' || $safe_operator === '<>' || $safe_operator === 'IS NOT' ) {
				return $safe_column . ' IS NOT NULL';
			}

			$this->state->add_error( 'invalid_argument', 'Operator is not supported for NULL comparison; use =, !=, <>, IS, or IS NOT in ' . $context, $safe_operator );

			return '';
		}


		// Reject IS / IS NOT against non-null values; these operators are reserved for NULL comparisons.
		if ( $safe_operator === 'IS' || $safe_operator === 'IS NOT' ) {
			$this->state->add_error( 'invalid_argument', 'Operators IS and IS NOT can only be used with NULL values in ' . $context, $safe_operator );

			return '';
		}

		switch ( $safe_operator ) {
			case 'IN':
			case 'NOT IN':
				if ( ! is_array( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Operator IN requires an array value in ' . $context, $safe_operator );

					return '';
				}

				if ( empty( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Empty arrays are not allowed for IN operator in ' . $context, $safe_operator );

					return '';
				}

				// Validate and quote each element in a single pass

				$quoted_values = array();

				foreach ( $value as $list_element ) {
					if ( ! $this->is_quotable_value( $list_element ) ) {
						$this->state->add_error( 'invalid_argument', 'Unsupported value type in IN list in ' . $context, $safe_column . ': ' . gettype( $list_element ) );

						return '';
					}

					$quoted_values[] = $this->db->quote( $list_element );
				}

				$safe_value = '(' . implode( ', ', $quoted_values ) . ')';
				break;

			case 'LIKE':
			case 'NOT LIKE':
				if ( ! is_string( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Cannot use array with LIKE operator in ' . $context, $safe_operator );

					return '';
				}

				if ( ! $this->is_quotable_value( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Unsupported LIKE value type in ' . $context, $safe_column . ': ' . gettype( $value ) );

					return '';
				}

				// Quotes a trusted LIKE pattern and appends the explicit ESCAPE clause.
				$safe_value = $this->db->quote( (string) $value ) . ' ESCAPE ' . $this->db->quote( CRB_Database::LIKE_ESCAPE_CHAR );

				return $safe_column . ' ' . $safe_operator . ' ' . $safe_value;

			default:
				if ( is_array( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Cannot use array with scalar operator in ' . $context, $safe_operator );

					return '';
				}

				if ( ! $this->is_quotable_value( $value ) ) {
					$this->state->add_error( 'invalid_argument', 'Unsupported value type in ' . $context, $safe_column . ': ' . gettype( $value ) );

					return '';
				}

				$safe_value = $this->db->quote( $value );
				break;
		}

		return $safe_column . ' ' . $safe_operator . ' ' . $safe_value;
	}

	/**
	 * Validates and normalizes associative column-value data for write operations.
	 *
	 * This helper intentionally validates only simple column identifiers because
	 * INSERT and single-table UPDATE SET clauses must not use dotted identifiers.
	 *
	 * Expected returned format:
	 * - Associative array where keys are canonical safe column names and values are original payload values.
	 *
	 * @param array<int|string, mixed> $write_data Associative column-value data.
	 * @param string $context Diagnostic context used in error messages.
	 *
	 * @return array<string, mixed> Normalized column-value data, or empty array if validation failed.
	 */
	private function normalize_write_data( array $write_data, string $context ): array {
		$normalized_data = array();
		$known_columns = array();

		foreach ( $write_data as $column => $column_value ) {
			$string_column = trim( (string) $column );
			$safe_column = $this->safe_simple_ident( $string_column );

			if ( $safe_column === '' ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in ' . $context, $string_column );

				return array();
			}

			// Reject non-canonical identifiers to prevent implicit whitespace bypasses.
			if ( $safe_column !== $string_column ) {
				$this->state->add_error( 'invalid_identifier', 'Invalid column identifier in ' . $context, $string_column );

				return array();
			}

			$lower_safe_column = strtolower( $safe_column );

			if ( isset( $known_columns[ $lower_safe_column ] ) ) {
				$this->state->add_error( 'duplicate_column', 'Duplicate column identifier detected in ' . $context, $safe_column );

				return array();
			}

			$known_columns[ $lower_safe_column ] = true;

			if ( ! $this->is_quotable_value( $column_value ) ) {
				$this->state->add_error( 'invalid_argument', 'Unsupported value type for column in ' . $context, $safe_column . ': ' . gettype( $column_value ) );

				return array();
			}

			$normalized_data[ $safe_column ] = $column_value;
		}

		return $normalized_data;
	}

	/**
	 * Determines whether the given value can be safely passed to CRB_Database::quote().
	 *
	 * This helper centralizes the supported-type check so the builder can record
	 * a structured validation error instead of letting the adapter exception propagate
	 * out of the fluent API.
	 *
	 * @param mixed $value Candidate value to be quoted by the database adapter.
	 *
	 * @return bool True when the value is null or a scalar; false otherwise.
	 */
	private function is_quotable_value( $value ): bool {
		return $value === null || is_scalar( $value );
	}

	/**
	 * Compiles the main table reference with the optional alias.
	 *
	 * Used by single-table write operations that support alias-aware conditions.
	 *
	 * @return string Main table reference, with alias if one was set.
	 */
	private function compile_main_table_reference(): string {
		if ( $this->table_alias === '' ) {
			return $this->table;
		}

		return $this->table . ' ' . $this->table_alias;
	}

	/**
	 * Rejects JOIN clauses for single-table write operations.
	 *
	 * @param string $operation Operation name used in diagnostics.
	 *
	 * @return bool True when no JOIN clauses are present.
	 */
	private function validate_no_joins_for_write( string $operation ): bool {
		if ( empty( $this->joins ) ) {
			return true;
		}

		$this->state->add_error(
			'unsupported_join_for_write',
			$operation . ' operation does not support JOIN clauses'
		);

		return false;
	}

	/**
	 * Retrieves the internal Revalt instance representing the current
	 * error and result state of this builder or database operation.
	 *
	 * @return Revalt<mixed> A cloned Revalt instance holding the current state.
	 */
	public function get_state(): Revalt {
		return clone $this->state;
	}
}
