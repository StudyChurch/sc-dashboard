<?php

namespace StudyChurch;

class Join {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Join
	 *
	 * @return Join
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Join ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {

	}

}