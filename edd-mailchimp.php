<?php
/*
Plugin Name: Easy Digital Downloads - Mail Chimp
Plugin URL: http://easydigitaldownloads.com/extension/mail-chimp
Description: Include a Mail Chimp signup option with your Easy Digital Downloads checkout
Version: 1.0.5
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

function eddmc_updater() {

	if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
		// load our custom updater
		include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
	}

	global $edd_options;

	// retrieve our license key from the DB
	$eddmc_license_key = isset( $edd_options['eddmc_license_key'] ) ? trim( $edd_options['eddmc_license_key'] ) : '';

	// setup the updater
	$edd_cr_updater = new EDD_SL_Plugin_Updater( EDD_MAILCHIMP_STORE_API_URL, __FILE__, array(
			'version' 	=> '1.0.5', 		// current version number
			'license' 	=> $eddmc_license_key, // license key (used get_option above to retrieve from DB)
			'item_name' => EDD_MAILCHIMP_PRODUCT_NAME, // name of this plugin
			'author' 	=> 'Pippin Williamson'  // author of this plugin
		)
	);
}
add_action( 'admin_init', 'eddmc_updater' );

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
			'id' => 'eddmc_license_key',
			'name' => __('License Key', 'edd_creddmc'),
			'desc' => __('Enter your license for EDD Mail Chimp to receive automatic upgrades', 'eddmc'),
			'type' => 'text',
			'size' => 'regular'
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

// activate the license key for automatic upgrades
function eddmc_activate_license() {
	global $edd_options;
	if( ! isset( $_POST['edd_settings_misc'] ) )
		return;
	if( ! isset( $_POST['edd_settings_misc']['eddmc_license_key'] ) )
		return;

	if( get_option( 'eddmc_license_active' ) == 'valid' )
		return;

	$license = sanitize_text_field( $_POST['edd_settings_misc']['eddmc_license_key'] );

	// data to send in our API request
	$api_params = array(
		'edd_action'=> 'activate_license',
		'license' 	=> $license,
		'item_name' => urlencode( EDD_MAILCHIMP_PRODUCT_NAME ) // the name of our product in EDD
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, EDD_MAILCHIMP_STORE_API_URL ) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) )
		return false;

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	update_option( 'eddmc_license_active', $license_data->license );

}
add_action( 'admin_init', 'eddmc_activate_license' );


// get an array of all mailchimp subscription lists
function eddmc_get_mailchimp_lists() {

	global $edd_options, $pagenow, $edd_settings_page;

	if( ! isset( $_GET['page'] ) || ! isset( $_GET['tab'] ) || $_GET['page'] != 'edd-settings' || $_GET['tab'] != 'misc' )
		return;

	if( isset( $edd_options['eddmc_api'] ) && strlen( trim( $edd_options['eddmc_api'] ) ) > 0 ) {

		$lists = array();
		if( !class_exists( 'MCAPI' ) )
			require_once('mailchimp/MCAPI.class.php');
		$api = new MCAPI($edd_options['eddmc_api']);
		$list_data = $api->lists();
		if($list_data) :
			foreach($list_data['data'] as $key => $list) :
				$lists[$list['id']] = $list['name'];
			endforeach;
		endif;
		return $lists;
	}
	return array();
}

// adds an email to the mailchimp subscription list
function eddmc_subscribe_email($email) {
	global $edd_options;

	if( isset( $edd_options['eddmc_api'] ) && strlen( trim( $edd_options['eddmc_api'] ) ) > 0 ) {
		if( !class_exists( 'MCAPI' ) )
			require_once('mailchimp/MCAPI.class.php');
		$api = new MCAPI($edd_options['eddmc_api']);
		$opt_in = isset($edd_options['eddmc_double_opt_in']) ? true : false;
		if($api->listSubscribe($edd_options['eddmc_list'], $email, '', 'html', $opt_in) === true) {
			return true;
		}
	}

	return false;
}

// displays the mailchimp checkbox
function eddmc_mailchimp_fields() {
	global $edd_options;
	ob_start();
		if( isset( $edd_options['eddmc_api'] ) && strlen( trim( $edd_options['eddmc_api'] ) ) > 0 ) { ?>
		<p>
			<input name="eddmc_mailchimp_signup" id="eddmc_mailchimp_signup" type="checkbox" checked="checked"/>
			<label for="eddmc_mailchimp_signup"><?php echo isset($edd_options['eddmc_label']) ? $edd_options['eddmc_label'] : __('Sign up for our mailing list', 'eddmc'); ?></label>
		</p>
		<?php
	}
	echo ob_get_clean();
}
add_action('edd_purchase_form_before_submit', 'eddmc_mailchimp_fields', 100);

// checks whether a user should be signed up for he mailchimp list
function eddmc_check_for_email_signup( $posted, $user_info, $valid_data ) {
	if( isset($posted['eddmc_mailchimp_signup']) ) {

		eddmc_subscribe_email( $user_info );
	}
}
add_action('edd_checkout_before_gateway', 'eddmc_check_for_email_signup', 10, 3 );
