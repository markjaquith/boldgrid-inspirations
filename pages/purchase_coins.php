<?php

// Prevent direct calls
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Check asset server availability:
$is_asset_server_available = ( bool ) get_site_transient( 'boldgrid_available' );

// Print a message for connection failure:
if ( false === $is_asset_server_available ) {
	require BOLDGRID_BASE_DIR . '/pages/templates/boldgrid_connection_issue.php';
}

/**
 * Configure a link to our reseller.
 *
 * If we don't have a reseller, the link will be an empty string.
 */
$boldgrid_reseller = get_option( 'boldgrid_reseller', array () );

if ( isset( $boldgrid_reseller['reseller_amp_url'] ) ) {
	$reseller_url = $boldgrid_reseller['reseller_amp_url'];
} else if ( isset( $boldgrid_reseller['reseller_website_url'] ) ) {
	$reseller_url = $boldgrid_reseller['reseller_website_url'];
}

if ( isset( $boldgrid_reseller['reseller_title'] ) && isset( $reseller_url ) ) {
	$reseller_link_template = ', <a href="%s" target="_blank">%s</a>';
	$reseller_link = sprintf( $reseller_link_template,
		// URL to reseller website.
		esc_url( $reseller_url ),
		// Title of reseller.
		$boldgrid_reseller['reseller_title'] );
} else {
	$reseller_link = '';
}

?>

<div class='wrap'>

<?php
	// include the navigation
	include BOLDGRID_BASE_DIR . '/pages/includes/cart_header.php';
?>

	<div class='plugin-card'>

		<div class='plugin-card-top'>
			<p>You can purchase additional coins through your official BoldGrid
				reseller<?php echo $reseller_link; ?>. After you have purchased additional coins, your new coin
				balance will update on the transaction pages.</p>
		</div>

	</div>

</div>