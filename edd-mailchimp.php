<?php
/*
Plugin Name: Easy Digital Downloads - Mail Chimp
Plugin URL: http://easydigitaldownloads.com/extension/mail-chimp
Description: Include a Mail Chimp signup option with your Easy Digital Downloads checkout
Version: 1.0.9
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson
*/

define( 'EDD_MAILCHIMP_STORE_API_URL', 'https://easydigitaldownloads.com' );
define( 'EDD_MAILCHIMP_PRODUCT_NAME', 'Mail Chimp' );


/*
|--------------------------------------------------------------------------
| LICENSING / UPDATES
|--------------------------------------------------------------------------
*/

if( ! class_exists( 'EDD_License' ) ) {
	include( dirname( __FILE__ ) . '/EDD_License_Handler.php' );
}
$eddmc_license = new EDD_License( __FILE__, EDD_MAILCHIMP_PRODUCT_NAME, '1.0.9', 'Pippin Williamson' );

/*
|--------------------------------------------------------------------------
| INTERNATIONALIZATION
|--------------------------------------------------------------------------
*/

function eddmc_textdomain() {

	// Set filter for plugin's languages directory
	$edd_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$edd_lang_dir = apply_filters( 'eddmc_languages_directory', $edd_lang_dir );

	// Load the translations
	load_plugin_textdomain( 'eddmc', false, $edd_lang_dir );
}
add_action('init', 'eddmc_textdomain');


// adds the settings to the Misc section
function eddmc_add_settings($settings) {

  $eddmc_settings = array(
		array(
			'id' => 'eddmc_settings',
			'name' => '<strong>' . __('Mail Chimp Settings', 'eddmc') . '</strong>',
			'desc' => __('Configure Mail Chimp Integration Settings', 'eddmc'),
			'type' => 'header'
		),
		array(
			'id' => 'eddmc_api',
			'name' => __('Mail Chimp API Key', 'eddmc'),
			'desc' => __('Enter your Mail Chimp API key', 'eddmc'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddmc_list',
			'name' => __('Choose a list', 'edda'),
			'desc' => __('Select the list you wish to subscribe buyers to', 'eddmc'),
			'type' => 'select',
			'options' => eddmc_get_mailchimp_lists()
		),
		array(
			'id' => 'eddmc_label',
			'name' => __('Checkout Label', 'eddmc'),
			'desc' => __('This is the text shown next to the signup option', 'eddmc'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddmc_double_opt_in',
			'name' => __('Double Opt-In', 'eddmc'),
			'desc' => __('When checked, users will be sent a confirmation email after signing up, and will only be adding once they have confirmed the subscription.', 'eddmc'),
			'type' => 'checkbox'
		)
	);

	return array_merge($settings, $eddmc_settings);
}
add_filter('edd_settings_misc', 'eddmc_add_settings');


// get an array of all mailchimp subscription lists
function eddmc_get_mailchimp_lists() {

	global $edd_options, $pagenow, $edd_settings_page;

	if( ! isset( $_GET['page'] ) || ! isset( $_GET['tab'] ) || $_GET['page'] != 'edd-settings' || $_GET['tab'] != 'misc' )
		return;
	if( isset( $edd_options['eddmc_api'] ) && strlen( trim( $edd_options['eddmc_api'] ) ) > 0 ) {

		if( !class_exists( 'MCAPI' ) )
			require_once('mailchimp/MCAPI.class.php');

		$lists = get_transient( 'sedd_mailchimp_lists' );
		if( false === $lists ) {

			$api = new MCAPI($edd_options['eddmc_api']);
			$list_data = $api->lists();

			if($list_data) :
				foreach($list_data['data'] as $key => $list) :
					$lists[$list['id']] = $list['name'];
				endforeach;
			endif;
			set_transient( 'edd_mailchimp_lists', $lists, 24*24*24 );
		}
		return $lists;
	}
	return array();
}

// adds an email to the mailchimp subscription list
function eddmc_subscribe_email( $user_info ) {
	global $edd_options;

	if( ! empty( $edd_options['eddmc_api'] ) ) {
		if( !class_exists( 'MCAPI' ) )
			require_once('mailchimp/MCAPI.class.php');
		$api = new MCAPI( trim( $edd_options['eddmc_api'] ) );
		$opt_in = isset($edd_options['eddmc_double_opt_in']) ? true : false;
		if( $api->listSubscribe( $edd_options['eddmc_list'], $user_info['email'], array( 'FNAME' => $user_info['first_name'], 'LNAME' => $user_info['last_name'] ), 'html', $opt_in ) === true) {
			return true;
		}
	}

	return false;
}

// displays the mailchimp checkbox
function eddmc_mailchimp_fields() {
	global $edd_options;
	$label = ! empty( $edd_options['eddmc_label'] ) ? $edd_options['eddmc_label'] : __( 'Sign up for our mailing list', 'eddmc' );
	ob_start();
		if( ! empty( $edd_options['eddmc_api'] ) ) { ?>
		<fieldset id="edd_mailchimp">
			<p>
				<input name="eddmc_mailchimp_signup" id="eddmc_mailchimp_signup" type="checkbox" checked="checked"/>
				<label for="eddmc_mailchimp_signup"><?php echo $label; ?></label>
			</p>
		</fieldset>
		<?php
	}
	echo ob_get_clean();
}
add_action('edd_purchase_form_before_submit', 'eddmc_mailchimp_fields', 100);

// checks whether a user should be signed up for he mailchimp list
function eddmc_check_for_email_signup( $posted, $user_info, $valid_data ) {
	if( isset( $posted['eddmc_mailchimp_signup'] ) ) {

		eddmc_subscribe_email( $user_info );
	}
}
add_action('edd_checkout_before_gateway', 'eddmc_check_for_email_signup', 10, 3 );
