<?php
$ref_is_dashboard_media = ( isset( $_GET['ref'] ) && 'dashboard-media' == $_GET['ref'] );
$ref_is_customizer = ( isset( $_GET['ref'] ) && 'dashboard-customizer' == $_GET['ref'] );

/**
 * Depending on the scenario, certain values need to be displayed in the attachment sidebar.
 * Instead of having php mixed in with html, we'll simply add the class "hide-if-need-be" to those
 * areas that may need to be hidden. If they need to be hidden, below, we'll set "hide-if-need-be"
 * to display:none;
 */
if ( $ref_is_dashboard_media || $ref_is_customizer ) {
	?>
<style>
.hide-if-need-be {
	display: none;
}
</style>
<?php
}

// Configure our download button text.
$download_button_text = ( true == $ref_is_dashboard_media || true == $ref_is_customizer ) ? "Download" : "Download and Insert into page";
?>

<script id="attachment-details-template"
	type="text/x-handlebars-template">
	<style>
		.attachment-display-settings,
		.attachment-display-settings label.setting span {
			float:none;
		}
	</style>

	<div class="attachment-details save-ready" data-id="10" tabindex="0">
		<h3>
			Attachment Details
			<span class="settings-save-status">
				<span class="spinner"></span>
				<span class="saved">Saved.</span>
			</span>
		</h3>
		<div class="attachment-info">
			<div id='attachment_details_thumbnail' class="thumbnail thumbnail-image">
				<img src="{{thumbnail_url}}" draggable="false">
			</div>
			<div class="details">
				<div class="filename">{{title}}</div>
				<div class="uploaded" style='display:none;'></div>
				<div class="file-size" style='display:none;'></div>
				<div class="dimensions" style='display:none;'></div>
				<a class="edit-attachment" style='display:none;' target="_blank">Edit Image</a>
				<a class="refresh-attachment" style='display:none;' href="#">Refresh</a>
				<a class="delete-attachment" style='display:none;' href="#">Delete Permanently</a>
				<div class="compat-meta"></div>
				<p><em>{{description}}</em></p>
			</div>
		</div>
		<div class='hide-if-need-be'>
			<label class="setting" data-setting="url" style='display:none;'>
				<span class="name">URL</span>
				<input value="" readonly="" type="text">
			</label>
			<label class="setting" data-setting="title">
				<span class="name">Title</span>
				<input value="" type="text" id='title'>
			</label>
			<label class="setting" data-setting="caption">
				<span class="name">Caption</span>
				<textarea id='caption'></textarea>
			</label>
			<label class="setting" data-setting="alt">
				<span class="name">Alt Text</span>
				<input value="" type="text" id='alt_text'>
			</label>
			<label class="setting" data-setting="description">
				<span class="name">Description</span>
				<textarea id='description'></textarea>
			</label>
		</div>
	</div>
	<form class="compat-item"></form>
	<div class="attachment-display-settings">
		<div class='hide-if-need-be'>
			<h3>Attachment Display Settings</h3>
			<label class="setting">
				<span>Alignment</span>
					<select class="alignment" data-setting="align" data-user-setting="align" id='alignment'>
						<option value="left">Left</option>
						<option value="center">Center</option>
						<option value="right">Right</option>
						<option value="none" selected="">None</option>
					</select>
			</label>
		</div>
		<div class="setting" style='display:none;'>
			<label>
				<span>Link To</span>
				<select class="link-to" data-setting="link" data-user-setting="urlbutton">
					<option value="file">Media File</option>
					<option value="post">Attachment Page</option>
					<option value="custom">Custom URL</option>
					<option value="none" selected=''>None</option>
				</select>
			</label>
			<input readonly="" class="link-to-custom" data-setting="linkUrl" type="text">
		</div>
		<label class="setting">
			<span>Size:</span><br />
			<select id='image_size' class="size" name="size" data-setting="size" data-user-setting="imgsize">
				{{#each sizes}}
					<option data-cost-coins="{{cost_coins}}" data-width="{{width}}" data-height="{{height}}" value="{{name}}">
							{{name}}: {{width}} × {{height}} - Coins: {{cost_coins}}
					</option>
				{{/each}}
			</select>
		</label>
	</div>

	<p>
		<a id='download_and_insert_into_page' class="button media-button button-primary button-large media-button-insert"><?php echo $download_button_text; ?></a>
		<input type='hidden' name='id_from_provider' id='id_from_provider' value={{id_from_provider}} />
		<input type='hidden' name='image_provider_id' id='image_provider_id' value={{image_provider_id}} />
		<input type='hidden' id='currently_downloading_image' value='0' />
	</p>
</script>

<script id="attachment-details-error-template"
	type="text/x-handlebars-template">
	<div class='error' style='margin:5px 0px;'>
		There was an error getting the details of this image. The image may no longer be available, or, there was a problem fetching the details from the image's provider.
	</div>
	<button onClick="jQuery('.selected').click();" class='button'>Try again</button>
</script>