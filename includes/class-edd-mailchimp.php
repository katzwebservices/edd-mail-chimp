<?php
/**
 * EDD Mail Chimp class, extension of the EDD base newsletter classs
 *
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
*/

class EDD_MailChimp extends EDD_Newsletter {

	/**
	 * Sets up the checkout label
	 */
	public function init() {
		global $edd_options;
		if( ! empty( $edd_options['eddmc_label'] ) ) {
			$this->checkout_label = trim( $edd_options['eddmc_label'] );
		} else {
			$this->checkout_label = 'Signup for the newsletter';
		}

		add_filter( 'edd_settings_extensions_sanitize', array( $this, 'save_settings' ) );

	}

	/**
	 * Retrieves the lists from Mail Chimp
	 */
	public function get_lists() {

		global $edd_options;

		if( ! empty( $edd_options['eddmc_api'] ) ) {

			$list_data = get_transient( 'edd_mailchimp_lists' );
			if( false === $list_data ) {

				if( ! class_exists( 'EDD_MailChimp_API' ) ) {
					require_once( EDD_MAILCHIMP_PATH . '/includes/MailChimp.class.php' );
				}

				$api       = new EDD_MailChimp_API( trim( $edd_options['eddmc_api'] ) );
				$list_data = $api->call('lists/list');

				set_transient( 'edd_mailchimp_lists', $list_data, 24*24*24 );
			}
			
			if( $list_data ) {
				foreach( $list_data->data as $key => $list ) {

					$this->lists[ $list->id ] = $list->name;

				}
			}
		}

		return (array) $this->lists;
	}

	/**
	 * Registers the plugin settings
	 */
	public function settings( $settings ) {

		$eddmc_settings = array(
			array(
				'id'      => 'eddmc_settings',
				'name'    => '<strong>' . __( 'Mail Chimp Settings', 'eddmc' ) . '</strong>',
				'desc'    => __( 'Configure Mail Chimp Integration Settings', 'eddmc' ),
				'type'    => 'header'
			),
			array(
				'id'      => 'eddmc_api',
				'name'    => __( 'Mail Chimp API Key', 'eddmc' ),
				'desc'    => __( 'Enter your Mail Chimp API key', 'eddmc' ),
				'type'    => 'text',
				'size'    => 'regular'
			),
			array(
				'id'      => 'eddmc_show_checkout_signup',
				'name'    => __( 'Show Signup on Checkout', 'eddmc' ),
				'desc'    => __( 'Allow customers to signup for the list selected below during checkout?', 'eddmc' ),
				'type'    => 'checkbox'
			),
			array(
				'id'      => 'eddmc_list',
				'name'    => __( 'Choose a list', 'edda'),
				'desc'    => __( 'Select the list you wish to subscribe buyers to', 'eddmc' ),
				'type'    => 'select',
				'options' => $this->get_lists()
			),
			array(
				'id'      => 'eddmc_label',
				'name'    => __( 'Checkout Label', 'eddmc' ),
				'desc'    => __( 'This is the text shown next to the signup option', 'eddmc' ),
				'type'    => 'text',
				'size'    => 'regular'
			),
			array(
				'id'      => 'eddmc_double_opt_in',
				'name'    => __( 'Double Opt-In', 'eddmc' ),
				'desc'    => __( 'When checked, users will be sent a confirmation email after signing up, and will only be adding once they have confirmed the subscription.', 'eddmc' ),
				'type'    => 'checkbox'
			)
		);

		return array_merge( $settings, $eddmc_settings );
	}

	/**
	 * Flush the list transient on save
	 */
	public function save_settings( $input ) {
		if( isset( $input['eddmc_api'] ) ) {
			delete_transient( 'edd_mailchimp_lists' );
		}
		return $input;
	}

	/**
	 * Determines if the checkout signup option should be displayed
	 */
	public function show_checkout_signup() {
		global $edd_options;

		return ! empty( $edd_options['eddmc_show_checkout_signup'] );
	}

	/**
	 * Subscribe an email to a list
	 */
	public function subscribe_email( $user_info = array(), $list_id = false, $opt_in_overridde = false ) {

		global $edd_options;

		// Make sure an API key has been entered
		if( empty( $edd_options['eddmc_api'] ) ) {
			return false;
		}

		// Retrieve the global list ID if none is provided
		if( ! $list_id ) {
			$list_id = ! empty( $edd_options['eddmc_list'] ) ? $edd_options['eddmc_list'] : false;
			if( ! $list_id ) {
				return false;
			}
		}

		if( ! class_exists( 'EDD_MailChimp_API' ) ) {
			require_once( EDD_MAILCHIMP_PATH . '/includes/MailChimp.class.php' );
		}

		$api    = new EDD_MailChimp_API( trim( $edd_options['eddmc_api'] ) );
		$opt_in = isset( $edd_options['eddmc_double_opt_in'] ) && ! $opt_in_overridde;

		$result = $api->call('lists/subscribe', array(
			'id'                => $list_id,
			'email'             => array( 'email' => $user_info['email'] ),
			'merge_vars'        => array( 'FNAME' => $user_info['first_name'], 'LNAME' => $user_info['last_name'] ),
			'double_optin'      => $opt_in,
			'update_existing'   => true,
			'replace_interests' => false,
			'send_welcome'      => false,
		));
		
		if( $result ) {
			return true;
		}

		return false;

	}

}