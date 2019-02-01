<?php

namespace StudyChurch\Study;

class Edit {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Edit
	 *
	 * @return Edit
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Edit ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_study_save' ) );
		add_action( 'wp', array( $this, 'study_edit_actions' ) );

		add_action( 'sc_study_edit_sidebar_before', array( $this, 'study_status' ) );

		add_filter( 'map_meta_cap', array( $this, 'can_user_edit_study' ), 10, 4 );
		add_filter( 'json_dispatch_args', array( $this, 'can_user_edit_study' ), 10, 4 );
	}

	public function maybe_study_save() {

		if ( ! isset( $_POST['study_id'], $_POST['study-save-nonce'], $_POST['step'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['study-save-nonce'], 'study-save' ) ) {
			return;
		}

		// make sure this user is the post author
		if ( ! current_user_can( 'edit_post', $_POST['study_id'] ) ) {
			return;
		}

		$function = 'handle_save_' . $_POST['step'];

		if ( ! method_exists( $this, $function ) ) {
			return;
		}

		$this->$function();

	}

	protected function handle_save_study() {
		$study_id = sc_get( 'study_id' );
		$title    = sc_get( 'study-title' );
		$excerpt  = sc_get( 'study-thesis' );

		if ( empty( $study_id ) || empty( $title ) || empty( $excerpt ) ) {
			return;
		}

		wp_update_post( array(
			'ID'           => absint( $study_id ),
			'post_title'   => sanitize_text_field( $title ),
			'post_excerpt' => wp_filter_post_kses( $excerpt ),
		) );


		wp_safe_redirect( add_query_arg( 'study', $study_id, get_permalink() ) );
		exit();
	}

	/**
	 * Save study format
	 */
	protected function handle_save_format() {

		$study_id = absint( $_POST['study_id'] );

		$format = sc_get( 'study-format', 'lesson' );

		// make sure we have an expected value;
		$format = ( in_array( $format, array( 'lesson', 'week-5', 'week-7' ) ) ) ? $format : 'lesson';

		update_post_meta( $study_id, '_sc_study_format', $format );

		wp_safe_redirect( get_permalink() );
		exit();

	}

	/**
	 * Save study format
	 */
	protected function handle_save_intro() {

		$study_id = absint( $_POST['study_id'] );

		$study                 = get_object_vars( get_post( $study_id ) );
		$study['post_content'] = wp_kses_post( $_POST['study-description'] );

		if ( empty( $study['post_content'] ) ) {
			update_post_meta( $study_id, '_sc_study_no_intro', 1 );
		} else {
			wp_update_post( $study );
		}

		wp_safe_redirect( $_SERVER['REQUEST_URI'] );
		exit();

	}

	public function study_edit_actions() {
		if ( 'templates/study-manage.php' != get_page_template_slug() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'study_edit_scripts' ) );
		add_action( 'wp_footer', array( $this, 'study_edit_templates' ) );
	}

	public function study_edit_scripts() {
	}

	/**
	 * Print Backbone templates
	 */
	public function study_edit_templates() {
		$slug = 'partials/backbone/study-edit';
		get_template_part( $slug, 'chapter' );
		get_template_part( $slug, 'chapter-sidebar' );
		get_template_part( $slug, 'item' );
	}

	public function can_user_edit_study( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// short circuit everyone can add study capabilities
		if ( ! apply_filters( 'sc_everyone_can_add_studies', true ) ) {
			return $caps;
		}

		// everyone can edit their own posts
		switch ( $cap ) {
			case 'edit_posts' :
			case 'publish_posts' :
				$caps = array( 'exist' );
				break;
			case 'edit_post' :
			case 'delete_post' :
			case 'read_post' :
				if ( empty( $args[0] ) ) {
					break;
				}

				$study = get_post( sc_get_study_id( $args[0] ) );

				if ( $study->post_author == $user_id ) {
					$caps = array( 'exist' );
					break;
				}

				if ( $groups = get_the_terms( $study, 'sc_group' ) ) {
					foreach ( $groups as $group ) {
						if ( groups_is_user_admin( get_current_user_id(), $group->name ) ) {
							$caps = array( 'exist' );
							break;
						}
					}
				}

				break;
		}

		return $caps;
	}

	public function study_status( $study_id ) {
		if ( 'pending' == get_post_status( $study_id ) ) : ?>
			<p class="description"><?php _e( 'This study is pending approval.', 'sc' ); ?></p>
		<?php elseif ( 'draft' == get_post_status( $study_id ) ) : ?>
			<p class="description"><?php _e( 'This study is in draft mode.', 'sc' ); ?></p>
		<?php endif;
	}

	/**
	 * Get the current step for this study
	 *
	 * @param $study_id
	 *
	 * @return string
	 */
	public static function get_current_step( $study_id ) {

		if ( sc_get( 'step' ) ) {
			return sc_get( 'step' );
		}

		if ( ! get_the_title( $study_id ) ) {
			return 'title';
		}

		if ( ! get_post_meta( $study_id, '_sc_study_format' ) ) {
			update_post_meta( $study_id, '_sc_study_format', 'lesson' );
			// return 'format';
		}

		//	if ( ( ! get_post( $study_id )->post_content ) && ( ! get_post_meta( $study_id, '_sc_study_no_intro', true ) ) ) {
		//		return 'intro';
		//	}

		return 'content';
	}

	/**
	 * Does the current study edit step have the sidebar?
	 *
	 * @return bool
	 */
	public static function step_has_sidebar( $step = null ) {
		if ( ! $step ) {
			$step = self::get_current_step( sc_get( 'study' ) );
		}

		return ( in_array( $step, array( 'content' ) ) );
	}

	/**
	 * Footer nav map for study edit
	 *
	 * @param $study_id
	 * @param $current_step
	 *
	 * @return array
	 */
	public static function get_manage_map( $study_id, $current_step ) {
		$nav = array();

		$study_map = array(
			'title'   => __( 'Title & Description', 'sc' ),
			'format'  => __( 'Format', 'sc' ),
			'intro'   => __( 'Introduction', 'sc' ),
			'content' => __( 'Content', 'sc' ),
		);

		if ( 'publish' == get_post_status( $study_id ) ) {
			unset( $study_map['publish'] );
		}

		foreach ( $study_map as $step => $label ) {
			$class = ( $current_step == $step ) ? ' class="current"' : '';
			$nav[] = sprintf( '<span %s >%s</span>', $class, esc_html( $label ) );
		}

		return $nav;
	}
}
