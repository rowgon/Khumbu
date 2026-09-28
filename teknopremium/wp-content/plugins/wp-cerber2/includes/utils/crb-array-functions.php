<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licenced under the GNU GPL.

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

/**
 * This file contains array utility functions.
 *
 * Scope
 * - Pure, framework-agnostic helpers for working with PHP arrays.
 *
 * Rules
 * - Pure functions only. No side effects, no hidden state.
 * - No WordPress API, no superglobals, no project-specific constants or classes.
 *
 * Contracts
 * - Always document key handling (preserved vs reindexed) and ordering.
 * - Be explicit about isset vs array_key_exists and null handling.
 * - One responsibility per function.
 *
 * Style
 * - Consistent naming (preferably crb_array_*).
 * - Document non-obvious behavior and edge cases.
 * - Do not silently change behavior when refactoring.
 */

// ------------------------------------------------------------------------------

/**
 * Retrieves a value from an array using the specified key.
 *
 * If $key is an array, performs deep extraction via crb_array_get_deep().
 * Uses isset() semantics: null values are treated as missing and result in $default.
 *
 * @param array              $arr      The array to retrieve the value from.
 * @param string|int|array   $key      The key or an array of keys for deep extraction from a multidimensional array.
 * @param mixed              $default  Optional. The default value to return if the key is not found or validation fails.
 *
 * @return mixed The value from the array if found and valid, or the default value otherwise.
 *
 * @version Sequoia
 */
function crb_array_get( $arr, $key, $default = false ) {
	if ( ! is_array( $arr ) || empty( $arr ) ) {
		return $default;
	}

	if ( is_array( $key ) ) {
		$value = crb_array_get_deep( $arr, $key );
		if ( $value === null ) {
			$value = $default;
		}
	}
	else {
		$value = ( isset( $arr[ $key ] ) ) ? $arr[ $key ] : $default;
	}

	return $value;
}

/**
 * Retrieves a value from an array and validates it against a regex pattern.
 *
 * If $key is an array, performs deep extraction via crb_array_get_deep().
 *
 * The validation pattern is a delimiter-free regex fragment. This function anchors it and applies the ui modifiers.
 * If the extracted value is an array, each scalar element is validated; invalid elements are removed.
 *
 * Notes
 *  - Uses isset() semantics for key existence (null is treated as missing).
 *  - For arrays, this function preserves keys and removes only elements that fail validation.
 *
 * @param array              $arr      The array to retrieve the value from.
 * @param string|int|array   $key      The key or an array of keys for deep extraction from a multidimensional array.
 * @param mixed              $default  Optional. The default value to return if the key is not found or validation fails.
 * @param string             $pattern  Optional. Delimiter-free regex fragment used for validation, e.g. '\w+'.
 *
 * @return mixed The value from the array if found and valid, or the default value otherwise.
 *
 * @since 9.6.14
 *
 * @version Sequoia
 */
function crb_array_get_validated( $arr, $key, $default = false, $pattern = '' ) {

	$value = crb_array_get( $arr, $key, $default );

	if ( ! $pattern ) {
		return $value;
	}

	$validate = '/^' . $pattern . '$/i';

	if ( ! is_array( $value ) ) {
		if ( is_scalar( $value )
		     && preg_match( $validate, (string) $value ) ) {
			return $value;
		}

		return $default;
	}

	array_walk( $value, static function ( &$item ) use ( $validate ) {
		if ( ! is_scalar( $item ) || ! preg_match( $validate, (string) $item ) ) {
			$item = '';
		}
	} );

	return array_filter( $value );
}

/**
 * Retrieve element from multi-dimensional array
 *
 * @param array $arr
 * @param array $keys Keys (dimensions)
 *
 * @return mixed|null Value of the element if it's defined, null otherwise
 */
function crb_array_get_deep( &$arr, $keys ) {
	if ( ! is_array( $arr ) ) {
		return null;
	}

	$key = array_shift( $keys );
	if ( isset( $arr[ $key ] ) ) {
		if ( empty( $keys ) ) {
			return $arr[ $key ];
		}

		return crb_array_get_deep( $arr[ $key ], $keys );
	}

	return null;
}

/**
 * Extract values from a single column of an array while preserving the original keys.
 *
 * For each element in the input array, if the requested column exists and is not null
 * (as per isset), its value is copied to the result under the same key.
 * Elements without the column are skipped.
 *
 * Note: array_column() has different contract.
 *
 * @param array $array Array to extract column
 * @param int|string $column Column key to extract from each sub array.
 *
 * @return array Array of extracted values keyed by the original keys from $array.
 *
 * @since 9.6.6.14
 */
function crb_extract_column_preserve_keys( array $array, $column ): array {

	$ret = array();

	foreach ( $array as $key => $item ) {
		if ( isset( $item[ $column ] ) ) {
			$ret[ $key ] = $item[ $column ];
		}
	}

	return $ret;
}

/**
 * Search for a string key in a given multidimensional array
 *
 * @param array $array
 * @param string $needle
 *
 * @return bool
 */
function crb_multi_search_key( $array, $needle ) {
	foreach ( $array as $key => $value ) {
		if ( (string) $key == (string) $needle ) {
			return true;
		}
		if ( is_array( $value ) ) {
			$ret = crb_multi_search_key( $value, $needle );
			if ( $ret == true ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Search for a row in a given multidimensional array based on a specific column and value
 *
 * @param array $array The multidimensional array to search in
 * @param string $column The column to search for within each row
 * @param mixed $value The value to match against in the specified column
 *
 * @return array The row that matches the specified column and value, or an empty array if no match is found
 */
function crb_array_search_row( array $array, string $column, $value ): array {
	foreach ( $array as $row ) {
		if ( isset( $row[ $column ] )
		     && $row[ $column ] == $value ) {
			return $row;
		}
	}

	return [];
}

/**
 * Compare two arrays by using the array keys. Compares the keys of two arrays and determines if they are different.
 *
 * @param array $arr1 Array to compare
 * @param array $arr2 Array to compare
 *
 * @return bool True if arrays have two different set of keys, false if arrays have equal set of keys. If either argument is not an array returns true.
 */
function crb_array_diff_keys( &$arr1, &$arr2 ): bool {
	if ( ! is_array( $arr1 )
	     || ! is_array( $arr2 ) ) {
		return true;
	}
	if ( count( $arr1 ) != count( $arr2 ) ) {
		return true;
	}
	if ( array_diff_key( $arr1, $arr2 ) ) {
		return true;
	}
	if ( array_diff_key( $arr2, $arr1 ) ) {
		return true;
	}

	return false;
}

/**
 * Compares two elements of two arrays
 *
 * @param $arr1 array
 * @param $arr2 array
 * @param $key1 string|int
 * @param $key2 string|int
 *
 * @return bool True if elements are equal or absent in two arrays
 */
function crb_array_cmp_val( &$arr1, &$arr2, $key1, $key2 = null ) {
	if ( ! $key2 ) {
		$key2 = $key1;
	}

	if ( ( $set = isset( $arr1[ $key1 ] ) ) !== isset( $arr2[ $key2 ] ) ) {
		return false;
	}

	if ( ! $set ) {
		return true;
	}

	return ( $arr1[ $key1 ] === $arr2[ $key2 ] );
}

/**
 * Changes the case of all keys in an array.
 * Supports multi-dimensional arrays.
 *
 * @param array $arr
 * @param int $case CASE_LOWER | CASE_UPPER
 *
 * @return array
 */
function crb_array_change_key_case( $arr, $case = CASE_LOWER ) {
	return array_map( function ( $item ) use ( $case ) {
		if ( is_array( $item ) ) {
			$item = crb_array_change_key_case( $item, $case );
		}

		return $item;
	}, array_change_key_case( $arr, $case ) );
}

/**
 * @param array $arr
 * @param array $fields
 *
 * @return bool
 */
function crb_arrays_similar( &$arr, $fields ) {
	if ( crb_array_diff_keys( $arr, $fields ) ) {
		return false;
	}

	foreach ( $fields as $field => $pattern ) {
		if ( is_callable( $pattern ) ) {
			if ( ! call_user_func( $pattern, $arr[ $field ] ) ) {
				return false;
			}
		}
		else {
			if ( ! preg_match( $pattern, $arr[ $field ] ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Convert multilevel object or array of objects to associative array recursively
 *
 * @param $var object|array
 *
 * @return array result of conversion
 * @since 3.0
 */
function obj_to_arr_deep( $var ) {
	if ( is_object( $var ) ) {
		$var = get_object_vars( $var );
	}
	if ( is_array( $var ) ) {
		return array_map( __FUNCTION__, $var );
	}

	return $var;
}