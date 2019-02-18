<?php

namespace StudyChurch;

use BP_Groups_Group;

class Organization extends BP_Groups_Group {

	const MEMBER_LIMIT_KEY = '_sc_member_limit';

	/**
	 * Return the member limit for this group
	 *
	 * @return int
	 * @author Tanner Moushey
	 */
	public function get_member_limit() {
		if ( ! $limit = absint( groups_get_groupmeta( $this->id, self::MEMBER_LIMIT_KEY, true ) ) ) {
			$owner = new Member( $this->creator_id );
			$limit = $owner->get_org_member_limit();
		}

		return $limit;
	}

	/**
	 * Update the member limit for this group
	 *
	 * @param $limit
	 *
	 * @return bool|int
	 * @author Tanner Moushey
	 */
	public function update_member_limit( $limit ) {
		return groups_update_groupmeta( $this->id, self::MEMBER_LIMIT_KEY, absint( $limit ) );
	}

	/**
	 * Get the premium categories that this group has access to
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_premium_access() {
		if ( ! $access = groups_get_groupmeta( $this->id, '_sc_premium_access', true ) ) {
			$access = [];
		}

		return apply_filters( 'sc_get_premium_access', $access, $this );
	}

	/**
	 * Update the premium categories that this group has access to
	 *
	 * @param $cats
	 *
	 * @author Tanner Moushey
	 */
	public function update_premium_access( $cats ) {
		$access = $this->get_premium_access();
		$access = array_merge( $access, $cats );

		groups_update_groupmeta( $this->id, '_sc_premium_access', array_map( 'sanitize_text_field', $access ) );
	}
}