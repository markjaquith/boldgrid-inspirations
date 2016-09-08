<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Attribution
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * The BoldGrid Attribution class.
 */
class Boldgrid_Inspirations_Attribution extends Boldgrid_Inspirations {
	/**
	 * Set the default values for our attribuion page.
	 *
	 * @var array
	 */
	public $generic_attribution_page = array(
		'post_content' => 'This is your attribution page.',
		'post_name' => 'attribution',
		'post_title' => 'Attribution',
		'post_type' => 'page',
		'post_status' => 'publish',
		// 2015.03.30 // BradM // Using an incorrect page_template will result in the following
		// error:
		// The page template is invalid.
		// To prevent this, set 'page_template' to 'default' rather than 'page-inside.php'
		'page_template' => 'default',
		'comment_status' => 'closed',
	);

	/**
	 * Hold the boldgrid_attribtuion variable from the wp_options table.
	 *
	 * @var unknown
	 */
	public $wp_options_attribution;

	/**
	 * Hold the boldgrid_asset variable from the wp_options table.
	 *
	 * @var unknown
	 */
	public $wp_options_asset;

	/**
	 * Stores the actual WP Attribution page object.
	 *
	 * @var unknown
	 */
	public $attribution_page;

	/**
	 * Stores an array of attribution page id's.
	 *
	 * @var array
	 */
	public $attribution_page_ids = array ();

	/**
	 * Stores:
	 * ['the number_of_assets_needing_attribution']
	 *
	 * @var array
	 */
	public $attribution_status;

	/**
	 * Stores the $settings variable passed over to our __construct
	 *
	 * @var unknown
	 */
	public $settings;

	/**
	 * Constructor
	 *
	 * @param array $settings
	 */
	public function __construct( $settings = array() ) {
		$this->wp_options_attribution = get_option( 'boldgrid_attribution' );

		/*
		 * The calls below use to make up the entire __construct. Also, this
		 * class assumed the user was logged in and was an admin. We've since
		 * added functionality to this this class meant for front end
		 * visitors. To keep things safe, we moved the original __construct
		 * calls in the below conditional so that front end calls would not
		 * use them.
		 */
		if ( current_user_can( 'manage_options' ) ) {
			$this->settings = $settings;

			$this->attribution_status['number_of_assets_needing_attribution'] = 0;

			$this->set_wp_options_asset();

			$this->remove_action_save_post_build_attribution_page();

			$this->set_license_details();
		}
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		if ( is_admin() ) {
			global $pagenow;

			if ( 'edit.php' === $pagenow || 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
				add_action( 'save_post',
					array(
						$this,
						'build_attribution_page',
					)
				);
			}

			if ( 'edit.php' === $pagenow ) {
				add_filter( 'parse_query',
					array(
						$this,
						'remove_attribution_from_all_pages',
					)
				);

				// Remove 1 from the total page count, because attribution is hidden from view.
				add_filter( 'wp_count_posts',
					array(
						$this,
						'remove_attribution_from_page_count',
					), 10, 2
				);

				// Remove 'staged' pages from the total page count if staging not enabled.
				add_filter( 'wp_count_posts',
					array(
						$this,
						'remove_staging_from_page_count',
					), 10, 2
				);
			}
		} else {
			add_action( 'admin_bar_menu',
				array(
					$this,
					'remove_edit_page_link_from_attribution_page_admin_bar',
				), 999
			);

			add_filter( 'edit_post_link',
				array(
					$this,
					'remove_edit_page_link_from_attribution_page_bottom',
				)
			);

			add_filter( 'wp_page_menu_args',
				array(
					$this,
					'wp_page_menu_args',
				)
			);
		}
	}

	/**
	 * Add frontend hooks.
	 *
	 * @since 1.1.2
	 */
	public function add_wp_hooks() {
		// Add a noindex meta tag to the attribution page.
		add_action( 'wp_head',
			array(
				$this,
				'noindex',
			)
		);
	}

	/**
	 * creates / update / build the attribution page (if needed).
	 *
	 * This is the meat and potatoes of this Attribution class.
	 */
	public function build_attribution_page() {
		// Get our attribution page. If it doesn't exist, this function will also create it.
		$this->get_attribution_page();

		// Loop through each asset and determine if it needs attribution.
		$this->flag_assets_that_need_attribution();

		// Create the html of the attribution page.
		$this->update_html_of_the_attribution_page_object();

		// Save the changes to the database.
		$this->wp_update_post_attribution_page();

		// Toggle "attribution" in menu.
		// $this->wp_update_nav_menu_item_attribution_page();
	}

	/**
	 * Get the boldgrid_asset variable for wp_options and save it to $this->wp_options_asset.
	 */
	public function set_wp_options_asset() {
		( $this->wp_options_asset = get_option( 'boldgrid_asset' ) ) ||
		( $this->wp_options_asset = get_option( 'imhwpb_asset' ) );
	}

	/**
	 * Create an Attribution 'page' if it does not already exist.
	 *
	 * If in the wp_options table, our boldgrid_attribution variable doesn't have a ['page']['id'],
	 * then create a generic attribution page and save the id of that new page.
	 */
	public function create_attribution_page() {
		// if we don't have an attribuion page
		if ( ! isset( $this->wp_options_attribution['page']['id'] ) ) {
			// Avoid infinite loop on the wp_insert_post call below.
			$this->remove_action_save_post_build_attribution_page();

			// Allow the BoldGrid Staging plugin to append "-staging".
			$this->generic_attribution_page = apply_filters( 'boldgrid_deployment_pre_insert_post',
				$this->generic_attribution_page );

			// When a menu has the following setting, 'Automatically add new top-level pages to this
			// menu', the Attribution page is added to the menu. As this is an undesired effect, we
			// will disable the call to _wp_auto_add_pages_to_menu right before creating the
			// Attribution page, and enable the call again immediately afterwards.
			remove_action( 'transition_post_status', '_wp_auto_add_pages_to_menu', 10, 3 );

			$attribuion_page_id = wp_insert_post( $this->generic_attribution_page, true );

			add_action( 'transition_post_status', '_wp_auto_add_pages_to_menu', 10, 3 );

			// Save the options.
			$this->wp_options_attribution['page']['id'] = $attribuion_page_id;

			update_option( 'boldgrid_attribution', $this->wp_options_attribution );
		}
	}

	/**
	 * Is current page the attribution page?
	 *
	 * @return bool
	 */
	public function current_page_is_attribution_page() {
		// If we don't have an attribution page, abort right away and return false.
		if ( ! isset( $this->wp_options_attribution['page']['id'] ) ) {
			return false;
		}

		// Get the id of the current page.
		$page_id = get_the_ID();

		if ( $page_id === $this->wp_options_attribution['page']['id'] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the object of our attribution page and save it is a class property.
	 */
	public function get_attribution_page() {
		// Create the attribtuion page if it doesn't already exist.
		$this->create_attribution_page();

		// Get the attribution page object and save it.
		$this->attribution_page = get_post( $this->wp_options_attribution['page']['id'] );
	}

	/**
	 * Add 'noindex' to attribution page.
	 *
	 * @since 1.1.2
	 * @see Boldgrid_Inspirations_Attribution::current_page_is_attribution_page().
	 * @link https://support.google.com/webmasters/answer/93710?hl=en
	 */
	public function noindex() {
		if ( $this->current_page_is_attribution_page() ) {
			echo "\n<meta name='robots' content='noindex'>\n";
		}
	}

	/**
	 * Filter wp_page_menu_args.
	 *
	 * When displaying a menu using wp_page_menu, remove our Attribution page.
	 *
	 * @since 1.1.4
	 *
	 * @param  array $args An array of page menu arguments.
	 * @return array An array of page menu arguments.
	 */
	public function wp_page_menu_args( $args ) {
		$attribution_id = (
			empty( $this->wp_options_attribution['page']['id'] ) ?
			null : $this->wp_options_attribution['page']['id']
		);

		/*
		 * If we have an Attribution page, add it to the ['exclude'] value.
		 *
		 * The exclude value is a csv of page id's to exclude.
		 *
		 * If it is blank, then set it to our Attribution page's id.
		 * Else there are values already there, then append our id.
		 */
		if ( ! is_null( $attribution_id ) ) {
			if ( empty( $args['exclude'] ) ) {
				$args['exclude'] = $attribution_id;
			} else {
				$args['exclude'] .= ',' . $attribution_id;
			}
		}

		return $args;
	}

	/**
	 * Handles the publish status of the Attribution page.
	 *
	 * If no items need attribution, then do not publish the Attribution page.
	 */
	public function wp_update_post_attribution_page() {
		// Avoid an infinite loop.
		$this->remove_action_save_post_build_attribution_page();

		// Allow the <style> tag on the attribution page.
		add_filter( 'wp_kses_allowed_html',
			array(
				$this,
				'attribution_wp_kses_allowed_html',
			), 1
		);

		// Use get_post() to grab our current Attribution page.
		// This is done to make sure it still exists, we don't want to update a page that doesn't.
		$attribution_page = ( isset( $this->attribution_page->ID ) &&
		is_numeric( $this->attribution_page->ID ) ) ?
		get_post( $this->attribution_page->ID ) : null;

		// Update the Attribution page if it exists, otherwise create it.
		if ( null === $attribution_page ) {
			unset( $this->wp_options_attribution['page']['id'] );
			$this->create_attribution_page();
		} else {
			wp_update_post( $this->attribution_page, true );
		}

		// Save the number of items needing attribution.
		// We'll use this to determine if we need to show the footer link.
		$this->wp_options_attribution['number_of_assets_needing_attribution'] = $this->attribution_status['number_of_assets_needing_attribution'];

		update_option( 'boldgrid_attribution', $this->wp_options_attribution );
	}

	/**
	 * Determine if a passed in asset needs attribution.
	 *
	 * We'll do this by checking to see if the asset is used within a page/post, or,
	 * it is set as a featured image.
	 *
	 * $asset = Array
	 * * (
	 * * [asset_id] => 74982
	 * * [coin_cost] =>
	 * * [name] =>
	 * /home/bradm/public_html/single-site/wp-content/uploads/2015/10/1-7866673682_26aa457ea7_q-110x110.jpg
	 * * [purchase_date] =>
	 * * [download_date] => 2015-10-30 03:42:01
	 * * [attribution] => {"license":"4","author_username":"Josh
	 * Meek","author_url":"https:\/\/www.flickr.com\/photos\/83760546@N05","image_homepage":"https:\/\/www.flickr.com\/photos\/83760546@N05\/7866673682"}
	 * * [attribution_license] =>
	 * * [attachment_id] => 29250
	 * * [width] => 110
	 * * [height] => 110
	 * * [image_provider_id] => 1
	 * * [id_from_provider] => 7866673682
	 * * [orientation] =>
	 * * [image_size] => Large Square
	 * * [transaction_item_id] =>
	 * * [transaction_id] =>
	 * * [asset_key] => 51
	 * * )
	 */
	public function asset_needs_attribution( $asset, $asset_type ) {
		// If there's no attribution_license, we can't attribute the asset; return false.
		if ( empty( $asset['attribution'] ) ) {
			return false;
		}

		// Make the database available.
		global $wpdb;

		// By default, when looking through pages and posts for images, look for those with a status
		// of 'publish'.
		// We don't want to attribute images that are not published.
		// We want to allow other plugins to change this too however, such as the BoldGrid staging
		// plugin.
		$post_status_to_search = "'publish'";

		$post_status_to_search = apply_filters( 'boldgrid_attribution_post_status_to_search',
			$post_status_to_search
		);

		/*
		 * ********************************************************************
		 * Is this a featured image needing attribution?
		 * ********************************************************************
		 */
		if ( ! ( empty( $asset['attachment_id'] ) || empty( $this->attribution_page->ID ) ) ) {
			/* @formatter:off */
			$asset_a_featured_image = $wpdb->get_var(
				$wpdb->prepare(
					"	SELECT `post_id`
					FROM	$wpdb->postmeta,
							$wpdb->posts
					WHERE	$wpdb->postmeta.meta_key = '_thumbnail_id' AND
					$wpdb->postmeta.meta_value = %s AND
					$wpdb->postmeta.post_id != %s AND
					$wpdb->postmeta.post_id = $wpdb->posts.ID AND
					$wpdb->posts.post_status IN ($post_status_to_search) AND
					$wpdb->posts.post_type IN ('page','post')
					", $asset['attachment_id'],
					$this->attribution_page->ID ) );
		/* @formatter:on */
		}

		// If we found results, then the image is being used in a page/post.
		if ( ! empty( $asset_a_featured_image ) ) {
			return true;
		}

		/*
		 * ********************************************************************
		 * Is this an image used within a page / post?
		 *
		 * // First, build a list of possible filenames for the asset.
		 * // Then, loop through each filename and check if it is in a page / post.
		 * ********************************************************************
		 */

		// First, build a list of possible filenames for the asset.

		// We will create an array of names this asset could have.
		// For example, the same asset might have been resized into several different files /
		// thumbnails, and we need to check for all of them.
		$array_file_names_to_query = array();

		// Get _wp_attachment_metadata.
		$wp_attachment_metadata = get_post_meta( $asset['attachment_id'],
			'_wp_attachment_metadata', true );

		if ( ! empty( $wp_attachment_metadata ) ) {
			// Save this metadata for future use.
			$this->wp_options_asset[$asset_type][$asset['asset_key']]['_wp_attachment_metadata'] = $wp_attachment_metadata;

			if ( ! empty( $wp_attachment_metadata['sizes'] ) ) {
				foreach ( $wp_attachment_metadata['sizes'] as $image_size ) {
					$array_file_names_to_query[] = $image_size['file'];
				}
			}
		}

		// Get _wp_attached_file.
		$wp_attached_file = get_post_meta( $asset['attachment_id'], '_wp_attached_file', true );

		if ( ! empty( $wp_attached_file ) ) {
			// save this metadata for future use
			$this->wp_options_asset[$asset_type][$asset['asset_key']]['_wp_attached_file'] = $wp_attached_file;

			$array_file_names_to_query[] = $wp_attached_file;
		}

		// Then, loop through each filename and check if it is in a page / post.
		if ( 'image' == $asset_type &&
		! ( empty( $array_file_names_to_query ) || empty( $this->attribution_page->ID ) ) ) {
			foreach ( $array_file_names_to_query as $file_name_to_query ) {
				// SELECT post_title where post_content like
				// '%2015/02/google-maps-int-1410976385-pi.jpg%'.
				/* @formatter:off */
				$asset_in_page = $wpdb->get_var(
					$wpdb->prepare(
						"	SELECT `post_title`
							FROM $wpdb->posts
							WHERE `post_content` LIKE %s AND
								`id` != %s  AND
								`post_type` IN ('page','post') AND
								`post_status` IN ($post_status_to_search)
						",
						'%' . $wpdb->esc_like( $file_name_to_query ) . '%',
						$this->attribution_page->ID ) );
				/* @formatter:on */

				// If we found results, then the image is being used in a page/post.
				if ( ! empty( $asset_in_page ) ) {
					return true;
				}
			}
		}

		/*
		 * ********************************************************************
		 * Is this a pde / theme mod?
		 * ********************************************************************
		 */
		$theme_mods = get_theme_mods();
		// If we have theme mods.
		if ( false != $theme_mods ) {
			// Loop through each mod.
			foreach ( $theme_mods as $mod_key => $mod_value ) {
				// If there is a value for the mod.
				// If the value is a string.
				// If the value is a url (begins with http).
				if ( isset( $mod_value ) &&  is_string( $mod_value ) &&
				'http' === substr( $mod_value, 0, 4 ) ) {
					// Loop through each possible filename.
					foreach ( $array_file_names_to_query as $file_name_to_query ) {
						// If the mod_value ends in the filename, return true.
						$length_of_filename = strlen( $file_name_to_query );

						if ( $file_name_to_query === substr( $mod_value, - 1 * $length_of_filename ) ) {
							return true;
						}
					}
				}
			}
		}

		// If there is no ID, set it to null.
		if ( empty( $this->attribution_page->ID ) ) {
			$this->attribution_page->ID = null;
		}

		/**
		 * ********************************************************************
		 * Is this an image used within a gallery shortcode?
		 *
		 * Example gallery call:
		 * [gallery targetsize="full" captions="hide" bottomspace="none" gutterwidth="0" link="file"
		 * columns="4" size="full" ids="29215,29216,29217,29218,29219,29220,29221,29222"
		 * data-imhwpb-assets='51737,51738,51739,51740,51741,51742,51743,51744' ]
		 * ********************************************************************
		 */
		// @todo Use a regular expression to find a match, rather than this excessive LIKE
		// statement.
		$gallery_like_statement = '%[gallery%ids%' . $wpdb->esc_like( $asset['attachment_id'] ) .
			 '%data-imhwpb-assets%' . $wpdb->esc_like( $asset['asset_id'] ) . '%]%';

		$asset_in_page = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `post_title`
				FROM $wpdb->posts
				WHERE `post_content` LIKE %s AND
				`id` != %s  AND
				`post_type` IN ('page','post') AND
				`post_status` IN ($post_status_to_search)
				", $gallery_like_statement, $this->attribution_page->ID ) );

		// If we found results, then the image is being used in a page/post.
		if ( ! empty( $asset_in_page ) ) {
			return true;
		}

		// If we weren't able to find the asset being used in a page/post or as a featured image,
		// then return false for asset_needs_attribution.
		return false;
	}

	/**
	 * Loop through each asset and determine if it needs attribution.
	 *
	 * There's a lot of if's and foreach's here.
	 * Essentially we're getting to our assets and sending them to:
	 * $this->asset_needs_attribution( $asset, $asset_type )
	 */
	public function flag_assets_that_need_attribution() {
		// If the boldgrid_asset variable is set.
		if ( ! empty( $this->wp_options_asset ) ) {
			// Loop through each asset type (image / plugin / theme).
			foreach ( $this->wp_options_asset as $asset_type => $assets ) {
				// If we have assets for this type... (for example, if we have image[0] and image[1].
				if ( $assets ) {
					// Loop through each of the assets belonging to this asset type.
					foreach ( $assets as $asset_key => $asset ) {
						$asset['asset_key'] = $asset_key;
						// If this asset needs attribution.
						if ( $this->asset_needs_attribution( $asset, $asset_type ) ) {
							// Flag this asset as needing attribution.
							$this->wp_options_asset[$asset_type][$asset_key]['needs_attribution'] = true;

							// Keep track of how many assets need attribution.
							$this->attribution_status['number_of_assets_needing_attribution'] ++;
						}
					}
				}
			}
		}
	}

	/**
	 * Creates the html that will make up the attribution page.
	 *
	 * This function is called from build_attribution_page().
	 *
	 * This function sets the value of $this->attribution_page->post_content.
	 *
	 * It is then up to the build_attribution_page() method to save the page by calling
	 * wp_update_post_attribution_page().
	 */
	public function update_html_of_the_attribution_page_object() {
		include BOLDGRID_BASE_DIR . '/pages/attribution.php';

		$image_attribution_html = '';

		$image_attribution_array = array();

		$html = $attribution_heading;

		/*
		 * ********************************************************************
		 * Create the html to attribute all images.
		 * ********************************************************************
		 */

		// If the boldgrid_asset variable is set.
		if ( ! empty( $this->wp_options_asset ) ) {
			// Loop through each asset type (image / plugin / theme).
			foreach ( $this->wp_options_asset as $asset_type => $assets ) {
				// If we have assets for this type... (for example, if we have image[0] and image[1].
				if ( $assets ) {
					// Loop through each of the assets belonging to this asset type.
					foreach ( $assets as $asset_key => $asset ) {
						if ( isset( $asset['needs_attribution'] ) &&
						$asset['needs_attribution'] ) {

							// Configure some variables to make printing easier.
							$attribution_details = array(
								'thumbnail' => wp_get_attachment_image_src(
									$asset['attachment_id']
								),
								'details' => json_decode( $asset['attribution'] ),
							);

							$image_attribution_array[] = $this->set_attribution_html_for_one_item(
								$attribution_details
							);
						}
					}
				}
			}
		}

		/*
		 * Set our $image_attribution_array into a wonderfully crafted, super delicious, piping hot
		 * bootstrap grid.
		 */
		if ( count( $image_attribution_array ) > 0 ) {
			$column_css = 'col-xs-12 col-sm-3 col-md-3 col-lg-3 attributed';

			$image_attribution_html .= '
				<style>
					.attributed{height:250px;overflow:hidden;}
					.attributed img{max-height:180px;}
				</style>
				<div class="row">
			';

			foreach ( $image_attribution_array as $array_key => $single_image_html ) {
				$image_attribution_html .= '<div class="' . $column_css . '">' . $single_image_html .
					 '</div>';
			}
			$image_attribution_html .= '</div>';
		}

		// If we have HTML to attribute our images, then update $html to include it.
		if ( ! empty( $image_attribution_html ) ) {
			$html .= $attribution_image_heading . $image_attribution_html;

			$in_addition_this = 'In addition, this ';
		} else {
			$in_addition_this = 'This ';
		}

		// Add attribution to WordPress and Inspirations.
		$html .= '<hr />' . sprintf( $attribution_wordpress_and_inspirations, $in_addition_this );

		// Add attribution for additional plugins.
		$html .= $attribution_additional_plugins;

		if ( ! isset( $this->attribution_page ) ) {
			$this->attribution_page = new stdClass();
		}

		$this->attribution_page->post_content = $html;
	}

	/**
	 * Allow the <style> tag on the attribution page.
	 */
	public function attribution_wp_kses_allowed_html( $tags ) {
		if ( ! isset( $tags['style'] ) ) {
			$tags['style'] = array ();
		}

		return $tags;
	}

	/**
	 * Avoid an infinite loop.
	 */
	public function remove_action_save_post_build_attribution_page() {
		remove_action( 'save_post',
			array(
				$this,
				'build_attribution_page',
			)
		);
	}

	/**
	 * Remove "Attribution" from the "All pages" screen in the dashboard.
	 *
	 * @param unknown $query Query.
	 */
	public function remove_attribution_from_all_pages( $query ) {
		// Get the current page filename.
		global $pagenow;

		// Abort if necessary.
		if ( ! ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) &&
		'page' === $_GET['post_type'] ) ) {
			return;
		}

		/*
		 * ********************************************************************
		 * Create an array of Attribution pages.
		 * This array of page id's will be filtered / removed from the page listing.
		 * ********************************************************************
		 */
		$this->set_attribution_page_ids();

		/*
		 * ********************************************************************
		 * If we have attribution page id's, remove them from the query.
		 * ********************************************************************
		 */
		if ( count( $this->attribution_page_ids ) > 0 ) {
			// Other plugins may set 'post__not_in' as well, and override our setting below.
			// We'll use array_merge and $query->get so to play nice with other plugins.
			$query->set( 'post__not_in',
				array_merge( $this->attribution_page_ids, $query->get( 'post__not_in' ) ) );
		}
	}

	/**
	 * The attribution page cannot be edited.
	 * Remove the 'Edit page' link from the admin bar on the attribution page.
	 *
	 * @see Boldgrid_Inspirations_Attribution::current_page_is_attribution_page().
	 */
	public function remove_edit_page_link_from_attribution_page_admin_bar( $wp_admin_bar ) {
		if ( $this->current_page_is_attribution_page() ) {
			$wp_admin_bar->remove_node( 'edit' );
		}
	}

	/**
	 * The attribution page cannot be edited.
	 * Remove the 'Edit' link from the bottom of the attribution page.
	 *
	 * IF this is the attribution page, return '' so there is no edit link.
	 * ELSE we have to return something in order to show the normal edit link, so just return
	 * 'edit'.
	 *
	 * @see Boldgrid_Inspirations_Attribution::current_page_is_attribution_page().
	 *
	 * @return string|null
	 */
	public function remove_edit_page_link_from_attribution_page_bottom() {
		if ( $this->current_page_is_attribution_page() ) {
			return '';
		} else {
			// Avoid an infinite loop.
			remove_action( 'edit_post_link',
				array(
					$this,
					'remove_edit_page_link_from_attribution_page_bottom',
				)
			);

			edit_post_link( esc_html__( 'Edit', 'boldgrid-inspirations' ) );
		}
	}

	/**
	 * Remove count of staged pages.
	 *
	 *
	 * At the top of "All Pages" is a page count, such as All(9).
	 *
	 * "Staged" pages, those created by the BoldGrid Staging plugin, add to this count.
	 *
	 * If you have "staged" pages but don't have the BoldGrid Staging plugin enabed:
	 * The "All" count will not be accurate to the number of pages listed. Staged pages
	 * will count towards the count, but will not show in the list of pages below.
	 */
	public function remove_staging_from_page_count( $counts, $type ) {
		if ( ! is_plugin_active( 'boldgrid-staging/boldgrid-staging.php' ) ) {
			unset( $counts->staging );
		}

		return $counts;
	}

	/**
	 * Remove 1 from the total page count, because attribution is hidden from view.
	 *
	 * @param object $counts
	 *        	(http://pastebin.com/WW9ZksMR)
	 * @param string $type
	 *
	 * @return int
	 */
	public function remove_attribution_from_page_count( $counts, $type ) {
		$this->set_attribution_page_ids();

		// We're only running this on pages.
		if ( 'page' !== $type ) {
			return $counts;
		}

		// If we don't have any attribution pages, abort.
		if ( ! is_array( $this->attribution_page_ids ) ||
		empty( $this->attribution_page_ids ) ) {
			return $counts;
		}

		foreach ( $this->attribution_page_ids as $key => $id ) {
			$post = get_post( $id );

			if ( empty( $post->post_status ) ) {
				continue;
			}

			$post_status = $post->post_status;

			if ( isset( $counts->$post_status ) ) {
				$counts->$post_status --;

				global $pagenow;

				// Post post_author is a numeric string (for compatibility reasons).
				// The get_current_user_id function returns an int, so no strict comparison here.
				$current_user_is_author = ( $post->post_author == get_current_user_id() );

				$attribution_page_is_trashed = ( 'trash' === $post->post_status );

				// One count type not listed in $counts is 'Mine', the number of pages authored by
				// the current user. To update this number, we'll include the below javascript file.
				// We'll only include this file if the attribution page is not trashed, because
				// trashed pages don't show in the 'Mine' count. Including this js file when we
				// shouldn't will cause the 'Mine' count to be inaccurate.
				if ( 'edit.php' === $pagenow && $current_user_is_author &&
				! $attribution_page_is_trashed ) {
					wp_enqueue_script(
						'boldgrid-all-pages',
						plugins_url(
							'assets/js/all-pages-mine-count.js',
							BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
						),
						array(),
						BOLDGRID_INSPIRATIONS_VERSION
					);
				}
			}
		}

		return $counts;
	}

	/**
	 * Set attribution HTML for one item.
	 *
	 * @param array $attribution_details Attribution details array.
	 *
	 * @return string
	 */
	public function set_attribution_html_for_one_item( $attribution_details ) {
		if ( isset( $attribution_details['details']->license ) &&
		is_numeric( $attribution_details['details']->license ) ) {
			$license_id = $attribution_details['details']->license;
		}

		/*
		 * Create the image to show.
		 */
		if ( isset( $attribution_details['thumbnail'][0] ) ) {
			$image_tag = "<img src='" . $attribution_details['thumbnail'][0] . "' />";
		} else {
			$image_tag = "<img src='http://placehold.it/300x150&text=Image%20not%20available' />";
		}

		/*
		 * Create the link to the image's homepage.
		 */
		if ( isset( $attribution_details['details']->image_homepage ) ) {
			$image_tag = "<a href='" . $attribution_details['details']->image_homepage .
				 "' target='_blank'>" . $image_tag . "</a>";
		}

		/*
		 * Create the link to the author.
		 */
		$author = "<strong>Author</strong>: ";
		if ( isset( $attribution_details['details']->author_username ) ) {
			if ( isset( $attribution_details['details']->author_url ) ) {
				$author .= "<a href='" . $attribution_details['details']->author_url .
					 "' target='_blank'>" . $attribution_details['details']->author_username . "</a>";
			} else {
				$author .= $attribution_details['details']->author_username;
			}
		} else {
			$author .= "<em>Unknown</em>";
		}

		/*
		 * Create the link to the license.
		 */
		$license = "<strong>License</strong>: ";
		if ( isset( $license_id ) && isset( $this->license_details[$license_id] ) ) {
			$license .= "<a href='" . $this->license_details[$license_id]['url'] .
				 "' target='_blank'><img src='" . $this->license_details[$license_id]['icon'] .
				 "' title='" . $this->license_details[$license_id]['name'] . "' /></a>";
		} else {
			$license .= "<em>Unknown license</em>";
		}

		return $image_tag . "<br />" . $author . "<br />" . $license;
	}

	/**
	 * Create an array of attribution page id's.
	 */
	public function set_attribution_page_ids() {
		if ( isset( $this->wp_options_attribution['page']['id'] ) ) {
			$this->attribution_page_ids[] = $this->wp_options_attribution['page']['id'];
		}

		// Allow other plugins to modify this array.
		$this->attribution_page_ids = apply_filters( 'boldgrid_attribution_page_ids',
			$this->attribution_page_ids );

		// Make sure we don't have any duplicate id's in the array.
		$this->attribution_page_ids = array_unique( $this->attribution_page_ids );
	}

	/**
	 * Flickr license id's: https://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html .
	 *
	 * Create Commons icons: https://licensebuttons.net/l/ .
	 */
	public function set_license_details() {
		$this->license_details = array(
			'4' => array(
				'name' => 'Attribution License',
				'icon' => 'https://licensebuttons.net/l/by/2.0/80x15.png',
				'url' => 'http://creativecommons.org/licenses/by/2.0/',
			),
			'5' => array(
				'name' => 'Attribution-ShareAlike License',
				'icon' => 'https://licensebuttons.net/l/by-sa/2.0/80x15.png',
				'url' => 'http://creativecommons.org/licenses/by-sa/2.0/',
			),
			'6' => array(
				'name' => 'Attribution-NoDerivs License',
				'icon' => 'https://licensebuttons.net/l/by-nd/2.0/80x15.png',
				'url' => 'http://creativecommons.org/licenses/by-nd/2.0/',
			)
		);
	}
}
