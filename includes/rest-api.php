<?php

namespace Beerkip\Rest_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require necessary files
 *
 * @return void
 */
function require_files() {
	$new_routes = [
		'auth',
		'push',
		'pull',
	];

	foreach ( $new_routes as $route ) {
		require_once BEERKIP_DIR . "includes/rest-api/{$route}.php";
	}
}
add_action( 'init', __NAMESPACE__ . '\\require_files', 10 );

/**
 * Register our new custom Rest API routes
 */
function register_new_routes() {
	$namespace = 'beerkip/v1/';

	/**
	 * Register /push POST
	 */
	register_rest_route( $namespace, 'push', [
		'methods'  => 'POST',
		'callback' => '\Beerkip\Rest_API\Push\process',
	] );

	/**
	 * Register /pull POST
	 */
	register_rest_route( $namespace, 'pull', [
		'methods'  => 'POST',
		'callback' => '\Beerkip\Rest_API\Pull\process',
	] );
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_new_routes' );
