<?php defined( 'WPINC' ) ? : die; // Don't let this page get loaded directly. ?>
<div class="youtube-container">
	<div class="youtube-player" data-id="fAbVXapGx48"></div>
</div>
<p>We encourage you to watch the video above before starting with 

<?php
// Get BoldGrid settings:
$boldgrid_settings = get_option( 'boldgrid_settings' );

// Show eiher Inspirations lightbulb or BoldGrid Logo depending on their menu settings.
( 1 == $boldgrid_settings['boldgrid_menu_option'] ? printf(
	'<a href="%s" class="dashicons-before dashicons-lightbulb">' .
		 esc_html__( ' Inspirations', 'boldgrid-inspirations' ) . '</a>.',
		esc_url( add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) ) : printf(
	'<a href="%s" class="dashicons-before boldgrid-icon">' .
	 esc_html__( ' BoldGrid', 'boldgrid-inspirations' ) . '</a>.',
	esc_url( add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) ) );
?>
</p>

<p>
	<b>Is this WordPress running a live website?</b>

<?php
// Use printf to separate out the actual words from HTML so it can be translated.
printf( 'Our <a href="%s">%s</a>',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to (BoldGrid Tutorials).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users"
				'tab' => 'inspirations',

				// The tutorial we are linking to (Staging)
				'tutorial' => '1'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Staging Plugin', 'boldgrid-inspirations' ) );

// End of printf()

?>

will help you transition to a new site while running your current Active Site.</p>

<p>In addition to the video above, we have more detail on things that have changed like the

<?php
// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to (BoldGrid Tutorials).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users"
				'tab' => 'inspirations',

				// The tutorial we are linking to (Admin Menu)
				'tutorial' => '4'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Admin Menu', 'boldgrid-inspirations' ) );

// End of printf()

?>

 (you can change it back

<?php
// Use printf to separate out the actual words from HTML so it can be translated.
printf( '<a href="%s">%s</a>',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg( 'page', 'boldgrid-settings', 'admin.php' ) ),

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'here', 'boldgrid-inspirations' ) );

// End of printf()

?> ), the

<?php
// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>, ',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to (BoldGrid Tutorials).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users"
				'tab' => 'customizations',

				// The tutorial we are linking to (Page and Post Editor)
				'tutorial' => '9'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Page and Post Editor', 'boldgrid-inspirations' ) );

// End of printf()

// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>,',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to ( BoldGrid Tutorials ).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users."
				'tab' => 'customizations',

				// The tutorial we are linking to ( Media Searches ).
				'tutorial' => '10'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Media Searches', 'boldgrid-inspirations' ) );

// End of printf()

?> our new <?php

// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>, easy ways to ',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to ( BoldGrid Tutorials ).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users."
				'tab' => 'inspirations',

				// The tutorial we are linking to ( GridBlocks ).
				'tutorial' => '6'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'GridBlocks', 'boldgrid-inspirations' ) );

// End of printf()

// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>, ',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to ( BoldGrid Tutorials ).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users."
				'tab' => 'inspirations',

				// The tutorial we are linking to ( GridBlocks ).
				'tutorial' => '9'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Start Over', 'boldgrid-inspirations' ) );

// End of printf()

?> and <?php

// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '<a href="%s">%s</a>.',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to ( BoldGrid Tutorials ).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users."
				'tab' => 'advanced'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'more', 'boldgrid-inspirations' ) );

// End of printf()

?>





<div class="boldgrid-button-wrapper-right">

<?php
// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( '
	<a href="%s">
		<span class="button button-secondary button-hero">%s</span>
	</a>',

	// Escape the URL to avoid XSS
	esc_url(

		// Add our query arguments
		add_query_arg(

			array (

				// The page we are linking to (BoldGrid Tutorials).
				'page' => 'boldgrid-tutorials',

				// The tab we are linking to, "Advanced For WordPress Users"
				'tab' => 'advanced'
			),

			// The root page we are building our query off of.
			'admin.php' ) ),

	// End of Query Argument

	// End of Escaping

	// Link title is by itself, esc_html() escapes this output,
	// then __() allows it to be sent to translate. 'boldgrid-inspirations'
	// is our text domain.
	esc_html__( 'Learn More', 'boldgrid-inspirations' ) );

// End of printf()

?>

</div>
<!-- End of Advanced For WordPress Users Section -->
