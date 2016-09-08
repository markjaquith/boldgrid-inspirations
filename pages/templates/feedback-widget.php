<div id='feedback-notice-1-1'>
	<div id='feedback-notice-1-1-header'>
		<div id='feedback-notice-1-1-intro'>
			<p><?php echo __( 'We love feedback, both positive and negative.  It helps us build a better tool.' ); ?></p>
			<p><?php echo __( 'Please take a moment to send us some of your thoughts about BoldGrid.' ); ?></p>
		</div>
	</div>
	<div id='feedback-notice-1-1-content'>
		<form action='#' id='boldgrid-feedback-form' method='POST'>
		<?php wp_nonce_field( 'feedback-notice-1-1', 'feedback_auth' ); ?>
			<div class='feedback-form-label'><?php echo __( 'Feedback type' ); ?></div>
			<div>
				<select id='feedback-type' class='feedback-form-field'
					name='feedback_type'>
					<option value=''><?php echo __('Select'); ?>...</option>
					<option value='Theme design'><?php echo __('Theme design'); ?></option>
					<option value='General usability'><?php echo __('General usability'); ?></option>
					<option value='Feature suggestion'><?php echo __('Feature suggestion'); ?></option>
					<option value='Your host'><?php echo __( 'Your web hosting provider' ); ?></option>
					<option value='Bug report'><?php echo __('Bug report'); ?></option>
					<option value='Other'><?php echo __('Other'); ?></option>
				</select>
			</div>
			<div id='feedback-comment-area'>
				<div class='feedback-form-label'><?php echo __('Comment'); ?></div>
				<div>
					<textarea id='feedback-comment' class='feedback-form-field'
						name='comment' rows='4' cols='53'
						placeholder='<?php echo __('Please type your feedback comment here.'); ?>'></textarea>
				</div>
				<div class='feedback-form-label'></div>
				<div class='feedback-form-field'>
					<input type='checkbox' id='feedback-contact-checkbox'
						name='contact_me' value='Y' /> <label
						for='feedback-contact-checkbox'><?php echo __('Please contact me about my feedback'); ?></label>
				</div>
				<div id='feedback-email-address'>
					<div class='feedback-form-label'><?php echo __('Email address'); ?></div>
					<div class='feedback-form-field'>
						<input type='text' id='feedback-email' name='email_address'
							size='30' value='<?php echo $user_email; ?>'
							placeholder='<?php echo __('Please type your email address here.'); ?>'>
					</div>
				</div>
				<div id='feedback-diagnostic-report'>
					<div class='feedback-form-label'><?php echo __('Diagnostic report'); ?></div>
					<div class='feedback-form-field'>
						<textarea id='feedback-diagnostic-text' name='diagnostic_report'
							rows='4' cols='80' disabled='disabled'
							placeholder='<?php
							echo __(
								'This area will be populated with diagnostic data to better assist you.' );
							?>'></textarea>
					</div>
				</div>
				<div class='feedback-form-label'><?php echo __('Website Experience'); ?></div>
				<div>
					<select id='feedback-experience' class='feedback-form-field'
						name='experience'>
						<option value=''><?php echo __('Select'); ?>...</option>
						<option value='Just Started'><?php echo __('Just Started'); ?></option>
						<option value='1-2 Years'><?php echo __('1-2 Years'); ?></option>
						<option value='2-5 Years'><?php echo __('2-5 Years'); ?></option>
						<option value='6+ Years'><?php echo __( '6+ Years' ); ?></option>
					</select>
				</div>
				<div id='feedback-error-message'>
					<div class='feedback-form-label'></div>
					<div class='feedback-form-field'></div>
				</div>
				<div class='feedback-form-label'></div>
				<div class='feedback-form-field'>
					<button id='feedback-submit' class='button button-primary'
						disabled='disabled'>Submit</button>
					<span class="spinner"></span>
				</div>
			</div>
		</form>
	</div>
</div>
