<?php
/*
Plugin Name: Bulk Featured Image
Plugin URI: https://wordpress.org/plugins/bulk-featured-image/
Description: Bulk Featured images update.
Version: 1.2.0
Author: CreedAlly
Author URI: https://creedally.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: bulk-featured-image
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define plugin version.
if ( ! defined( 'BFIE_VERSION' ) ) {
	define( 'BFIE_VERSION', '1.2.0' );
}

// Define plugin dir path.
if ( ! defined( 'BFIE_PATH' ) ) {
	define( 'BFIE_PATH', plugin_dir_path( __FILE__ ) );
}

// Define plugin dir url.
if ( ! defined( 'BFIE_PLUGIN_URL' ) ) {
	define( 'BFIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Define default per page.
if ( ! defined( 'BFIE_PER_PAGE' ) ) {
	define( 'BFIE_PER_PAGE', 20 );
}

// Define plugin menu slug.
if ( ! defined( 'BFIE_MENU_SLUG' ) ) {
	define('BFIE_MENU_SLUG', 'bulk-featured-image');
}

// Define plugin basename.
if ( ! defined( 'BFIE_PLUGIN_BASENAME' ) ) {
	define('BFIE_PLUGIN_BASENAME', plugin_basename( __FILE__ ));
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-bulk-featured-image.php';

register_activation_hook( __FILE__, 'activate_bulk_featured_image' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/activator.php
 */
function activate_bulk_featured_image() {
	require_once ('includes/activator.php');
	BFIE_Activator::activate();
}

register_deactivation_hook( __FILE__, 'deactivate_bulk_featured_image' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/deactivator.php
 */
function deactivate_bulk_featured_image() {
	require_once ('includes/deactivator.php');
	BFIE_Deactivator::deactivate();
}

/**
 * Begins execution of the plugin.
 * 
 * @since    1.0.0
 */
function init_bulk_featured_image() {
	BFIE::get_instance();
}

add_action( 'plugins_loaded', 'init_bulk_featured_image', apply_filters( 'bulk_featured_image_action_priority', 10 ) );
