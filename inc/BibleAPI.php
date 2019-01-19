<?php
/**
 * Define the AvaTax BibleAPI class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce AvaTax to newer
 * versions in the future. If you wish to customize WooCommerce AvaTax for your
 * needs please refer to http://docs.woocommerce.com/document/rcp-avatax/
 *
 * @package   AvaTax\BibleAPI
 * @author    SkyVerge
 * @copyright Copyright (c) 2016-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace StudyChurch;

use SkillfulPlugins\RequestAPI\Base;
use SkillfulPlugins\RequestAPI\Exception;
use SkillfulPlugins\Helpers;

defined( 'ABSPATH' ) or exit;

/**
 * The BibleAPI.
 *
 * @since 1.0.0
 */
class BibleAPI extends Base {

	/** @var  string base request URI */
	protected $request_uri;

	/** @var string response handler class */
	protected $response_handler;

	/**
	 * BibleAPI constructor.
	 *
	 * @throws Exception
	 */
	public function __construct() {

		if ( ! $token = Settings::get( 'bible_auth_token' ) ) {
			throw new Exception( 'Please enter a valid BibleAPI Token' );
		}

		if ( ! $url = Settings::get( 'bible_url' ) ) {
			throw new Exception( 'Please enter a valid BibleAPI Path' );
		}

		$this->request_uri = trailingslashit( $url );

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );

		$this->set_request_header( 'Authorization', sprintf( 'Token %s', $token ) );

	}

	/**
	 * Update the provided episode
	 *
	 * @param $search
	 *
	 * @return object
	 * @author Tanner Moushey
	 */
	public function get_passage( $search ) {
		$request = $this->get_new_request();
		$request->get_passage( $search );
		return $this->perform_request( $request );
	}

	/**
	 * Allow child classes to validate a response prior to instantiating the
	 * response object. Useful for checking response codes or messages, e.g.
	 * throw an exception if the response code is not 200.
	 *
	 * A child class implementing this method should simply return true if the response
	 * processing should continue, or throw a \SkillfulPlugins\RequestAPI\Exception with a
	 * relevant error message & code to stop processing.
	 *
	 * Note: Child classes *must* sanitize the raw response body before throwing
	 * an exception, as it will be included in the broadcast_request() method
	 * which is typically used to log requests.
	 *
	 * @since 1.0.0
	 */
	protected function do_pre_parse_response_validation() {

		// Get the response data
		$response      = $this->get_parsed_response( $this->get_raw_response_body() );
		$response      = $response->response_data;
		$response_code = $this->get_response_code();

		if ( ! is_object( $response ) && 200 !== $response_code ) {
			throw new Exception( __( 'Could not connect to the BibleAPI.', $this->get_plugin()->get_id() ), $response_code );
		}

		if ( ! empty( $response->error ) ) {
			throw new Exception( $this->get_response_exception_message( $response ), $response_code );
		}

		return true;
	}


	/**
	 * Provide the log with more specific response exception messages for easier debugging.
	 *
	 * @since 1.0.0
	 * @param object $response The AvaTax BibleAPI response.
	 * @return string
	 */
	protected function get_response_exception_message( $response ) {

		$default_message = 'Unspecified error.';

		if ( empty( $response->error ) ) {
			return $default_message;
		}

		$error = $response->error;

		foreach( $error->details as $detail ) {

			if ( empty( $detail->message ) ) {
				continue;
			}

			$default_message = $detail->message;

			switch( $detail->message ) {

				case 'The address is not deliverable.' :
					return $detail->message;

			}

		}

		return $default_message;

	}


	/**
	 * Builds and returns a new BibleAPI request object
	 *
	 * @since 1.0.0
	 * @param string $type The desired request type
	 * @return BibleAPI\Request
	 */
	protected function get_new_request( $type = '' ) {

		switch ( $type ) {
			default:
				$this->set_response_handler( 'StudyChurch\BibleAPI\Response' );
				return new BibleAPI\Request();
		}

	}


	/**
	 * Return the plugin class instance associated with this BibleAPI.
	 *
	 * @return \StudyChurch\Setup
	 * @author Tanner Moushey
	 */
	protected function get_plugin() {
		return studychurch();
	}


}
