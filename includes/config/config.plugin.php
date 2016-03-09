<?php
// Prevent direct calls
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/* @formatter:off */
return array (
	'ajax_calls' => array (
		// layout
		'get_theme_ids' =>								'/api/layout/get-themes-for-category',
		'get_num_themes' =>								'/api/layout/get-num-available-themes',
		// category
		'get_categories' =>								'/api/category/get-active',
		'get_subcategories_from_page_set' =>			'/api/category/get-subcategories-from-page-set',
		'get_category_tags' =>							'/api/category/get-tags',
		'category_search' =>							'/api/category/search',
		// asset
		'get_asset' =>									'/api/asset/get',
		'get-total-asset-cost' =>						'/api/asset/get-total-asset-cost',
		// build
		'get_layouts' =>								'/api/build/create',
		'get_build_profile' =>							'/api/build/get',
		'get_build_profile_using_in_progress_theme' =>	'/api/build/get-using-in-progress-theme',
		// pde
		'get_curated' =>								'/api/pde/get-curated-asset',
		// theme
		'get_theme_details' =>							'/api/theme/get',
		'get_all_active_themes' =>						'/api/theme/get-all-active',
		'get_theme_info' =>								'/api/theme/get-wordpress-info',
		'get_theme_groups' =>							'/api/theme/get-groups',
		'get_themes_in_group' =>						'/api/theme/get-themes-in-group',
		'get_theme_basic_details' =>					'/api/theme/get-theme-basic-details',
		'create_theme' =>								'/api/theme/create',
		'submit_theme_for_approval' =>					'/api/theme/submit-for-approval',
		// plugin
		'get_plugins' =>								'/api/plugin/get-plugin-data',
		'get_version' =>								'/api/plugin/check-version',
		'get_plugin_version' =>							'/api/open/get-plugin-version',
		// pageset
		'get_page_set_custom_details' =>				'/api/page-set/get-custom-details',
		'get_page_set_details' =>						'/api/page-set/get-details',
		'get_category_page_sets' =>						'/api/page-set/get-category-related',
		'get_page_set' =>								'/api/page-set/get',
		'get_count_pages_in_progress' =>				'/api/page-set/get-count-pages-in-progress',
		'create_page_revision' =>						'/api/page-set/create-page-revision',
		'submit_page_set_for_approval' =>				'/api/page-set/submit-for-approval',
		'get_all_active_pages' =>						'/api/page-set/get-all-active',
		// language
		'get_languages' =>								'/api/language/get',
		// Page and Post Editor
		'get_page_post_layouts' =>						'/api/page-post/get-configs',
		// user
		'get_transaction_history' =>					'/api/user/get-coin-history',
		'get_coin_balance' =>							'/api/user/get-coin-balance',
		'validate_connect_key' =>						'/api/user/validate-connect-key',
		// image
		'image_search' =>								'/api/image/search',
		'image_get_details' =>							'/api/image/get-details',
		'image_download' =>								'/api/image/download',
		// built photo search
		'bps-get-photo' =>								'/api/built-photo-search/get-photo',
		'bps-get-photos' =>								'/api/built-photo-search/get-photos',
		'bps-get-queries' =>							'/api/built-photo-search/get-queries',
		'bps-get-results' =>							'/api/built-photo-search/get-results',
		'bps-save-new-query' =>							'/api/built-photo-search/save-new-query',
		'bps-save-new-phrase-and-results' =>			'/api/built-photo-search/save-new-phrase-and-results',
		
		//Preview Server
		'get-site-content' => 							'/wpb-maintenance/get-site-content.php',
	),
	'asset_server' =>									'https://wp-assets.boldgrid.com',
	'preview_server' =>									'https://wp-preview.boldgrid.com',
	'author_preview_server' =>							'https://wp-staging.boldgrid.com',
	'installation' => array (
		'max_num_install_attempts' => 5 
	),
	'plugins' => array(
		'staging' => array (
			'path' => 'boldgrid-staging/boldgrid-staging.php'
		),
		'gallery' => array (
			'path' => 'boldgrid-gallery/wc-gallery.php'
		),
		'editor' => array (
			'path' => 'boldgrid-editor/boldgrid-editor.php'
		),
		'author' => array (
			'path' => 'boldgrid-author/boldgrid-author.php'
		),
		'form' => array (
			'path' => 'boldgrid-ninja-forms/ninja-forms.php'
		),
	),
	'features' => array(
		'boldgrid-theme-install' => array(
			'candidate',
			'edge',
		)
	)
);
/* @formatter:on */