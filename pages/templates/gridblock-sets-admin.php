<script id="tmpl-gridblock_set_container" type="text/html">
<div class='gridblock-set theme' data-gridblock-set-key='{{data.key}}' data-gridblock-set-category='{{data.category}}'>
	<div class='preview'>
		<div class='preview-fader'>
				<span>Preview</span>
		</div>
		<iframe></iframe>
	</div>
	<div class='controls'>
		<span>{{data.title}}</span>
		<a class='button button-primary'>Select</a>
		<div style='clear:both;'></div>
	</div>
</div>
</script>
<script id="tmpl-gridblock_set_blank_container" type="text/html">
<div class='gridblock-set blank theme'>
	<div class='preview'>
		<!-- <iframe></iframe> -->
	</div>
	<div class='controls'>
		<span>Blank</span>
		<a class='button button-primary'>Select</a>
		<div style='clear:both;'></div>
	</div>
</div>
</script>
<script id="tmpl-gridblock_set_error_fetching" type="text/html">
<div class='error-fetching-gridblock-set'>
	<p>Whoops! There seemed to be a problem downloading the newest GridBlock Sets.</p>
	<p>
		<a class='button' id='try_again' >Try again</a> <a class='button button-primary' href='post-new.php?post_type=page'>Add blank</a>
	</p>
</div>
</script>
