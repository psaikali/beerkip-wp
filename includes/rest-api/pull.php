<?php

namespace Beerkip\Rest_API\Pull;

use Beerkip\Data;
use Beerkip\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Process the /pull/ route requests to get data for a specific user and type
 *
 * @param \WP_REST_Request $rest_request
 * @return WP_REST_Response
 */
function process( \WP_REST_Request $rest_request ) {
	$data = $rest_request->get_params();

	if ( WP_DEBUG ) {
		sleep( 1 );
	}

	/**
	 * Hooks to validate the request.
	 */
	$is_valid = apply_filters( 'beerkip/rest-api/pull/validate', new \WP_Error(), $rest_request );

	if ( is_wp_error( $is_valid ) ) {
		do_action( 'beerkip/rest-api/pull/error', $rest_request, $is_valid );
		return $is_valid;
	}

	$user_id = (int) $data['user_id'];

	/**
	 * Triggered hooks after to do the pushing logic.
	 * Exceptionally, these hooks also send the \WP_Rest_Response answer because it needs data from it.
	 */
	$response = [ 'success' => true ];
	$response = apply_filters( 'beerkip/rest-api/pull/process', $response, $data, $rest_request );

	if ( is_wp_error( $response ) ) {
		do_action( 'beerkip/rest-api/pull/error', $rest_request, $response );
		return $response;
	}

	return new \WP_REST_Response( $response );
}

/**
 * Validate the request first
 *
 * @param boolean|WP_Error $valid
 * @param WP_REST_Request $rest_request
 * @return true|WP_Error
 */
function validate_push_request( $valid, \WP_REST_Request $rest_request ) {
	$data = $rest_request->get_params();

	/**
	 * No user ID (comes from the magic of JWT auth, see 'determine_current_user' filter)
	 */
	if ( get_current_user_id() === 0 ) {
		return new \WP_Error( 'auth_invalid', __( 'Identité inconnue', 'beerkip' ), [ 'status' => 403 ] );
	}

	return true;
}
add_filter( 'beerkip/rest-api/pull/validate', __NAMESPACE__ . '\\validate_push_request', 10, 2 );

/**
 * Get data for a specific user
 *
 * @param array $data
 * @param WP_Rest_Request $rest_request
 * @return void
 */
function get_user_data( $ajax_response, $data, $rest_request ) {
	$user_id  = (int) $data['user_id'];
	$wp_data  = Data\get_data_for_user( $user_id );
	$app_data = array_map( __NAMESPACE__ . '\\enhance_item_for_app', $wp_data );

	/**
	 * Send the AJAX call response back
	 */
	return [
		'success' => true,
		'data'    => $app_data,
	];
}
add_filter( 'beerkip/rest-api/pull/process', __NAMESPACE__ . '\\get_user_data', 10, 3 );

/**
 * Transform a WP_Post and return the correct structure for the React app
 *
 * @param WP_Post $item
 * @return object
 */
function enhance_item_for_app( $item ) {
	$meta_values = get_post_custom( $item->ID );
	$item_data   = [];

	$meta_data   = extract_data_from_custom_fields( 'meta', $meta_values );
	$rel_data    = extract_data_from_custom_fields( 'rel', $meta_values );
	$fields_data = extract_data_from_custom_fields( 'data', $meta_values );

	// Top-level results.
	$data           = array_merge( $meta_data, $rel_data );
	$data['pushId'] = $item->ID;

	// Fields data.
	$data['fields'] = $fields_data;

	array_walk_recursive( $data, function( &$value, $key ) {
		$cleaned_value = $value;

		$cleaned_value = apply_filters( 'beerkip/pull/single_meta_value', $value, $key );

		// Convert string numbers to proper integers or floats.
		if ( is_numeric( $cleaned_value ) ) {
			$cleaned_value = $cleaned_value + 0;
		}

		$value = $cleaned_value;
	} );

	return apply_filters( 'beerkip/pull/enhanced_item', $data, $item );
}

/**
 * Extract and prepare an array of data (meta/data/rel) from custom fields array
 *
 * @param string $type meta|data|rel
 * @param array $meta_values
 * @return array
 */
function extract_data_from_custom_fields( $type = null, $meta_values = [] ) {
	$starts_with = "{$type}/";
	$results     = [];

	foreach ( $meta_values as $key => $value ) {
		if ( strpos( $key, $starts_with ) !== 0 ) {
			continue;
		}

		$exploded_key = explode( '/', $key );
		$last_part    = end( $exploded_key );

		if ( $last_part === '$length' ) {
			continue;
		}

		$bracketed = 'data[' . str_replace( '/', '][', $key ) . ']=' . $value[0];
		parse_str( $bracketed, $parsed_result );

		$results = Helpers\array_merge_recursive_distinct( $results, $parsed_result['data'] );
	}

	if ( isset( $results[ $type ] ) ) {
		$results = $results[ $type ];
	}

	return $results;
}

/**
 * Force the meta/edited data to 0, so the app does not consider this pulled data as "new",
 * and it won't try to push it without user editing it.
 *
 * @param array $data
 * @param WP_Post $item
 * @return array
 */
function set_meta_edited_to_false( $data, $item ) {
	$data['edited'] = false;

	return $data;
}
add_filter( 'beerkip/pull/enhanced_item', __NAMESPACE__ . '\\set_meta_edited_to_false', 10, 2 );

/**
 * Transform __false & __true meta values to real booleans, so that
 * the React App gets proper values back.
 *
 * @param array $data
 * @param WP_Post $item
 * @return array
 */
function transform_fake_booleans_to_real_booleans( $value, $key ) {
	switch ( $value ) {
		default:
			return $value;

		case '__false':
			return false;

		case '__true':
			return true;
	}
}
add_filter( 'beerkip/pull/single_meta_value', __NAMESPACE__ . '\\transform_fake_booleans_to_real_booleans', 10, 2 );
