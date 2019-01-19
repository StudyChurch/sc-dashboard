<?php

namespace StudyChurch\Assignments;


class Group extends \BP_Group_Extension {

	function __construct() {
		parent::init( array(
			'slug'              => 'assignments',
			'name'              => 'Assignments',
			'nav_item_position' => 105,
		) );

	}

	function display( $group_id = null ) {
		bp_get_template_part( 'assignments/edit' );
	}

}
