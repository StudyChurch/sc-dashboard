<?php
/**
 * SkillfulPlugins Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace SkillfulPlugins\RequestAPI;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SkillfulPlugins\RequestAPI\RequestJSON' ) ) :

/**
 * Base JSON API request class.
 *
 * @since 1.0.0
 */
abstract class RequestJSON implements InterfaceRequest {


	/** @var string The request method, one of HEAD, GET, PUT, PATCH, POST, DELETE */
	protected $method;

	/** @var string The request path */
	protected $path;

	/** @var array The request parameters, if any */
	protected $params = array();

	/** @var array the request data */
	protected $data = array();


	/**
	 * Get the request method.
	 *
	 * @since 1.0.0
	 * @see InterfaceRequest::get_method()
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}


	/**
	 * Get the request path.
	 *
	 * @since 1.0.0
	 * @see InterfaceRequest::get_path()
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}


	/**
	 * Get the request parameters.
	 *
	 * @since 1.0.0
	 * @see InterfaceRequest::get_params()
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}


	/**
	 * Get the request data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_data() {
		return $this->data;
	}


	/** API Helper Methods ******************************************************/


	/**
	 * Get the string representation of this request.
	 *
	 * @since 1.0.0
	 * @see InterfaceRequest::to_string()
	 * @return string
	 */
	public function to_string() {

		$data = $this->get_data();

		if ( empty( $data ) && ! in_array( strtoupper( $this->get_method() ), array( 'GET', 'HEAD' ) ) ) {
			$data = $this->get_params();
		}

		return ! empty( $data ) ? json_encode( $data ) : '';
	}


	/**
	 * Get the string representation of this request with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 1.0.0
	 * @see InterfaceRequest::to_string_safe()
	 * @return string
	 */
	public function to_string_safe() {
		return $this->to_string();
	}


}

endif; // class exists check
