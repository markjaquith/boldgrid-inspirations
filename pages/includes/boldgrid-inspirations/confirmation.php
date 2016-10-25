<?php

/**
 * Inspirations Confirmation message
 *
 * This file is intended to produced the confirmation message for the user in the last step of
 * Inspirations.
 *
 * @since 1.2.3
 */

// Language strings.
$lang = array(

	/*
	 * Confirmation Text.
	 *
	 * This is the text displayed to the user before any decision options, notes, or buttons.
	 */
	'selected_inspiration'   => __( 'You have completed selecting your Inspiration. Before you can add your own personal touches, we\'ll first need to install this Inspiration.', 'boldgrid-inspiration' ),
	'have_default_content'   => __( 'We have detected you have default WordPress content as your current website, so we will remove the default content as we install your new website.', 'boldgrid-inspiration' ),
	'detected_existing_site' => __( 'We have detected you have an existing website.', 'boldgrid-inspirations' ),
	'how_to_install'         => __( 'How would you like to install this site?', 'boldgrid-inspirations' ),

	/*
	 * Confirmation Choices.
	 *
	 * IE, do you want to install as active or staging?
	 */
	'make_new_my_website'    => __( 'Make this new Inspiration my website.', 'boldgrid-inspirations' ),
	'make_new_my_staging'    => __( 'Make this new Inspiration my Staging website.', 'boldgrid-inspirations' ),
	'make_staged'            => __( 'Keep my existing website available for visitors and make this new Inspiration a "Staged Website".', 'boldgrid-inspirations' ),
	'recommended'            => __( 'Recommended', 'boldgrid-inspirations' ),

	/*
	 * Confirmation choice notes.
	 *
	 * IE, the grey text displayed as you select different install options.
	 */
	'note_overwrite'         => __( 'Fastest and Easiest. Your current theme will be saved but deactivated. Your current pages will be moved to the Trash and can be recovered from the Trash if needed.', 'boldgrid-inspirations' ),
	'note_overwrite_staging' => __( 'Your current staged theme will be saved but deactivated. Your current staged pages will be moved to the Trash and can be recovered from the Trash if needed.', 'boldgrid-inspirations' ),
	'note_download_staging'  => __( 'Typically used by more advanced webmasters. Your current website stays running while you modify the "Staged Site". Once complete, you launch the Staged Site.', 'boldgrid-inspirations' ),

	/*
	 * Buttons.
	 *
	 * The "Go back" and "Install" buttons.
	 */
	'go_back'                => __( 'Go back', 'boldgrid-inspirations' ),
	'install_this_website'   => __( 'Install this website!', 'boldgrid-inspirations' ),

	// Other.
	'note'                   => __( 'Note', 'boldgrid-inspirations' ),
);

$template = '
	<div class="wrap confirmation hidden">
		<div class="boldgrid-plugin-card">
			<div class="top">
				%s
				<p class="note-overwrite">'         . $lang['note'] . ': <em>' . $lang['note_overwrite']         . '</em></p>
				<p class="note-overwrite-staging">' . $lang['note'] . ': <em>' . $lang['note_overwrite_staging'] . '</em></p>
				<p class="note-download-staging">'  . $lang['note'] . ': <em>' . $lang['note_download_staging']  . '</em></p>
			</div>
			<div id="select-install-type" class="bottom">
				<button class="go-back button button-secondary">' . $lang['go_back'] . '</button>
				<button class="button button-primary install-this-website" data-start-over="true" >' . $lang['install_this_website'] . '</button>
			</div>
		</div>
	</div>
';

// Generate an array of scenario data. This will be used in the switch statement immediately below.
$scenario = array(
	$mode_data['has_blank_active_site'],
	$mode_data['has_active_bg_site'],
	$mode_data['has_staged_site'],
	$mode_data['staging_active'],
	$mode_data['staging_installed'],
);

/*
 * Create the message for the user in the final step of inspirations.
 *
 * That message is built based upon the user's current scenario. For example, do they have an
 * existing website, do they have the Staging plugin installed, etc.
 */
switch( $scenario ) {
	/*
	 * [T] Has blank active site.
	 * [ ] Has active BG site.
	 * [ ] Has staged site.
	 * [ ] Staging is active.
	 * [ ] Staging is installed.
	 */
	case array( true, false, false, false, false ):
		$top = '<p>' . $lang['selected_inspiration'] . ' ' . $lang['have_default_content'] . '</p>';
		break;

	/*
	 * [T] Has blank active site.
	 * [ ] Has active BG site.
	 * [ ] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( true, false, false, true, true ):
		$top = '
			<p>' .
					$lang['selected_inspiration'] . ' ' .
					$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="install-as-active" checked>' . $lang['make_new_my_website'] . ' (' . $lang['recommended'] . ')<br />
				<input type="radio" name="install-decision" value="install-as-staging">' . $lang['make_new_my_staging'] . '
			</p>
		';
		break;

	/*
	 * [ ] Has blank active site.
	 * [T] Has active BG site.
	 * [ ] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, true, false, true, true ):
	/*
	 * [ ] Has blank active site.
	 * [ ] Has active BG site.
	 * [ ] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, false, false, true, true ):
		$top = '
			<p>' .
					$lang['selected_inspiration'] . ' ' .
					$lang['detected_existing_site'] . ' ' .
					$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="overwrite-active">' . $lang['make_new_my_website'] . '<br />
				<input type="radio" name="install-decision" value="install-as-staging" checked>' . $lang['make_staged'] . '
			</p>
		';
		break;

	/*
	 * [ ] Has blank active site.
	 * [T] Has active BG site.
	 * [T] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, true, true, true, true ):
	/*
	 * [ ] Has blank active site.
	 * [ ] Has active BG site.
	 * [T] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, false, true, true, true ):
		$top = '
			<p>' .
					$lang['selected_inspiration'] . ' ' .
					$lang['detected_existing_site'] . ' ' .
					$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="overwrite-active">' . $lang['make_new_my_website'] . '<br />
				<input type="radio" name="install-decision" value="overwrite-staging" checked>' . $lang['make_new_my_staging'] . '
			</p>
		';
		break;

	/*
	 * [T] Has blank active site.
	 * [ ] Has active BG site.
	 * [T] Has staged site.
	 * [T] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( true, false, true, true, true ):
		$top = '
			<p>' .
				$lang['selected_inspiration'] . ' ' .
				$lang['detected_existing_site'] . ' ' .
				$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="install-as-active" checked>' . $lang['make_new_my_website'] . '<br />
				<input type="radio" name="install-decision" value="overwrite-staging">' . $lang['make_new_my_staging'] . '
			</p>
		';
		break;

	/*
	 * [ ] Has blank active site.
	 * [T] Has active BG site.
	 * [ ] Has staged site.
	 * [ ] Staging is active.
	 * [ ] Staging is installed.
	 */
	case array( false, true, false, false, false ):
	/*
	 * [ ] Has blank active site.
	 * [ ] Has active BG site.
	 * [ ] Has staged site.
	 * [ ] Staging is active.
	 * [ ] Staging is installed.
	 */
	case array( false, false, false, false, false ):
		$top = '
			<p>' .
				$lang['selected_inspiration'] . ' ' .
				$lang['detected_existing_site'] . ' ' .
				$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="overwrite-active">' . $lang['make_new_my_website'] . '<br />
				<input type="radio" name="install-decision" value="download-staging" checked>' . $lang['make_staged'] . '
			</p>
		';
		break;

	/*
	 * [ ] Has blank active site.
	 * [T] Has active BG site.
	 * [ ] Has staged site.
	 * [ ] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, true, false, false, true ):
	/*
	 * [ ] Has blank active site.
	 * [ ] Has active BG site.
	 * [ ] Has staged site.
	 * [ ] Staging is active.
	 * [T] Staging is installed.
	 */
	case array( false, false, false, false, true ):
		$top = '
			<p>' .
				$lang['selected_inspiration'] . ' ' .
				$lang['detected_existing_site'] . ' ' .
				$lang['how_to_install'] . '
			</p>
			<p>
				<input type="radio" name="install-decision" value="overwrite-active">' . $lang['make_new_my_website'] . '<br />
				<input type="radio" name="install-decision" value="activate-staging" checked>' . $lang['make_staged'] . '
			</p>
		';
		break;

	/*
	 * [?] Has blank active site.
	 * [?] Has active BG site.
	 * [?] Has staged site.
	 * [?] Staging is active.
	 * [?] Staging is installed.
	 */
	default:
		$top = '<p>' . $lang['selected_inspiration'] . ' ' . $lang['have_default_content'] . '</p>';
		break;
}

printf( $template, $top );

?>