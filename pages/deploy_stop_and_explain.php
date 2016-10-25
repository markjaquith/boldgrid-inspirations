<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

/**
 * ****************************************************************************
 * 1: Generate the "Your new site is now installed" message.
 * 2: Set any applicable cookies.
 * ****************************************************************************
 */
$url_to_customizer = 'customize.php?return=' . get_dashboard_url();

$review_documentation = null;

// If we don't have the staging plugin installed:
if ( ! is_plugin_active( 'boldgrid-staging/boldgrid-staging.php' ) ) {
	$template = 'Your new BoldGrid site is now installed and <a href="%s" target="_blank">ready to view</a>.';
	$your_new_site_is_now_installed_message = sprintf( $template, esc_url( get_site_url() ) );

	$_SESSION['wp_staging_view_version'] = 'production';
} else {
	// If installed to your active site:
	if ( false == $this->is_staging_install() ) {
		$site_type = 'Active';

		$_SESSION['wp_staging_view_version'] = 'production';
	} else {
		// If installed to your staging site:
		$site_type = 'Staging';

		$_SESSION['wp_staging_view_version'] = 'staging';
		$url_to_customizer = "customize.php?staging=1";

		$review_documentation = sprintf(
			__( 'Since you have installed your new site into Staging you may want to review the %sStaging Guide%s to learn about Active vs. Staging and how to deploy from Staging when you are ready.', 'boldgrid-inspirations' ),
			'<a href="https://www.boldgrid.com/support/getting-to-know-boldgrid/understanding-active-vs-staging-in-boldgrid/" target="_blank">',
			'</a>'
		);

		// Just avoiding html in translation.
		$review_documentation = '<p>' . $review_documentation . '</p>';
	}

	$template = 'Your new BoldGrid site has installed as your <strong>%s</strong> site, and is <a href="%s" target="_blank">ready to view</a>.';
	$your_new_site_is_now_installed_message = sprintf( $template, esc_html( $site_type ), esc_url( get_site_url() ) );
}

?>

<div class='wrap hidden' id='stop_and_explain'>
	<div class='plugin-card no-float'>
		<div class='plugin-card-top '>
			<?php echo $this->get_plugin_activation_notices(); ?>
			<h3><?php _e( 'Congratulations!', 'boldgrid-inspirations' ); ?></h3>

			<p><?php echo $your_new_site_is_now_installed_message; ?></p>

			<p><?php printf( __( 'Next, we will move on to Phase 2 - Customization. This is the stage where you will make the site your own. For first time webmasters, we recommend you open up our %s Customizer Guide%s to refer to as you work.', 'boldgrid-inspirations' ), '<a href=" https://www.boldgrid.com/support/using-the-customizer/" target="_blank">', '</a>' ); ?></p>

			<?php
				/*
				 * This string is not enclosed in a p tag. Sometimes the string will be empty and
				 * we don't want to print an empty p tag.
				 */
				echo $review_documentation;
			?>
		</div>
		<div class='plugin-card-bottom'>
			<div class='column-updated'>
				<a href='<?php echo esc_url( admin_url( $url_to_customizer ) ); ?>' class='button button-primary'><?php _e( 'Customize', 'boldgrid-inspirations' ); ?></a>
			</div>
		</div>
	</div>

	<hr />
</div>

<?php

// Render 'stop and explain' message.
Boldgrid_Inspirations_Utility::inline_js_file( 'deploy_stop_and_explain.js' );

?>
