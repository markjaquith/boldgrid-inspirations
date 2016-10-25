<h3>Start Over</h3>

<?php
// Allow the user to add &show_start_over=1 to the url to force showing the start over
// feature.
$show_start_over = ( isset( $_GET['show_start_over'] ) && 1 == $_GET['show_start_over'] );

if ( true == $this->user_has_built_a_boldgrid_site() || true == $show_start_over ) {
	?>

<p>Starting over will let you start over with a fresh site to run the
	BoldGrid Inspirations installer again.</p>

<form method="post">

<?php wp_nonce_field( 'start_over' ); ?>

<div class='plugin-card col-xs-12 col-sm-10 col-md-10 col-lg-6'>
		<div class='plugin-card-top'>

			<strong>Pages, Posts, and Menus:</strong>

			<p>
				This <b>WILL</b> unpublish all of your pages and posts, and all of
				your menus <b>WILL</b> be deleted!
			</p>

			<input type="checkbox" id="start_over" name="start_over" value="Y" />

			<span>Yes, let me start fresh!</span> <span
				id="boldgrid-alert-remove" style="display: none;"> <b> WARNING: </b>
				Pressing the "Start Over" button below will move your pages and
				posts to your trash!
			</span><br /><br />

	<?php

	/**
	 * Allow an action after the "Start Over" option is printed.
	 *
	 * @since 1.2.12
	 */
	do_action( 'boldgrid_settings_after_start_fresh' );

	/**
	 * Give the user the option to start over with either / both their active / staging
	 * site.
	 *
	 * If the BoldGrid Staging plugin is installed, give the user the option to select which site to
	 * start over with. Otherwise, they will start over with their active site.
	 */
	// If the staging l
	if ( true == $this->staging_installed ) {
		?>
 		Which sites would you like to perform the above actions with?<br /> <input
				type="checkbox" name="start_over_active" value="start_over_active"
				checked> Active<br /> <input type="checkbox"
				name="start_over_staging" value="start_over_staging" checked>
			Staging<br /> <br />
 		<?php
	} else {
		?>
		<input type="hidden" name="start_over_active"
				value="start_over_active" class="hidden">
		<?php
	}
	?>

			<hr />
			<br /> <strong>BoldGrid Plugins and Themes:</strong><br /> <br />

			<?php if ( is_plugin_active( 'boldgrid-ninja-forms/ninja-forms.php' ) && ( current_user_can( 'delete_plugins' ) ||  function_exists( 'is_multisite' ) && is_multisite() && is_super_admin() ) ) { ?>
				<input type="checkbox" id="boldgrid_delete_forms"
				name="boldgrid_delete_forms" value="1" /> <span>Delete all BoldGrid
				Forms and Entries.</span><br /> <br />
			<?php } ?>

			<input type="checkbox" id="boldgrid_delete_themes"
				name="boldgrid_delete_themes" value="1" /> <span>Remove all BoldGrid
				Themes.</span>
		</div>
	</div>
	<div style='clear: both;'></div>

<?php
	// Print the "Start Over" button.
	submit_button( __( 'Start Over' ), 'secondary', 'submit', false,
		array (
			'id' => 'start_over_button'
		) );
	?>
</form>

<?php }else { ?>

<p>
	You do not have a BoldGrid site to delete! You can build a new website
	using <span class="dashicons-before dashicons-lightbulb"><?php

	printf( '<a href="%s">' . esc_html__( 'BoldGrid Inspirations' ) . '</a>.',
		esc_url( add_query_arg( 'page', 'boldgrid-inspirations', admin_url( 'admin.php' ) ) ) );
	?>
	</span>
</p>
<?php
}
?>

<hr />