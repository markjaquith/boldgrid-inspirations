<?php
// Prevent direct calls.
require BOLDGRID_BASE_DIR . '/pages/templates/restrict-direct-access.php';

?>
<div class="wrap">
	<?php if (false == $error) { ?>
    <h2>Installing Theme: <?php echo esc_html( $theme_label ); ?></h2>
    <p>
        Downloaded install package...
    </p>
    <p>Unpacked the package…</p>
    <p>Installed the theme…</p>
    <p>
        Successfully installed the theme <strong><?php echo esc_html( $theme_label ); ?></strong>.
    </p>
    <p>
        <a title="Enable theme for this site"
        href="<?php echo esc_url( $enable_theme_url ); ?>">Enable Theme</a> |
        <a title="Return to Theme Installer"
            href="<?php echo esc_url( admin_url( 'themes.php' ) ) ?>">View Themes</a>
    </p>
    <?php } else { ?>

        <div class="error">
	        <p>An error occurred while updating your theme.</p>
	    </div>

    <?php } ?>
</div>
