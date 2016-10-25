<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>
<div class="youtube-container">
	<div class="youtube-player" data-id="DnMmRUVvf4A"></div>
</div>
<p>
<?php
printf( __( 'We encourage you to watch the video above and check out the %s Advanced Tutorials %s', 'boldgrid-inspirations' ),
'<a href="https://www.boldgrid.com/support/advanced-tutorials/" target="_blank">',
'</a>'
);

?> before starting with

<?php
// Get BoldGrid settings.
$boldgrid_settings = get_option( 'boldgrid_settings' );

// Show eiher Inspirations lightbulb or BoldGrid Logo depending on their menu settings.
( 1 == $boldgrid_settings['boldgrid_menu_option'] ? printf(
	'<a href="%s" class="dashicons-before dashicons-lightbulb">' .
		 esc_html__( ' Inspirations', 'boldgrid-inspirations' ) . '</a>.',
		esc_url( add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) ) : printf(
	'<a href="%s" class="dashicons-before boldgrid-icon">' .
	 esc_html__( ' BoldGrid', 'boldgrid-inspirations' ) . '</a>.',
	esc_url( add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) )
);
?>
</p>

<p>
	<b><?php _e( 'Is this WordPress running a live website?', 'boldgrid-inspirations' ); ?></b>
</p>
<p>
<?php
// Use printf to separate out the actual words from HTML so it can be translated.
printf( __( 'Our %s Staging Plugin %s will help you transition to a new site while running your current Active Site.', 'boldgrid-inspirations' ),
	'<a href="https://www.boldgrid.com/support/getting-to-know-boldgrid/how-to-set-up-staging-in-boldgrid/" target="_blank">',
	'</a>'
);
?>
</p>

<p>

<?php
// Use printf to separate out the actual words from HTML
// so it can be sent through translate.
printf( __( 'In addition to the video above, we have more detail on things that have changed like the %s Admin Menu %s (you can change it back %s here %s).', 'boldgrid-inspirations' ),
	'<a href="https://www.boldgrid.com/support/designers-developers/how-to-restore-the-wordpress-admin-menu/" target="_blank">',
	'</a>',
	'<a href="' . esc_url( add_query_arg( 'page', 'boldgrid-settings', 'admin.php' ) ) . '">',
	'</a>'
);

?>

<div class="boldgrid-button-wrapper-right">

<?php printf( '<a href="https://www.boldgrid.com/support/advanced-tutorials/" target="_blank"><span class="button button-secondary button-hero">%s</span></a>', __( 'Learn More', 'boldgrid-inspirations' ) ); ?>

</div>
<!-- End of Advanced For WordPress Users Section -->
