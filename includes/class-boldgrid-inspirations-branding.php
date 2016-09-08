<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Branding
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Branding class.
 */
class Boldgrid_Inspirations_Branding {
	/**
	 * Class property for reseller information array with elements: {
	 * 	reseller_identifier
	 * 	reseller_title
	 * 	reseller_logo_url
	 * 	reseller_website_url
	 * 	reseller_support_url
	 * 	reseller_amp_url
	 * 	reseller_email
	 * 	reseller_phone
	 * 	reseller_css_url
	 * }
	 *
	 * @var array
	 * @access private
	 */
	private $reseller_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Get the WP option containing reseller data.
		$reseller_data = get_option( 'boldgrid_reseller' );

		// Set the class property $reseller_data.
		$this->set_reseller_data( $reseller_data );
	}

	/**
	 * Set the class property $reseller_data.
	 *
	 * @param array $reseller_data Reseller data array.
	 */
	private function set_reseller_data( $reseller_data = array() ) {
		$this->reseller_data = $reseller_data;
	}

	/**
	 * Get the class property $reseller_data.
	 */
	private function get_reseller_data() {
		return $this->reseller_data;
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		// Add action to enqueue BoldGrid login CSS script.
		add_action( 'login_enqueue_scripts',
			array(
				$this,
				'boldgrid_login_css',
			)
		);

		// Add filter for login logo URL.
		add_filter( 'login_headerurl',
			array(
				$this,
				'boldgrid_login_logo_url',
			)
		);

		// Add filter for BoldGrid login logo title.
		add_filter( 'login_headertitle',
			array(
				$this,
				'boldgrid_login_logo_title',
			)
		);

		// Add action for BoldGrid admin icon.
		add_action( 'init',
			array(
				$this,
				'boldgrid_admin_icon',
			)
		);

		// Add BoldGrid admin bar menu.
		add_action( 'admin_bar_menu',
			array(
				$this,
				'boldgrid_admin_node',
			), 5
		);

		// Add actions and filters for reseller admin bar menu and footer.
		$reseller_data = $this->get_reseller_data();

		if ( ! empty( $reseller_data['reseller_identifier'] ) ) {
			// Reseller.
			add_action( 'admin_bar_menu',
				array(
					$this,
					'reseller_admin_node',
				), 15
			);

			add_filter( 'admin_footer_text',
				array(
					$this,
					'boldgrid_footer_admin_reseller',
				)
			);
		} else {
			// No reseller.
			add_filter( 'admin_footer_text',
				array(
					$this,
					'boldgrid_footer_admin',
				)
			);
		}

		// Add action for login footer.
		add_action( 'login_footer',
			array(
				$this,
				'boldgrid_login_footer',
			)
		);
	}

	/**
	 * Change the default WordPress login logo to BoldGrid Login Logo.
	 *
	 * @see login_enqueue_scripts don't echo out to login_head.
	 * @see login_headerurl.
	 * @see login_headertitle.
	 */
	public function boldgrid_login_css() {
		// Get the reseller vars.
		$reseller_data = $this->get_reseller_data();

		$reseller_css_url = esc_url(
			! empty( $reseller_data['reseller_css_url'] ) ?
			$reseller_data['reseller_css_url'] : plugins_url() . '/' .
			basename( BOLDGRID_BASE_DIR ) . '/assets/css/boldgrid-login.css'
		);

		wp_register_style(
			'custom-login',
			$reseller_css_url,
			array(),
			BOLDGRID_INSPIRATIONS_VERSION
		);

		wp_enqueue_style( 'custom-login' );

		/* @formatter:off */
		echo "
			<style type='text/css'>
				.login h1 a {
					background-image: url(" . esc_url( plugins_url() . '/' .
			basename( BOLDGRID_BASE_DIR ) .
		'/assets/images/boldgrid-login-logo.png') . ') !important;
                }
            </style>
        ';
		/* @formatter:on */
	}

	/**
	 * Add the login logo url instead of default wordpress.org logo url.
	 */
	public function boldgrid_login_logo_url() {
		return esc_url( 'http://www.boldgrid.com/' );
	}

	/**
	 * Change the hover title from WordPress.org to BoldGrid.com.
	 */
	public function boldgrid_login_logo_title() {
		return esc_html( 'BoldGrid.com' );
	}

	/**
	 * Add custom links and logos to footer.
	 *
	 * BoldGrid with no partner login page footer.
	 */
	public function boldgrid_login_footer() {
		// Get the reseller vars.
		$reseller_data = $this->get_reseller_data();

		$reseller_logo_url = (
			! empty( $reseller_data['reseller_logo_url'] ) ?
			$reseller_data['reseller_logo_url'] :
			plugins_url() . '/' . basename( BOLDGRID_BASE_DIR ) . '/assets/images/wordpresslogo.png'
		);

		$reseller_title = esc_html(
			! empty( $reseller_data['reseller_title'] ) ?
			$reseller_data['reseller_title'] : null
		);

		$reseller_support_url = esc_url(
			! empty( $reseller_data['reseller_support_url'] ) ?
			$reseller_data['reseller_support_url'] : 'http://www.boldgrid.com/documentation'
		);

		// Print HTML.
		?>
<br />
<center>
	<img src='<?php echo $reseller_logo_url; ?>'>
</center>
<br />
<div style='text-align: center;'>
	Need Support?<br />
		<?php

		if ( ! empty( $reseller_title ) ) {
			echo $reseller_title;
			?> provides dedicated help for <a target='_blank'
		href='<?php echo $reseller_support_url; ?>'>BoldGrid</a>.
		<?php
		} else {
		?>
			Check out the<a href='http://www.boldgrid.com/support/'
		target='_blank'>BoldGrid Education Channel</a>!
		<?php
		}
		?>
		</div>
<?php
	}

	/**
	 * If the WordPress admin bar is visible, enqueue our 'adminiconstyle.css' sheet to add the
	 * icons.
	 *
	 * This will avoid the icon from breaking if there are changes made to CSS styling by user, or
	 * WP core.
	 *
	 * @see is_admin_bar_showing().
	 * @see wp_enqueue_style().
	 */
	public function boldgrid_admin_icon() {
		if ( is_admin_bar_showing() ) {
			wp_enqueue_style(
				'adminiconstyle',
				plugins_url() . '/' . basename( BOLDGRID_BASE_DIR ) .
				'/assets/css/adminiconstyle.css',
				array(
					'admin-bar',
				),
				BOLDGRID_INSPIRATIONS_VERSION,
				'all'
			);
		}
	}

	/**
	 * Custom BoldGrid Icon in Admin Bar.
	 *
	 * Adds the BoldGrid logo before the WordPress Logo.
	 *
	 * @see wp_admin_bar().
	 * @see add_node().
	 * @see add_menu() each $boldgrid_submenu_item adds menu item to boldgrid parent menu node.
	 *
	 * @param object $wp_admin_bar WP admin bar object.
	 */
	public function boldgrid_admin_node( $wp_admin_bar ) {
		$args = array(
			'id' => 'boldgrid-adminbar-icon',
			'title' => '<span aria-hidden="true" class="boldgrid-icon ab-icon"></span>',
			'href' => 'http://www.boldgrid.com/',
			'meta' => array(
				'class' => 'boldgrid-node-icon',
			),
		);

		$wp_admin_bar->add_node( $args );

		$boldgrid_submenu_item = array(
			'id' => 'boldgrid-site-url',
			'parent' => 'boldgrid-adminbar-icon',
			'title' => __( 'BoldGrid.com', 'boldgrid-inspirations' ),
			'href' => 'http://www.boldgrid.com/',
			'meta' => array(
				'class' => 'boldgrid-dropdown',
				'target' => '_blank',
				'title' => 'BoldGrid.com',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $boldgrid_submenu_item );

		$boldgrid_submenu_item = array(
			'id' => 'boldgrid-site-documentation',
			'parent' => 'boldgrid-adminbar-icon',
			'title' => __( 'Documentation', 'boldgrid-inspirations' ),
			'href' => 'http://www.boldgrid.com/docs',
			'meta' => array(
				'class' => 'boldgrid-dropdown',
				'target' => '_blank',
				'title' => 'Documentation',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $boldgrid_submenu_item );

		$boldgrid_submenu_item = array(
			'id' => 'boldgrid-support-center',
			'parent' => 'boldgrid-adminbar-icon',
			'title' => __( 'Support Center', 'boldgrid-inspirations' ),
			'href' => 'http://www.boldgrid.com/support',
			'meta' => array(
				'class' => 'boldgrid-dropdown',
				'target' => '_blank',
				'title' => 'Support Center',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $boldgrid_submenu_item );

		$boldgrid_submenu_item = array(
			'id' => 'boldgrid-feedback-url',
			'parent' => 'boldgrid-adminbar-icon',
			'title' => __( 'Feedback', 'boldgrid-inspirations' ),
			'href' => 'http://www.boldgrid.com/feedback',
			'meta' => array(
				'class' => 'boldgrid-dropdown',
				'target' => '_blank',
				'title' => 'Feedback',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $boldgrid_submenu_item );
	}

	/**
	 * Custom IMH Icon in Admin Bar.
	 *
	 * Adds IMH Icon in third position directly after WordPress Icon.
	 *
	 * @see wp_admin_bar().
	 * @see add_node().
	 * @see add_menu() each $imh_submenu_item adds menu item to imh parent menu node.
	 *
	 * @param object $wp_admin_bar WP admin bar object.
	 */
	public function reseller_admin_node( $wp_admin_bar ) {
		// Get the reseller vars.
		$reseller_data = $this->get_reseller_data();

		$reseller_identifier = (
			! empty( $reseller_data['reseller_identifier'] ) ?
			$reseller_data['reseller_identifier'] : null
		);

		$reseller_title = (
			esc_html(
				! empty( $reseller_data['reseller_title'] ) ?
				$reseller_data['reseller_title'] : 'BoldGrid.com'
			)
		);

		$reseller_website_url = (
			esc_url(
				! empty( $reseller_data['reseller_website_url'] ) ?
				$reseller_data['reseller_website_url'] : 'http://www.boldgrid.com/'
			)
		);

		$reseller_support_url = (
			esc_url(
				! empty( $reseller_data['reseller_support_url'] ) ?
				$reseller_data['reseller_support_url'] : 'http://www.boldgrid.com/documentation'
			)
		);

		$reseller_amp_url = (
			! empty( $reseller_data['reseller_amp_url'] ) ?
			esc_url( $reseller_data['reseller_amp_url'] ) : null
		);

		$args = array(
			'id' => 'reseller-adminbar-icon',
			'title' => '<span aria-hidden="true" class="' . strtolower( $reseller_identifier ) .
			'-icon ab-icon"></span>',
			'href' => $reseller_website_url,
			'meta' => array(
				'class' => 'reseller-node-icon',
			),
		);

		$wp_admin_bar->add_node( $args );

		$reseller_submenu_item = array(
			'id' => 'reseller-site-url',
			'parent' => 'reseller-adminbar-icon',
			'title' => esc_html__( $reseller_title, 'boldgrid-inspirations' ),
			'href' => $reseller_website_url,
			'meta' => array(
				'class' => 'reseller-dropdown',
				'target' => '_blank',
				'title' => $reseller_title,
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $reseller_submenu_item );

		$reseller_submenu_item = array(
			'id' => 'reseller-support-center',
			'parent' => 'reseller-adminbar-icon',
			'title' => esc_html__( 'Support Center', 'boldgrid-inspirations' ),
			'href' => $reseller_support_url,
			'meta' => array(
				'class' => 'reseller-dropdown',
				'target' => '_blank',
				'title' => 'Support Center',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $reseller_submenu_item );

		$reseller_submenu_item = array(
			'id' => 'reseller-amp-login',
			'parent' => 'reseller-adminbar-icon',
			'title' => esc_html__( 'AMP Login', 'boldgrid-inspirations' ),
			'href' => $reseller_amp_url,
			'meta' => array(
				'class' => 'reseller-dropdown',
				'target' => '_blank',
				'title' => 'Feedback',
				'tabindex' => '1',
			),
		);

		$wp_admin_bar->add_menu( $reseller_submenu_item );
	}

	/**
	 * Custom Footer in Admin Dashboard.
	 *
	 * Replaces default admin footer text.
	 * BoldGrid - No partner branding.
	 *
	 * @see admin_footer_text().
	 */
	public function boldgrid_footer_admin() {
		?>
<i>Built with <a href='https://www.boldgrid.com/' target='_blank'>BoldGrid</a>.
</i>
|
<i>Powered by <a href='http://wordpress.org/' target='_blank'>WordPress</a>.
</i>
		<?php
	}

	/**
	 * Reseller Admin Footer Branding.
	 */
	public function boldgrid_footer_admin_reseller() {
		// Load the general footer.
		$this->boldgrid_footer_admin();

		// Get the reseller vars.
		$reseller_data = $this->get_reseller_data();

		$reseller_title = (
			esc_html(
				! empty( $reseller_data['reseller_title'] ) ?
				$reseller_data['reseller_title'] : 'BoldGrid.com'
			)
		);

		$reseller_support_url = (
			esc_url(
				! empty( $reseller_data['reseller_support_url'] ) ?
				$reseller_data['reseller_support_url'] : 'https://www.boldgrid.com/documentation'
			)
		);

		// Display the reseller footer.
		?>|
<i>Support from <a target='_blank'
href='<?php echo $reseller_support_url; ?>'><?php echo $reseller_title; ?></a>.</i>
		<?php
	}
}
