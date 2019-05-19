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
			  'methods' => WP_REST_Server::EDITABLE,
              'callback' => array( $this, 'update_item' ),
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

	public function delete_item( $request ) {

	    if ( empty ( $request['assignment_id'] ) ) {
	        return new WP_Error( 'invalid data', 'Please provide an assignment ID' );
        }

        if ( sc_delete_group_assignment( absint( $request['assignment_id'] ) ) ) {
            return array(
              'message' => 'Item has been successfully removed.',
                'success' => true,
            );
        }

	    return array(
	        'message' => 'An error has occurred, please try again. If the problem persists please contact support.',
        );
    }

    public function get_item( $request ) {

	    if ( empty( $request['assignment_id'] ) ) {
	        return new WP_Error( 'invalid date', 'Please provide an id' );
        }

	    return sc_get_group_assignment( $request['assignment_id'] );
    }

    public function update_item( $request ) {

        if ( empty( $request['id'] ) ) {
            return new WP_Error( 'invalid data', 'Please provide an id' );
        }

        try {
            if ( ! $timezone = get_option( 'timezone_string', 'America/Los_Angeles' ) ) {
                $timezone = 'America/Los_Angeles';
            }

            $date = new \DateTime( $request['date'], new \DateTimeZone( $timezone ) );

            $edit = sc_update_group_assignment( [
                'id' => $request['id'],
                'post_content' => $request['content'],
                'post_date'    => $date->format( 'Y-m-d H:i:s' ),
                'lessons'      => $request['lessons'],
            ], $request['group_id'] );

            if ( $edit !== 0 ) {
                return array(
                    'message' => 'Item has been successfully updated!',
                    'success' => true,
                    'request' => $request['content'],
                );
            }
            return array(
                'message' => 'An error has occurred, please try again. If the problem persists please contact support.',
            );
        } catch ( \Exception $e ) {
            return new WP_Error( $request['date'], $e->getMessage() );
        }

    }
}
