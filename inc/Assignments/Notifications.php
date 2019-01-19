<?php

namespace StudyChurch\Assignments;

use DateTime;
use DateTimeZone;

class Notifications {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Notifications
	 *
	 * @return Notifications
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Notifications ) {
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

	protected function hooks() {
		add_action( 'init', array( $this, 'schedule_reminder' ) );
		add_action( 'sc_assignment_create',   array( $this, 'new_assignment' ), 10, 2 );
		add_action( 'sc_assignment_reminder', array( $this, 'assignment_reminders' ) );
	}

	public function new_assignment( $assignment, $group_id ) {

		if ( ! $date = strtotime( $assignment['date'] ) ) {
			return;
		}

		$assignments = sc_get_group_assignments( 'group_id=' . $group_id );

		// if there are already assignments and this one is more than 6 days out
		// then don't notify on this one right away
		if ( $assignments->count > 1 && $date > current_time( 'timestamp' ) + ( DAY_IN_SECONDS * 6 ) ) {
			return;
		}

		$members = groups_get_group_members( array(
			'exclude_admins_mods' => false,
			'group_id'            => $group_id
		) )['members'];

		// make sure we have members
		if ( empty( $members ) ) {
			return;
		}

		remove_filter( 'the_content', 'rcp_filter_restricted_content', 100 );

		$group = groups_get_group( 'group_id=' . $group_id );
		$ass = new Query( false );
		$ass->assignment = get_post( $assignment['id'] );
		ob_start(); ?>
		<p><?php esc_html_e( 'You have a new todo from', 'sc' ); ?> <a href="<?php bp_group_permalink( $group ); ?>"><?php bp_group_name( $group ); ?></a></p>
		<hr />
		<h4><?php _e( 'Due on:', 'sc' ); ?> <?php $ass->the_date_formatted(); ?></h4>
		<?php $ass->the_lessons(); ?>
		<?php $ass->the_content(); ?>

		<?php
		$content = ob_get_clean();

		$content = str_replace( "\r\n", '', $content );
		$content = str_replace( "\n", '', $content );
		$content = str_replace( "\t", '', $content );

		foreach( $members as $member ) {
			wp_mail( $member->user_email, __( 'You have a new assignment', 'sc' ), $content );
		}

	}

	public function schedule_reminder() {

		if ( wp_next_scheduled( 'sc_assignment_reminder' ) ) {
			return;
		}

		if ( ! $timezone = get_option( 'timezone_string', 'America/Los_Angeles' ) ) {
			$timezone = 'America/Los_Angeles';
		}

		$time = new DateTime( 'tomorrow 9:00:00', new DateTimeZone( $timezone ) );

		wp_schedule_event( $time->getTimestamp(), 'daily', 'sc_assignment_reminder' );
	}

	public function assignment_reminders() {

		$reminders = array(
			array(
				'subject' => __( 'Here\'s your todo list.', 'sc' ),
				'content'    => __( 'You have an assignment due in 6 days for %group_name%', 'sc' ),
				'date_start'  => '+ 6 days',
				'date_finish' => '+ 7 days',
			),
			array(
				'subject' => __( 'Don\'t forget about this.', 'sc' ),
				'content'    => __( 'You\'ve got a couple more days to finish up this assignment from %group_name%', 'sc' ),
				'date_start'  => '+ 2 days',
				'date_finish' => '+ 3 days',
			),
			array(
				'subject' => __( 'You have an assignment due today.', 'sc' ),
				'content'    => __( 'Don\'t forget about the assignment from %group_name% that is due today.', 'sc' ),
				'date_start'  => 'today',
				'date_finish' => '+ 1 day',
			)
		);

		foreach( $reminders as $reminder ) {

			$assignments = sc_get_group_assignments( array(
				'group_id'    => false,
				'date_start'  => $reminder['date_start'],
				'date_finish' => $reminder['date_finish'],
			) );

			foreach( $assignments->assignments as $assignment ) {
				$this->send_reminder( $assignment, $reminder );
			}

		}

	}

	protected function send_reminder( $assignment, $args ) {

		// make sure reminders are not disabled for this assignment
		if ( get_post_meta( $assignment->ID, 'disable_reminders', true ) ) {
			return;
		}

		remove_filter( 'the_content', 'rcp_filter_restricted_content', 100 );

		$group_id = get_the_terms( $assignment->ID, 'sc_group' );

		if ( is_wp_error( $group_id ) || empty( $group_id ) ) {
			return;
		}

		$group_id = array_shift( $group_id )->slug;

		$group   = groups_get_group( 'group_id=' . $group_id );
		$members = groups_get_group_members( array(
			'exclude_admins_mods' => false,
			'group_id'            => $group_id
		) )['members'];

		// make sure we have members
		if ( empty( $members ) ) {
			return;
		}

		$ass = new Query( false );
		$ass->assignment = $assignment;
		global $post;
		$old_post = $post;
		$post = $assignment;
		ob_start(); ?>
		<p><?php echo esc_html( str_replace( '%group_name%', bp_get_group_name( $group ), $args['content'] ) ); ?></p>
		<hr />
		<h4><?php _e( 'Due on:', 'sc' ); ?> <?php $ass->the_date_formatted(); ?></h4>
		<?php $ass->the_lessons(); ?>
		<?php $ass->the_content(); ?>
		<p style="text-align:right;font-size:small;">
			<a href="<?php bp_group_permalink( $group ); ?>"><?php bp_group_name( $group ); ?></a>
		</p>
		<?php
		$content = ob_get_clean();

		$content = str_replace( "\r\n", '', $content );
		$content = str_replace( "\n", '', $content );
		$content = str_replace( "\t", '', $content );

		foreach( $members as $member ) {
			wp_mail( $member->user_email, $args['subject'], $content );
		}

		$post = $old_post;
	}

}
