<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>
<!-- Start of BoldGrid Dashboard Widget -->
<div id="dashboard-widget" class="metabox-holder">
	<div id="postbox-container-0" class="postbox-container"
		style="width: 100%">
		<div class="meta-box-sortables ui-sortable">
			<div id="boldgrid-postbox" class="postbox">
				<div class="handlediv" title="Click to toggle">
					<br>
				</div>
				<h3 class="boldgrid-welcome-handle">
					<span style="line-height: 1.4em;">Welcome to BoldGrid!</span>
				</h3>
				<div class="boldgrid-breaker-bar"></div>
				<div class="inside">
					<section id="boldgrid-first-widget" class="left w50">
						<div class="boldgrid-50-50-left">
							<h2>First Time Users</h2>
							<?php include BOLDGRID_BASE_DIR . '/pages/boldgrid-dashboard-first-time-users.php'; ?>
						</div>
					</section>
					<section id="boldgrid-experienced-section" class="right w50">
						<div class="boldgrid-50-50-right">
							<h2>Experienced WordPress Users</h2>
							<?php include BOLDGRID_BASE_DIR . '/pages/boldgrid-dashboard-advanced-wp-users.php'; ?>
						</div>
					</section>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- End of BoldGrid DashBoard Widget Content -->
