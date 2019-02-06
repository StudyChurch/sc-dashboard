<?php

namespace StudyChurch;

use RCP_Customer;

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
//		$customer = rcp_get_customer();
//
//		/**
//		 * Processes the billing card update. Individual gateways hook into here.
//		 *
//		 * @param \RCP_Membership $membership
//		 *
//		 * @since 3.0
//		 */
//		foreach ( $customer->get_memberships() as $membership ) {
//			if ( $membership->is_active() ) {
//				self::$_member_caps = [
//					'create_study' => true,
//					'create_group' => true,
//				];
//			}
//		}

	}

	public function cap_router( $allcaps, $caps, $args, $user ) {
		if ( ! class_exists( 'RCP_Customer' ) ) {
			return $allcaps;
		}

		return array_merge( $allcaps, self::$_member_caps );
	}

}