<?php

namespace StudyChurch;

class Library {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Library
	 *
	 * @return Library
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Library ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		if ( ! defined( 'EDD_SLUG' ) ) {
			define('EDD_SLUG', 'library');
		}
	}

}