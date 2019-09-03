<?php

namespace Beerkip\Rest_API\Pull;

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
	return [ 'success' => true ];
}
