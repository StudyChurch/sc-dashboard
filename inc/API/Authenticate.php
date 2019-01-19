<?php

namespace StudyChurch\API;

use StudyChurch\API\Auth\User;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Firebase\JWT\JWT;

/**
 * Class used to manage a user's API Passwords via the REST API.
 *
 * @since 4.7.0
 *
 * @see   WP_REST_Controller
 */
class Authenticate extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since  4.7.0
	 * @access public
	 */
	public function __construct() {
		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'authenticate';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since  4.7.0
	 * @access public
	 *
	 * @see    register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_user' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Retrieves the passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_user( $request ) {

		$user       = new User( wp_get_current_user() );
		$key        = file_get_contents( __DIR__ . '/Auth/jwtRS256.key' );
		$expiration = time() + 2 * WEEK_IN_SECONDS;

		$user_new     = $this->do_rest_request( '/studychurch/v1/users/' . $user->ID );

		$groups     = $this->do_rest_request( '/studychurch/v1/groups', array(
			'show_hidden' => true,
			'user_id'     => $user->ID,
			'status'      => 'hidden',
			'members'     => 'all',
		) );

		$studies = $this->do_rest_request( '/studychurch/v1/studies', array(
			'status'   => 'any',
			'per_page' => 100,
			'orderby'  => 'title',
			'order'    => 'asc',
			'author'   => $user->ID
		) );

		$token = JWT::encode( array(
			'secret' => wp_generate_password(),
			'exp'    => $expiration,
		), $key, 'RS256' );

		set_transient( 'user_authentication_' . get_current_user_id(), $token, $expiration );

		$response = [
			'token' => $token,
			'user_new' => $user_new,
			'groups' => $groups,
			'studies' => $studies,
			'user'  => [
				'avatar'    => [
					'img'  => bp_get_displayed_user_avatar( ['type' => 'full', 'html' => true, 'item_id' => $user->ID ] ),
					'full' => bp_get_displayed_user_avatar( ['type' => 'full', 'html' => false, 'item_id' => $user->ID ] ),
				],
				'id'        => $user->ID,
				'name'      => $user->display_name,
				'username'  => $user->user_login,
				'firstName' => $user->first_name,
				'lastName'  => $user->last_name,
				'email'     => $user->user_email,
				'groups'    => $groups,
				'studies'   => $studies,
			],
		];

		return $response;
	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full d   etails about the request.
	 *
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_permissions_check( $request ) {

		return is_user_logged_in();

		if ( empty( $request['username'] ) || empty( $request['password'] ) ) {
			return false;
		}

		if ( is_email( $request['username'] ) ) {
			$user = get_user_by( 'email', $request['username'] );
		} else {
			$user = get_user_by( 'login', $request['username'] );
		}

		if ( ! $user ) {
			return false;
		}

		// get the user by the username
		$user = new User( $user );

		if ( ! $user->authenticate( $request['password'] ) ) {
			return false;
		}

		rcp_login_user_in( $user->ID, $user->user_login, true );

		return is_user_logged_in();
	}

	protected function do_rest_request( $route, $atts = array() ) {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $atts );
		$response = rest_do_request( $request );
		$server   = rest_get_server();

		return $server->response_to_data( $response, false );
	}

}
