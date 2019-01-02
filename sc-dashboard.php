<?php

/**
 * Plugin Name: StudyChurch Dashboard
 * Description: Dashboard for StudyChurch
 * Version: 1.0.0
 * Author: StudyChurch
 * Author URI: https://study.church
 * Contributors: tannerm
 * Text Domain: sc-dashboard
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class SC_Dashboard {

	/**
	 * @var
	 */
	protected static $_instance;

	protected static $_version = '0.1.0';

	/**
	 * Only make one instance of the SC_Dashboard
	 *
	 * @return SC_Dashboard
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof SC_Dashboard ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->includes();
		$this->add_actions();
		$this->add_filters();
	}

	public function get_version() {
		return self::$_version;
	}

	public function get_url() {
		return plugin_dir_url( __FILE__ );
	}

	public function get_dir() {
		return plugin_dir_path( __FILE__ );
	}

	protected function includes() {}

	/**
	 * Wire up actions
	 */
	protected function add_actions() {}

	protected function add_filters() {
		add_filter( 'template_include', array( $this, 'app_template' ), 11 );
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

	/**
	 * Enqueue Styles
	 */
	protected function enqueue_styles() {
		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
	}

	/**
	 * Enqueue scripts
	 */
	protected function enqueue_scripts() {
		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
	}

	public function app_template( $template ) {
		if ( ! is_user_logged_in() ) {
			return $template;
		}

		if ( in_array( $_SERVER['REQUEST_URI'], array( '/', '/settings/', '/notifications/' ) ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'groups/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'organizations/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'studies/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		return $template;
	}
}

function sc_dashboard() {
	return SC_Dashboard::get_instance();
}