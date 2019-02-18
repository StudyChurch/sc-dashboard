<?php

namespace StudyChurch;

use StudyChurch\RCP\Settings;

class Capabilities {

	/**
	 * @var
	 */
	protected static $_instance;

	protected static $_member_caps = [];

	/**
	 * Only make one instance of the Capabilities
	 *
	 * @return Capabilities
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Capabilities ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_filter( 'user_has_cap', [ $this, 'cap_router' ], 10, 4 );
		add_action( 'init', [ $this, 'member_caps' ], 2 );
		add_action( 'set_current_user', [ $this, 'member_caps' ], 2 );
	}

	public function member_caps() {

		if ( ! function_exists( 'rcp_get_customer' ) ) {
			return;
		}

		if ( ! $customer = rcp_get_customer() ) {
			return;
		}

		$member = new Member();

		self::$_member_caps = [
			'create_study' => $member->can_create_study(),
			'create_group' => $member->can_create_group(),
		];

	}

	public function cap_router( $allcaps, $caps, $args, $user ) {
		if ( ! class_exists( 'RCP_Customer' ) ) {
			return $allcaps;
		}

		return array_merge( $allcaps, self::$_member_caps );
	}

}