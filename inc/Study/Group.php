<?php

namespace StudyChurch\Study;

class Group extends \BP_Group_Extension {

	public $screen = null;

	public static $_slug = 'study';

	public static $_name = 'Study';

	/**
	 * Constructor
	 */
	public function __construct() {

		/**
		 * Init the Group Extension vars
		 */
		$this->init_vars();

		/**
		 * Add actions and filters
		 */
		$this->setup_hooks();

	}

	public function setup_hooks() {}

	/** Group extension methods ***************************************************/

	/**
	 * Registers the and sets some globals
	 *
	 * @uses buddypress()                         to get the BuddyPress instance
	 * @uses BP_Group_Extension::init()
	 */
	public function init_vars() {
		$args = array(
			'slug'              => self::$_slug,
			'name'              => self::$_name,
			'visibility'        => 'private',
			'nav_item_position' => 80,
			'enable_nav_item'   => false,
			'screens'           => array(
				'admin'  => array(
					'name'             => self::$_name,
					'enabled'          => true,
					'metabox_context'  => 'side',
					'metabox_priority' => 'core'
				),
				'create' => array(
					'enabled' => false,
				),
				'edit'   => array(
					'name'     => self::$_name,
					'position' => 80,
					'enabled'  => true,
				),
			)
		);

		parent::init( $args );
	}

	/**
	 * Group extension settings form
	 *
	 * Used in Group Administration, Edit and Create screens
	 *
	 *
	 * @param  int $group_id the group ID
	 *
	 * @uses   is_admin()                            to check if we're in WP Administration
	 * @uses   checked()                             to add a checked attribute to checkbox if needed
	 * @uses   Groups::group_get_option() to get the needed group metas.
	 * @uses   bp_is_group_admin_page()              to check if the group edit screen is displayed
	 * @uses   wp_nonce_field()                      to add a security token to check upon once submitted
	 * @return string                                html output
	 */
	public function edit_screen( $group_id = null ) {

		$is_admin = is_admin();

		$studies = new \WP_Query();
		$studies = $studies->query( 'post_type=sc_study&numberposts=-1&post_parent=0' );

		if ( ! $is_admin ) : ?>

			<h4><?php printf( esc_html__( 'Group %s', 'sc' ), $this->name ); ?></h4>

		<?php endif; ?>

		<?php if ( $is_admin ) : ?>

			<legend class="screen-reader-text"><?php printf( esc_html__( 'Group %s', 'sc' ), $this->name ); ?></legend>

		<?php endif; ?>

		<p><?php _e( 'Select a study for this group or head over to your profile and write a new one!', 'sc' ); ?></p>

		<div class="studies radio">
			<table style="width: 100%;">
				<tbody>
				<?php foreach ( $studies as $study ) : ?>
					<tr>
						<td>
							<input type="checkbox" id="study-<?php echo absint( $study->ID ); ?>" name="_sc_study[]" value="<?php echo absint( $study->ID ); ?>" <?php checked( in_array( $study->ID, (array) self::group_get_option( $group_id, '_sc_study' ) ) ); ?>/>
						</td>
						<td>
							<label for="study-<?php echo absint( $study->ID ); ?>">
								<h4><?php echo get_the_title( $study->ID ); ?></h4>

								<p class="small"><?php echo apply_filters( 'get_the_excerpt', $study->post_excerpt ); ?></p>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( bp_is_group_admin_page() ) : ?>
			<input type="submit" name="save" value="<?php _e( 'Save Group Study', 'sc' ); ?>" class="button secondary expand" />
		<?php endif; ?>

		<?php
		wp_nonce_field( 'groups_settings_save_' . $this->slug, 'sc_group_study_admin' );
	}


	/**
	 * Save the settings for the current the group
	 *
	 * @param int $group_id the group id we save settings for
	 *
	 * @uses  check_admin_referer()     to check the request was made on the site
	 * @uses  bp_get_current_group_id() to get the group id
	 * @uses  wp_parse_args()           to merge args with defaults
	 * @uses  groups_update_groupmeta() to set the extension option
	 * @uses  bp_is_group_admin_page()  to check the group edit screen is displayed
	 * @uses  bp_core_add_message()     to give a feedback to the user
	 * @uses  bp_core_redirect()        to safely redirect the user
	 * @uses  bp_get_group_permalink()  to build the group permalink
	 */
	public function edit_screen_save( $group_id = null ) {

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		check_admin_referer( 'groups_settings_save_' . $this->slug, 'sc_group_study_admin' );

		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		$studies = array();

		if ( ! empty( $_POST['_sc_study'] ) ) {
			$studies = array_map( 'absint', $_POST['_sc_study'] );
		}

		groups_update_groupmeta( $group_id, '_sc_study', $studies );

		do_action( 'sc_group_study_update', $group_id, $studies );

		if ( bp_is_group_admin_page() || is_admin() ) {
			// Only redirect on Manage screen
			if ( bp_is_group_admin_page() ) {
				bp_core_add_message( __( 'Settings saved successfully', 'sc' ) );
				bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
			}
		}
	}

	/**
	 * Adds a Meta Box in Group's Administration screen
	 *
	 * @param  int $group_id the group id
	 *
	 * @uses   Groups->edit_screen() to display the group extension settings form
	 */
	public function admin_screen( $group_id = null ) {
		$this->edit_screen( $group_id );
	}

	/**
	 * Saves the group settings (set in the Meta Box of the Group's Administration screen)
	 *
	 * @param  int $group_id the group id
	 *
	 * @uses   Groups->edit_screen_save() to save the group extension settings
	 */
	public function admin_screen_save( $group_id = null ) {
		$this->edit_screen_save( $group_id );
	}

	/**
	 * We do not use group widgets
	 *
	 * @return boolean false
	 */
	public function widget_display() {
		return false;
	}

	/**
	 * Gets the group meta, use default if meta value is not set
	 *
	 * @param  int    $group_id the group ID
	 * @param  string $option   meta key
	 * @param  mixed  $default  the default value to fallback with
	 *
	 * @uses   groups_get_groupmeta() to get the meta value
	 * @uses   apply_filters()        call "sc_group_study_save_{$option}" to override the group meta value
	 * @return mixed                  the meta value
	 */
	public static function group_get_option( $group_id = 0, $option = '', $default = '' ) {
		if ( empty( $group_id ) || empty( $option ) ) {
			return false;
		}

		$group_option = groups_get_groupmeta( $group_id, $option );

		if ( '' === $group_option ) {
			$group_option = $default;
		}

		/**
		 * @param   mixed $group_option the meta value
		 * @param   int   $group_id     the group ID
		 */
		return apply_filters( "sc_group_study_save_{$option}", $group_option, $group_id );
	}

}
