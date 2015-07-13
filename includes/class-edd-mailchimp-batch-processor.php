<?php
/**
 * Mailchimp eCommerce360 Batch Processor
 *
 * This class handles payment sending to Mailchimp in atches
 *
 * @package     EDD_MC
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2015, Chris Klosowski
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDD_Batch_Payments_Export Class
 *
 * @since 2.4
 */
class EDD_Batch_Mailchimp_Ecommerce extends EDD_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var string
	 * @since 2.4
	 */
	public $export_type = 'mailchimp_ecommerce_data';

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 2.4
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @return array $data The data for the CSV file
	 */
	public function send_data() {
		global $wpdb;

		$data = array();

		$args = array(
			'number'   => 30,
			'page'     => $this->step,
			'status'   => 'publish'
		);

		if( ! empty( $this->start ) || ! empty( $this->end ) ) {

			$args['date_query'] = array(
				array(
					'after'     => date( 'Y-n-d H:i:s', strtotime( $this->start ) ),
					'before'    => date( 'Y-n-d H:i:s', strtotime( $this->end ) ),
					'inclusive' => true
				)
			);

		}

		//echo json_encode($args ); exit;

		$payments = edd_get_payments( $args );

		if( $payments ) {

			$mailchimp_ecommerce = new EDD_MC_Ecommerce_360();

			foreach ( $payments as $payment ) {

				$mailchimp_ecommerce->record_ecommerce360_purchase( $payment->ID );

			}

			return true;

		}

		return false;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 2.4
	 * @return int
	 */
	public function get_percentage_complete() {

		$status = 'publish';
		$args   = array(
			'start-date' => date( 'Y-n-d H:i:s', strtotime( $this->start ) ),
			'end-date'   => date( 'Y-n-d H:i:s', strtotime( $this->end ) ),
		);

		$total = edd_count_payments( $args )->$status;

		$percentage = 100;

		if( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the payments export
	 *
	 * @since 2.4.2
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->start  = isset( $request['start'] )  ? sanitize_text_field( $request['start'] )  : '';
		$this->end    = isset( $request['end']  )   ? sanitize_text_field( $request['end']  )   : '';
	}

	/**
	 * Process a step
	 *
	 * @since 2.4
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'edd' ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
		}

		$rows = $this->send_data();

		if( $rows ) {
			return true;
		} else {
			return false;
		}
	}

	public function headers() {
		ignore_user_abort( true );

		if ( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )
			set_time_limit( 0 );

		nocache_headers();
		header( "Location: " . admin_url( 'edit.php?post_type=download&page=edd-tools&tab=general' ) );
	}

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since 2.4
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		edd_die();
	}

}
