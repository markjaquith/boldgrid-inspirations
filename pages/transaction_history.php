<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

// Check if asset server is available.
$is_asset_server_available = (bool) get_site_transient( 'boldgrid_available' );

add_thickbox();

include BOLDGRID_BASE_DIR . '/pages/templates/transaction_history.php';

?>

<div class='wrap'>

<?php
	include BOLDGRID_BASE_DIR . '/pages/includes/cart_header.php';
?>

	<h1>Transaction History</h1>

	<div class='tablenav top'></div>

<?php
$notice_template_file = BOLDGRID_BASE_DIR .
'/pages/templates/boldgrid-connection-issue.php';

if ( ! $is_asset_server_available &&
! in_array( $notice_template_file, get_included_files(), true ) ) {
	include $notice_template_file;
} else {
	?>
	<div id='transactions'>Loading transaction history...</div>
<?php
}
?>
	<div class='tablenav bottom'></div>

	<div id='transaction' class='hidden'></div>

</div>
