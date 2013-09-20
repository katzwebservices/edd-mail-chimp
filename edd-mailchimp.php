<?php
/*
Plugin Name: Easy Digital Downloads - Mail Chimp
Plugin URL: http://easydigitaldownloads.com/extension/mail-chimp
Description: Include a Mail Chimp signup option with your Easy Digital Downloads checkout
Version: 2.0.1
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson
*/

define( 'EDD_MAILCHIMP_STORE_API_URL', 'https://easydigitaldownloads.com' );
define( 'EDD_MAILCHIMP_PRODUCT_NAME', 'Mail Chimp' );
define( 'EDD_MAILCHIMP_PATH', dirname( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| LICENSING / UPDATES
|--------------------------------------------------------------------------
*/

if( ! class_exists( 'EDD_License' ) ) {
	include( dirname( __FILE__ ) . '/EDD_License_Handler.php' );
}
$eddmc_license = new EDD_License( __FILE__, EDD_MAILCHIMP_PRODUCT_NAME, '2.0.1', 'Pippin Williamson' );


if( ! class_exists( 'EDD_Newsletter' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-edd-newsletter.php' );
}
include( dirname( __FILE__ ) . '/includes/class-edd-mailchimp.php' );

$edd_mc = new EDD_MailChimp( 'mailchimp', 'Mail Chimp' );
