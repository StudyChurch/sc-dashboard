<?php

namespace StudyChurch;

use StudyChurch\RCP\Settings;

class Member extends \WP_User {

	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		if ( empty( $id ) ) {
			$id = get_current_user_id();
		}

		parent::__construct( $id, $name, $site_id );
	}

	/**
	 * Return the RCP Customer
	 *
	 * @return false|\RCP_Customer
	 * @author Tanner Moushey
	 */
	public function get_customer() {
		return rcp_get_customer_by_user_id( $this->ID );
	}

	/**
	 * Get the member limit for the corresponding organization/group.
	 *
	 * @return int|mixed
	 * @author Tanner Moushey
	 */
	public function get_org_member_limit() {
		foreach ( $this->get_memberships() as $membership ) {
			return Settings::get_member_count( $membership->get_object_id() );
		}

		return 0;
	}

	/**
	 * Gets the groups owned by this user that are not part of an organization
	 *
	 * @return array|null|object
	 * @author Tanner Moushey
	 */
	public function get_owned_groups() {
		global $wpdb, $bp;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} WHERE creator_id = %d AND parent_id=0", $this->ID ) );
	}

	/**
	 * Whether or not the user can purchase a group/organization licence for studies
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function can_purchase_group_licenses() {
		$groups = [];
		$orgs = $this->get_organizations();

		foreach( $orgs as $org ) {
			if ( groups_is_user_admin( $this->ID, $org->id ) ) {
				$groups[] = $org;
			}
		}

		// if there are no applicable org groups, check to see if the user has created a non org group
		if ( empty( $groups ) ) {
			foreach( $this->get_owned_groups() as $group ) {
				$groups[] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Determine if user can create a study
	 *
	 * @return bool|mixed
	 * @author Tanner Moushey
	 */
	public function can_create_study() {
		if ( ! $studies = get_user_meta( $this->ID, '_sc_create_studies', true ) ) {

			foreach ( $this->get_memberships() as $membership ) {
				$count = Settings::get_study_count( $membership->get_object_id() );

				if ( - 1 == $count ) {
					return true;
				}

				if ( $count > 0 ) {
					return $count;
				}
			}

		}

		return $studies;
	}

	/**
	 * Determine if user can create a group
	 *
	 * @return bool|mixed
	 * @author Tanner Moushey
	 */
	public function can_create_group() {
		if ( ! $groups = get_user_meta( $this->ID, '_sc_create_groups', true ) ) {
			foreach ( $this->get_memberships() as $membership ) {
				$count = Settings::get_group_count( $membership->get_object_id() );

				if ( - 1 == $count ) {
					return true;
				}

				if ( $count > 0 ) {
					return $count;
				}
			}
		}

		return $groups;
	}

	/**
	 * Get the user's memberships
	 *
	 * @param array $status
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_memberships( $status = [ 'active', 'cancelled', 'free' ]) {
		if ( ! $customer = $this->get_customer() ) {
			return [];
		}

		return $customer->get_memberships( [ 'status' => $status ] );
	}

	/**
	 * Get the link for the user to upgrade to the next level.
	 *
	 * @return bool|string
	 * @author Tanner Moushey
	 */
	public function get_upgrade_link() {
		$memberships = $this->get_memberships();

		foreach ( $memberships as $key => $membership ) {
			return Settings::get_upgrade_url( $membership->get_object_id() );
		}

		return '';
	}

	/**
	 * Return if the user is an organization owner, if so return the org id
	 *
	 * @return bool | int
	 * @author Tanner Moushey
	 */
	public function is_organization_owner() {
		foreach ( $this->get_organizations() as $org ) {
			if ( $org->creator_id === $this->ID ) {
				return $org->id;
			}
		}

		return false;
	}

	/**
	 * Return organizations for user
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_organizations() {
		$groups = groups_get_groups( [
			'user_id'        => $this->ID,
			'group_type__in' => OrganizationSetup::TYPE,
			'show_hidden'    => true
		] );

		return $groups['groups'];
	}

	/**
	 * Get the premium categories that this user has access to
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_premium_access() {
		if ( ! $access = get_user_meta( $this->ID, '_sc_premium_access', true ) ) {
			$access = [];
		}

		return apply_filters( 'sc_get_premium_access', $access, $this );
	}

	/**
	 * Update premium access cats
	 *
	 * @param array $cats
	 *
	 * @author Tanner Moushey
	 */
	public function update_premium_access( $cats ) {
		$access = $this->get_premium_access();
		$access = array_merge( $access, $cats );

		update_user_meta( $this->ID, '_sc_premium_access', array_map( 'sanitize_text_field', $access ) );
	}

}