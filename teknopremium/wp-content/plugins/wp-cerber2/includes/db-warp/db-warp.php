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

spl_autoload_register( function ( $class_name ) {
	static $classes = [
		'CRB_Database'        => '/CRB_Database.php',
		'CRB_Query_Builder'   => '/CRB_Query_Builder.php',
		'CRB_Schema_Manager'  => '/CRB_Schema_Manager.php',
		'CRB_Schema_Compiler' => '/CRB_Schema_Compiler.php',
	];

	if ( $file = $classes[ $class_name ] ?? '' ) {
		require_once( __DIR__ . $file );
	}
} );

/**
 * Returns a database connection wrapping the global WordPress mysqli handle.
 *
 * Caches the wrapper in a process-wide static variable. The wrapper holds the
 * mysqli handle captured at first call and intentionally does NOT track later
 * changes to $wpdb->dbh. Call with $rewrap = true to discard the cached wrapper
 * and rebuild it against the current $wpdb->dbh.
 *
 * @param bool $rewrap Discards the cached wrapper and re-wraps the current $wpdb->dbh.
 *
 * @return Revalt<CRB_Database> The connection wrapped in a DTO, or a dependency error.
 */
function warp_get_db( bool $rewrap = false ): Revalt {
	static $db_instance = null;

	if ( $rewrap ) {
		$db_instance = null;
	}

	if ( $db_instance !== null ) {
		$result = new Revalt();
		$result->success( $db_instance );

		return $result;
	}

	global $wpdb;

	// Pragmatic guard: ensure the external dependency is fully initialized
	// and holds a valid mysqli object before passing it to wrap().
	if ( ! isset( $wpdb->dbh ) || ! ( $wpdb->dbh instanceof mysqli ) ) {
		return new Revalt(
			null,
			'db_dependency_error',
			'Global $wpdb->dbh is not initialized or is not a valid mysqli instance.'
		);
	}

	$db_instance = CRB_Database::wrap( $wpdb->dbh );

	$result = new Revalt();
	$result->success( $db_instance );

	return $result;
}

/**
 * Executes a database operation against a guaranteed valid connection.
 *
 * @param Closure(CRB_Database): Revalt $operation The query builder logic to execute.
 *
 * @return Revalt The outcome of the operation, or a connection error.
 */
function warp_run_db( Closure $operation ): Revalt {
	$db_result = warp_get_db();
	if ( $db_result->has_errors() ) {
		return $db_result;
	}

	return $operation( $db_result->get_results() );
}