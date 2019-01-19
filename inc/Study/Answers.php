<?php

namespace StudyChurch\Study;

class Answers {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Answers
	 *
	 * @return Answers
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Answers ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'wp_ajax_sc_save_answer', array( $this, 'save_answer' ) );
	}

	/**
	 * Save study answers via ajax
	 */
	public function save_answer() {

		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			wp_send_json_error();
		}

		$data = array(
			'comment_post_ID'      => absint( $_POST['post_id'] ),
			'comment_ID'           => absint( $_POST['comment_id'] ),
			'comment_author'       => wp_slash( $user->display_name ),
			'comment_author_email' => wp_slash( $user->user_email ),
			'comment_author_url'   => wp_slash( $user->user_url ),
			'comment_content'      => $_POST['answer'],
			'comment_parent'       => 0,
			'user_id'              => $user->ID,
		);

		global $post;
		$post = get_post( $data['comment_post_ID'] );

		$lesson_id = wp_get_post_parent_id( $data['comment_post_ID'] );

		$activity_meta = array(
			'action'            => sprintf( __( '%s answered a question in <a href="%s#post-%s">%s</a>' ), $user->display_name, studychurch()->study::get_group_link( $lesson_id, absint( $_POST['group_id'] ) ), $data['comment_post_ID'], get_the_title( $lesson_id ) ),
			'content'           => wp_filter_kses( $data['comment_content'] ),
			'component'         => buddypress()->groups->id,
			'type'              => 'answer_update',
			'user_id'           => $user->ID,
			'item_id'           => absint( $_POST['group_id'] ),
			'recorded_time'     => bp_core_current_time(),
			'secondary_item_id' => $data['comment_post_ID'],
			'hide_sitewide'     => false,
		);

		if ( $data['comment_ID'] ) {
			wp_update_comment( $data );
			$activity_meta['id'] = sc_answer_get_activity_id( $data['comment_ID'] );
		} else {
			$data['comment_ID'] = wp_new_comment( $data );
		}

		update_comment_meta( $data['comment_ID'], 'group_id', absint( $_POST['group_id'] ) );

		$group_id = studychurch()->study->setup_study_group();

		if ( $group_id && ! sc_answer_is_private( $data['comment_post_ID'] ) ) {
			$activity_id = bp_activity_add( $activity_meta );
			update_comment_meta( $data['comment_ID'], 'activity_id', $activity_id );
		}

		ob_start();
		global $sc_answer;
		$sc_answer = get_comment( $data['comment_ID'] );
		get_template_part( 'partials/study-element', 'answers' );
		$data['answers'] = ob_get_clean();

		wp_send_json_success( $data );
	}


}
