<?php

namespace StudyChurch\RCP;

use StudyChurch\Member;
use StudyChurch\Organization;
use StudyChurch\OrganizationSetup;

class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->hooks();
	}

	/**
	 * Actions and Filters
	 */
	protected function hooks() {
		// Add form field to subscription level add and edit forms
		add_action( 'rcp_add_subscription_form', array( $this, 'subscription_seat_count' ) );
		add_action( 'rcp_edit_subscription_form', array( $this, 'subscription_seat_count' ) );

		// Actions for saving subscription seat count
		add_action( 'rcp_edit_subscription_level', array( $this, 'subscription_level_save_settings' ), 10, 2 );
		add_action( 'rcp_add_subscription', array( $this, 'subscription_level_save_settings' ), 10, 2 );

		add_filter( 'rcp_template_stack', array( $this, 'template_stack' ) );
		add_action( 'rcp_after_register_form_fields', array( $this, 'registration_fields' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_action( 'rcp_form_processing', array( $this, 'create_pending_org' ), 10, 3 );
		add_action( 'rcp_set_status', array( $this, 'register_member_group' ), 10, 4 );

	}

	/**
	 * Enqueue frontend scripts
	 */
	public function scripts() {
		wp_register_script( 'sc-org-accounts', studychurch()->get_url() . '/assets/org-accounts.js', array( 'jquery' ), studychurch()->get_version(), true );
		wp_localize_script( 'sc-org-accounts', 'scOrgLevelMap', self::get_org_levels() );
	}

	/**
	 * Output registration fields for Group Accounts
	 */
	public function registration_fields() {
		rcp_get_template_part( 'organization', 'register' );
		wp_enqueue_script( 'sc-org-accounts' );
	}

	public function subscription_seat_count( $level = null ) {

		$is_org       = ( empty( $level->id ) ) ? false : self::is_organization_level( $level->id );
		$url = ( empty( $level->id ) ) ? 0 : self::get_upgrade_url( $level->id );
		$member_count = ( empty( $level->id ) ) ? 0 : self::get_member_count( $level->id );
		$study_count = ( empty( $level->id ) ) ? 0 : self::get_study_count( $level->id );
		$group_count  = ( empty( $level->id ) ) ? 0 : self::get_group_count( $level->id ); ?>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="sc-upgrade-url"><?php _e( 'Upgrade URL', studychurch()->get_id() ); ?></label>
			</th>
			<td>
				<input id="sc-upgrade-url" type="text" name="sc-upgrade-url" value="<?php echo esc_url( $url ); ?>" style="width: 300px;" />
				<p class="description"><?php _e( 'The URL for the level to upgrade to.', studychurch()->get_id() ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="sc-group-count"><?php _e( 'Groups', studychurch()->get_id() ); ?></label>
			</th>
			<td>
				<input id="sc-group-count" type="number" name="sc-group-count" value="<?php echo intval( $group_count ); ?>" min="-1" style="width: 100px;" />
				<p class="description"><?php _e( 'The number of group available to this level. -1 for infinite.', studychurch()->get_id() ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="sc-study-count"><?php _e( 'Studies', studychurch()->get_id() ); ?></label>
			</th>
			<td>
				<input id="sc-study-count" type="number" name="sc-study-count" value="<?php echo intval( $study_count ); ?>" min="-1" style="width: 100px;" />
				<p class="description"><?php _e( 'The number of studies available to this level. -1 for infinite.', studychurch()->get_id() ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="sc-is-organization"><?php _e( 'Is Organization', studychurch()->get_id() ); ?></label>
			</th>
			<td>
				<input id="sc-is-organization" type="checkbox" name="sc-is-organization" <?php checked( $is_org ); ?> />
				<p class="description"><?php _e( 'Check to mark this as an organization account.', studychurch()->get_id() ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="sc-member-count"><?php _e( 'Members', studychurch()->get_id() ); ?></label>
			</th>
			<td>
				<input id="sc-member-count" type="number" name="sc-member-count" value="<?php echo intval( $member_count ); ?>" min="-1" style="width: 100px;" />
				<p class="description"><?php _e( 'The number of members available to this organization including the account owner. -1 for infinite.', studychurch()->get_id() ); ?></p>
			</td>
		</tr>

		<?php
	}

	/**
	 * Save the member type for this subscription
	 *
	 * @param $level_id
	 * @param $args
	 */
	public function subscription_level_save_settings( $level_id, $args ) {

		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;


		if ( isset( $_POST['sc-is-organization'] ) ) {
			$rcp_levels_db->update_meta( $level_id, 'sc_is_organization', true );
		} else {
			$rcp_levels_db->update_meta( $level_id, 'sc_is_organization', false );
		}

		if ( isset( $_POST['sc-upgrade-url'] ) ) {
			$rcp_levels_db->update_meta( $level_id, 'sc_upgrade_url', esc_url_raw( $_POST['sc-upgrade-url'] ) );
		}

		if ( isset( $_POST['sc-member-count'] ) ) {
			$rcp_levels_db->update_meta( $level_id, 'sc_member_count', intval( $_POST['sc-member-count'] ) );
		}

		if ( isset( $_POST['sc-study-count'] ) ) {
			$rcp_levels_db->update_meta( $level_id, 'sc_study_count', intval( $_POST['sc-study-count'] ) );
		}

		if ( isset( $_POST['sc-group-count'] ) ) {
			$rcp_levels_db->update_meta( $level_id, 'sc_group_count', intval( $_POST['sc-group-count'] ) );
		}

	}

	/**
	 * Get the id of the levels that support Organizations
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public static function get_org_levels() {
		$levels     = rcp_get_subscription_levels( 'active' );
		$org_levels = [];
		foreach ( $levels as $level ) {
			if ( self::is_organization_level( $level->id ) ) {
				$org_levels[] = $level->id;
			}
		}

		return apply_filters( 'sc_get_org_levels', $org_levels );
	}

	/**
	 * Return if the provided level is an organization
	 *
	 * @param $level_id
	 *
	 * @return bool
	 * @author Tanner Moushey
	 */
	public static function get_upgrade_url( $level_id ) {
		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return $rcp_levels_db->get_meta( $level_id, 'sc_upgrade_url', true );
	}

	/**
	 * Return if the provided level is an organization
	 *
	 * @param $level_id
	 *
	 * @return bool
	 * @author Tanner Moushey
	 */
	public static function is_organization_level( $level_id ) {
		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return boolval( $rcp_levels_db->get_meta( $level_id, 'sc_is_organization', true ) );
	}

	/**
	 * @param $level_id
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public static function get_study_count( $level_id ) {
		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return intval( $rcp_levels_db->get_meta( $level_id, 'sc_study_count', true ) );
	}

	/**
	 * @param $level_id
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public static function get_member_count( $level_id ) {
		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return intval( $rcp_levels_db->get_meta( $level_id, 'sc_member_count', true ) );
	}

	/**
	 * @param $level_id
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public static function get_group_count( $level_id ) {
		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return intval( $rcp_levels_db->get_meta( $level_id, 'sc_group_count', true ) );
	}

	/**
	 * Filter RCP Template
	 *
	 * @param $template_stack
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function template_stack( $template_stack ) {
		$template_stack[] = studychurch()->get_dir() . '/templates';

		return $template_stack;
	}

	/**
	 * Save org details in meta for successful registration
	 *
	 * @author Tanner Moushey
	 */
	public function create_pending_org( $post, $user_id, $price ) {
		// make sure we have a subscription
		if ( ! $level_id = rcp_get_registration()->get_membership_level_id() ) {
			return;
		}

		$member = new Member( $user_id );

		// make sure this user does not already own a group
		if ( $member->is_organization_owner() ) {
			return;
		}

		if ( ! self::is_organization_level( $level_id ) ) {
			return;
		}

		// finally, make sure we have a group name
		if ( empty( $post['sc-org-name'] ) ) {
			return;
		}

		$args = array(
			'members'    => absint( self::get_member_count( $level_id ) ),
			'name'       => wp_unslash( sanitize_text_field( $post['sc-org-name'] ) ),
			'creator_id' => $user_id,
		);

		update_user_meta( absint( $user_id ), 'sc_pending_org', $args );
	}

	/**
	 * Create a group for this member when their account is activated
	 *
	 * @param string      $status     New status being set.
	 * @param int         $user_id    ID of the user.
	 * @param string      $old_status Previous status.
	 * @param \RCP_Member $member     Member object.
	 *
	 * @access public
	 * @return void
	 */
	public function register_member_group( $status, $user_id, $old_status, $member ) {

		if ( ! in_array( $status, array( 'active', 'free' ) ) ) {
			return;
		}

		$args   = get_user_meta( $user_id, 'sc_pending_org', true );
		$user    = new Member( $user_id );

		if ( empty( $args ) || ! is_array( $args ) ) {

			if ( ! $group_id = $user->is_organization_owner() ) {
				return;
			}

			// If this is a group owner moving to a non-Group Accounts level, delete their group.
			if ( ! self::is_organization_level( $member->get_subscription_id() ) ) {

				// @todo delete org group if the new subscription doesn't support it
				if ( function_exists( 'rcp_log' ) ) {
					rcp_log( sprintf( 'StudyChurch: User #%d is an organization owner who has moved to a new membership that does not support organization. Need to delete group #%d.', $user_id, $group_id ) );
				}

			}

			return;
		}

		$prevous_groups = $user->get_owned_groups();

		$group_id = groups_create_group( [
			'creator_id' => $user_id,
			'status'     => 'hidden',
			'name'       => $args['name'],
		] );

		if ( ! $group_id ) {
			if ( function_exists( 'rcp_log' ) ) {
				rcp_log( sprintf( 'StudyChurch: tried to create organization group, but ran into an error.' ) );
			}

			return;
		}

		bp_groups_set_group_type( $group_id, OrganizationSetup::TYPE );

		$org = new Organization( $group_id );

		foreach( $prevous_groups as $group ) {
			groups_create_group( [
				'group_id' => $group->id,
				'parent_id' => $group_id, // set the parent id equal to the new organization
			] );

			$group = new Organization( ( $group->id ) );
			$org->update_premium_access( $group->get_premium_access() );
		}

		delete_user_meta( $user_id, 'sc_pending_org' );
	}

}
