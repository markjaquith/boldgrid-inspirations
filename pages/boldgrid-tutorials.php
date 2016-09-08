<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

$boldgrid_menu_options = get_option( 'boldgrid_settings' );

/**
 * Create the link to BoldGrid support.
 *
 * If we're using the single menu, then don't use the BG icon, as that correlates to the icon in the
 * dashboard menu.
 */
$anchor_text = esc_html__( 'BoldGrid Support', 'boldgrid-inspirations' );

$boldgrid_support_url = esc_url( '//www.boldgrid.com/support' );

if ( 1 == $boldgrid_menu_options['boldgrid_menu_option'] ) {
	$template = '<a target="_blank" href="%s" class="dashicons-before boldgrid-icon">%s</a>, ';

	$link_to_boldgrid_support = sprintf( $template, $boldgrid_support_url, $anchor_text );
} else {
	$template = '<a target="_blank" href="%s">%s</a>, ';

	$link_to_boldgrid_support = sprintf( $template, $boldgrid_support_url, $anchor_text );
}
?>
<div class='wrap'>
<h2>BoldGrid Tutorials</h2>
<div class="boldgrid-tutorials">
	<div class="plugin-card no-float">
		<div class="plugin-card-top ">
			<p>
				The following tutorials contain technical and business advice to help you create a beautiful and effective web site. Additional guides and community help can be found at
				<?php echo $link_to_boldgrid_support; ?>
				or from your official host at top left. Have you watched the BoldGrid Introduction videos? Check those out on your
				<?php
				// Use printf to separate out the actual words from HTML so it can be translated.
				printf( ' <a href="%s" class="dashicons-before dashicons-dashboard"> %s</a>.',
					esc_url( get_dashboard_url() ),
					esc_html__( 'Dashboard', 'boldgrid-inspirations' ) ); // End of printf()
				?>
			</p>
			<p>
				The Inspiration Tab is applicable for all users. You will learn the process for choosing the look and feel of your Base Website (including your WordPress theme), and picking pages typical for your industry (called Page Sets) as you go through
				<?php
				// Show eiher Inspirations lightbulb or BoldGrid Logo depending on their menu
				// settings.
				( 1 == $boldgrid_menu_options['boldgrid_menu_option'] ? printf(
					'<a href="%s" class="dashicons-before dashicons-lightbulb"> ' .
						 esc_html__( 'Inspirations', 'boldgrid-inspirations' ) . '</a>.  ',
						esc_url(
							add_query_arg( 'page', 'boldgrid-inspirations',
								admin_url( 'admin.php' ) ) ) ) : printf(
					'<a href="%s" class="dashicons-before boldgrid-icon"> ' .
					 esc_html__( 'BoldGrid', 'boldgrid-inspirations' ) . '</a>.  ',
					esc_url(
						add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) ) );
				?>
			</p>
			<p>
				After that, you will move on to making the Inspiration your own. Before starting to

				<?php

				// Use printf to separate out the actual words from HTML
				// so it can be sent through translate.
				printf(
					'<a href="%s" class="dashicons-before dashicons-admin-customize"> %s</a>, ',

					// build URL and make sure it's escaped to avoid XSS attacks
					esc_url(

						// build our query
						add_query_arg(

							// pack it in an array
							array (

								// we want to get the proper URL encoded and without slashes
								'return' => urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) )
							),
							// End of array.

							// root page to apply our query to
							'customize.php' ) ),

					// End of our query argument

					// End of escaped URL build

					// Link title is "Customize."
					esc_html__( 'Customize', 'boldgrid-inspirations' ) );

				// End of printf()

				?> it is usually best to determine how much time you will have to put into Customization. Keep in mind you don't need to do it all now, you can grow your site over time. For first time webmasters, we recommend starting small. BoldGrid will help you build up skills in running your site. As you progress, you will know more about what you want to accomplish and how to do it.
			</p>
			<p>
				If you find something confusing in the Tutorials or with BoldGrid itself, please

				<?php

				// Use printf to separate out the actual words from HTML so it can be translated.
				printf( ' <a target="_blank" href="%s">%s</a>, ',

					// URL that we are linking to.
					esc_url( '//boldgrid.com/feedback' ),

					// Link's text, "let us know.""
					esc_html__( 'let us know', 'boldgrid-inspirations' ) );

				// End of printf()

				?> and we will work on it right away!
			</p>
		</div>
	</div>
	<article id="boldgrid-dashboard">
		<section id="boldgrid-welcome-custom">
			<script type="text/template" id="boldgrid-dashboard-view">
	<div id="TabContainer">
		<div class="accordion-wrapper">
			{{#each tabs}}
			<h2 class="tabs-container__title" id="TabPanelTitle-Tab{{@index}}" data-tab-key="{{@index}}">{{tab}}</h2>
			<div class="tabs-container__panel boldgrid-tab-content" id="Tab{{@index}}">
		        <div id="boldgrid-tab-view-content" class="left w50">
			        {{#each links}}
					<div class="boldgrid-tab-content-wrapper" data-link-key="{{@index}}" style="display: none;">
					{{#if video_id}}<div class="youtube-container"><div class="youtube-player" data-id="{{{video_id}}}"></div></div>{{/if}}
					{{#if video_title}}<h4>{{{video_title}}}</h4>{{/if}}
					{{#if video_summary}}{{{video_summary}}}{{/if}}
					</div>
					{{/each}}
		        </div>
		        <div id="boldgrid-tab-view-navigation" class="right w40">
				{{#each links}}
				{{#if content_heading}}<h2>{{content_heading}}</h2><ul>{{/if}}
				<li id="tutorial{{@../index}}{{@index}}" class="boldgrid-tab-links" data-tab-key="{{@../index}}" data-link-key="{{@index}}">
				{{#if icon}}<span class="dashicons {{icon}}"></span>{{/if}}
				<a>{{{link}}}</a> ({{{time}}})
				</li>
				{{/each}}
				</ul>
				</div>
			</div>
            {{/each}}
    	</div>
	</div>
	</script>
		</section>
	</article>
</div>
</div>
