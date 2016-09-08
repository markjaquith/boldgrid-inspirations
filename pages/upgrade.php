<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>
<h1>Updating plugin...</h1>

<p>We noticed that your version of the BoldGrid Inspirations plugin is
	out of date. Please hold while we upgrade to the latest version for
	you.</p>

<div style='display: none;'>
<?php

$latest_version = $version_info->version;

$configs = $this->get_configs();

$plugin_url = $configs['asset_server'] . $configs['ajax_calls']['get_asset'] . "?key=" .
	 $configs['api_key'] . "&id=" . $version_info->asset_id;

$plugin = 'boldgrid-inspirations/boldgrid-inspirations';

$activate_path = 'boldgrid-inspirations/boldgrid-inspirations.php';

// Security.
if ( ! is_numeric( $latest_version ) ) {
	die( 'Error: Latest version value not a number.' );
}

include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

// Update transient values.
$update_plugins = get_site_transient( 'update_plugins' );

$update_plugins->response['boldgrid-inspirations/boldgrid-inspirations.php']->package = $plugin_url;

set_site_transient( 'update_plugins', $update_plugins );

// UPGRADE the plugin.
$upgrader = new Plugin_Upgrader(
	new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

$upgrader_install = $upgrader->upgrade( $plugin );

// ACTIVATE the plugin.
$result = activate_plugin( $activate_path );

if ( is_wp_error( $result ) ) {
	wp_die( $result );
}

// REDIRECT back to the plugin.
$redirect_url = admin_url() . "admin.php?page=imh-wpb";

?>
</div>
<script type='text/javascript'>
	<!--
		alert('We noticed that your version of the IMHWPB plugin was out of date, so we updated it for you! Click OK to continue.');
		window.location = '<?php echo $redirect_url; ?>';
	//-->
</script>
