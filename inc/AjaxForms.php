<?php

namespace StudyChurch;

class AjaxForms {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the AjaxForms
	 *
	 * @return AjaxForms
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof AjaxForms ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'wp_ajax_sc_ajax_form',         array( $this, 'trigger_form' ) );
		add_action( 'wp_ajax_nopriv_sc_ajax_form',  array( $this, 'trigger_form' ) );
	}

	public function trigger_form() {

		if ( empty( $_POST['formdata'] ) ) {
			wp_send_json_error();
		}

		$data = array();
		wp_parse_str( $_POST['formdata'], $data );

		if ( is_user_logged_in() ) {
			do_action( 'sc_ajax_form_' . $data['action'], $data );
		} else {
			do_action( 'sc_ajax_form_nopriv_' . $data['action'], $data );
		}

	}

}