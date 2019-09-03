<?php

namespace Beerkip\Rest_API\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Beerkip\Data;

/**
 * Extends the JWT Authentication for WP-API plugin token expiration to 1 year
 *
 * @param integer $expiration
 * @param integer $issued_at
 * @return integer
 */
function extend_jwt_token_expiration( $expiration, $issued_at ) {
	$new_expiration = $issued_at + ( 3 * MONTH_IN_SECONDS );

	return $new_expiration;
}
add_filter( 'jwt_auth_expire', __NAMESPACE__ . '\\extend_jwt_token_expiration', 100, 2 );

/**
 * Add extra data to JWT response to include user ID and user role
 *
 * @param array $data
 * @param WP_User $user
 * @return array
 */
function add_extra_data_to_jwt_token_response( $data, $user ) {
	$data['user_id']             = $user->ID;
	$data['user_login']          = $user->user_login;

	return $data;
}
add_filter( 'jwt_auth_token_before_dispatch', __NAMESPACE__ . '\\add_extra_data_to_jwt_token_response', 100, 2 );
