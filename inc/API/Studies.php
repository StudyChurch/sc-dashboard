<?php

namespace StudyChurch\API;

use WP_REST_Posts_Controller;
use WP_REST_Server;
use WP_Error;
use WP_Query;

class Studies extends WP_REST_Posts_Controller {
	protected $post_type = 'sc_study';

	public function __construct() {
		parent::__construct( $this->post_type );

		$this->namespace = studychurch()->get_api_namespace();
		$this->base      = 'studies';

		add_action( 'before_delete_post', array( $this, 'delete_chapter_items' ) );
	}

	public function register_routes() {

		parent::register_routes();

		register_rest_field( 'sc_study', 'data_type', array(
			'update_callback' => array( $this, 'save_data_type' ),
			'get_callback'    => array( $this, 'get_data_type' )
		) );

		register_rest_field( 'sc_study', 'is_private', array(
			'update_callback' => array( $this, 'save_is_private' ),
			'get_callback'    => array( $this, 'get_is_private' )
		) );

		register_rest_field( 'sc_study', 'thumbnail', array(
			'get_callback' => array( $this, 'get_thumbnail' ),
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The thumbnail for the study', studychurch()->get_id() ),
				'type'        => 'string',
			],
		) );

		$posts_args = array(
			'context'  => array(
				'default' => 'view',
			),
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'filter'   => array(),
		);

		register_rest_route( $this->namespace, $this->base . '/(?P<study_id>[a-zA-Z0-9-]+)/navigation', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_navigation' ),
				'args'     => $posts_args,
				//				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
		) );

		register_rest_route( $this->namespace, $this->base . '/(?P<study_id>[a-zA-Z0-9-]+)/chapters', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_chapters' ),
				'args'     => $posts_args,
				//				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_item' ),
				'args'     => $this->get_endpoint_args_for_item_schema( true ),
			),
		) );

		register_rest_route( $this->namespace, $this->base . '/(?P<study_id>[a-zA-Z0-9-]+)/chapters/(?P<id>[a-zA-Z0-9-]+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_chapter' ),
				'args'     => $posts_args,
				//				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default' => true,
					),
				),
			),
		) );

		register_rest_route( $this->namespace, $this->base . '/(?P<study_id>[a-zA-Z0-9-]+)/chapters/(?P<chapter_id>[a-zA-Z0-9-]+)/items', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_chapter_items' ),
				'args'                => $posts_args,
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_item' ),
				'args'     => $this->get_endpoint_args_for_item_schema( true ),
			),
		) );

		register_rest_route( $this->namespace, $this->base . '/(?P<study_id>[a-zA-Z0-9-]+)/chapters/(?P<chapter_id>[a-zA-Z0-9-]+)/items/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_chapter_item' ),
				'args'                => $posts_args,
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default' => true,
					),
				),
			),
		) );

	}

	public function create_item( $request ) {
		if ( is_array( $request['sc_group'] ) ) {
			$request->set_param( 'sc_group', array_map( 'strval', $request['sc_group'] ) );
		}

		return parent::create_item( $request );
	}

	public function get_chapter( $request, $context = 'view' ) {

		$args       = (array) $request->get_params();
		$chapter_id = $args['id'];
		$study_id   = $study_slug = $args['study_id'];

		if ( is_numeric( $study_slug ) ) {
			$study_slug = get_post( $study_id )->post_name;
		}

		if ( ! is_numeric( $chapter_id ) ) {
			$chapter_id = get_page_by_path( $study_slug . '/' . $chapter_id, OBJECT, 'sc_study' )->ID;
		}

		if ( empty( $chapter_id ) || ! $chapter = get_post( $chapter_id ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		$chapter = $this->prepare_item_for_response( $chapter, $request );

		$query = array(
			'post_type'      => 'sc_study',
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $chapter_id,
			'post_status'    => array( 'draft', 'pending', 'publish' ),
			'posts_per_page' => 10000,
		);

		$chapter_query = new WP_Query();
		$chapter_parts = $chapter_query->query( $query );

		$struct = array();

		foreach ( $chapter_parts as $part ) {

			// Do we have permission to read this post?
			if ( ! $this->check_read_permission( $part ) ) {
				continue;
			}

			$part_data = $this->prepare_item_for_response( $part, $request );
			$part_data = $this->prepare_response_for_collection( $part_data );

			// @todo patch real issue later. See class-wp-json-post.php line 906
			if ( is_wp_error( $part_data ) ) {
				continue;
			}

			/** Now get children for this chapter */
			$query['post_parent'] = $part->ID;
			$child_query          = new WP_Query();
			$children             = $child_query->query( $query );

			$part_data['elements'] = array();
			$part_data['sections'] = array();

			foreach ( $children as $child ) {

				// Do we have permission to read this post?
				if ( ! $this->check_read_permission( $child ) ) {
					continue;
				}

				$child_data = $this->prepare_item_for_response( $child, $request );
				$child_data = $this->prepare_response_for_collection( $child_data );

				if ( is_wp_error( $child_data ) ) {
					continue;
				}

				if ( sc_get_data_type( $child_data['id'] ) ) {
					$part_data['elements'][] = array(
						'id'         => $child_data['id'],
						'content'    => $child_data['content'],
						'data_type'  => esc_html( sc_get_data_type( $child['id'] ) ),
						'is_private' => sc_answer_is_private( $child['id'] ),
						'parent'     => $part['id'],
					);
				} else {
					$part_data['sections'][] = array(
						'id'     => $child_data['id'],
						'title'  => $child_data['title'],
						'parent' => $part['id'],
					);
				}

			}

			$struct[] = $part_data;
		}

		$chapter             = $this->prepare_response_for_collection( $chapter );
		$chapter['elements'] = $struct;
		$chapter['study']    = get_the_title( sc_get_study_id( $chapter_id ) );

		$response = rest_ensure_response( $chapter );

		return $response;
	}

	public function get_chapters( $request, $context = 'view' ) {

		$args     = (array) $request->get_params();
		$study_id = $args['study_id'];

		if ( ! is_numeric( $study_id ) ) {
			$study_id = get_page_by_path( $study_id, OBJECT, 'sc_study' )->ID;
		}

		$study_id = sc_get_study_id( $study_id );

		if ( empty( $study_id ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		$query = array(
			'post_type'      => 'sc_study',
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $study_id,
			'post_status'    => array( 'draft', 'pending', 'publish' ),
			'posts_per_page' => 10000,
		);

		$study_query    = new WP_Query();
		$study_chapters = $study_query->query( $query );

		if ( 0 === $study_query->found_posts ) {
			return rest_ensure_response( array() );
		}

		$struct = array();

		foreach ( $study_chapters as $chapter ) {

			// Do we have permission to read this post?
			if ( ! $this->check_read_permission( $chapter ) ) {
				continue;
			}

			$chapter_data = $this->prepare_item_for_response( $chapter, $request );
			$chapter_data = $this->prepare_response_for_collection( $chapter_data );

			/** Now get children for this chapter */
			$query['post_parent'] = $chapter_data['id'];
			$child_query          = new WP_Query();
			$children             = $child_query->query( $query );

			$chapter_data['study']    = get_the_title( $study_id );
			$chapter_data['elements'] = array();
			$chapter_data['sections'] = array();

			foreach ( $children as $child ) {

				// Do we have permission to read this post?
				if ( ! $this->check_read_permission( $child ) ) {
					continue;
				}

				$child_data = $this->prepare_item_for_response( $child, $request );
				$child_data = $this->prepare_response_for_collection( $child_data );

				if ( sc_get_data_type( $child_data['id'] ) ) {
					$chapter_data['elements'][] = array(
						'id'         => $child_data['id'],
						'content'    => $child_data['content'],
						'data_type'  => esc_html( sc_get_data_type( $child_data['id'] ) ),
						'is_private' => sc_answer_is_private( $child_data['id'] ),
						'menu_order' => $child_data['menu_order'],
						'parent'     => $chapter_data['id'],
					);
				} else {
					$chapter_data['sections'][] = array(
						'id'         => $child_data['id'],
						'title'      => $child_data['title'],
						'parent'     => $chapter_data['id'],
						'menu_order' => $child_data['menu_order'],
					);
				}

			}

			$struct[] = $chapter_data;
		}

		$response = rest_ensure_response( $struct );

		return $response;
	}

	public function get_chapter_items( $request, $context = 'view' ) {

		$args       = (array) $request->get_params();
		$chapter_id = $args['chapter_id'];

		$study_id = sc_get_study_id( $chapter_id );

		if ( empty( $study_id ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		$query = array(
			'post_type'      => 'sc_study',
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $chapter_id,
			'post_status'    => array( 'draft', 'pending', 'publish' ),
			'posts_per_page' => 10000,
		);

		$study_query = new WP_Query();
		$study_items = $study_query->query( $query );

		if ( 0 === $study_query->found_posts ) {
			return rest_ensure_response( array() );
		}

		$struct = array();

		foreach ( $study_items as $item ) {

			// Do we have permission to read this post?
			if ( ! $this->check_read_permission( $item ) ) {
				continue;
			}

			$item_data = $this->prepare_item_for_response( $item, $request );
			$item_data = $this->prepare_response_for_collection( $item_data );

			$item_data['data_type']  = esc_html( sc_get_data_type( $item_data['id'] ) );
			$item_data['is_private'] = sc_answer_is_private( $item_data['id'] );

			$struct[] = $item_data;
		}

		$response = rest_ensure_response( $struct );

		return $response;
	}

	public function get_chapter_item( $request, $context = 'view' ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		$data                     = $this->prepare_item_for_response( $post, $request );
		$data->data['data_type']  = esc_html( sc_get_data_type( $data->data['id'] ) );
		$data->data['is_private'] = sc_answer_is_private( $data->data['id'] );

		$response = rest_ensure_response( $data );

		$response->link_header( 'alternate', get_permalink( $id ), array( 'type' => 'text/html' ) );

		return $response;
	}

	public function delete_chapter_items( $id ) {
		if ( 'sc_study' != get_post_type( $id ) ) {
			return;
		}

		foreach ( get_posts( 'post_type=sc_study&posts_per_page=-1&post_parent=' . $id ) as $post ) {
			wp_delete_post( $post->ID );
		}
	}

	/**
	 * Get the navigation for the study
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_navigation( $request ) {
		$args     = (array) $request->get_params();
		$study_id = $args['study_id'];

		if ( ! is_numeric( $study_id ) ) {
			$study_id = get_page_by_path( $study_id, OBJECT, 'sc_study' )->ID;
		}

		$query_result = sc_study_get_navigation( $study_id );
		$posts        = [];

		foreach ( $query_result as $post ) {
			if ( ! $this->check_read_permission( $post ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $posts );
	}

	public function save_data_type( $value, $object, $field_name, $request ) {
		if ( ! $value ) {
			return;
		}

		return sc_save_data_type( $value, $object->ID );
	}

	public function get_data_type( $object, $field_name, $request ) {
		return sc_get_data_type( $object['id'] );
	}

	public function save_is_private( $value, $object, $field_name, $request ) {
		// if is_private is set, then the answer is private
		$value = ( $value ) ? 'private' : 'public';

		return sc_set_privacy( $value, $object->ID );
	}

	public function get_is_private( $object, $field_name, $request ) {
		return sc_answer_is_private( $object['id'] );
	}

	/**
	 * Get the Study Thumbnail
	 *
	 * @param $object
	 *
	 * @return false|mixed|string
	 * @author Tanner Moushey
	 */
	public function get_thumbnail( $object ) {
		if ( has_post_thumbnail( $object['id'] ) ) {
			return get_the_post_thumbnail_url( $object['id'], 'medium' );
		}

		if ( sc_get_study_id( $object['id'] ) == $object['id'] ) {
			return studychurch()->study->default_thumbnail();
		}

		return '';
	}

	/**
	 * Customize the collection parameters
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['parent']['default'] = 0;

		return $query_params;
	}

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $id Supplied ID.
	 *
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id ) {
		$error = new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return $error;
		}

		return $post;
	}

	/**
	 * Allow all stati when querying one's own studies
	 *
	 * @param array|string     $statuses
	 * @param \WP_REST_Request $request
	 * @param string           $parameter
	 *
	 * @return array|bool|string|WP_Error
	 * @author Tanner Moushey
	 */
	public function sanitize_post_statuses( $statuses, $request, $parameter ) {
		$statuses = wp_parse_slug_list( $statuses );

		if ( get_current_user_id() == $request['author'] ) {

			foreach ( $statuses as $status ) {
				$result = rest_validate_request_arg( $status, $request, $parameter );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			return $statuses;
		}

		$allowed = [ 'private', 'publish' ];
		if ( empty( array_diff( $statuses, $allowed ) ) ) {
			return $statuses;
		}

		return parent::sanitize_post_statuses( $statuses, $request, $parameter ); // TODO: Change the autogenerated stub
	}

}
