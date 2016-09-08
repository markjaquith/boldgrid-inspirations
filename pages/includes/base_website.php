<h1 id="base-website-selection-heading"><?php _e('Step 2: Select a Base Website','boldgrid-inspirations'); ?>
&nbsp;<span class="spinner"></span>
</h1>

<?php include BOLDGRID_BASE_DIR . '/pages/includes/step-2-nav-bar.php'; ?>

<div id="base-website-selection" class="theme-browser rendered">
	<div class='themes' id='available_themes'></div>

	<div style='clear: both;'></div>

	<div class="loading-wrapper boldgrid-loading hidden"></div>
</div>

<div id='step-2-additional-themes-message' class='hidden'>
	<h3>Additional Themes</h3>
	<div>The following themes don't match your category, but the home page
		content can be changed to fit your needs.</div>
	<hr />
</div>

<div id='step-2-load-more-themes' class='hidden'>
	<button class='button'>Load more</button>
</div>

<div id='step-2-request-a-theme' class='hidden'>
	<span>Not seeing a theme you like?</span><br /> <a
		href='https://www.boldgrid.com/faqs#request-a-theme' target='_blank'
		class='button'>Request a Theme</a>
</div>
