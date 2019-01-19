<?php

namespace StudyChurch\API;

use StudyChurch\API\Auth\User;
use StudyChurch\Assignments\Query;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class used to manage a user's API Passwords via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class Assignments extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function __construct() {
		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'assignments';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
//				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_all_items' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, $this->rest_base . '/(?P<assignment_id>[\d]+)', array(
			'args' => array(
				'assignment_id' => array(
					'description' => __( 'The ID of the assignment.', 'awesome-support-api' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_permissions_check( $request ) {
		return is_user_logged_in();
	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function create_permissions_check( $request ) {
		return is_user_logged_in() && sc_user_can_manage_group();
	}

	/**
	 * Retrieves the passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		if ( empty( $request['group_id'] ) ) {
			$groups = bp_get_user_groups( get_current_user_id(), array(
				'is_admin' => null,
			    'is_mod'   => null,
			) );

			$request['group_id'] = array_keys( $groups );
		}

		$assignments = new Query( array(
			'group_id'    => $request['group_id'],
		) );

		$response = [];

		if ( ! $assignments->have_assignments() ) {
			return $response;
		}

		while ( $assignments->the_assignment() ) {
			$ass = [
				'key'     => $assignments->get_the_key(),
				'date'    => $assignments->get_the_date(),
				'group'   => $assignments->get_group_id(),
				'content' => apply_filters( 'the_content', wp_kses_post( $assignments->get_the_content() ) ),
			    'lessons' => [],
			];

			foreach ( $assignments->get_the_lessons() as $lesson ) {
				$ass['lessons'][] = [
					'id'    => $lesson,
					'title' => get_the_title( $lesson ),
				    'link'  => get_the_permalink( $lesson ),
				];
			}

			$response[] = $ass;
		}

		return $response;
	}

	/**
	 * Create a password for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or error object on failure.
	 */
	public function create_item( $request ) {

		if ( ( empty( $request['content'] ) && empty( $request['lessons'] ) ) || empty( $request['date'] ) ) {
			return new WP_Error( 'invalid data', 'Please provide content and a due date' );
		}

		$id = sc_add_group_assignment( [
			'content' => $request['content'],
		    'lessons' => $request['lessons'],
		    'date'    => $request['date'],
		], $request['group_id'] );

		return sc_get_group_assignment( $id );
	}

	/**
	 * Delete a password for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or error object on failure.
	 */
	public function delete_item( $request ) {
		$user = new User( $request['user_id'] );
		$slug = $request['slug'];

		if ( ! $item = $user->get_api_password( $slug ) ) {
			return new WP_Error( 'no-item-found', __( 'No password was found with that slug.', 'awesome-support-api' ), array( 'status' => 404 ) );
		}

		if ( $user->delete_api_password( $slug ) ) {
			return array( 'deleted' => true, 'previous' => $item );
		} else {
			return new WP_Error( 'no-item-found', __( 'No password was found with that slug.', 'awesome-support-api' ), array( 'status' => 404 ) );
		}

	}

	/**
	 * Delete all api passwords for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Array on success
	 */
	public function delete_all_items( $request ) {
		$user  = new User( $request['user_id'] );
		$items = $this->get_items( $request );

		$user->delete_all_api_passwords();

		return array( 'deleted' => true, 'previous' => $items );
	}

	/**
	 * Retrieves the site setting schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'password',
			'type'       => 'object',
			'properties' => array(
				'name'      => array(
					'description' => __( "The name of the new password" ),
					'required'    => true,
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'password'  => array(
					'description' => __( "The hashed password that was created" ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'created'   => array(
					'description' => __( 'The date the password was created' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_used' => array(
					'description' => __( 'The date the password was last used' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_ip'   => array(
					'description' => __( 'The IP address that the password was last used from' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'      => array(
					'description' => __( 'The password\'s unique slug' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

}
