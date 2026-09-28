<?php

declare( strict_types=1 );

/**
 * Compiles declarative DB schema arrays into MySQL DDL statements and fragments.
 *
 * This class is pure compilation logic:
 * - Does not execute SQL.
 * - Does not inspect database metadata.
 * - Does not depend on CRB_Database.
 * - Returns Revalt outcomes for deterministic error handling.
 *
 * Supported column facts:
 * - type: required SQL type string, for example "varchar(250)", "bigint(20) unsigned", "decimal(14,4)".
 * - charset: optional column-level CHARACTER SET value.
 * - nullable: required boolean.
 * - default: optional literal default. Omit when no DEFAULT clause is declared.
 *
 * Supported index facts:
 * - columns: required non-empty ordered list of column names.
 * - unique: optional boolean. Defaults to false.
 * - primary: optional boolean. Defaults to false.
 *
 * @version 1.0
 */
final class CRB_Schema_Compiler {

	/**
	 * Compiles a CREATE TABLE IF NOT EXISTS statement.
	 *
	 * Expected table definition shape:
	 *
	 * array(
	 *     'charset' => 'utf8mb4',
	 *     'engine'  => 'InnoDB',
	 *     'collate' => 'utf8mb4_unicode_ci',
	 *     'columns' => array(
	 *         'column_name' => array(
	 *             'type' => 'varchar(250)',
	 *             'nullable' => false,
	 *             'default' => '',
	 *         ),
	 *     ),
	 *     'indexes' => array(
	 *         'index_name' => array(
	 *             'columns' => array('column_name'),
	 *             'unique' => false,
	 *         ),
	 *     ),
	 *  )
	 *
	 * Table-level validation:
	 * - Duplicate column names are rejected case-insensitively.
	 * - Duplicate index names are rejected case-insensitively.
	 * - At most one primary key declaration is accepted, whether declared
	 *   via the reserved index name PRIMARY or via the primary flag.
	 * - Every index column must be declared in the columns array.
	 *
	 * @param string $table_name Table name. May be plain or database-qualified.
	 * @param array<string, mixed> $table_definition Declarative table definition.
	 *
	 * @return Revalt<string> Result object containing the compiled CREATE TABLE statement.
	 */
	public function compile_create_table_sql( string $table_name, array $table_definition ): Revalt {
		$safe_table = $this->quote_identifier_reference( $table_name );

		if ( $safe_table === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid table identifier for CREATE TABLE.',
				$table_name
			);
		}

		if ( empty( $table_definition['columns'] ) || ! is_array( $table_definition['columns'] ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Table definition must contain a non-empty columns array.',
				$table_definition
			);
		}

		$compiled_lines = array();
		$known_columns = array();

		foreach ( $table_definition['columns'] as $column_name => $column_definition ) {
			if ( ! is_string( $column_name ) || ! is_array( $column_definition ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Column definition must use a string column name and an array definition.',
					array(
						'column_name'       => $column_name,
						'column_definition' => $column_definition,
					)
				);
			}

			// MySQL and MariaDB compare column names case-insensitively, so ID and id
			// are duplicates even though they are distinct PHP array keys.
			$column_key = strtolower( trim( $column_name ) );

			if ( isset( $known_columns[ $column_key ] ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Table definition contains duplicate column names.',
					$column_name
				);
			}

			$known_columns[ $column_key ] = true;

			$column_result = $this->compile_column_sql( $column_name, $column_definition );

			if ( $column_result->has_errors() ) {
				return $column_result;
			}

			$compiled_lines[] = $column_result->get_results( '' );
		}

		if ( ! empty( $table_definition['indexes'] ) ) {
			if ( ! is_array( $table_definition['indexes'] ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Indexes definition must be an array.',
					$table_definition['indexes']
				);
			}

			$known_indexes = array();
			$has_primary_key = false;

			foreach ( $table_definition['indexes'] as $index_name => $index_definition ) {
				if ( ! is_string( $index_name ) || ! is_array( $index_definition ) ) {
					return new Revalt(
						null,
						'invalid_schema_definition',
						'Index definition must use a string index name and an array definition.',
						array(
							'index_name'       => $index_name,
							'index_definition' => $index_definition,
						)
					);
				}

				// MySQL compares index names case-insensitively, so idx_a and IDX_A
				// are duplicates even though they are distinct PHP array keys.
				$index_key = strtolower( trim( $index_name ) );

				if ( isset( $known_indexes[ $index_key ] ) ) {
					return new Revalt(
						null,
						'invalid_schema_definition',
						'Table definition contains duplicate index names.',
						$index_name
					);
				}

				$known_indexes[ $index_key ] = true;

				$index_result = $this->compile_index_sql( $index_name, $index_definition );

				if ( $index_result->has_errors() ) {
					return $index_result;
				}

				$compiled_index = $index_result->get_results( '' );

				// compile_index_sql() is the single source of truth for whether a
				// declaration is a primary key; its compiled fragment starts with
				// PRIMARY KEY exactly in that case. Counting here avoids duplicating
				// the name-or-flag detection logic.
				if ( strncmp( $compiled_index, 'PRIMARY KEY', 11 ) === 0 ) {
					if ( $has_primary_key ) {
						return new Revalt(
							null,
							'invalid_schema_definition',
							'Table definition declares more than one primary key.',
							$index_name
						);
					}

					$has_primary_key = true;
				}

				// Cross-check index columns against declared table columns. At this
				// point compile_index_sql() has guaranteed that columns is a non-empty
				// list of valid string identifiers, so iterating it raw is safe.
				foreach ( $index_definition['columns'] as $index_column ) {
					if ( ! isset( $known_columns[ strtolower( trim( $index_column ) ) ] ) ) {
						return new Revalt(
							null,
							'invalid_schema_definition',
							'Index references a column that is not declared in the table definition.',
							array(
								'index_name' => $index_name,
								'column'     => $index_column,
							)
						);
					}
				}

				$compiled_lines[] = $compiled_index;
			}
		}

		$options_result = $this->compile_table_options_sql( $table_definition );

		if ( $options_result->has_errors() ) {
			return $options_result;
		}

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $safe_table . " (\n\t"
		       . implode( ",\n\t", $compiled_lines )
		       . "\n) " . $options_result->get_results( '' );

		return new Revalt( $sql );
	}

	/**
	 * Compiles an ALTER TABLE ADD COLUMN statement.
	 *
	 * @param string $table_name Table name. May be plain or database-qualified.
	 * @param string $column_name Column name.
	 * @param array<string, mixed> $column_definition Declarative column definition.
	 *
	 * @return Revalt<string> Result object containing the compiled ALTER TABLE ADD COLUMN statement.
	 */
	public function compile_add_column_sql( string $table_name, string $column_name, array $column_definition ): Revalt {
		$safe_table = $this->quote_identifier_reference( $table_name );

		if ( $safe_table === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid table identifier for ADD COLUMN.',
				$table_name
			);
		}

		$column_result = $this->compile_column_sql( $column_name, $column_definition );

		if ( $column_result->has_errors() ) {
			return $column_result;
		}

		return new Revalt( 'ALTER TABLE ' . $safe_table . ' ADD COLUMN ' . $column_result->get_results( '' ) );
	}

	/**
	 * Compiles an ALTER TABLE ADD INDEX statement.
	 *
	 * @param string $table_name Table name. May be plain or database-qualified.
	 * @param string $index_name Index name, or PRIMARY for a primary key declaration.
	 * @param array<string, mixed> $index_definition Declarative index definition.
	 *
	 * @return Revalt<string> Result object containing the compiled ALTER TABLE ADD INDEX statement.
	 */
	public function compile_add_index_sql( string $table_name, string $index_name, array $index_definition ): Revalt {
		$safe_table = $this->quote_identifier_reference( $table_name );

		if ( $safe_table === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid table identifier for ADD INDEX.',
				$table_name
			);
		}

		$index_result = $this->compile_index_sql( $index_name, $index_definition );

		if ( $index_result->has_errors() ) {
			return $index_result;
		}

		return new Revalt( 'ALTER TABLE ' . $safe_table . ' ADD ' . $index_result->get_results( '' ) );
	}

	/**
	 * Compiles a column declaration fragment.
	 *
	 * @param string $column_name Column name.
	 * @param array<string, mixed> $column_definition Declarative column definition.
	 *
	 * @return Revalt<string> Result object containing a column declaration fragment.
	 */
	public function compile_column_sql( string $column_name, array $column_definition ): Revalt {
		$safe_column = $this->quote_simple_identifier( $column_name );

		if ( $safe_column === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid column identifier.',
				$column_name
			);
		}

		$type_result = $this->get_required_column_type( $column_definition );

		if ( $type_result->has_errors() ) {
			return $type_result;
		}

		$nullable_result = $this->get_required_boolean_flag( $column_definition, 'nullable' );

		if ( $nullable_result->has_errors() ) {
			return $nullable_result;
		}

		$sql = $safe_column . ' ' . $type_result->get_results( '' );

		if ( array_key_exists( 'charset', $column_definition ) ) {
			$charset_result = $this->get_optional_identifier_option( $column_definition, 'charset' );

			if ( $charset_result->has_errors() ) {
				return $charset_result;
			}

			$sql .= ' CHARACTER SET ' . $charset_result->get_results( '' );
		}

		$sql .= $nullable_result->get_results( false ) ? ' NULL' : ' NOT NULL';

		if ( array_key_exists( 'default', $column_definition ) ) {
			$default_result = $this->compile_default_sql( $column_definition['default'] );

			if ( $default_result->has_errors() ) {
				return $default_result;
			}

			$sql .= $default_result->get_results( '' );
		}

		return new Revalt( $sql );
	}

	/**
	 * Compiles an index declaration fragment.
	 *
	 * @param string $index_name Index name, or PRIMARY for a primary key declaration.
	 * @param array<string, mixed> $index_definition Declarative index definition.
	 *
	 * @return Revalt<string> Result object containing an index declaration fragment.
	 */
	public function compile_index_sql( string $index_name, array $index_definition ): Revalt {
		$columns_result = $this->compile_index_columns_sql( $index_definition );

		if ( $columns_result->has_errors() ) {
			return $columns_result;
		}

		$is_primary = false;
		$primary_declared = array_key_exists( 'primary', $index_definition );

		if ( $primary_declared ) {
			if ( ! is_bool( $index_definition['primary'] ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Index primary flag must be boolean.',
					$index_definition
				);
			}

			$is_primary = $index_definition['primary'];
		}

		// The reserved name PRIMARY implies a primary key. An explicit primary => false
		// combined with this name is contradictory input and fails fast.
		if ( strtoupper( $index_name ) === 'PRIMARY' ) {
			if ( $primary_declared && ! $is_primary ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Index named PRIMARY cannot declare primary => false.',
					$index_definition
				);
			}

			$is_primary = true;
		}

		if ( $is_primary ) {
			return new Revalt( 'PRIMARY KEY (' . $columns_result->get_results( '' ) . ')' );
		}

		$safe_index = $this->quote_simple_identifier( $index_name );

		if ( $safe_index === '' ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid index identifier.',
				$index_name
			);
		}

		$is_unique = false;

		if ( array_key_exists( 'unique', $index_definition ) ) {
			if ( ! is_bool( $index_definition['unique'] ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Index unique flag must be boolean.',
					$index_definition
				);
			}

			$is_unique = $index_definition['unique'];
		}

		$prefix = $is_unique ? 'UNIQUE KEY ' : 'KEY ';

		return new Revalt( $prefix . $safe_index . ' (' . $columns_result->get_results( '' ) . ') USING BTREE' );
	}

	/**
	 * Compiles table options: storage engine, default charset, and optional collation.
	 *
	 * Supported table definition keys:
	 * - engine: optional storage engine identifier. Defaults to InnoDB so the
	 *   compiled DDL never depends on the server-level default engine.
	 * - charset: optional default charset identifier. Defaults to utf8mb4.
	 * - collate: optional collation identifier. Emitted only when declared.
	 *
	 * No default collation is emitted on purpose. Collation availability differs
	 * between MySQL and MariaDB (for example, utf8mb4_0900_ai_ci exists only in
	 * MySQL 8), while omitting COLLATE lets the server apply the default collation
	 * of the declared charset, which is valid on both.
	 *
	 * @param array<string, mixed> $table_definition Declarative table definition.
	 *
	 * @return Revalt<string> Result object containing the table options fragment.
	 */
	private function compile_table_options_sql( array $table_definition ): Revalt {
		// Resolve the storage engine, defaulting to an explicit InnoDB declaration.
		$engine = 'InnoDB';

		if ( array_key_exists( 'engine', $table_definition ) ) {
			$engine_result = $this->get_optional_identifier_option( $table_definition, 'engine' );

			if ( $engine_result->has_errors() ) {
				return $engine_result;
			}

			$engine = $engine_result->get_results( '' );
		}

		// Resolve the default table charset.
		$charset = 'utf8mb4';

		if ( array_key_exists( 'charset', $table_definition ) ) {
			$charset_result = $this->get_optional_identifier_option( $table_definition, 'charset' );

			if ( $charset_result->has_errors() ) {
				return $charset_result;
			}

			$charset = $charset_result->get_results( '' );
		}

		$table_options = 'ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset;

		// Append the collation only when explicitly declared.
		if ( array_key_exists( 'collate', $table_definition ) ) {
			$collate_result = $this->get_optional_identifier_option( $table_definition, 'collate' );

			if ( $collate_result->has_errors() ) {
				return $collate_result;
			}

			$table_options .= ' COLLATE=' . $collate_result->get_results( '' );
		}

		return new Revalt( $table_options );
	}

	/**
	 * Retrieves, normalizes, and validates the required column type.
	 *
	 * Whitespace handling:
	 * - Whitespace runs are collapsed to single spaces.
	 * - Insignificant spaces adjacent to parentheses and commas are removed.
	 * - The returned type is the canonical normalized form, not the raw input.
	 *
	 * Accepted canonical structure:
	 * - A base type word consisting of ASCII letters, for example varchar, bigint, decimal.
	 * - An optional parenthesized length or precision: a single number, or two numbers
	 *   separated by a comma, for example (250) or (14,4).
	 * - Optional trailing attribute keywords, for example unsigned, zerofill.
	 *
	 * A comma is accepted only inside the parenthesized precision pair. Types that
	 * require quoted value lists, such as enum and set, are not supported.
	 *
	 * @param array<string, mixed> $column_definition Declarative column definition.
	 *
	 * @return Revalt<string> Result object containing the normalized SQL type string.
	 */
	private function get_required_column_type( array $column_definition ): Revalt {
		if ( ! array_key_exists( 'type', $column_definition ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Column definition must contain a type.',
				$column_definition
			);
		}

		$type = $column_definition['type'];

		if ( ! is_string( $type ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Column type must be a string.',
				$column_definition
			);
		}

		$type = trim( $type );

		if ( $type === '' ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Column type must be non-empty.',
				$column_definition
			);
		}

		// Normalize whitespace so the structural check below validates a canonical
		// form: collapse whitespace runs, then drop insignificant spaces after an
		// opening parenthesis, around commas, and before a closing parenthesis.
		// The space after a closing parenthesis is preserved because it separates
		// the parenthesized part from attribute keywords.
		$type = preg_replace( '/\s+/', ' ', $type );
		$type = preg_replace( '/\s*([(,])\s*/', '$1', $type );
		$type = preg_replace( '/\s+\)/', ')', $type );

		// Enforce the single-type structure instead of a flat character whitelist.
		// A free-standing comma in the type would otherwise smuggle an extra column
		// declaration into the compiled DDL line; the comma is only legal between
		// the two numbers of a precision pair such as decimal(14,4).
		if ( ! preg_match( '/\A[A-Za-z]+(?:\([0-9]+(?:,[0-9]+)?\))?(?: [A-Za-z]+)*\z/', $type ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Column type does not match the supported single-type structure.',
				$type
			);
		}

		return new Revalt( $type );
	}

	/**
	 * Retrieves and validates a required boolean flag.
	 *
	 * @param array<string, mixed> $definition Declarative schema definition.
	 * @param string $flag_name Required flag name.
	 *
	 * @return Revalt<bool> Result object containing the flag value.
	 */
	private function get_required_boolean_flag( array $definition, string $flag_name ): Revalt {
		if ( ! array_key_exists( $flag_name, $definition ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Required boolean schema flag is missing.',
				$flag_name
			);
		}

		if ( ! is_bool( $definition[ $flag_name ] ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Schema flag must be boolean.',
				array(
					'flag'  => $flag_name,
					'value' => $definition[ $flag_name ],
				)
			);
		}

		return new Revalt( $definition[ $flag_name ] );
	}

	/**
	 * Retrieves and validates a declared option that must be a single identifier.
	 *
	 * Used for charset, collation, and storage engine declarations. The option key
	 * must exist in the definition; callers check presence with array_key_exists()
	 * before calling this method.
	 *
	 * @param array<string, mixed> $definition Declarative schema definition.
	 * @param string $option_key Option field name.
	 *
	 * @return Revalt<string> Result object containing the validated option value.
	 */
	private function get_optional_identifier_option( array $definition, string $option_key ): Revalt {
		$option_value = $definition[ $option_key ];

		if ( ! is_string( $option_value ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Schema option must be a string.',
				array(
					'option' => $option_key,
					'value'  => $option_value,
				)
			);
		}

		$option_value = trim( $option_value );

		if ( $option_value === '' || ! $this->is_safe_identifier_segment( $option_value ) ) {
			return new Revalt(
				null,
				'invalid_identifier',
				'Invalid schema option identifier.',
				array(
					'option' => $option_key,
					'value'  => $option_value,
				)
			);
		}

		return new Revalt( $option_value );
	}

	/**
	 * Compiles a DEFAULT clause.
	 *
	 * Null defaults are intentionally unsupported because null is ambiguous here:
	 * it can mean DEFAULT NULL or no DEFAULT clause. Omit the default key when
	 * no DEFAULT clause is declared.
	 *
	 * String defaults must not contain backslashes or ASCII control characters.
	 * The compiler holds no database connection and cannot perform connection-aware
	 * escaping, so such values are rejected instead of escaped.
	 *
	 * @param mixed $default_value Default value from the schema declaration.
	 *
	 * @return Revalt<string> Result object containing the compiled DEFAULT clause.
	 */
	private function compile_default_sql( $default_value ): Revalt {
		if ( is_int( $default_value ) ) {
			return new Revalt( ' DEFAULT ' . (string) $default_value );
		}

		if ( is_float( $default_value ) ) {
			return new Revalt( ' DEFAULT ' . sprintf( '%F', $default_value ) );
		}

		if ( is_bool( $default_value ) ) {
			return new Revalt( ' DEFAULT ' . ( $default_value ? '1' : '0' ) );
		}

		if ( is_string( $default_value ) ) {
			// Quote doubling neutralizes single quotes, but MySQL also treats the
			// backslash as an escape character unless NO_BACKSLASH_ESCAPES is active.
			// A trailing backslash before the doubled quote would corrupt the literal,
			// so backslashes and control characters are rejected up front.
			if ( preg_match( '/[\x00-\x1F\x7F\\\\]/', $default_value ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'String default must not contain backslashes or control characters.',
					$default_value
				);
			}

			return new Revalt( ' DEFAULT \'' . str_replace( '\'', '\'\'', $default_value ) . '\'' );
		}

		return new Revalt(
			null,
			'invalid_schema_definition',
			'Default value must be int, float, bool, or string. Omit default when no DEFAULT clause is declared.',
			$default_value
		);
	}

	/**
	 * Compiles the index column list.
	 *
	 * @param array<string, mixed> $index_definition Declarative index definition.
	 *
	 * @return Revalt<string> Result object containing quoted index columns.
	 */
	private function compile_index_columns_sql( array $index_definition ): Revalt {
		if ( empty( $index_definition['columns'] ) || ! is_array( $index_definition['columns'] ) ) {
			return new Revalt(
				null,
				'invalid_schema_definition',
				'Index definition must contain a non-empty columns array.',
				$index_definition
			);
		}

		$compiled_columns = array();
		$known_columns = array();

		foreach ( $index_definition['columns'] as $column_name ) {
			if ( ! is_string( $column_name ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Index column name must be a string.',
					$column_name
				);
			}

			$safe_column = $this->quote_simple_identifier( $column_name );

			if ( $safe_column === '' ) {
				return new Revalt(
					null,
					'invalid_identifier',
					'Invalid index column identifier.',
					$column_name
				);
			}

			$column_key = strtolower( trim( $column_name ) );

			if ( isset( $known_columns[ $column_key ] ) ) {
				return new Revalt(
					null,
					'invalid_schema_definition',
					'Index definition contains duplicate columns.',
					$column_name
				);
			}

			$known_columns[ $column_key ] = true;
			$compiled_columns[] = $safe_column;
		}

		return new Revalt( implode( ', ', $compiled_columns ) );
	}

	/**
	 * Quotes a plain or database-qualified identifier reference.
	 *
	 * The full reference is trimmed once at this boundary. Whitespace inside
	 * the reference, including spaces around the dot separator, is rejected
	 * because each segment must already be in canonical form.
	 *
	 * @param string $identifier Identifier reference.
	 *
	 * @return string Backtick-quoted identifier reference, or empty string when invalid.
	 */
	private function quote_identifier_reference( string $identifier ): string {
		$identifier = trim( $identifier );

		if ( $identifier === '' ) {
			return '';
		}

		$segments = explode( '.', $identifier );

		if ( count( $segments ) > 2 ) {
			return '';
		}

		$quoted_segments = array();

		// Validate and quote the same string: segments are not normalized here,
		// so a segment that passes validation is exactly the segment emitted.
		foreach ( $segments as $segment ) {
			if ( ! $this->is_safe_identifier_segment( $segment ) ) {
				return '';
			}

			$quoted_segments[] = '`' . $segment . '`';
		}

		return implode( '.', $quoted_segments );
	}

	/**
	 * Quotes a single identifier segment.
	 *
	 * The identifier is trimmed once at this boundary before validation,
	 * so the validated string and the quoted string are always identical.
	 *
	 * @param string $identifier Identifier segment.
	 *
	 * @return string Backtick-quoted identifier, or empty string when invalid.
	 */
	private function quote_simple_identifier( string $identifier ): string {
		$identifier = trim( $identifier );

		if ( ! $this->is_safe_identifier_segment( $identifier ) ) {
			return '';
		}

		return '`' . $identifier . '`';
	}

	/**
	 * Validates a single MySQL identifier segment in canonical form.
	 *
	 * The segment is validated as-is: no trimming or normalization is performed.
	 * Any whitespace, including leading or trailing, fails validation. Callers
	 * own boundary trimming before invoking this method.
	 *
	 * @param string $segment Identifier segment in canonical form.
	 *
	 * @return bool True when the segment is valid.
	 */
	private function is_safe_identifier_segment( string $segment ): bool {
		if ( $segment === '' ) {
			return false;
		}

		if ( strlen( $segment ) > 64 ) {
			return false;
		}

		return (bool) preg_match( '/\A[A-Za-z0-9_]+\z/', $segment );
	}
}