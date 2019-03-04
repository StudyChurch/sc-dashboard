<?php

namespace StudyChurch;

class Study {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var Study\Edit
	 */
	public $edit;

	public $answers;

	/**
	 * @var string
	 */
	protected static $_prefix = '_sc_';

	/**
	 * Only make one instance of the Study
	 *
	 * @return Study
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Study ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_setup_study_group' ) );
		add_action( 'template_redirect', array( $this, 'redirect_on_empty' ) );
		add_action( 'wp_head', array( $this, 'print_styles' ) );
		add_action( 'pre_get_posts', array( $this, 'study_archive' ) );
		add_action( 'pre_get_posts', array( $this, 'default_groups' ) );

		add_filter( 'private_title_format', array( $this, 'private_title_format' ), 10, 2 );
		add_filter( 'user_has_cap', array( $this, 'private_study_cap' ), 10, 4 );
		add_filter( 'get_page_uri', array( $this, 'allow_private_parent' ), 10, 2 );

		// CPT
		add_action( 'init', array( $this, 'study_cpt' ), 2 );
		add_action( 'wp_insert_post', [ $this, 'maybe_add_to_org' ], 999 );
		add_action( 'rest_after_insert_sc_study', [ $this, 'maybe_add_to_org' ], 999 );
		add_action( 'cmb2_init', array( $this, 'study_meta' ) );
		add_filter( 'block_editor_preload_paths', [ $this, 'api_path' ], 10, 2 );
		add_action( 'rest_api_init', [ $this, 'api_redirect' ] );
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_block_editor' ], 10, 2 );

		// Groups
		add_action( 'bp_init', array( $this, 'register_group_extension' ) );

		$this->edit    = Study\Edit::get_instance();
		$this->answers = Study\Answers::get_instance();
	}

	/**
	 * Catch wp calls to the default api
	 *
	 * @param $wp_rest_server
	 *
	 * @author Tanner Moushey
	 */
	public function api_redirect( $wp_rest_server ) {
		$uri = $_SERVER['REQUEST_URI'];
		$url = str_replace( 'wp/v2/studies', studychurch()->get_api_namespace() . '/studies', $_SERVER['REQUEST_URI'] );

		if ( $uri == $url ) {
			return;
		}

		wp_redirect( $url );
		die();
	}

	/**
	 * Disable editor for Studies
	 *
	 * @param $user_block_editor
	 * @param $post_type
	 *
	 * @return bool
	 * @author Tanner Moushey
	 */
	public function disable_block_editor( $user_block_editor, $post_type ) {
		if ( 'sc_study' == $post_type ) {
			return false;
		}

		return $user_block_editor;
	}

	public function register_group_extension() {
		// if we aren't in a group, don't bother
		if ( ! bp_is_group() || ! bp_is_active( 'groups' ) || ! class_exists( 'BP_Group_Extension' ) ) {
			return;
		}

		bp_register_group_extension( 'StudyChurch\Study\Group' );
	}

	/**
	 * If the study does not have an introduction, redirect to the first chapter
	 */
	public function redirect_on_empty() {
		if ( ! is_singular( 'sc_study' ) ) {
			return;
		}

		// if we are not on the main study page continue
		if ( get_the_ID() != sc_get_study_id( get_the_ID() ) ) {
			return;
		}

		if ( get_the_content() ) {
			return;
		}

		$nav = sc_study_get_navigation( get_the_ID() );

		// if we have no content for this page, redirect to the first item
		if ( ! empty( $nav[0] ) ) {
			wp_safe_redirect( get_the_permalink( $nav[0]->ID ) );
			die();
		}

	}

	/**
	 * Setup the group attached to this study.
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_setup_study_group() {

		if ( ! is_singular( 'sc_study' ) ) {
			return;
		}

		$study_id = sc_get_study_id();

		// if the group was setup successfully, return
		if ( $this->setup_study_group() ) {
			return;
		}

		// allow editors and up to proceed
		if ( current_user_can( 'edit_post', $study_id ) ) {
			return;
		}

		// if we are allowing personal studies, we don't care if the group was setup
		if ( apply_filters( 'sc_allow_personal_studies', false, $study_id ) ) {
			return;
		}

		wp_safe_redirect( bp_loggedin_user_domain() );
		die();

	}

	/**
	 * Setup global for current group and redirect if user does not have access to this
	 * study
	 *
	 * @param bool $group_id
	 *
	 * @return bool|int
	 * @author Tanner Moushey
	 */
	public function setup_study_group( $group_id = false ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( empty( $group_id ) ) {

			if ( ! empty( $_REQUEST['sc-group'] ) ) {
				$group_id = absint( $_REQUEST['sc-group'] );

				if ( empty( $_COOKIE['sc-group'] ) || $group_id != $_COOKIE['sc-group'] ) {
					@setcookie( 'sc-group', $group_id, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
				}
			} else if ( ! empty( $_COOKIE['sc-group'] ) ) {
				$group_id = absint( $_COOKIE['sc-group'] );
			}

		}

		if ( empty( $group_id ) ) {
			return false;
		}

		bp_has_groups( 'include=' . $group_id );
		bp_groups();
		bp_the_group();

		return bp_get_group_id();

	}

	/**
	 * Remove "Private:" label from private sc_study posts
	 *
	 * @param $format
	 * @param $post
	 *
	 * @return string
	 */
	public function private_title_format( $format, $post ) {
		if ( 'sc_study' != $post->post_type ) {
			return $format;
		}

		return '%s';
	}

	public function private_study_cap( $allcaps, $caps, $args, $user ) {
		if ( empty( $user->ID ) ) {
			return $allcaps;
		}

		// we are only interested in private posts capability
		if ( ! in_array( 'read_private_posts', $caps ) ) {
			return $allcaps;
		}

		// this user can already ready private posts
		if ( isset( $allcaps['read_private_posts'] ) && $allcaps['read_private_posts'] ) {
			return $allcaps;
		}

		// make sure this is a study
		if ( empty( $args[2] ) || 'sc_study' != get_post_type( absint( $args[2] ) ) ) {
			return $allcaps;
		}

		// make sure this user has access to this study
		if ( ! self::user_can_access( absint( $args[2] ), $user->ID ) ) {
			return $allcaps;
		}

		$allcaps['read_private_posts'] = true;

		return $allcaps;
	}

	public function print_styles() {
		if ( ! is_singular( 'sc_study' ) ) {
			return;
		} ?>
		<style>
			@page {
				size: 8.5in 11in;
				margin: 10%;
			}
		</style>
		<?php
	}

	public function study_archive( $query ) {
//		if ( is_admin() ) {
//			return;
//		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( 'sc_study' != $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! $query->is_archive ) {
			return;
		}

		$query->set( 'post_parent', 0 );
	}


	/**
	 * Set the default query for studies
	 *
	 * @param \WP_Query $query
	 *
	 * @author Tanner Moushey
	 */
	public function default_groups( $query ) {
		if ( is_admin() || 'sc_study' !== $query->get( 'post_type' ) ) {
			return;
		}

		// break early if we already have a tax query
		if ( $query->get( 'tax_query' ) ) {
			return;
		}

		$tax_query = [];

		$tax_query[] = [
			'relation' => 'OR',
			[
				'taxonomy' => 'sc_group',
				'operator' => 'NOT EXISTS',
			],
			[
				'taxonomy' => 'sc_group',
				'field' => 'slug',
				'terms' => [0],
			],
		];

		$query->set( 'tax_query', $tax_query );
	}

	public function allow_private_parent( $uri, $page ) {
		if ( 'sc_study' != $page->post_type ) {
			return $uri;
		}

		$uri = $page->post_name;

		foreach ( $page->ancestors as $parent ) {
			$parent = get_post( $parent );
			if ( in_array( $parent->post_status, array( 'publish', 'private' ) ) ) {
				$uri = $parent->post_name . '/' . $uri;
			}
		}

		return $uri;
	}

	public function study_cpt() {
		$labels = array(
			'name'               => _x( 'Studies', 'post type general name', 'sc' ),
			'singular_name'      => _x( 'Study', 'post type singular name', 'sc' ),
			'add_new_item'       => __( 'Add New Study', 'sc' ),
			'new_item'           => __( 'New Study', 'sc' ),
			'edit_item'          => __( 'Edit Study', 'sc' ),
			'view_item'          => __( 'View Study', 'sc' ),
			'all_items'          => __( 'All Studies', 'sc' ),
			'search_items'       => __( 'Search Studies', 'sc' ),
			'not_found'          => __( 'No studies found.', 'sc' ),
			'not_found_in_trash' => __( 'No studies found in Trash.', 'sc' )
		);

		$args = array(
			'labels'                => $labels,
			'public'                => true,
			'rewrite'               => array(
				'slug'       => 'studies',
				'with_front' => false,
			),
			'hierarchical'          => true,
			'has_archive'           => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-welcome-write-blog',
			'show_in_rest'          => true,
			'rest_base'             => 'studies',
			'rest_controller_class' => '\StudyChurch\API\Studies',
			'supports'              => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'comments',
				'page-attributes'
			)
		);

		register_post_type( 'sc_study', $args );

		register_taxonomy( 'sc_category', 'sc_study', array(
			'hierarchical' => true,
		) );

	}

	/**
	 * Custom API vars for Study
	 *
	 * @param $preload_paths
	 * @param $post
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function api_path( $preload_paths, $post ) {
		if ( 'sc_study' !== get_post_type( $post ) ) {
			return $preload_paths;
		}

		$post_type = get_post_type_object( get_post_type( $post ) );

		return [
			'/',
			'/wp/v2/types?context=edit',
			'/wp/v2/taxonomies?per_page=-1&context=edit',
			'/wp/v2/themes?status=active',
			sprintf( '/' . studychurch()->get_api_namespace() . '/%s/%s?context=edit', $post_type->rest_base, $post->ID ),
			sprintf( '/wp/v2/types/%s?context=edit', $post_type->name ),
			sprintf( '/wp/v2/users/me?post_type=%s&context=edit', $post_type->name ),
			array( '/wp/v2/media', 'OPTIONS' ),
		];
	}

	public function study_meta() {

		$cmb = new_cmb2_box( array(
			'id'           => 'study_meta',
			'title'        => __( 'Advanced', 'sc' ),
			'object_types' => array( 'sc_study' ), // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		) );


		if ( isset( $_GET['post'] ) ) {
			$cmb->add_field( array(
				'name' => '<a href="/studio/studies/' . absint( $_GET['post'] ) . '">Edit study</a>',
				'id'   => self::$_prefix . 'edit_link',
				'type' => 'title',
			) );
		}

		$cmb->add_field( array(
			'name'    => __( 'Data Type', 'sc' ),
			'id'      => self::$_prefix . 'data_type',
			'type'    => 'select',
			'options' => array(
				''               => __( 'None', 'sc' ),
				'question_short' => __( 'Short Answer Question', 'sc' ),
				'question_long'  => __( 'Long Answer Question', 'sc' ),
				'content'        => __( 'Content', 'sc' ),
				'assignment'     => __( 'Assignment', 'sc' ),
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Privacy', 'sc' ),
			'desc' => __( 'This question is private.', 'sc' ),
			'id'   => self::$_prefix . 'privacy',
			'type' => 'checkbox',
		) );
	}

	public function maybe_add_to_org( $post_id ) {
		$study = get_post( $post_id );

		if ( 'sc_study' !== $study->post_type || $study->post_parent || ! $groups = get_the_terms( $post_id, 'sc_group' ) ) {
			return;
		}

		foreach ( (array) $groups as $group ) {
			$studies   = self::get_group_studies( $group->slug );
			$studies[] = $study->ID;
			self::update_group_studies( $group->slug, $studies );
		}

	}

	/** Study Helper Functions */

	/**
	 * @param null $group_id
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function get_group_studies( $group_id = null ) {
		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		$studies = groups_get_groupmeta( $group_id, '_sc_study', true );

		if ( empty( $studies ) ) {
			$studies = [];
		}

		if ( ! is_array( $studies ) ) {
			$studies = [ $studies ];
		}

		return $studies;
	}

	/**
	 * Update Group studies
	 *
	 * @param null  $group_id
	 * @param array $studies
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function update_group_studies( $group_id = null, $studies = [] ) {
		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		self::update_group_studies_archive( $group_id, $studies );

		return groups_update_groupmeta( $group_id, '_sc_study', array_unique( array_map( 'absint', $studies ) ) );
	}

	/**
	 * Get groups studies archive
	 *
	 * @param null $group_id
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function get_group_studies_archive( $group_id = null ) {
		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		$studies = groups_get_groupmeta( $group_id, '_sc_study_archive', true );

		if ( empty( $studies ) ) {
			$studies = [];
		}

		return $studies;
	}

	/**
	 * Save any new studies to the group archive
	 *
	 * @param null  $group_id
	 * @param array $studies
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function update_group_studies_archive( $group_id = null, $studies = [] ) {
		$archive = array_merge( self::get_group_studies_archive( $group_id ), $studies );

		return groups_update_groupmeta( $group_id, '_sc_study_all', array_unique( array_map( 'absint', $archive ) ) );
	}

	/**
	 * @param null $user_id
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function get_user_studies( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$studies = get_user_meta( $user_id, '_sc_study', true );

		if ( empty( $studies ) ) {
			$studies = [];
		}

		if ( ! is_array( $studies ) ) {
			$studies = [ $studies ];
		}

		return $studies;
	}

	/**
	 * @param null  $user_id
	 * @param array $studies
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function update_user_studies( $user_id = null, $studies = [] ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		self::update_user_studies_archive( $user_id, $studies );

		return update_user_meta( $user_id, '_sc_study', array_unique( array_map( 'absint', $studies ) ) );
	}

	/**
	 * @param null $user_id
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function get_user_studies_archive( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$studies = get_user_meta( $user_id, '_sc_study_archive', true );

		if ( empty( $studies ) ) {
			$studies = [];
		}

		return $studies;
	}

	/**
	 * @param null  $user_id
	 * @param array $studies
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function update_user_studies_archive( $user_id = null, $studies = [] ) {
		$archive = array_merge( self::get_user_studies_archive( $user_id ), $studies );

		return update_user_meta( $user_id, '_sc_study_archive', array_unique( array_map( 'absint', $archive ) ) );
	}

	/** Study Helper Functions */

	/**
	 * Customize Study link to include group parameter for study answers
	 *
	 * @param      $study_id
	 * @param null $group_id
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public static function get_group_link( $study_id, $group_id = null ) {
		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		$study_id = self::get_study_id( $study_id );

		if ( ! apply_filters( 'sc_new_group_link', false ) ) {
			return add_query_arg( 'sc-group', $group_id, get_permalink( $study_id ) );
		} else {
			$group = groups_get_group( $group_id );

			// TODO: This is wrong
			return str_replace( '/studies/', '/groups/' . bp_get_group_slug( $group ) . '/studies/', get_permalink( $study_id ) );
		}
	}

	/**
	 * Get the top level id for this study
	 *
	 * @param null $id
	 *
	 * @return bool|int|mixed|null
	 */
	public static function get_study_id( $id = null ) {

		if ( ! $id ) {
			$id = get_the_ID();
		}

		if ( $parent_id = get_post_meta( $id, '_sc_study_id', true ) ) {
			return $parent_id;
		}

		$this_id = $id;

		// keep getting parents until there are no more to get.
		while ( $parent_id = wp_get_post_parent_id( $id ) ) {
			$id = $parent_id;
		}

		// cache results
		update_post_meta( $this_id, '_sc_study_id', $id );

		return $id;
	}

	/**
	 * Get default thumbnail for studies
	 *
	 * @return mixed|string
	 * @author Tanner Moushey
	 */
	public function default_thumbnail() {
		return Settings::get( 'study_image' );
	}

	/**
	 * Can the user access this study?
	 *
	 * @param null $study_id
	 * @param null $user_id
	 *
	 * @return bool
	 */
	public static function user_can_access( $study_id = null, $user_id = null ) {
		if ( ! $study_id ) {
			$study_id = get_the_ID();
		}

		$study_id = sc_get_study_id( $study_id );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $study_id ) || empty( $user_id ) ) {
			return false;
		}

		if ( $study_access = get_user_meta( $user_id, '_studies', true ) ) {
			if ( ! empty( $study_access[ $study_id ] ) ) {
				return true;
			}
		} else {
			$study_access = array();
		}

		foreach ( groups_get_groups( 'show_hidden=true&user_id=' . $user_id )['groups'] as $group ) {
			if ( in_array( $study_id, studychurch()->study::get_group_studies( $group->id ) ) ) {
				$study_access[] = $study_id;
				update_user_meta( $user_id, '_studies', $study_access );

				return true;
			}
		}

		return false;
	}

	public static function get_data( $study ) {

		global $post;

		$orig_post = $post;
		$post      = get_post( $study );

		setup_postdata( $post );

		if ( $groups = get_the_terms( $post->ID, 'sc_group' ) ) {
			$groups = array_map( 'absint', wp_list_pluck( $groups, 'name' ) );
		}

		$data = [
			'id'           => $post->ID,
			'status'       => $post->post_status,
			'link'         => get_permalink( $post ),
			'title'        => [ 'rendered' => get_the_title( $post ) ],
			'excerpt'      => [ 'rendered' => apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $study ) ) ],
			'thumbnail'    => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'medium' ) : studychurch()->study->default_thumbnail(),
			'author'       => absint( $post->post_author ),
			'organization' => $groups ? $groups : [],
		];

		wp_reset_postdata();

		$post = $orig_post;

		return $data;
	}

}
