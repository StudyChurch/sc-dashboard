<?php

namespace StudyChurch;

use WP_Error;

/**
 * Awesome Support API main plugin class.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * @var object StudyChurch\API\Auth\Init
	 */
	public $auth;

	/**
	 * Instance of this loader class.
	 *
	 * @since    0.1.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * StudyChurch constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->actions();
		$this->filters();
	}

	/**
	 * Handle Actions
	 */
	protected function actions() {
		add_action( 'rest_api_init', array( $this, 'load_api_routes' ), 20 ); // load after BuddyPress
	}

	/**
	 * Handle Filters
	 */
	protected function filters() {}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		$this->auth = API\Auth\Init::get_instance();
	}


	/** Actions ******************************************************/

	/**
	 * Load APIs that are not loaded automatically
	 */
	public function load_api_routes() {
		$controller = new API\Users();
		$controller->register_routes();

		$controller = new API\Passwords();
		$controller->register_routes();

		$controller = new API\Attachments();
		$controller->register_routes();

		if ( class_exists( 'BP_REST_Activity_Endpoint' ) ) {
			$controller = new API\Activity();
			$controller->register_routes();
		}

		if ( class_exists( 'BP_REST_Groups_Endpoint' ) ) {
			$controller = new API\Groups();
			$controller->register_routes();
		}

		$controller = new API\Authenticate();
		$controller->register_routes();

	}

	/** Filters ******************************************************/

	/** Additional API fields ******************************************************/


	/** Helper methods ******************************************************/


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}