<?php

namespace StudyChurch\API;

use BP_REST_Members_Endpoint;
use WP_Error;
use WP_REST_Server;
use StudyChurch\API\Auth\User;
use WP_REST_Request;
use stdClass;

class Users extends BP_REST_Members_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'users';
	}

	public function register_routes() {

		parent::register_routes();

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
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

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

		return $params;
	}

	/**
	 * Register custom fields for Users
	 *
	 * @author Tanner Moushey
	 */
	protected function get_additional_fields( $object_type = null ) {
		$fields = parent::get_additional_fields( $object_type );

		$fields['studies'] = [
			'get_callback'    => [ $this, 'get_studies' ],
			'update_callback' => [ $this, 'update_studies' ],
			'schema'          => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies attached to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];


		return $fields;
	}

	/**
	 * @param array           $object
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function add_additional_fields_to_object( $object, $request ) {
		$object = parent::add_additional_fields_to_object( $object, $request );

		$object['can']['create_study'] = user_can( $object['id'], 'create_study' );
		$object['can']['create_group'] = user_can( $object['id'], 'create_group' );

		return $object;
	}

	/**
	 * Update the user's avatar
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_avatar( $request ) {
		$user = $this->get_user( $request['id'] );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		global $bp;
		$bp->displayed_user = $user;

		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new stdClass();
		}

		$bp->avatar_admin->step = 'upload-image';

		if ( ! empty( $_FILES ) ) {
			// Pass the file to the avatar upload handler.
			if ( bp_core_avatar_handle_upload( $_FILES, 'xprofile_avatar_upload_dir' ) ) {
				bp_core_avatar_handle_crop( [
					'item_id'       => $user->ID,
					'original_file' => str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->image->file ),
					'crop_w'        => bp_core_avatar_original_max_width(),
					'crop_h'        => bp_core_avatar_original_max_width(),
					'crop_x'        => 0,
					'crop_y'        => 0
				] );
			}
		}

		/**
		 * Fires right before the loading of the XProfile change avatar screen template file.
		 *
		 * @since 1.0.0
		 */
		do_action( 'xprofile_screen_change_avatar' );

		$user          = get_user_by( 'id', $user->ID );
		$fields_update = $this->update_additional_fields_for_object( $user, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Can user manage (delete/update) a member?
	 *
	 * @param  \WP_User $user User object.
	 *
	 * @return bool
	 */
	protected function can_manage_member( $user ) {

		if ( current_user_can( 'edit_user', $user->ID ) ) {
			return true;
		}

		return parent::can_manage_member( $user );
	}

	/**
	 * Get the studies for the queried user
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_studies( $object ) {
		$studies  = studychurch()->study::get_user_studies( $object['id'] );
		$gstudies = [];

		foreach ( $studies as $study ) {
			$gstudies[] = studychurch()->study::get_data( $study );
		}

		return $gstudies;
	}

	/**
	 * Handle Update for user studies
	 *
	 * @param $value
	 * @param $object
	 *
	 * @author Tanner Moushey
	 */
	public function update_studies( $value, $object ) {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		studychurch()->study::update_user_studies( $object->ID, array_map( 'absint', $value ) );
	}

}
