<?php

namespace StudyChurch;

class Assignments {

	/**
	 * @var
	 */
	protected static $_instance;

	public $notifications;
	public $templates;

	/**
	 * Only make one instance of the Assignments
	 *
	 * @return Assignments
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Assignments ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->notifications = Assignments\Notifications::get_instance();

		add_action( 'bp_init', array( $this, 'register_group_extension' ) );
		add_action( 'init', array( $this, 'ass_cpt' ) );
		add_action( 'template_redirect', array( $this, 'save_assignment' ) );
		add_action( 'template_redirect', array( $this, 'delete_assignment' ) );
	}

	public function register_group_extension() {
		// if we aren't in a group, don't bother
		if ( ! bp_is_group() || ! bp_is_active( 'groups' ) || ! class_exists( 'BP_Group_Extension' ) ) {
			return;
		}

		bp_register_group_extension( 'StudyChurch\Assignments\Group' );
	}

	public function ass_cpt() {
		register_post_type( 'sc_assignment', array(
			'show_in_rest' => true,
		    'rest_controller_class' => 'StudyChurch\API\Assignments'
		) );
		register_taxonomy( 'sc_group', ['sc_assignment', 'sc_study'], array(
			'public' => false,
		    'show_ui' => true,
		    'show_in_rest' => true,
		    'label' => 'Groups',
		    'meta_box_cb' => 'post_categories_meta_box'
		) );
	}

	public function delete_assignment() {
		if ( ! isset( $_POST['delete_assignment_nonce'] ) ) {
			return;
		}

		if ( empty( $_POST['assignment'] ) || ! wp_verify_nonce( $_POST['delete_assignment_nonce'], 'delete_assignment' ) ) {
			return;
		}

		if ( sc_delete_group_assignment( absint( $_POST['assignment'] ) ) ) {
			bp_core_add_message( __( 'Success! Assignment was deleted.', 'sc' ), 'success' );
			wp_safe_redirect( $_SERVER['REQUEST_URI'] );
			exit();
		} else {
			bp_core_add_message( __( 'Ooops. Something went wrong, please try again.', 'sc' ), 'error' );
		}
	}

	public function save_assignment() {

		// If no form submission, bail
		if ( ! isset( $_POST['new_assignment_nonce'] ) ) {
			return;
		}

		// make sure this user can manage assignments
		if ( ! sc_user_can_manage_group() ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['new_assignment_nonce'], 'create_new_assignment' ) ) {
			bp_core_add_message( __( 'Ooops. Something went wrong, please try again.', 'sc' ), 'error' );

			return;
		}

		if ( sc_add_group_assignment( $_POST, bp_get_current_group_id() ) ) {
			bp_core_add_message( __( 'Success! Created a new assignment', 'sc' ), 'success' );
			wp_safe_redirect( $_SERVER['REQUEST_URI'] );
			exit();
		} else {
			bp_core_add_message( __( 'Ooops. Something went wrong, please make sure you have specified content or lessons and a due date.', 'sc' ), 'error' );
		}

	}

	/** Study Helper Functions */

	/**
	 * Customize Study link to include group parameter for study answers
	 *
	 * @param      $lesson_id
	 * @param null $group_id
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public static function get_group_link( $lesson_id, $group_id = null ) {
		if ( ! $group_id ) {
			$group_id = bp_get_current_group_id();
		}

		return add_query_arg( 'sc-group', $group_id, get_permalink( $lesson_id ) );
	}

}