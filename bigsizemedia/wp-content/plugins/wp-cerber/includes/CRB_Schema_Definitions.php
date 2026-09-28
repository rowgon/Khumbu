<?php

declare( strict_types=1 );

/**
 * Database schema declarations.
 *
 * This registry is the canonical source for declared table schemas used by
 * CRB_Schema_Compiler and CRB_Schema_Manager.
 *
 * The class is intentionally stateless: it stores declarations and exposes
 * read-only access helpers, but does not compile SQL or execute DDL.
 *
 * @since 9.8.1
 */
final class CRB_Schema_Definitions {
	public const TABLES = array(
		CERBER_LOG_TABLE  => array(
			'engine'  => 'InnoDB',
			'charset' => 'utf8mb4',
			'collate' => 'utf8mb4_general_ci',

			'columns' => array(
				'ip'          => array(
					'type'     => 'varchar(39)',
					'charset'  => 'ascii',
					'nullable' => false,
				),
				'ip_long'     => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'ipv6_net_64' => array(
					'type'     => 'binary(8)',
					'nullable' => true,
				),
				'user_login'  => array(
					'type'     => 'varchar(60)',
					'nullable' => false,
				),
				'user_id'     => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'stamp'       => array(
					'type'     => 'decimal(14,4)',
					'nullable' => false,
				),
				'activity'    => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'session_id'  => array(
					'type'     => 'char(32)',
					'charset'  => 'ascii',
					'nullable' => false,
					'default'  => '',
				),
				'country'     => array(
					'type'     => 'char(3)',
					'charset'  => 'ascii',
					'nullable' => false,
					'default'  => '',
				),
				'details'     => array(
					'type'     => 'varchar(250)',
					'charset'  => 'ascii',
					'nullable' => false,
					'default'  => '',
				),
				'ac_bot'      => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'ac_status'   => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'ac_by_user'  => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
			),

			'indexes' => array(
				'ip'              => array(
					'columns' => array( 'ip' ),
				),
				'ip_long'         => array(
					'columns' => array( 'ip_long' ),
				),
				'session_index'   => array(
					'columns' => array( 'session_id' ),
				),
				'idx_ipv6_net_64' => array(
					'columns' => array( 'ipv6_net_64' ),
				),
			),
		),
		CERBER_TRAF_TABLE => array(
			'charset' => 'utf8mb4',
			'engine'  => 'InnoDB',
			'collate' => 'utf8mb4_unicode_ci',

			'columns' => array(
				'ip'              => array(
					'type'     => 'varchar(39)',
					'charset'  => 'ascii',
					'nullable' => false,
				),
				'ip_long'         => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'hostname'        => array(
					'type'     => 'varchar(250)',
					'nullable' => false,
					'default'  => '',
				),
				'uri'             => array(
					'type'     => 'text',
					'nullable' => false,
				),
				'request_fields'  => array(
					'type'     => 'mediumtext',
					'nullable' => true,
				),
				'request_details' => array(
					'type'     => 'mediumtext',
					'nullable' => false,
				),
				'session_id'      => array(
					'type'     => 'char(32)',
					'charset'  => 'ascii',
					'nullable' => false,
				),
				'user_id'         => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'stamp'           => array(
					'type'     => 'decimal(14,4)',
					'nullable' => false,
				),
				'processing'      => array(
					'type'     => 'int',
					'nullable' => false,
					'default'  => 0,
				),
				'country'         => array(
					'type'     => 'char(3)',
					'charset'  => 'ascii',
					'nullable' => false,
					'default'  => '',
				),
				'request_method'  => array(
					'type'     => 'char(8)',
					'charset'  => 'ascii',
					'nullable' => false,
				),
				'http_code'       => array(
					'type'     => 'int unsigned',
					'nullable' => false,
				),
				'wp_id'           => array(
					'type'     => 'bigint unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'wp_type'         => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'is_bot'          => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'blog_id'         => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
				'php_errors'      => array(
					'type'     => 'text',
					'nullable' => false,
				),
				'req_status'      => array(
					'type'     => 'int unsigned',
					'nullable' => false,
					'default'  => 0,
				),
			),

			'indexes' => array(
				'stamp' => array(
					'columns' => array( 'stamp' ),
				),
			),
		),
	);

	/**
	 * Retrieves a declared table schema definition.
	 *
	 * @param string $table_name Table name constant value.
	 *
	 * @return Revalt<array<string, mixed>> Result object containing the table definition.
	 */
	public static function get_table( string $table_name ): Revalt {
		if ( ! array_key_exists( $table_name, self::TABLES ) ) {
			return new Revalt(
				null,
				'schema_definition_missing',
				'Table schema definition is not declared.',
				$table_name
			);
		}

		return new Revalt( self::TABLES[ $table_name ] );
	}

	/**
	 * Determines whether a table schema definition exists.
	 *
	 * @param string $table_name Table name constant value.
	 *
	 * @return bool True when the table is declared.
	 */
	public static function has_table( string $table_name ): bool {
		return array_key_exists( $table_name, self::TABLES );
	}

	/**
	 * Retrieves all declared table schema definitions.
	 *
	 * @return array<string, array<string, mixed>> Declared table definitions keyed by table name.
	 */
	public static function get_tables(): array {
		return self::TABLES;
	}
}