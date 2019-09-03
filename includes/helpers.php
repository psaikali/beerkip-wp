<?php

namespace Beerkip\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a big array and return a smaller one containing only the keys => values that we want.
 *
 * @param array $array
 * @param array $keys
 * @return array
 */
function restrict_array_to( $array, $keys ) {
	$result = [];

	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $array ) ) {
			$result[ $key ] = $array[ $key ];
		}
	}

	return $result;
}

/**
 * Flatten a multi-level object with a "key/subkey/subsubkey" => "value" logic
 *
 * @param object|array $object
 * @param string $subkey
 * @param string $separator
 * @return array
 */
function flatten_multilevel_object( $object, $subkey = null, $separator = '_' ) {
	$flat_data = [];

	if ( is_object( $object ) || is_array( $object ) ) {
		// Store array length in a separate key.
		if ( is_array( $object ) ) {
			$new_object    = [];

			// Fix the [ 'something', null, 'something' ] arrays coming from photos' fields.
			array_walk( $object, function( $v, $k ) use ( &$new_object ) {
				if ( ! is_null( $v ) ) {
					if ( is_numeric( $k ) ) {
						$new_object[] = $v;
					} else {
						$new_object[ $k ] = $v;
					}
				}
			} );

			$object = $new_object;
		}

		// Let go one level deep.
		foreach ( $object as $key => $value ) {
			if ( ! is_null( $subkey ) ) {
				$key = "{$subkey}{$separator}{$key}";
			}

			$flat_data = array_merge( $flat_data, flatten_multilevel_object( $value, $key, $separator ) );
		}
	} else {
		$flat_data[ $subkey ] = $object;
	}

	return $flat_data;
}

/**
 * Merge recursive arrays together, but keep the proper index because
 * array_merge_recursive() transforms a numeric key into non-numeric
 *
 * @see https://www.php.net/manual/fr/function.array-merge-recursive.php
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
	$merged = $array1;

	foreach ( $array2 as $key => &$value ) {
		if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
			$merged[ $key ] = array_merge_recursive_distinct( $merged[ $key ], $value );
		} else {
			$merged[ $key ] = $value;
		}
	}

	return $merged;
}
