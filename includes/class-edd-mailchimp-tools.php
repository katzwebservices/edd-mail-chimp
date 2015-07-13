<?php

/**
 * EDD MailChimp Tools class
 *
 * @copyright   Copyright (c) 2015, Chris Klosowski
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5.3
*/
class EDD_MC_Tools {

	public function __construct() {

		$this->hooks();
		$this->filters();

	}

	private function hooks() {
		add_action( 'edd_tools_tab_general', array( $this, 'display_tools' ) );
		add_action( 'edd_register_batch_exporter', array( $this, 'register_batch_processor' ) );
	}

	private function filters() {

	}

	public function display_tools() {
		?>
		<div class="postbox">
			<h3><span><?php _e( 'Send sales data to Mailchimp', 'eddmc' ); ?></span></h3>
			<div class="inside">
				<form id="eddmc-send-data" class="edd-export-form" method="post">
					<?php echo EDD()->html->date_field( array( 'id' => 'edd-payment-export-start', 'name' => 'start', 'placeholder' => __( 'Choose start date', 'eddmc' ) ) ); ?>
					<?php echo EDD()->html->date_field( array( 'id' => 'edd-payment-export-end','name' => 'end', 'placeholder' => __( 'Choose end date', 'eddmc' ) ) ); ?>
					<?php wp_nonce_field( 'edd_ajax_export', 'edd_ajax_export' ); ?>
					<input type="hidden" name="edd-export-class" value="EDD_Batch_Mailchimp_Ecommerce"/>
					<span>
						<input type="submit" value="<?php _e( 'Send Sales Data', 'eddmc' ); ?>" class="button-secondary"/>
						<span class="spinner"></span>
					</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
		<?php
	}

	/**
	 * Register the batch processor
	 * @since  2.5.3
	 */
	function register_batch_processor() {
		add_action( 'edd_batch_export_class_include', array( $this, 'inclue_batch_processor' ), 10, 1 );
	}

	/**
	 * Loads the batch processor
	 *
	 * @since  2.5.3
	 * @param  string $class The class being requested to run for the batch export
	 * @return void
	 */
	function inclue_batch_processor( $class ) {

		if ( 'EDD_Batch_Mailchimp_Ecommerce' === $class ) {
			require_once EDD_MAILCHIMP_PATH . '/includes/class-edd-mailchimp-batch-processor.php';
		}

	}


}
