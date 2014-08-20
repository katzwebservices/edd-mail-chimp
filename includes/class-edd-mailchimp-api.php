<?php
/**
 * Super-simple, minimum abstraction MailChimp API v2 wrapper
 * 
 * This probably has more comments than code.
 * 
 * @author Based on class by Drew McLellan <drew.mclellan@gmail.com>
 * @version 1.0
 */
class EDD_MailChimp_API
{
	private $api_key;
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0/';
	private $verify_ssl   = false;

	/**
	 * Create a new instance
	 * @param string $api_key Your MailChimp API key
	 */
	function __construct($api_key, $options = array())
	{
		$this->api_key = $api_key;
		list(, $datacentre) = explode('-', $this->api_key);
		$this->api_endpoint = str_replace('<dc>', $datacentre, $this->api_endpoint);
	}

	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call($method, $args=array())
	{
		return $this->_raw_request($method, $args);
	}

	/**
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function _raw_request($method, $args=array())
	{      
		$args['apikey'] = $this->api_key;

		$url = $this->api_endpoint.'/'.$method.'.json';

		$request_args = array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'content-type' => 'application/json'
			),
			'body'        => json_encode( $args ),
		);

		$request = wp_remote_post( $url, $request_args );

		return is_wp_error( $request ) ? false : json_decode( wp_remote_retrieve_body( $request ) );

	}

}