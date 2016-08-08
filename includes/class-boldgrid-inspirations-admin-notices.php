<?php

/**
 * BoldGrid Source Code
 *
 * @package BoldGrid_Inspirations_Admin_Notices
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

// Prevent direct calls
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * BoldGrid Inspirations Admin Notices
 */
class Boldgrid_Inspirations_Admin_Notices {
	/**
	 * Add hooks
	 */
	public function add_hooks() {
		if ( is_admin() ) {
			// Allow BoldGrid Admin Notices to be dismissed and remembered.
			add_action( 'wp_ajax_dismiss_boldgrid_admin_notice', 
				array (
					$this,
					'dismiss_boldgrid_admin_notice_callback' 
				) );
			
			// Add the javascript that dismissed admin notices via ajax.
			add_action( 'admin_enqueue_scripts', 
				array (
					$this,
					'admin_enqueue_scripts' 
				) );
		}
	}
	
	/**
	 * Enqueue scripts.
	 */
	public function admin_enqueue_scripts() {
		// Add the javascript that dismissed admin notices via ajax.
		wp_enqueue_script( 'boldgrid-admin-notices', 
			plugins_url( 'assets/js/boldgrid-admin-notices.js', 
				BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' ), array (), BOLDGRID_INSPIRATIONS_VERSION, 
			true );
	}
	
	/**
	 * Allow BoldGrid Admin Notices to be dismissed and remembered.
	 *
	 * @param int $_POST['id']
	 *        	The admin notice id.
	 */
	public function dismiss_boldgrid_admin_notice_callback() {
		global $wpdb;
		
		// Abort if we did not pass in an admin notice id.
		if ( ! isset( $_POST['id'] ) ) {
			echo 'false';
		}
		
		// Sanitize the data key.
		$id = sanitize_key( $_POST['id'] );
		
		// Get our array of dismissed notices.
		$boldgrid_dismissed_admin_notices = get_option( 'boldgrid_dismissed_admin_notices' );
		
		// If we have not dismissed this notice before, add it to the array and save the option.
		if ( false == $boldgrid_dismissed_admin_notices ||
			 ! in_array( $id, $boldgrid_dismissed_admin_notices ) ) {
			$time = time();
			
			$boldgrid_dismissed_admin_notices[$time] = $id;
			update_option( 'boldgrid_dismissed_admin_notices', $boldgrid_dismissed_admin_notices );
		}
		
		echo 'true';
		
		wp_die();
	}
	
	/**
	 * Return wheather or not an admin notice has been dismissed.
	 */
	public function has_been_dismissed( $id ) {
		$boldgrid_dismissed_admin_notices = get_option( 'boldgrid_dismissed_admin_notices' );
		
		if ( false == $boldgrid_dismissed_admin_notices ||
			 ! in_array( $id, $boldgrid_dismissed_admin_notices ) ) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Return boolean for BoldGrid connection issue.
	 */
	public function boldgrid_connection_issue_exists() {
		return ! get_site_transient( 'boldgrid_available' );
	}
}
