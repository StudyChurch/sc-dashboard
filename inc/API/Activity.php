<?php

namespace StudyChurch\API;

use BP_REST_Activity_Endpoint;
use WP_Error;

class Activity extends BP_REST_Activity_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'activity';

		add_filter( 'rest_activity_show_hidden', [ $this, 'allow_group_0' ], 10, 4 );
	}

	public function register_routes() {

		parent::register_routes();

	}

	/**
	 * Customize item creation for answers
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|\WP_REST_Request|\WP_REST_Response
	 * @author Tanner Moushey
	 */
	public function create_item( $request ) {

		if ( ! in_array( 'answer_update', array( $request['type'] ) ) ) {
			return parent::create_item( $request );
		}

		$request->set_param( 'context', 'edit' );
		$prepared_activity = $this->prepare_item_for_database( $request );
		$prepared_activity->item_id = $prepared_activity->group_id;

		$activity_id = bp_activity_add( $prepared_activity );

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error( 'rest_user_cannot_create_activity',
				__( 'Cannot create new activity.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity = bp_activity_get( array(
			'in'               => $activity_id,
			'display_comments' => 'threaded',
			'show_hidden'      => $request['hidden'],
		) );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $activity['activities'][0], $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is created via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param /BP_Activity_Activity $activity The created activity.
		 * @param /WP_REST_Response     $response The response data.
		 * @param /WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'rest_activity_create_item', $activity, $response, $request );

		return $response;

	}

	/**
	 * Show hidden activity?
	 *
	 * @since 0.1.0
	 *
	 * @param  string $component The activity component.
	 * @param  int    $item_id   The activity item ID.
	 *
	 * @return boolean
	 */
	protected function show_hidden( $component, $item_id ) {
		$user_id = get_current_user_id();
		$retval  = false;

		// If activity is from a group, do an extra cap check.
		if ( ! $retval && ! empty( $item_id ) && in_array( $component, array( buddypress()->groups->id, 'study' ) ) ) {
			foreach ( (array) $item_id as $group_id ) {
				// Group admins and mods have access as well.
				if ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) ) {
					$retval = true;

					// User is a member of the group.
				} elseif ( (bool) groups_is_user_member( $user_id, $group_id ) ) {
					$retval = true;
				}
			}
		}

		// Moderators as well.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter here to edit the `show_hidden` activity param for the given component/item_id.
		 *
		 * @since 0.1.0
		 *
		 * @param boolean $retval    True to include hidden activities. False otherwise.
		 * @param integer $user_id   The current user ID.
		 * @param string  $component The activity component.
		 * @param integer $item_id   The activity item ID.
		 */
		return (bool) apply_filters( 'rest_activity_show_hidden', $retval, $user_id, $component, $item_id );
	}


	/**
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['component']['enum'][]                     = 'study';
		$schema['properties']['id']['readonly']                          = false;
		$schema['properties']['content']['properties']['raw']['context'] = array( 'view', 'edit' );

		return $schema;
	}

	/**
	 * Update params
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['component']['enum'][] = 'study';

		$params['primary_id']['type']              = 'array';
		$params['primary_id']['default']           = array();
		$params['primary_id']['sanitize_callback'] = 'wp_parse_id_list';

		$params['secondary_id']['type']              = 'array';
		$params['secondary_id']['default']           = array();
		$params['secondary_id']['sanitize_callback'] = 'wp_parse_id_list';


		return $params;
	}

	public function allow_group_0( $retval, $user_id, $component, $item_id ) {
		if ( $retval || 'groups' != $component ) {
			return $retval;
		}

		// everyone has access to group 0 for personal studies.
		if ( 0 === $item_id ) {
			return true;
		}

		$group = groups_get_group( $item_id );

		if ( ! $group->parent_id ) {
			return $retval;
		}

		// @TODO make this more secure
		add_filter( 'bp_current_user_can', function( $retval, $capability ) {
			if ( 'bp_moderate' == $capability ) {
				return true;
			}

			return $retval;
		}, 10, 2 );

		return groups_is_user_admin( $user_id, $group->parent_id );
	}

    public function delete_item_permissions_check( $request ) {

        $activity = $this->get_activity_object( $request );

        if ( 'activity_update' === $activity->type &&  ) {
            if ( groups_is_user_admin( get_current_user_id(), $activity->item_id ) ) {
                return true;
            }
        } elseif ( 'activity_comment' === $activity->type ) {
            $parent = bp_activity_get_specific( array( 'activity_ids' => $activity->item_id ) );

            if ( groups_is_user_admin( get_current_user_id(), $parent['activities'][0]->item_id ) ) {
                return true;
            }
        }

        return parent::delete_item_permissions_check( $request );
    }
}
