<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>

<style>
.spinner {
	visibility: visible;
	float: none;
	margin: 0px;
	vertical-align: top;
}

h1 .dashicons.dashicons-yes {
	color: green;
	font-size: 30px;
	padding-right: 15px;
}

.boldgrid-loading {
	display: inline-block;
	margin: 15px 0px 5px 0px;
}
</style>

<div class="wrap">

	<h1 class="purchasing clear">Purchasing items in your cart</h1>

	<div class='boldgrid-loading'></div>

	<p>
		<strong>Installation log</strong>: <a class="toggle-log pointer">show
			/ hide log</a> (<em class="deploy_log_line_count"></em>) <span
			class="spinner"></span>
	</p>

	<div
		class="plugin-card installation-log hidden col-xs-12 col-sm-8 col-md-8 col-lg-6">
		<div class="plugin-card-top"></div>
	</div>

</div>

<div class="wrap">

	<div
		class="plugin-card stop-and-explain hidden col-xs-12 col-sm-8 col-md-8 col-lg-6">

		<div class="plugin-card-top">
			<h3>Congratulations on your purchase!</h3>

			<p>Your images have now downloaded to your Media Library and have
				replaced the watermarked versions used throughout your site.</p>

			<p>
				Should you ever delete a purchased image from your Media Library,
				you'll be able to download them again from the <strong>Transactions
					&gt; Receipts</strong> page.
			</p>
		</div>

		<div class="plugin-card-bottom">
			<div class="column-updated">
				<a class="button" href="<?php echo get_site_url(); ?>">Visit Your
					Site</a> <a class="button button-primary"
					href="<?php echo get_admin_url(); ?>">Continue to your Dashboard</a>
			</div>
		</div>
	</div>

</div>

<?php Boldgrid_Inspirations_Utility::inline_js_file('checking_out.js'); ?>