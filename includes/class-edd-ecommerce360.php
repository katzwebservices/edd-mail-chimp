<?php

/**
 * EDD MailChimp Ecommerce360 class
 *
 * @copyright   Copyright (c) 2014, Dave Kiss
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
*/
class EDD_MC_Ecommerce_360 {

  /**
   * MailChimp API Key
   *
   * @var string | NULL
   */
  public $key = NULL;

  public function __construct() {

    if( ! function_exists( 'edd_get_option' ) ) {
      return;
    }

    $api_key = edd_get_option( 'eddmc_api', '' );

    if ( ! empty( $api_key ) ) {
      $this->key = trim( $api_key );
    }

    add_action( 'init', array( $this, 'set_ecommerce360_session' ) );

    add_action( 'edd_insert_payment', array( $this, 'set_ecommerce360_flags' ), 10, 2 );
    add_action( 'edd_complete_purchase', array( $this, 'record_ecommerce360_purchase' ) );
    add_action( 'edd_update_payment_status', array( $this, 'delete_ecommerce360_purchase' ), 10, 3 );
  }

  /**
   * Sets flags in post meta so that we can detect them when completing a purchase via IPN
   *
   * @param  integer $payment_id
   * @param  array $payment_data
   * @return bool
   */
  public function set_ecommerce360_flags( $payment_id = 0, $payment_data = array() ) {

    // Make sure an API key has been entered
    if ( empty( $this->key ) ) {
      return FALSE;
    }

    // Don't record details if we're in test mode
    if ( edd_is_test_mode() ) {
      return FALSE;
    }

    $mc_cid_key  = self::_edd_ec360_get_session_id( 'campaign' );
    $mc_eid_key  = self::_edd_ec360_get_session_id( 'email' );

    $campaign_id = EDD()->session->get( $mc_cid_key );
    $email_id    = EDD()->session->get( $mc_eid_key );

    if ( isset( $campaign_id ) && isset( $email_id ) ) {

      add_post_meta( $payment_id, '_edd_mc_campaign_id', $campaign_id, true );
      add_post_meta( $payment_id, '_edd_mc_email_id', $email_id, true );

      EDD()->session->set( $mc_cid_key, NULL );
      EDD()->session->set( $mc_eid_key, NULL );

    }
    
  }

  /**
   * Send purchase details to MailChimp's Ecommerce360 extension.
   *
   * @param  integer $payment_id    [description]
   * @return bool
   */
  public function record_ecommerce360_purchase( $payment_id = 0 ) {

    // Make sure an API key has been entered
    if ( empty( $this->key ) ) {
      return FALSE;
    }

    // Don't record details if we're in test mode
    if ( edd_is_test_mode() ) {
      return FALSE;
    }

    $payment      = edd_get_payment_meta( $payment_id );
    $user_info    = edd_get_payment_meta_user_info( $payment_id );
    $amount       = edd_get_payment_amount( $payment_id );
    $cart_details = edd_get_payment_meta_cart_details( $payment_id );
    $tax          = edd_get_payment_tax( $payment_id );

    if ( is_array( $cart_details ) ) {

      $items = array();

      // Increase purchase count and earnings
      foreach ( $cart_details as $index => $download ) {

        // Get the categories that this download belongs to, if any
        $post = edd_get_download( $download['id'] );
        $terms = get_the_terms( $download['id'], 'download_category' );

        if ( $terms && ! is_wp_error( $terms ) ) {
          $categories = array();

          foreach ( $terms as $term ) {
            $categories[] = $term->name;
          }

          $category_id   = $terms[0]->term_id;
          $category_name = join( " - ", $categories );
        } else {
          $category_id   = 1;
          $category_name = 'Download';
        }

        // "bundle" or "default"
        $download_type    = edd_get_download_type( $download['id'] );
        $download['sku']  = edd_get_download_sku( $download['id'] );

        // if ( 'bundle' == $download_type ) {

        //   $downloads = edd_get_bundled_products( $download_id );
        //   if ( $downloads ) {
        //     foreach ( $downloads as $d_id ) {
        //       # Do something
        //     }
        //   }
        // }

        $item = array(
          'line_num'      => $index + 1,
          'product_id'    => (int) $download['id'], // int only and required, lame
          'product_name'  => $download['name'],
          'category_id'   => $category_id,          // int, required
          'category_name' => $category_name,        // string, required
          'qty'           => $download['quantity'], // double
          'cost'          => $download['subtotal'], // double, cost of single line item
        );

        if ( $download['sku'] !== '-' ) {
          $item['sku'] = $download['sku']; // optional, 30 char limit
        }

        $items[] = $item;
      }

      $order = array(
        'id'         => (string) $payment_id,// string
        'email'      => $user_info['email'], // string
        'total'      => $amount,             // double
        'store_id'   => self::_edd_ec360_get_store_id(),   // string, 32 char limit
        'store_name' => home_url(),          // string
        'items'      => $items,
      );

      // Set Ecommerce360 variables if they exist
      $campaign_id = get_post_meta( $payment_id, '_edd_mc_campaign_id', true );
      $email_id    = get_post_meta( $payment_id, '_edd_mc_email_id', true );

      if ( ! empty( $campaign_id ) ) {
        $order['campaign_id'] = $campaign_id;
      }

      if ( ! empty( $email_id ) ) {
        $order['email_id'] = $email_id;
      }

      if ( $tax != 0 ) {
        $order['tax'] = $tax; // double, optional
      }

      // Send to MailChimp
      $options = array(
        'CURLOPT_FOLLOWLOCATION' => false
      );
      $mailchimp = new EDD_MailChimp_API( $this->key, $options );

      try {
        $result = $mailchimp->call( 'ecomm/order-add', array( 'order' => $order ) );
        var_dump( $result ); exit;
        edd_insert_payment_note( $payment_id, __( 'Order details have been added to MailChimp successfully', 'eddmc' ) );
      } catch (Exception $e) {
        edd_insert_payment_note( $payment_id, __( 'MailChimp Ecommerce360 Error: ', 'eddmc' ) . $e->getMessage() );
        return FALSE;
      }

      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Remove an order from MailChimp if the payment was refunded
   *
   * @return bool
   */
  public function delete_ecommerce360_purchase( $payment_id, $new_status, $old_status) {
    if ( 'publish' != $old_status && 'revoked' != $old_status ) {
      return;
    }

    if ( 'refunded' != $new_status ) {
      return;
    }

    // Make sure an API key has been entered
    if ( empty( $this->key ) ) {
      return FALSE;
    }

    // Send to MailChimp
    $options = array(
      'CURLOPT_FOLLOWLOCATION' => false
    );
    $mailchimp = new EDD_MailChimp_API( $this->key, $options );

    try {
      $result = $mailchimp->call( 'ecomm/order-del', array( 'store_id' => self::_edd_ec360_get_store_id(), 'order_id' => $payment_id ) );
      edd_insert_payment_note( $payment_id, __( 'Order details have been removed from MailChimp successfully', 'eddmc' ) );
      return TRUE;
    } catch (Exception $e) {
      edd_insert_payment_note( $payment_id, __( 'MailChimp Ecommerce360 Error: ', 'eddmc' ) . $e->getMessage() );
      return FALSE;
    }
  }

  /**
   * Enables MailChimp's Ecommerce360 tracking from the parameters
   * added to a newsletter campaign
   *
   * @uses campaign UID
   * @uses member email's UID
   */
  public function set_ecommerce360_session() {
    $mc_cid = isset( $_GET['mc_cid'] ) ? $_GET['mc_cid'] : '';
    $mc_eid = isset( $_GET['mc_eid'] ) ? $_GET['mc_eid'] : '';

    if ( ! empty( $mc_cid ) && ! empty( $mc_eid ) ) {
      EDD()->session->set( self::_edd_ec360_get_session_id( 'campaign' ), filter_var( $mc_cid , FILTER_SANITIZE_STRING ) );
      EDD()->session->set( self::_edd_ec360_get_session_id( 'email' ),    filter_var( $mc_eid , FILTER_SANITIZE_STRING ) );
    }
  }

  /**
   * Returns the unique EC360 session keys for this EDD installation.
   *
   * @param  string $type campaign | email
   * @return string Key identifier for stored sessions
   */
  protected static function _edd_ec360_get_session_id( $type = 'campaign' ) {
    $prefix = substr( $type, 0, 1);
    return sprintf( 'edd_mc360_%1$s_%2$sid', substr( self::_edd_ec360_get_store_id(), 0, 10 ), $prefix );
  }

  /**
   * Returns the store ID variable for use in the MailChimp API
   *
   * @return string
   */
  protected static function _edd_ec360_get_store_id() {
    return md5( home_url() );
  }

}