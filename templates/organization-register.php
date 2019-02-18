<?php

$member = new StudyChurch\Member( get_current_user_id() );

// make sure this user does not already own a group
if ( $member->is_organization_owner() ) {
	return;
}
?>

<div class="sc-org-fields">

	<fieldset class="sc-org-fieldset">

		<p id="sc-org-name-wrap">
			<label for="sc-org-name"><?php _e( 'Church/Organization Name', 'rcp-group-accounts' ); ?></label>
			<input type="text" name="sc-org-name" id="sc-org-name" />
		</p>

	</fieldset>

</div>
