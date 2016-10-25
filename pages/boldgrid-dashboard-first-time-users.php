<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>
<div class="youtube-container">
	<div class="youtube-player" data-id="Swzm9Qw0llw"></div>
</div>
<p>Creating a site with BoldGrid is done in 3 steps:</p>
<ol class="boldgrid-counter">
	<li>Go to
			<?php
			// Get BoldGrid settings.
			$boldgrid_settings = get_option( 'boldgrid_settings' );

			// Show either Inspirations lightbulb or BoldGrid Logo depending on their
			// menu settings.
			( 1 == $boldgrid_settings['boldgrid_menu_option'] ? printf(
				' <a href="%s" class="dashicons-before dashicons-lightbulb">' .
					 esc_html__( 'Inspirations', 'boldgrid-inspirations' ) . '</a> ',
					esc_url(
						add_query_arg( 'page', 'boldgrid-inspirations',
							admin_url( 'admin.php' ) ) ) ) : printf(
				' <a href="%s" class="dashicons-before boldgrid-icon"> ' .
				 esc_html__( 'BoldGrid', 'boldgrid-inspirations' ) . '</a>',
				esc_url(
					add_query_arg( 'page', 'boldgrid-inspirations',
						admin_url( 'admin.php' ) ) ) )
			);
			?>
		to install your starter website and pages typical for your industry.</li>
	<li>
		<?php
			printf( ' <a href="%s" class="dashicons-before dashicons-admin-customize">' .
				esc_html__( 'Customize', 'boldgrid-inspirations' ) . '</a> ',
				// Build URL and make sure it's escaped to avoid XSS attacks.
				esc_url(
					// Build our query.
					add_query_arg(
						// We want to get the proper URL encoded and without slashes since
						// we are escaping our URL.
						'return', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
						// Root page to apply our query to.
						admin_url( 'customize.php' )
					)
				)
			);
		?>
		site wide settings like business name, colors, menus and content in your header and footer.</li>
	<li>Edit your
		<?php
			printf( ' <a href="%s" class="dashicons-before dashicons-admin-page">' .
				esc_html__( 'Pages', 'boldgrid-inspirations' ) . '</a> ',
				esc_url( add_query_arg( 'post_type', 'page', admin_url( 'edit.php' ) ) )
			);
		?>
		to add your content and photos.</li>
</ol>
<p>
<?php printf( __( 'Watching the video above is recommended.  You can also visit our %s support center %s to learn more.', 'boldgrid-inspirations' ), '<a href="http://www.boldgrid.com/support/" target="_blank">', '</a>' ); ?>
</p>
<div class="boldgrid-button-wrapper-left">
	<?php
	// Use printf to separate out the actual words from HTML
	// so it can be sent through translate.
	printf(
		'<a href="https://www.boldgrid.com/support/" target="_blank"><span class="button button-secondary button-hero">%s</span></a>',
		esc_html__( 'Learn More', 'boldgrid-inspirations' )
	);

	?>
	<span class="boldgrid-between-buttons">or</span>
	<?php

	printf(
		'<a href="%s"><span class="button button-primary button-hero">' .
		esc_html__( 'Get Started', 'boldgrid-inspirations' ) .
		'</span></a>',
		esc_url(
			add_query_arg(
				array(
					'page' => 'boldgrid-inspirations',
					'boldgrid-tab' => 'install',
				),
				admin_url( 'admin.php' )
			)
		)
	);
	?>
</div>
<!-- End of First Time Users Section -->
