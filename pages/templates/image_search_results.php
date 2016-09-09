<script id="search-results-template" type="text/x-handlebars-template">
{{#each data}}
	<!-- <img src='{{thumb_url}}' style='height:200px;' title='{{source}}' /> -->

<li class="attachment save-ready" data-id-from-provider="{{id_from_provider}}" data-image-provider-id="{{image_provider_id}}" data-requires-attribution="{{requires_attribution}}" aria-checked="false" aria-label="title goes here" role="checkbox" tabindex="0">
		<div class="attachment-preview js--select-attachment type-image subtype-jpeg portrait">
			<div class="thumbnail">			
					<div class="centered">
						<img src="{{thumb_url}}" draggable="false" alt="">
					</div>
			</div>
		</div>
			<a class="check" href="#" title="Deselect" tabindex="-1"><div class="media-modal-icon"></div></a>
	<div class='image-details'>
		{{#if_eq license_type 'creative_commons'}}
			<img src='<?php echo esc_url( plugins_url() . '/' . basename( BOLDGRID_BASE_DIR ) . '/assets/images/cc/cc.png' ); ?>' class='cc-sm' />
		{{/if_eq}}
		{{#if_eq license_type 'paid'}}
			Coins: <strong>{{coins}}</strong>
		{{/if_eq}}
	</div>
	</li>

{{/each}}
</script>
