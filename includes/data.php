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
