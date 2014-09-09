<?php
/*
Plugin Name: Easy Digital Downloads - Mail Chimp
Plugin URL: http://easydigitaldownloads.com/extension/mail-chimp
Description: Include a Mail Chimp signup option with your Easy Digital Downloads checkout
Version: 2.4.3
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson, Dave Kiss
*/

define( 'EDD_MAILCHIMP_STORE_API_URL', 'https://easydigitaldownloads.com' );
define( 'EDD_MAILCHIMP_PRODUCT_NAME', 'Mail Chimp' );
define( 'EDD_MAILCHIMP_PATH', dirname( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| LICENSING / UPDATES
|--------------------------------------------------------------------------
*/

if ( class_exists( 'EDD_License' ) && is_admin() ) {
  $eddmc_license = new EDD_License( __FILE__, EDD_MAILCHIMP_PRODUCT_NAME, '2.4.3', 'Pippin Williamson' );
}

if( ! class_exists( 'EDD_MailChimp_API' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-edd-mailchimp-api.php' );
}

if( ! class_exists( 'EDD_Newsletter' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-edd-newsletter.php' );
}

if( ! class_exists( 'EDD_MailChimp' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-edd-mailchimp.php' );
}

if( ! class_exists( 'EDD_MC_Ecommerce_360' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-edd-ecommerce360.php' );
}

$edd_mc    = new EDD_MailChimp( 'mailchimp', 'Mail Chimp' );
$edd_mc360 = new EDD_MC_Ecommerce_360;