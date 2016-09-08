<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>

<!--
*******************************************************************************
Inline style
*******************************************************************************
 -->
<style>
ul#deploy_log {
	list-style-position: inside;
	list-style-type: disc;
	font-family: "Courier New", Courier, monospace;
	line-height: 11px;
}

/* When deployment is installing a theme, it prints the details of the theme on
	the screen. This css hides those details. */
div#wpbody-content div.wrap {
	display: none;
}

div#wpbody-content div#deploy_status.wrap {
	display: block;
}

#deploy_status .spinner {
	visibility: visible;
	float: none;
	margin: 0px 5px 0px 0px;
}

#deploy_text {
	font-style: italic;
}

#deploy_status .boldgrid-loading {
	display: inline-block;
	margin: 15px 0px 20px 0px;
}

h1 .dashicons.dashicons-yes {
	color: green;
	font-size: 30px;
	padding-right: 15px;
}
</style>

<!--
*******************************************************************************
Deployment container
*******************************************************************************
 -->

<div name='deploy_status' id='deploy_status' class='wrap'>
	<h1>Installing your new content</h1>

	<div class='boldgrid-loading'></div>

	<p>
		<strong>Installation log:</strong> <a id='toggle_view_deploy_log'>show
			/ hide log</a> (<em><span class='deploy_log_line_count'></span></em>)<br />
		<span class='spinner'></span><span name='deploy_text' id='deploy_text'>loading...</span>
	</p>

	<div class='plugin-card hidden'>
		<div class='plugin-card-top'>
			<ul name='deploy_log' id='deploy_log'></ul>
		</div>
	</div>
</div>

<?php

Boldgrid_Inspirations_Utility::inline_js_file( 'after_deployment_container.js' );

add_shortcode( 'imhwpb', array (
	'imhwpbDeploy',
	'dummy_shortcode_imhwpb'
) );

$new_deploy = new Boldgrid_Inspirations_Deploy( $this->get_configs() );
$new_deploy->do_deploy();
?>
