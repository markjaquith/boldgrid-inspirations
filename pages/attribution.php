<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

$attribution_heading = '
	<p>
		This site has been created with the help of many different people and companies.
	</p>
';

$attribution_image_heading = '
	<p>
		In particular, a special thanks goes to the following for content running on this site:
	</p>
';

// Create attribution for the web host reseller.
$reseller = get_option( 'boldgrid_reseller' );

if ( false !== $reseller && ! empty( $reseller['reseller_title'] ) ) {
	$reseller_attribution = ' Web hosting support is provided by ';

	if ( ! empty( $reseller['reseller_website_url'] ) ) {
		$reseller_attribution .= '<a href="' . $reseller['reseller_website_url'] . '">' .
			 $reseller['reseller_title'] . '</a>.';
	} else {
		$reseller_attribution .= $reseller['reseller_title'] . '.';
	}
} else {
	$reseller_attribution = '';
}

$attribution_wordpress_and_inspirations = '
	<p style="clear:both;">
		%s site was built on a powerful, Inspirations based web builder called <a href="http://www.boldgrid.com">BoldGrid</a>. It is running on <a href="http://wordpress.org">WordPress</a>, the most popular content management software online today.' .
	 $reseller_attribution . '
	</p>
';

// Create attribution for plugins we install from 3rd Party sources.
$attribution_additional_plugins = '';
if ( function_exists( 'is_plugin_active' ) ) {
	// Check if some plugins are active:
	$is_boldgrid_ninja_forms_active = ( bool ) is_plugin_active(
		'boldgrid-ninja-forms/ninja-forms.php' );
	$is_boldgrid_gallery_active = ( bool ) is_plugin_active( 'boldgrid-gallery/wc-gallery.php' );

	if ( $is_boldgrid_ninja_forms_active || $is_boldgrid_gallery_active ) {
		$attribution_additional_plugins .= '<div class="boldgrid-attribution"><p>Additional functionality provided by:</p><ul>';

		if ( $is_boldgrid_ninja_forms_active ) {
			$attribution_additional_plugins .= '<li><a href="' . esc_url( 'ninjaforms.com',
				array (
					'http',
					'https'
				) ) . '">Ninja Forms</a></li>';
		}

		if ( $is_boldgrid_gallery_active ) {
			$attribution_additional_plugins .= '<li><a href="' . esc_url(
				'https://wordpress.org/plugins/wc-gallery/',
				array (
					'http',
					'https'
				) ) . '">WP Canvas - Gallery</a></li>';
		}
		$attribution_additional_plugins .= '</ul></div>';
	}
}
