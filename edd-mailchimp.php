<?php
/*
Plugin Name: Easy Digital Downloads - MailChimp
Plugin URL: http://easydigitaldownloads.com/extension/mail-chimp
Description: Include a MailChimp signup option with your Easy Digital Downloads checkout
Version: 2.5.3
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson, Dave Kiss
*/

define( 'EDD_MAILCHIMP_PRODUCT_NAME', 'Mail Chimp' );
define( 'EDD_MAILCHIMP_PATH', dirname( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| LICENSING / UPDATES
|--------------------------------------------------------------------------
*/

if ( class_exists( 'EDD_License' ) && is_admin() ) {
  $eddmc_license = new EDD_License( __FILE__, EDD_MAILCHIMP_PRODUCT_NAME, '2.5.3', 'Pippin Williamson' );
}

if( ! class_exists( 'EDD_MailChimp_API' ) ) {
	include( EDD_MAILCHIMP_PATH . '/includes/class-edd-mailchimp-api.php' );
}

if( ! class_exists( 'EDD_Newsletter' ) ) {
	include( EDD_MAILCHIMP_PATH . '/includes/class-edd-newsletter.php' );
}

if( ! class_exists( 'EDD_MailChimp' ) ) {
	include( EDD_MAILCHIMP_PATH . '/includes/class-edd-mailchimp.php' );
}

if( ! class_exists( 'EDD_MC_Ecommerce_360' ) ) {
	include( EDD_MAILCHIMP_PATH . '/includes/class-edd-ecommerce360.php' );
}

if ( ! class_exists( 'EDD_MC_Tools' ) && class_exists( 'Easy_Digital_Downloads' ) ) {
	if ( ( defined ( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '2.4.2', '>=' ) ) && is_admin() ) {
		include( EDD_MAILCHIMP_PATH . '/includes/class-edd-mailchimp-tools.php' );
	}
}

$edd_mc       = new EDD_MailChimp( 'mailchimp', 'MailChimp' );
$edd_mc360    = new EDD_MC_Ecommerce_360;

if ( class_exists( 'EDD_MC_Tools' ) ) {
	$edd_mc_tools = new EDD_MC_Tools();
}
