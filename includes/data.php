<?php

namespace Beerkip\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Find an item by its UID
 *
 * @param string $type
 * @param string $uid
 * @return integer|WP_Post
 */
function find_beer_by_uid( $type = null, $uid, $only_id = true ) {
	$uid = sanitize_text_field( $uid );

	$posts = get_posts( [
		'post_type'  => $type,
		'meta_query' => [
			[
				'key'     => 'meta/uid',
				'value'   => $uid,
				'compare' => '=',
			],
		],
	] );

	if ( ! is_wp_error( $posts ) && ! empty( $posts ) ) {
		return $only_id ? (int) $posts[0]->ID : $posts[0];
	}

	return null;
}

/**
 * Get all data for a specific user: this is sent to the React App to populate
 * the app with data after a successful login.
 *
 * @param int $user_id
 * @param string $type
 * @return array
 */
function get_data_for_user( $user_id = null, $type = null ) {
	$allowed_types = [ 'beers', 'photos' ];

	if ( is_null( $user_id ) || ! in_array( $type, $allowed_types, true ) ) {
		return [];
	}

	/**
	 * Get user beers
	 */
	if ( $type !== 'photos' ) {
		$cpt = substr( $type, 0, -1 );

		$items = get_posts( [
			'post_type'      => $cpt,
			'author'         => (int) $user_id,
			'post_status'    => 'publish',
			'posts_per_page' => 9999,
		] );

		if ( ! is_wp_error( $items ) && ! empty( $items ) ) {
			return $items;
		}
	}

	/**
	 * Get photos (WP medias)
	 */
	else {
		$items = get_posts( [
			'post_type'      => 'attachment',
			'author'         => (int) $user_id,
			'posts_per_page' => 9999,
		] );

		if ( ! is_wp_error( $items ) && ! empty( $items ) ) {
			return $items;
		}
	}

	return [];
}
