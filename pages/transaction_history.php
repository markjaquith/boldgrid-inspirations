<?php

// Prevent direct calls
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$is_asset_server_available = ( bool ) get_site_transient( 'boldgrid_available' );

add_thickbox();

include BOLDGRID_BASE_DIR . '/pages/templates/transaction_history.php';

?>

<div class='wrap'>

<?php
	include BOLDGRID_BASE_DIR . '/pages/includes/cart_header.php';
?>

	<h1>Transaction History</h1>

	<div class="tablenav top"></div>

<?php
if ( false === $is_asset_server_available ) {
	require BOLDGRID_BASE_DIR . '/pages/templates/boldgrid_connection_issue.php';
} else {
	?>
	<div id='transactions'>Loading transaction history...</div>
<?php
}
?>
	<div class="tablenav bottom"></div>

	<div id='transaction' class='hidden'></div>

</div>
