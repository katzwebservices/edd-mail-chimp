<?php
/**
 * Base newsletter class
 *
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
*/

class EDD_Newsletter {

	/*************************************************************************************
	 *
	 * The functions in this section must be overwritten by the extension using this class
	 *
	 ************************************************************************************/


	/**
	 * Defines the default label shown on checkout
	 *
	 * Other things can be done here if necessary, such as additional filters or actions
	 */
	public function init() {
		$this->checkout_label = 'Signup for the newsletter';
	}

	/**
	 * Retrieve the newsletter lists
	 *
	 * Must return an array like this:
	 *   array(
	 *     'some_id'  => 'value1',
	 *     'other_id' => 'value2'
	 *   )
	 */
	public function get_lists() {
		return (array)$this->lists;
	}

	/**
	 * Determines if the signup checkbox should be shown on checkout
	 *
	 */
	public function show_checkout_signup() {
		return true;
	}

	/**
	 * Subscribe an customer to a list
	 *
	 * $user_info is an array containing the user ID, email, first name, and last name
	 *
	 * $list_id is the list ID the user should be subscribed to. If it is false, sign the user
	 * up for the default list defined in settings
	 *
	 */
	public function subscribe_email( $user_info = array(), $list_id = false ) {
		return true;
	}

	/**
	 * Register the plugin settings
	 *
	 */
	public function settings( $settings ) {
		return $settings;
	}


	/*************************************************************************************
	 *
	 * The properties and functions in this section may be overwritten by the extension using this class
	 * but are not mandatory
	 *
	 ************************************************************************************/

	/**
	 * The ID for this newsletter extension, such as 'mailchimp'
	 */
	public $id;

	/**
	 * The label for the extension, probably just shown as the title of the metabox
	 */
	public $label;

	/**
	 * Newsletter lists retrieved from the API
	 */
	public $lists;

	/**
	 * Text shown on the checkout, if none is set in the settings
	 */
	public $checkout_label;

	/**
	 * Class constructor
	 */
	public function __construct( $_id = 'newsletter', $_label = 'Newsletter' ) {

		global $edd_options;

		$this->id    = $_id;
		$this->label = $_label;

		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_filter( 'edd_metabox_fields_save', array( $this, 'save_metabox' ) );
		add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );
		add_action( 'edd_purchase_form_before_submit', array( $this, 'checkout_fields' ), 100 );
		add_action( 'edd_checkout_before_gateway', array( $this, 'check_for_email_signup' ), 10, 3 );

		$this->init();

	}

	/**
	 * Load the plugin's textdomain
	 */
	public function textdomain() {
		// Load the translations
		load_plugin_textdomain( 'edd_' . $this->id, false, EDD_MAILCHIMP_PATH . '/languages/' );
	}

	/**
	 * Output the signup checkbox on the checkout screen, if enabled
	 */
	public function checkout_fields() {
		global $edd_options;

		if( ! $this->show_checkout_signup() )
			return;

		ob_start(); ?>
		<fieldset id="edd_<?php echo $this->id; ?>">
			<p>
				<input name="edd_<?php echo $this->id; ?>_signup" id="edd_<?php echo $this->id; ?>_signup" type="checkbox" checked="checked"/>
				<label for="edd_<?php echo $this->id; ?>_signup"><?php echo $this->checkout_label; ?></label>
			</p>
		</fieldset>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Check if a customer needs to be subscribed
	 */
	public function check_for_email_signup( $posted, $user_info, $valid_data ) {

		// Check for global newsletter
		if( isset( $posted['edd_' . $this->id . '_signup'] ) ) {

			$this->subscribe_email( $user_info );

		}

		// Check for product specific newsletter
		$cart_items = edd_get_cart_contents();
		if( ! empty( $cart_items ) ) {
			foreach( $cart_items as $cart_item ) {

				$lists = get_post_meta( $cart_item['id'], '_edd_' . esc_attr( $this->id ), true );

				if( empty( $lists ) )
					continue;

				foreach( $lists as $list ) {

					$this->subscribe_email( $user_info, $list );

				}

			}
		}
	}

	/**
	 * Register the metabox on the 'download' post type
	 */
	public function add_metabox() {
		if ( current_user_can( 'edit_product', get_the_ID() ) ) {
			add_meta_box( 'edd_' . $this->id, $this->label, array( $this, 'render_metabox' ), 'download', 'side' );
		}
	}

	/**
	 * Display the metabox, which is a list of newsletter lists
	 */
	public function render_metabox() {

		global $post;

		echo '<p>' . __( 'Select the lists you wish buyers to be subscribed to when purchasing.' ) . '</p>';

		$checked = (array) get_post_meta( $post->ID, '_edd_' . esc_attr( $this->id ), true );
		foreach( $this->get_lists() as $list_id => $list_name ) {
			echo '<label>';
				echo '<input type="checkbox" name="_edd_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
				echo '&nbsp;' . $list_name;
			echo '</label><br/>';
		}
	}

	/**
	 * Save the metabox
	 */
	public function save_metabox( $fields ) {

		$fields[] = '_edd_' . esc_attr( $this->id );
		return $fields;
	}

}