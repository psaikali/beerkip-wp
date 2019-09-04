<?php

namespace Beerkip\Rest_API\Push;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Beerkip\Push_Helpers;

/**
 * Process the /push/ route requests to SYNC data from the app to the WP database
 *
 * @param \WP_REST_Request $rest_request
 * @return WP_REST_Response
 */
function process( \WP_REST_Request $rest_request ) {
	$data = $rest_request->get_params();

	// Slow down the process locally, to check for some UI changes in the app.
	if ( WP_DEBUG && class_exists( 'MSK' ) ) {
		sleep( 1 );
	}

	/**
	 * Hooks to validate the request.
	 */
	$is_valid = apply_filters( 'beerkip/rest-api/push/validate', new \WP_Error(), $rest_request );

	if ( is_wp_error( $is_valid ) ) {
		do_action( 'beerkip/rest-api/push/error', $rest_request, $is_valid );
		return $is_valid;
	}

	/**
	 * Triggered hooks after to do the pushing logic.
	 * Exceptionally, these hooks also send the \WP_Rest_Response answer because it needs data from it.
	 */
	$response = [ 'success' => true ];
	$response = apply_filters( 'beerkip/rest-api/push/process', $response, $data, $rest_request );

	if ( is_wp_error( $response ) ) {
		do_action( 'beerkip/rest-api/push/error', $rest_request, $response );
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
	 * No user ID (comes from the magic of JWT auth, see 'determine_current_user' filter)?
	 */
	if ( get_current_user_id() === 0 ) {
		return new \WP_Error( 'auth_invalid', __( 'We don\'t know you.', 'beerkip' ), [ 'status' => 403 ] );
	}

	return true;
}
add_filter( 'beerkip/rest-api/push/validate', __NAMESPACE__ . '\\validate_push_request', 10, 2 );

/**
 * Create or edit a post for Sites / Silos / Fans / Configs / Measures
 *
 * @param array $data
 * @param WP_Rest_Request $rest_request
 * @return void
 */
function create_or_edit_post( $ajax_response, $data, $rest_request ) {
	$objects_uid_and_id = [];

	foreach ( $data['data'] as $object ) {
		$object_id = Push_Helpers\process_push_data_item( $object );

		/**
		 * If we get an ID back, the object was edited or created.
		 * We keep track of the uid => id relationship to inform the app that the object was saved in WP.
		 */
		if ( is_numeric( $object_id ) ) {
			$objects_uid_and_id[] = (object) [
				'uid' => $object['uid'],
				'id'  => (int) $object_id,
			];
		}
	}

	/**
	 * Send the AJAX call response back
	 */
	return [
		'success'     => true,
		'synced_data' => $objects_uid_and_id,
	];
}
add_filter( 'beerkip/rest-api/push/process', __NAMESPACE__ . '\\create_or_edit_post', 10, 3 );
