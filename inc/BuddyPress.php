<?php

namespace StudyChurch;

class BuddyPress {

	/**
	 * @var
	 */
	protected static $_instance;

	protected static $saved_comment_id = null; // This is so we can update a comment on the Dashboard

	/**
	 * Only make one instance of the BuddyPress
	 *
	 * @return BuddyPress
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof BuddyPress ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_filter( 'groups_activity_new_update_action',  array( $this, 'group_activity_action' ) );
		add_filter( 'bp_get_activity_css_class',          array( $this, 'no_mini_class'         ) );
//		add_filter( 'bp_get_loggedin_user_avatar',        array( $this, 'current_user_avatar_container' ) );
		add_filter( 'bp_core_fetch_avatar',               array( $this, 'user_avatar_container' ), 10, 2 );
		add_filter( 'bp_avatar_is_front_edit',            array( $this, 'avatar_is_front_edit'  ) );
		add_filter( 'bp_displayed_user_id',               array( $this, 'displayed_user_id'     ) );
		add_filter( 'bp_activity_get',                    array( $this, 'sort_activities'       ) );
		add_filter( 'bp_before_has_groups_parse_args',    array( $this, 'has_group_args' ) );
//		add_filter( 'bp_before_has_members_parse_args',   array( $this, 'has_members_args' ) );

		add_action( 'template_redirect',                 array( $this, 'redirect_single_activity' ) );
		add_action( 'template_redirect',                 array( $this, 'redirect_members_page'    ) );
		add_action( 'bp_activity_before_save',           array( $this, 'activity_mentions'     ), 9 );

		remove_filter( 'groups_group_description_before_save', 'wp_filter_kses', 1 );
		add_filter( 'groups_group_description_before_save', 'wp_filter_post_kses', 1 );

		add_filter( 'groups_group_slug_before_save', array( $this, 'id_as_slug' ), 10, 2 );

		add_filter( 'bp_before_groups_post_update_parse_args', array( $this, 'save_comment_id' ) );
		add_filter( 'bp_after_groups_record_activity_parse_args', array( $this, 'retrieve_comment_id' ) );

	}

	public function save_comment_id( $r ) {

	    if ( isset ( $r['id'] ) ) {
	        self::$saved_comment_id = absint( $r['id'] );
        }

        return $r;
    }

    public function retrieve_comment_id( $r ) {

	    if ( self::$saved_comment_id !== null ) {
	        $r['id'] = absint( self::$saved_comment_id );
        }

        return $r;
    }

	/**
	 * Default to show hidden
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function has_group_args( $args ) {
		$args['show_hidden'] = true;
		return $args;
	}

	/**
	 * Show only members from this site on network
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function has_members_args( $args ) {
		$members = get_users( array( 'blog_id' => get_current_blog_id() ) );
		$args['include']  = wp_list_pluck( $members, 'ID' );
		$args['per_page'] = 100;
		return $args;
	}

	public function group_activity_action( $action ) {
		return sprintf( __( '%1$s posted an update', 'buddypress'), bp_get_mentioned_user_display_name( bp_loggedin_user_id() ) );
	}

	public function current_user_avatar_container( $avatar ) {
		return sprintf( '<div class="avatar-container online">%s</div>', $avatar );
	}

	public function user_avatar_container( $avatar, $params ) {
		if ( 'group' == $params['object'] ) {
			return $avatar;
		}

		$online_class = ( sc_is_user_online( (int) $params['item_id'] ) ) ? 'online' : '';

		return sprintf( '<div class="avatar-container %s">%s</div>', $online_class, $avatar );
	}

	/**
	 * Remove the mini class from activities
	 *
	 * @param $classes
	 *
	 * @return mixed
	 */
	public function no_mini_class( $classes ) {
		return str_replace( ' mini', '', $classes );
	}

	public function avatar_is_front_edit( $val ) {
		if ( $val ) {
			return $val;
		}

		return bp_is_user_settings();
	}

	/**
	 * If we don't have a displayed user, get the current user id.
	 *
	 * @param $id
	 *
	 * @return int
	 */
	public function displayed_user_id( $id ) {
		return $id;

		if ( $id ) {
			return $id;
		}

		return get_current_user_id();
	}

	/**
	 * Redefine bp_activity_at_name_filter_updates to remove links
	 *
	 * @param $activity
	 */
	public function activity_mentions( $activity ) {
		remove_action( 'bp_activity_before_save', 'bp_activity_at_name_filter_updates' );

		// Are mentions disabled?
		if ( ! bp_activity_do_mentions() ) {
			return;
		}

		// If activity was marked as spam, stop the rest of this function.
		if ( ! empty( $activity->is_spam ) )
			return;

		if ( preg_match( '/(@group\b)/', $activity->content ) ) {
			$usernames = groups_get_group_members( array( 'exclude_admins_mods' => false ) )['members'];
			$usernames = wp_list_pluck( $usernames, 'user_login', 'ID' );
			$activity->content = preg_replace( '/(@group\b)/', "<span class='mention'>@group</span>", $activity->content );
		} else {
			// Try to find mentions
			$usernames = bp_activity_find_mentions( $activity->content );
		}

		// We have mentions!
		if ( ! empty( $usernames ) ) {
			// Replace @mention text with userlinks
			foreach( (array) $usernames as $user_id => $username ) {

			    $editing = strpos( $activity->content, "<span class='mention username'>" ) !== false ? true : false;

				$activity->content = preg_replace( '/(@' . $username . '\b)/', ( $editing ? "@$username" : "<span class='mention username'>@$username</span>" ), $activity->content );
			}

			// Add our hook to send @mention emails after the activity item is saved
			add_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

			// temporary variable to avoid having to run bp_activity_find_mentions() again
			buddypress()->activity->mentioned_users = $usernames;
		}

	}

	/**
	 * Sort activities by the most recent discussion
	 *
	 * @param $activities
	 *
	 * @return mixed
	 */
	public function sort_activities( $activities ) {

		if ( empty( $activities['activities'] ) ) {
			return $activities;
		}

		foreach ( $activities['activities'] as $key => $activity ) {
			$time = strtotime( $activity->date_recorded );
			foreach( (array) $activity->children as $child ) {

				if ( empty( $child->date_recorded ) ) {
					continue;
				}

				$child_time = strtotime( $child->date_recorded );
				if ( $child_time > $time ) {
					$time = $child_time;
				}
			}

			unset( $activities['activities'][ $key ] );
			$activities['activities'][ $time ] = $activity;
		}

		krsort( $activities['activities'] );
		$activities['activities'] = array_values( $activities['activities'] );
		return $activities;
	}

	public function redirect_single_activity() {
		if ( ! bp_is_activity_component() ) {
			return;
		}

		$activity = bp_activity_get( array( 'in' => absint( bp_current_action() ), 'show_hidden' => true ) );

		if ( empty( $activity['activities'][0] ) ) {
			wp_safe_redirect( bp_loggedin_user_domain() );
			die();
		}

		$activity = $activity['activities'][0];

		if ( groups_is_user_member( get_current_user_id(), $activity->item_id ) ) {
			$group = groups_get_group( array( 'group_id' => $activity->item_id ) );
			wp_safe_redirect( bp_get_group_permalink( $group ) );
			die();
		}

		wp_safe_redirect( bp_loggedin_user_domain() );
		die();
	}

	/**
	 * Only site admins should see the members page
	 */
	public function redirect_members_page() {

		if ( ! bp_is_members_directory() ) {
			return;
		}

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_safe_redirect( bp_loggedin_user_domain() );
			die();
		}

	}


	/**
	 * Use the group id as the slug
	 *
	 * @param $slug
	 * @param $id
	 *
	 * @return mixed
	 */
	public function id_as_slug( $slug, $id ) {
		if ( is_numeric( $slug ) ) {
			return $slug;
		}

		return time();
	}

}