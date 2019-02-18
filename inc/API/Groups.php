<?php

namespace StudyChurch\API;

use BP_REST_Groups_Endpoint;
use StudyChurch\Organization;
use WP_Error;
use BP_Groups_Member;
use WP_REST_Server;

class Groups extends BP_REST_Groups_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'groups';

		add_filter( 'rest_group_can_see', [ $this, 'can_see_group' ], 10, 2 );
	}

	public function register_routes() {

		parent::register_routes();

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/remove/(?P<user_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'remove_member' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/promote/(?P<user_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'promote_member' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/demote/(?P<user_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'demote_member' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/avatar', array(
			'args'   => array(
				'id' => array(
					'description' => __( 'Unique identifier for the user.' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item_avatar' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Remove the provided user
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response | WP_Error
	 * @author Tanner Moushey
	 */
	public function remove_member( $request ) {
		if ( ! groups_remove_member( $request['user_id'], $request['id'] ) ) {
			return new WP_Error( 'rest_group_cannot_remove_member',
				__( 'Could not remove the member.', studychurch()->get_id() ),
				array(
					'status' => 500,
				)
			);
		}

		return parent::get_item( $request );
	}

	/**
	 * Promote the provided user
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response | WP_Error
	 * @author Tanner Moushey
	 */
	public function promote_member( $request ) {
		if ( ! groups_promote_member( $request['user_id'], $request['id'], 'admin' ) ) {
			return new WP_Error( 'rest_group_cannot_promote_member',
				__( 'Could not promote the member.', studychurch()->get_id() ),
				array(
					'status' => 500,
				)
			);
		}

		return parent::get_item( $request );
	}

	/**
	 * Promote the provided user
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response | WP_Error
	 * @author Tanner Moushey
	 */
	public function demote_member( $request ) {
		if ( ! groups_demote_member( $request['user_id'], $request['id'] ) ) {
			return new WP_Error( 'rest_group_cannot_demote_member',
				__( 'Could not demote the member.', studychurch()->get_id() ),
				array(
					'status' => 500,
				)
			);
		}

		return parent::get_item( $request );
	}

	/**
	 * Update the user's avatar
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_avatar( $request ) {
		$group = $this->get_group_object( $request );
		if ( is_wp_error( $group ) ) {
			return $group;
		}

		global $bp;
		$bp->groups->current_group = $group;

		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new \stdClass();
		}

		$bp->avatar_admin->step = 'upload-image';

		if ( ! empty( $_FILES ) ) {
			// Pass the file to the avatar upload handler.
			if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
				bp_core_avatar_handle_crop( [
					'object'        => 'group',
					'avatar_dir'    => 'group-avatars',
					'item_id'       => $group->id,
					'original_file' => str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->image->file ),
					'crop_w'        => bp_core_avatar_original_max_width(),
					'crop_h'        => bp_core_avatar_original_max_width(),
					'crop_x'        => 0,
					'crop_y'        => 0
				] );
			}
		}

		/**
		 * Fires right before the loading of the group change avatar screen template file.
		 *
		 * @since 1.0.0
		 */
		do_action( 'group_screen_change_avatar' );

		$group = $this->get_group_object( $group->id );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		return $response;
	}

	/**
	 * Add support for retrieving the group by slug
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return WP_Error|\WP_REST_Request
	 * @author Tanner Moushey
	 */
	public function get_item( $request ) {

		// check if this is a group slug or not. If not, proceed as normal
		if ( $group_id = groups_get_id( $request->get_url_params()['id'] ) ) {
			$request->set_url_params( array( 'id' => $group_id ) );
		}

		return parent::get_item( $request );

	}

	/**
	 * Get group object.
	 *
	 * @since 0.1.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\BP_Groups_Group
	 */
	public function get_group_object( $request ) {
		$group_id = is_numeric( $request ) ? $request : (int) $request['id'];

		if ( $group_id_from_slug = groups_get_id( $group_id ) ) {
			$group_id = $group_id_from_slug;
		}

		$group = groups_get_group( array(
			'group_id'        => $group_id,
			'load_users'      => false,
			'populate_extras' => false,
		) );

		if ( empty( $group ) || empty( $group->id ) ) {
			return false;
		}

		return $group;
	}

	/**
	 * Update a group.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		// If the update was fired but returned an error,
		// send a custom error to the api.
		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error( 'rest_user_cannot_update_group',
				__( 'Cannot update existing group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		if ( isset( $request['studies'] ) ) {
			studychurch()->study::update_group_studies( $group_id, $request['studies'] );
		}

		$group = $this->get_group_object( $group_id );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is updated via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param \BP_Groups_Group  $group    The updated group.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'rest_group_update_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['component']['enum'][]                         = 'study';
		$schema['properties']['id']['readonly']                              = false;
		$schema['properties']['description']['properties']['raw']['context'] = [ 'view', 'edit' ];

		return $this->add_additional_fields_schema( $schema );
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

		return $params;
	}

	/**
	 * Register custom fields for Groups
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	protected function get_additional_fields( $object_type = null ) {
		$fields = parent::get_additional_fields( $object_type );

		$fields['studies'] = [
			'get_callback' => [ $this, 'get_studies' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies attached to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];

		$fields['members'] = [
			'get_callback' => [ $this, 'get_group_members' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The members that belong to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];

		$fields['invite'] = [
			'get_callback' => [ $this, 'get_group_invite_link' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The invite link for this group', studychurch()->get_id() ),
				'type'        => 'string',
			],
		];

		$fields['group_type'] = [
			'get_callback' => [ $this, 'get_group_type' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The type for this group', studychurch()->get_id() ),
				'type'        => 'string',
			],
		];

		$fields['member_limit'] = [
			'get_callback' => [ $this, 'get_member_limit' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The member limit for this group.', studychurch()->get_id() ),
				'type'        => 'integer',
			],
		];

		$fields['premium_access'] = [
			'get_callback' => [ $this, 'get_premium_access' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies that this group has access to.', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];

		return $fields;
	}

	/**
	 * Get the studies for the queried group
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_studies( $object ) {
		$studies  = studychurch()->study::get_group_studies( $object['id'] );
		$gstudies = [];

		foreach ( $studies as $study ) {
			$gstudies[] = studychurch()->study::get_data( $study );
		}

		return $gstudies;
	}

	/**
	 * Get the Group Type, should be either Organization or Group
	 *
	 * @param $object
	 *
	 * @return array|bool|string
	 * @author Tanner Moushey
	 */
	public function get_group_type( $object ) {
		if ( ! $type = bp_groups_get_group_type( $object['id'], true ) ) {
			$type = 'group';
		}

		return $type;
	}

	/**
	 * Get the member limit for the provided group
	 *
	 * @param $object
	 *
	 * @return int
	 * @author Tanner Moushey
	 */
	public function get_member_limit( $object ) {
		$org = new Organization( $object['id'] );
		return $org->get_member_limit();
	}

	/**
	 * Get the members for this group
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 * @param $object_type
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_group_members( $object, $field_name, $request, $object_type ) {
		return [
			'members' => BP_Groups_Member::get_group_member_ids( $object['id'] ),
			'admins'  => wp_list_pluck( BP_Groups_Member::get_group_administrator_ids( $object['id'] ), 'user_id' ),
			'mods'    => BP_Groups_Member::get_group_moderator_ids( $object['id'] )
		];

		if ( 'hide' == $request['members'] ) {
			return [];
		}

		if ( empty( $request['members'] ) ) {
			$roles = array( 'member', 'mod', 'admin' );
		} else {
			$roles = explode( ',', $request['members'] );
		}

		$members = groups_get_group_members( [
			'group_id'   => $object['id'],
			'per_page'   => 100,
			'group_role' => $roles,
		] );

		if ( empty( $members['members'] ) ) {
			return [];
		}

		$group_members = [];
		foreach ( $members['members'] as $member ) {
			$group_members[] = [
				'id'           => $member->ID,
				'username'     => $member->user_nicename,
				'name'         => $member->display_name,
				'admin'        => $member->is_admin,
				'mod'          => $member->is_mod,
				'lastActivity' => $member->last_activity,
				'avatar'       => [
					'img'  => bp_core_fetch_avatar( [ 'type' => 'full', 'html' => true, 'item_id' => $member->ID ] ),
					'full' => bp_core_fetch_avatar( [ 'type' => 'full', 'html' => false, 'item_id' => $member->ID ] ),
				],
			];
		}

		return $group_members;

	}

	/**
	 * Return studies that this group has access to
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_premium_access( $object ) {
		$org = new Organization( $object['id'] );
		return $org->get_premium_access();
	}

	public function get_group_invite_link( $object ) {
		return sprintf( "%s?group=%s&key=%s", trailingslashit( home_url( 'join' ) ), $object['slug'], sc_get_group_invite_key( $object['id'] ) );
	}

	public function can_user_delete_or_update( $group ) {
		if ( $retval = parent::can_user_delete_or_update( $group ) ) {
			return $retval;
		}

		if ( ! $group->parent_id ) {
			return $retval;
		}

		return groups_is_user_admin( bp_loggedin_user_id(), $group->parent_id );
	}

	public function can_see_group( $retval, $request ) {
		if ( $retval ) {
			return $retval;
		}

		$group = $this->get_group_object( $request );

		if ( ! $group->parent_id ) {
			return $retval;
		}

		return groups_is_user_admin( bp_loggedin_user_id(), $group->parent_id );
	}
}
