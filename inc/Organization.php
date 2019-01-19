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
		return absint( groups_get_groupmeta( $this->id, self::MEMBER_LIMIT_KEY, true ) );
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
}