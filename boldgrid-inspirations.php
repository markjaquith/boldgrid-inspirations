<?php
/*
 * Plugin Name: BoldGrid Inspirations
 * Plugin URI: http://www.boldgrid.com
 * Version: 1.2.5
 * Author: BoldGrid.com <wpb@boldgrid.com>
 * Author URI: http://www.boldgrid.com
 * Description: Be inspired, be custom, be bold!
 * Text Domain: boldgrid-inspirations
 * Domain Path: /languages
 * License: GPL
 */

// Define version.
if ( ! defined( 'BOLDGRID_INSPIRATIONS_VERSION' ) ) {
	define( 'BOLDGRID_INSPIRATIONS_VERSION', '1.2.5' );
}

// Used for this and other BoldGrid plugins to locate the core plugin directory.
if ( ! defined( 'BOLDGRID_BASE_DIR' ) ) {
	define( 'BOLDGRID_BASE_DIR', dirname( __FILE__ ) );
}

// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

// If our class is not loaded, then require it.
if ( ! class_exists( 'Boldgrid_Inspirations' ) ) {
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations.php';
}

// If PHP is compatible, then load the rest.
if ( Boldgrid_Inspirations::is_php_compatible() ) {
	// Load the inspiration class.
	require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-inspiration.php';

	// Instantiate the inspiration class (also loads the parent class Boldgrid_Inspirations).
	$boldgrid_inspirations_inspiration = new Boldgrid_Inspirations_Inspiration();

	// Add action to call pre_add_hooks after init.
	add_action( 'init',
		array(
			$boldgrid_inspirations_inspiration,
			'pre_add_hooks',
		)
	);
} else {
	// If PHP is not compatible, deactivate and die if activating from an admin page, or do nothing.
	add_action( 'admin_init', 'Boldgrid_Inspirations::check_php_wp_version' );
}
