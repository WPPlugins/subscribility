<?php
/**
 * Wrapper class to handle the registration form
 */

class WP99234_Forms {

	var $submit_name = 'wp99234_form_submit';

	var $nonce_name = 'wp99234_form_nonce';

	var $nonce_action = 'wp99234_form_submit';

	var $template;

	var $errors = array();

	function __construct() {

		if (!session_id()) {
			session_start();
		}

		$this->setup_actions();

	}

	function setup_actions() {

		add_action('init', array($this, 'init'));

	}

	function init() {

		if (isset($_POST[$this->submit_name])) {

			if (!wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) {
				wp_die(__('Invalid Form. Please Refresh Page.', 'wp99234'));
			}

			$this->handle_submit();

		}

	}

	/**
	 * Handle the form submission.
	 *
	 * @return bool
	 */
	function handle_submit() {
		wp_die(__('Invalid Form Submission Handler', 'wp99234'));
	}

	/**
	 * Validate the given value against the given rules.
	 *
	 * @param $key
	 * @param $value
	 * @param $rules
	 *
	 * @return mixed
	 */
	public function validate_field($key, $value, $rules) {

		if (isset($this->errors[$key])) {
			return $value;
		}

		foreach ($rules as $method => $check) {

			if (is_array($check)) {
				$msg = $check['error_msg'];
				$contains = $check['check_val'];
			} else {
				$msg = $check;
			}
      
			switch ($method) {

				case 'required':

					if (!$value || $value === '') {
						$this->errors[$key] = $msg;
					}

					break;

				case 'is_email':

					if ($value != '' && !is_email($value)) {
						$this->errors[$key] = $msg;
					}

					break;

				case 'is_numeric':

					if ($value != '' && !is_numeric($value)) {
						$this->errors[$key] = $msg;
					}

					break;
				case 'is_phone':
					if ($value != '' && !WC_Validation::is_phone($value)) {
						$this->errors[$key] = $msg;
					}
					break;
          
				case 'contains':
					if (strpos($value, $contains) === false) {
						$this->errors[$key] = $msg;
					}
					break;
			}

		}

		return $value;

	}

	/**
	 * Get the form HTML.
	 *
	 * @return string
	 */
	public function get_form() {

		if (!empty($this->errors)) {
			foreach ($this->errors as $error) {
				wc_add_notice($error, 'error');
			}
		}

		return WP99234()->template->get_template($this->template);

	}

	function display_field($key, $field) {
		$type = (isset($field['type']))?$field['type']:'text';

		?>
		<p class="form-row <?php echo $key;?>">

		<?php /*if ( isset( $this->errors[ $key ] ) ): ?>
		<p class="error">
		<?php echo esc_html( $this->errors[ $key ] ); ?>
		</p>
		<?php endif;*/?>

		<?php 
		if ($type != 'select') {
			$value = (isset($_POST[$key]))?$_POST[$key]:$field['default'];
		} else {
			$value = (isset($_POST[$key]))?$_POST[$key]:$field['default'];
			$options = $field['options'];
		}
		?>

		            <label for="<?php echo $key;?>">
		<?php echo $field['label'];?>

		<?php $attributes = '';?>
		                <?php if (isset($field['attributes'])):
		foreach ($field['attributes'] as $_key => $val) {
			$attributes .= sprintf(' %s="%s"', $_key, esc_attr($val));
		}
		endif;?>
		<?php
		switch ($type):

		case 'textarea':
			echo "<textarea $attributes name=\"" .esc_attr($key)."\">".esc_textarea($value)."</textarea>";
			break;
		
		case 'password':
			echo "<input $attributes type=\"password\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" id='".$field['id']."' />";
			break;	

		case 'select':
			echo "<select $attributes name=\"" . esc_attr($key) . "\" id='" . $field['id'] . "'>";  
			foreach ($options as $option) {
				if ($option == $value) {
					echo "<option value='$option' selected='selected'>$option</option>";
				} else {
					echo "<option value='$option'>$option</option>";
				}
			}
			echo "</select>";
			break;
    
		case 'hidden':
			echo "<input $attributes type=\"hidden\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" />";
			break;
    
		case 'text':
		default:
			echo "<input $attributes type=\"text\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" />";
			break;

		endswitch;
		?>
		</label>

		</p>
		<?php

	}

		/**
	 * Flag to test if the given user is registered to a given membership already.
	 *
	 * @param $user_id
	 * @param $membership_id
	 *
	 * @return bool
	 */
		public function user_is_registered_for_membership($user_id, $membership_id) {

			$user_memberships = get_user_meta($user_id, 'current_memberships', true);

			if ($user_memberships && is_array($user_memberships)) {

				if (isset($user_memberships[$membership_id])) {
					return true;
				}

		}

		return false;

	}

}