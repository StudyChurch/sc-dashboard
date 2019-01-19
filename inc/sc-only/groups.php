<?php

SC_Member_Groups::get_instance();
class SC_Member_Groups {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the SC_Member_Groups
	 *
	 * @return SC_Member_Groups
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof SC_Member_Groups ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_filter( 'user_has_cap', array( $this, 'user_can_create_groups' ), 10, 4 );
	}

	/**
	 * Everyone can create groups
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 * @param $user
	 *
	 * @return mixed
	 */
	public function user_can_create_groups( $allcaps, $caps, $args, $user ) {
		if ( empty( $user->ID ) ) {
			return $allcaps;
		}

		$allcaps['manage_groups'] = true;
		return $allcaps;
	}

}