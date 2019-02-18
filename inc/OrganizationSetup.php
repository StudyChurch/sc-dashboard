<?php

namespace StudyChurch;

class OrganizationSetup {

	const TYPE = 'organization';

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the OrganizationSetup
	 *
	 * @return OrganizationSetup
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof OrganizationSetup ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'bp_groups_admin_meta_boxes', [ $this, 'organization_meta_boxes' ] );
		add_action( 'bp_group_admin_edit_after', [ $this, 'organization_meta_save' ] );
		add_action( 'bp_init', [ $this, 'organization_group_type' ] );
		add_filter( 'bp_get_group_permalink', [ $this, 'org_permalink' ], 10, 2 );
		add_action( 'groups_join_group', [ $this, 'join_org' ], 10, 2 );
		add_action( 'admin_init', array( $this, 'add_roles' ) );
	}

	/**
	 * Add meta box
	 *
	 * @author Tanner Moushey
	 */
	public function organization_meta_boxes() {
		add_meta_box( 'sc_organization_meta', __( 'Organization ', studychurch()->get_id() ), [ $this, 'org_meta_cb' ], get_current_screen()->id, 'side', 'core' );
	}

	/**
	 * Register Organization group type
	 *
	 * @author Tanner Moushey
	 */
	public function organization_group_type() {
		bp_groups_register_group_type( self::TYPE );
	}

	/**
	 * Save meta
	 *
	 * @param $group_id
	 *
	 * @author Tanner Moushey
	 */
	public function organization_meta_save( $group_id ) {
		if ( ! isset( $_POST['group-member-limit'] ) ) {
			return;
		}

		$org = new Organization( $group_id );
		$org->update_member_limit( absint( $_POST['group-member-limit'] ) );
	}

	/**
	 * Meta callback
	 *
	 * @param $item
	 *
	 * @author Tanner Moushey
	 */
	public function org_meta_cb( $item ) {

		$org = new Organization( $item->id ); ?>

		<div class="bp-groups-settings-section" id="bp-groups-settings-section-invite-status">
			<fieldset>
				<legend><?php _e( 'What is the member limit for this group/organization?', studychurch()->get_id() ); ?></legend>
				<label for="sc-group-member-limit"><input type="number" name="group-member-limit" id="sc-group-member-limit" value="<?php echo $org->get_member_limit(); ?>" /></label>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Custom permalink for organization
	 *
	 * @param $link
	 * @param $group
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function org_permalink( $link, $group ) {
		if ( 'organization' != bp_groups_get_group_type( $group->id ) ) {
			return $link;
		}

		return trailingslashit( bp_get_root_domain() . '/organizations/' . bp_get_group_slug( $group ) );
	}

	/**
	 * Make sure that users are automatically added to organization groups.
	 *
	 * @param $group_id
	 * @param $user_id
	 *
	 * @author Tanner Moushey
	 */
	public function join_org( $group_id, $user_id ) {
		$group = groups_get_group( $group_id );

		if ( ! $group->parent_id ) {
			return;
		}

		groups_join_group( $group->parent_id, $user_id );
	}

	/**
	 * Add custom roles
	 *
	 * @author Tanner Moushey
	 */
	public function add_roles() {

		if ( 2 === get_option( 'sc_updated_roles' ) ) {
			return;
		}

		add_role( 'leader', __( 'Leader', 'sc' ), array( 'read' => true ) );
		add_role( 'organization', __( 'Organization', 'sc' ), array( 'read' => true ) );

		update_option( 'sc_updated_roles', 2, 'no' );

	}

}