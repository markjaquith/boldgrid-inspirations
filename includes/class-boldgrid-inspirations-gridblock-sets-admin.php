<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_GridBlock_Sets_Admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations GridBlock Sets Admin.
 *
 * This class helps manage the Dashboard page for "Add GridBlock Set".
 *
 * @since 1.0.10
 */
class Boldgrid_Inspirations_GridBlock_Sets_Admin {
	/**
	 * Constructor.
	 *
	 * @since 1.0.10
	 *
	 * @param array $configs
	 */
	public function __construct( $configs ) {
		$this->configs = $configs;

		include_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-gridblock-sets.php';
		$this->gridblock_sets = new Boldgrid_Inspirations_GridBlock_Sets( $this->configs );

		/**
		 * Allow plugins to take action when this constructor is finished.
		 *
		 * For example, the staging plugin needs to set the proper staging / production cookie. We
		 * can set that here.
		 *
		 * @since 1.0.10
		 */
		do_action( 'boldgrid_inspirations_gridblock_sets_admin_post_construct' );
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.0.10
	 */
	public function add_hooks() {
		// If we're not in the admin section, return.
		if ( ! is_admin() || is_network_admin() ) {
			return;
		}

		// Add our scripts.
		add_action( 'admin_enqueue_scripts', array (
			$this,
			'admin_enqueue_scripts'
		) );

		// Register our menu.
		add_action( 'admin_menu', array (
			$this,
			'register_menu_gridblock_set'
		), 1000 );

		add_action( 'wp_ajax_get_gridblock_sets',
			array (
				$this,
				'get_gridblock_sets_callback'
			) );

		add_action( 'admin_footer', array (
			$this,
			'admin_footer'
		), 100 );

		add_action( 'wp_ajax_gridblock_set_create_preview',
			array (
				$this,
				'gridblock_set_create_preview_callback'
			) );

		add_action( 'wp_ajax_gridblock_set_create_page',
			array (
				$this,
				'gridblock_set_create_page_callback'
			) );
	}

	/**
	 * Actions to take within the Admin Footer.
	 *
	 * @since 1.0.10
	 */
	public function admin_footer() {
		$gridblock_sets['kitchen_sink'] = get_transient( 'boldgrid_inspirations_kitchen_sink' );

		Boldgrid_Inspirations_Utility::inline_js_oneliner(
			'IMHWPB.gridblock_sets = ' . json_encode( $gridblock_sets ) . ';' );

		Boldgrid_Inspirations_Utility::inline_js_oneliner(
			'IMHWPB.homepage_url = "' . get_home_url() . '";' );

		Boldgrid_Inspirations_Utility::inline_js_oneliner(
			'IMHWPB.gridblock_sets_admin = "' . $this->add_gridblock_set_url . '";' );

		include_once BOLDGRID_BASE_DIR . '/pages/templates/gridblock-sets-admin.php';
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.0.10
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( $this->hook == $hook ) {
			wp_enqueue_script( 'boldgrid-inspirations-add-gridblock-set',
				plugins_url( 'assets/js/boldgrid-inspirations-add-gridblock-set.js',
					BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' ), array (),
				BOLDGRID_INSPIRATIONS_VERSION, true );

			// Add our css.
			wp_register_style( 'boldgrid-inspirations-add-gridblock-set',
				plugins_url( '/assets/css/boldgrid-inspirations-add-gridblock-set.css',
					BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' ), array (),
				BOLDGRID_INSPIRATIONS_VERSION );
			wp_enqueue_style( 'boldgrid-inspirations-add-gridblock-set' );
		}
	}

	/**
	 * Create the markup for the "Add GridBlock Set" page.
	 *
	 * @since 1.0.10
	 */
	public function admin_page_add_gridblock_set() {
		include_once BOLDGRID_BASE_DIR . '/pages/boldgrid-inspirations-add-gridblock-set.php';
	}

	/**
	 * Ajax callback for get_gridblock_sets.
	 *
	 * @since 1.0.10
	 */
	public function get_gridblock_sets_callback() {
		global $wpdb;

		// If you cannot edit a post, you cannot get GridBlocks.
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_die();
		}

		$this->gridblock_sets = $this->gridblock_sets->get();

		echo json_encode( $this->gridblock_sets );

		wp_die();
	}

	/**
	 * Ajax callback for gridblock_set_create_page.
	 *
	 * @since 1.0.10
	 *
	 * @return int The id of the new page we created.
	 */
	public function gridblock_set_create_page_callback() {
		global $wpdb;

		// If you cannot edit pages, you cannot create a page from a GridBlock Set.
		if( ! current_user_can( 'edit_pages' ) ) {
			wp_die();
		}

		include_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-asset-manager.php';
		$assetManager = new Boldgrid_Inspirations_Asset_Manager();

		$key = $_POST['key'];
		$category = $_POST['category'];

		// If we don't have a valid key, abort.
		if ( ! is_numeric( $key ) ) {
			echo '0';
			wp_die();
		}

		// Get our GridBlock Set.
		$gridbock_sets = $this->gridblock_sets->get();
		$gridblock_set = $gridbock_sets[$category]['data']['pages'][$key];

		// Save our new page.
		$new_page = array (
			'post_title' => $gridblock_set['preview_data']['post_title'],
			'post_status' => 'draft',
			'post_type' => $gridblock_set['preview_data']['post_type'],
			'post_content' => $gridblock_set['preview_data']['post_content']
		);
		$page_id = wp_insert_post( $new_page );

		// Download all of the images within the page.
		$this->gridblock_set_create_page_download_images( $page_id );

		// Set the page template.
		update_post_meta( $page_id, '_wp_page_template',
			$gridblock_set['preview_data']['wp_page_template'] );

		// Add a flag for this template signifying that the user has not edited it yet.
		// This flag is needed for "In menu" to recognize this is a new page.
		update_post_meta( $page_id, 'new_gridblock_set', true );

		// Create a variable to pass the boldgrid_page_data id in do_action().
		$boldgrid_page_id = isset( $gridblock_set['boldgrid_page_data']['id'] ) ? $gridblock_set['boldgrid_page_data']['id'] : null;

		/**
		 * Action definition for other developers to hook into.
		 *
		 * The action is called to trigger any add_action() calls that may exist.
		 *
		 * @since 1.0.10
		 *
		 * @param string $tag
		 *        	The name of the action to be executed.
		 * @param int $page_id
		 *        	The new page id.
		 */
		do_action( 'boldgrid_inspirations_post_gridblock_set_create_page_callback',
			array (
				'page_id' => $page_id,
				'boldgrid_page_id' => $boldgrid_page_id
			) );

		// Echo the new page id.
		echo $page_id;

		// WordPress callbacks die nicely.
		wp_die();
	}

	/**
	 * For a given page id, download all images within the page.
	 *
	 * @since 1.0.10
	 *
	 * @param int $page_id
	 * @return int|bool The ID of the post if the post is successfully updated in the database.
	 *         Otherwise returns 0. @link
	 *         https://codex.wordpress.org/Function_Reference/wp_update_post
	 */
	public function gridblock_set_create_page_download_images( $page_id ) {
		$page = get_post( $page_id );

		// If we have an invalid page, abort.
		if ( null == $page ) {
			return false;
		}

		include_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-asset-manager.php';
		$assetManager = new Boldgrid_Inspirations_Asset_Manager();

		$dom = new DOMDocument();
		@$dom->loadHTML( Boldgrid_Inspirations_Utility::utf8_to_html( $page->post_content ) );

		// Get all of the images in the selection.
		$images = $dom->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			$id_from_provider = $image->getAttribute( 'data-id-from-provider' );
			$image_provider_id = $image->getAttribute( 'data-image-provider-id' );
			$width = $image->getAttribute( 'width' );
			$asset_id = $image->getAttribute( 'data-imhwpb-asset-id' );

			// If we have the necessary details to download an image, then download it.
			// The first If statement is for downloading 'built_photo_search'.
			// The second If statement (elseif) is for downloading asset ids.
			if ( $id_from_provider && $image_provider_id && $width ) {
				$download_data = array (
					'type' => 'built_photo_search',
					'params' => array (
						'key' => $this->configs['api_key'],
						'id_from_provider' => $id_from_provider,
						'image_provider_id' => $image_provider_id,
						'width' => $width
					)
				);

				$downloaded_image = $assetManager->download_and_attach_asset( $page_id, null,
					$download_data );

				$image->setAttribute( 'src', $downloaded_image );
			} elseif ( isset( $asset_id ) && is_numeric( $asset_id ) ) {
				$downloaded_image = $assetManager->download_and_attach_asset( $page_id, null, $asset_id );

				$image->setAttribute( 'src', $downloaded_image );
			}

			// If something went wrong and this image still has a 'src' pointing to the preview
			// server, change the src.
			if ( strpos( $image->getAttribute( 'src' ), 'https://wp-preview' ) !== false ) {
				$image->setAttribute( 'src',
					"https://placeholdit.imgix.net/~text?txtsize=33&txt=$width%C3%97150&w=$width&h=150" );
			}

			// Certain data attributes only need to be published on the preview server. Remove them
			// now as they're no longer needed.
			$image->removeAttribute( 'data-id-from-provider' );
			$image->removeAttribute( 'data-image-provider-id' );
		}

		$page->post_content = $dom->saveHTML();

		return wp_update_post( $page );
	}

	/**
	 * Ajax callback for gridblock_set_create_preview.
	 *
	 * @since 1.0.10
	 */
	public function gridblock_set_create_preview_callback() {
		global $wpdb;

		// If you cannot edit pages, you cannot create a GridBlock Set preview.
		if( ! current_user_can( 'edit_pages' ) ) {
			wp_die();
		}

		$key = $_POST['key'];
		$category = $_POST['category'];

		// If we don't have a valid key, abort.
		if ( ! is_numeric( $key ) ) {
			echo '0';
			wp_die();
		}

		// Get our GridBlock Set.
		$gridbock_sets = $this->gridblock_sets->get();
		$gridblock_set = $gridbock_sets[$category]['data']['pages'][$key];

		// Create a new instance of our 'Preview Page' helper.
		include_once BOLDGRID_BASE_DIR .
			 '/includes/class-boldgrid-inspirations-gridblock-sets-preview-page.php';
		$preview_page_helper = new Boldgrid_Inspirations_GridBlock_Sets_Preview_Page();

		// Get and update our preview page.
		$preview_page = $preview_page_helper->get();
		$preview_page->post_content = $gridblock_set['preview_data']['post_content'];
		$preview_page->post_title = $gridblock_set['preview_data']['post_title'];

		// Update the content of the preview page.
		wp_update_post( $preview_page );

		// Update the wp_page_template of the preview page.
		$wp_page_template = ( empty( $gridblock_set['preview_data']['wp_page_template'] ) ? 'default' : $gridblock_set['preview_data']['wp_page_template'] );
		update_post_meta( $preview_page->ID, '_wp_page_template', $wp_page_template );

		echo $preview_page->guid;

		wp_die();
	}

	/**
	 * Create "Add GridBlock Set" menu items.
	 *
	 * @since 1.0.10
	 */
	public function register_menu_gridblock_set() {
		if ( 0 == $this->configs['settings']['boldgrid_menu_option'] ) {
			add_submenu_page( 'edit.php?post_type=page', 'New From GridBlocks',
				'New From GridBlocks', 'administrator', 'boldgrid-add-gridblock-sets',
				array (
					$this,
					'admin_page_add_gridblock_set'
				) );

			$this->add_gridblock_set_url = 'edit.php?post_type=page&page=boldgrid-add-gridblock-sets';
			$this->hook = 'pages_page_boldgrid-add-gridblock-sets';
		} else {
			// Remove existing "Add New".
			remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );

			// Add New.
			add_submenu_page( 'edit.php?post_type=page', 'Add New', 'Add New', 'administrator',
				'boldgrid-add-gridblock-sets',
				array (
					$this,
					'admin_page_add_gridblock_set'
				) );

			$this->add_gridblock_set_url = 'edit.php?post_type=page&page=boldgrid-add-gridblock-sets';
			$this->hook = 'pages_page_boldgrid-add-gridblock-sets';

			// Add Blank.
			add_submenu_page( 'edit.php?post_type=page', 'Add Blank', 'Add Blank', 'administrator',
				'post-new.php?post_type=page' );
		}
	}
}