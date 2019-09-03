<?php
/**
 * Plugin Name: Beerkip
 * Description: Main plugin in charge of communicating with the Beerkip React Native app
 * Author: Pierre Saïkali
 * Author URI: https://saika.li
 * Text Domain: beerkip
 * Version: 1.0.0
 */

namespace Beerkip;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 */
define( 'BEERKIP_VERSION', '1.0.0' );
define( 'BEERKIP_URL', plugin_dir_url( __FILE__ ) );
define( 'BEERKIP_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEERKIP_PLUGIN_DIRNAME', basename( rtrim( dirname( __FILE__ ), '/' ) ) );
define( 'BEERKIP_BASENAME', plugin_basename( __FILE__ ) );
define( 'BEERKIP_TEMPLATES_PATH', BEERKIP_DIR . '/templates/' );

/**
 * Load translation
 */
//load_plugin_textdomain( 'beerkip', false, BEERKIP_PLUGIN_DIRNAME . '/languages/' );

/**
 * Check for plugin requirements and dependencies
 *
 * @return void
 */
function check_plugin_requirements() {
	if ( ! is_plugin_active( 'jwt-authentication-for-wp-rest-api/jwt-auth.php' ) && current_user_can( 'activate_plugins' ) ) {
		wp_die( 'The Beerkip plugin requires the "JWT Authentication for WP-API" plugin to be installed and activated.' );
	}
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\check_plugin_requirements' );

/**
 * Register required files.
 */
function fire() {
	$files = [
		'helpers',
		'data',
		'rest-api',
		'push-helpers',
		'schema/cpt-beer',
		'rest-api/auth',
		'rest-api/push',
		'rest-api/pull',
	];

	foreach ( $files as $file ) {
		$full_path_file = BEERKIP_DIR . "includes/{$file}.php";

		if ( ! file_exists( $full_path_file ) ) {
			//\MSK::debug( sprintf( 'includes/%1$s.php does not exist!', $file ) );
			continue;
		}

		require_once $full_path_file;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\fire' );
