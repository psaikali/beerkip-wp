<?php

namespace Beerkip\CPT\Beer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Beer post type
 *
 * @return void
 */
function register_beer_post_type() {
	register_post_type(
		'beer',
		[
			'description' => __( 'Beers', 'beerkip' ),
			'labels'      => [
				'name'          => esc_html__( 'Beers', 'beerkip' ),
				'singular_name' => esc_html__( 'Beer', 'beerkip' ),
				'menu_name'     => esc_html__( 'Beers', 'beerkip' ),
			],
			'supports' => [
				'title',
				'editor',
				'author',
			],
			'public'        => false,
			'show_ui'       => true,
			'menu_position' => 2,
			'menu_icon'     => 'dashicons-arrow-right',
			'has_archive'   => false,
		]
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_beer_post_type', 14 );
