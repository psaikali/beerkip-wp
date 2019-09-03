<?php

namespace Beerkip\Push_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Beerkip\Data;
use Beerkip\Helpers;

/**
 * Process a piece of data from AJAX Sync request, to create or edit an item
 *
 * @param array $object
 * @return null|integer
 */
function process_push_data_item( $object ) {
	/**
	 * Check if we have a UID
	 */
	if ( ! isset( $object['uid'] ) || empty( $object['uid'] ) ) {
		return false;
	}

	// Check if we already have this UID to decide if it's an edition or creation.
	$existing_object_id = Data\find_beer_by_uid( 'beer', $object['uid'] );

	if (  isset( $object['deleted'] ) && $object['deleted'] && (int) $object['deleted'] === 1 ) {
		return delete_item( $object, $existing_object_id );
	} else {
		return create_item( $object, $existing_object_id );
	}
}

/**
 * Create or edit an item
 *
 * @param array $data
 * @param null|integer $id_to_edit
 * @return false|integer Post ID created or updated.
 */
function create_item( $data = [], $id_to_edit = null ) {
	if ( empty( $data ) ) {
		return false;
	}

	$creation_date = isset( $data['createdAt'] ) ? round( $data['createdAt'] / 1000 ) : null;

	$item_data = [
		'post_type'    => 'beer',
		'post_title'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null,
		'post_content' => isset( $data['comment'] ) ? sanitize_textarea_field( $data['comment'] ) : null,
		'post_status'  => 'publish',
		'post_author'  => (int) $data['author'],
		'post_date'    => date( 'Y-m-d H:i:s', $creation_date ),
	];

	if ( ! is_null( $id_to_edit ) ) {
		$item_data['ID'] = (int) $id_to_edit;
	}

	$item_id = wp_insert_post( $item_data );

	do_action( 'beerkip/item_created', $data, $item_id, $id_to_edit, $item_data );

	return $item_id;
}

/**
 * Delete a specific item
 *
 * @param array $data
 * @param null|integer $id_to_delete
 * @return false|integer Post ID deleted.
 */
function delete_item( $data = [], $id_to_delete = null ) {
	if ( is_null( $id_to_delete ) ) {
		return false;
	}

	$item = wp_delete_post( $id_to_delete );

	if ( ! is_a( $item, 'WP_Post' ) ) {
		return false;
	}

	do_action( 'beerkip/item_deleted', $data, $id_to_delete );

	return $id_to_delete;
}

/**
 * Save item meta data and fields in wp_postmeta
 *
 * @param array $data
 * @param int $item_id
 * @return void
 */
function save_item_meta_and_fields_data( $data, $item_id ) {
	$meta_values = Helpers\restrict_array_to( $data, [ 'uid', 'createdAt', 'editedAt' ] );
	$data_values = Helpers\restrict_array_to( $data, [ 'name', 'brewery', 'style', 'abv', 'aromas', 'rating' ] );

	$meta_values = Helpers\flatten_multilevel_object( $meta_values, 'meta', '/' );
	$data_values = Helpers\flatten_multilevel_object( $data_values, 'data', '/' );

	$values = array_merge( $meta_values, $data_values );

	// Delete keys without values but keep boolean values.
	$values = array_filter( $values, function( $value ) {
		// We want to keep falsy values.
		if ( $value === true || $value === false ) {
			return true;
		}

		if ( is_null( $value ) ) {
			return false;
		}

		return true;
	} );

	save_item_meta_values( $values, $item_id );
}
add_action( 'beerkip/item_created', __NAMESPACE__ . '\\save_item_meta_and_fields_data', 20, 2 );

/**
 * Save item meta values. Value is filterable, and action is triggered after save.
 *
 * @param array $values
 * @param integer $item_id
 * @return void
 */
function save_item_meta_values( $values, $item_id ) {
	foreach ( $values as $meta_key => $meta_value ) {
		$filtered_meta_value = apply_filters( 'beerkip/item_meta_value', $meta_value, $meta_key );
		$filtered_meta_value = apply_filters( "beerkip/item_meta_value/{$meta_key}", $filtered_meta_value );

		update_post_meta( $item_id, $meta_key, sanitize_text_field( $filtered_meta_value ) );

		do_action( "beerkip/item_meta_after_save/{$meta_key}", $item_id, $filtered_meta_value, $meta_value );
	}
}

/**
 * Convert boolean values to 0 or 1
 *
 * @param mixed $value
 * @return mixed
 */
function convert_boolean_to_0_1( $value, $key ) {
	if ( gettype( $value ) === 'boolean' && $value === false ) {
		return '__false';
	} elseif ( gettype( $value ) === 'boolean' && $value === true ) {
		return '__true';
	}

	return $value;
}
add_filter( 'beerkip/item_meta_value', __NAMESPACE__ . '\\convert_boolean_to_0_1', 10, 2 );
