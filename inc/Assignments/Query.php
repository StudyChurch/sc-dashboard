<?php

namespace StudyChurch\Assignments;

class Query {

	public static $_key = 'sc_assignments';

	public $count = 0;

	public $current = 0;

	public $assignments = array();

	/**
	 * @var \WP_Post
	 */
	public $assignment = null;

	public $query_args = array();

	public function __construct( $args = array() ) {

		if ( false === $args ) {
			return $this;
		}

		$this->query_args = wp_parse_args( $args, array(
			'group_id'    => bp_get_current_group_id(),
			'date_start'  => date( DATE_RSS, current_time( 'timestamp' ) ),
			'date_finish' => '+ 1 year',
			'count'       => - 1,
		) );

		return $this->parse_query();

	}

	/**
	 * Get the assignments and return those that are valid
	 *
	 * @return $this
	 */
	public function parse_query() {

		// remove legacy assignments
 		if ( $assignments = groups_get_groupmeta( $this->query_args['group_id'], Query::$_key, true ) ) {
			remove_action( 'sc_assignment_create', array(
				Notifications::get_instance(),
				'new_assignment'
			), 10 );

			foreach ( $assignments as $assignment ) {
				sc_add_group_assignment( $assignment, $this->query_args['group_id'] );
			}

			add_action( 'sc_assignment_create', array(
				Notifications::get_instance(),
				'new_assignment'
			), 10, 2 );

			groups_delete_groupmeta( $this->query_args['group_id'], Query::$_key );
		}

		$order = ( current_time( 'timestamp' ) > $this->query_args['date_start'] ) ? 'ASC' : 'DESC';

		if ( ! empty( $this->query_args['id'] ) ) {
			$this->assignments = get_posts( 'id=' . $this->query_args['id'] );
		} else {
			$args = array(
				'order'          => $order,
				'post_type'      => 'sc_assignment',
				'post_status'    => array( 'future', 'publish' ),
				'posts_per_page' => $this->query_args['count'],
				'date_query'     => array(
					array(
						'column' => 'post_date',
						'before' => $this->query_args['date_finish'],
					),
					array(
						'column' => 'post_date',
						'after'  => $this->query_args['date_start'],
					)
				)
			);

			if ( $this->query_args['group_id'] ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'sc_group',
						'field'    => 'slug',
						'terms'    => $this->query_args['group_id'],
					)
				);
			}

			$this->assignments = get_posts( $args );
		}

		if ( empty( $this->assignments ) ) {
			$this->assignments = array();
		}

		$this->count = count( $this->assignments );

		return $this;

	}

	/**
	 * Does this query have assignments?
	 *
	 * @return bool
	 */
	public function have_assignments() {
		return (bool) reset( $this->assignments );
	}

	public function the_assignment() {
		$this->assignment = current( $this->assignments );
		next( $this->assignments );

		return $this->assignment;
	}

	/**
	 * echo the current assignments key
	 */
	public function the_key() {
		echo $this->get_the_key();
	}

	/**
	 * Get the current assignments key
	 * @return mixed
	 */
	public function get_the_key() {
		return $this->assignment->ID;
	}

	/**
	 * Print the assignment content
	 */
	public function the_content() {
		echo apply_filters( 'the_content', wp_kses_post( $this->get_the_content() ) );
	}

	/**
	 * Get the assignment content
	 *
	 * @return mixed
	 */
	public function get_the_content() {
		return $this->assignment->post_content;
	}

	/**
	 * Print the assignment due date
	 *
	 * @param string $d
	 */
	public function the_date( $d = 'l, F j' ) {
		echo $this->get_the_date( $d );
	}

	/**
	 * Get the assignment due date
	 *
	 * @param string $d
	 *
	 * @return int
	 */
	public function get_the_date( $d = 'l, F j' ) {
		return get_the_date( $d, $this->assignment->ID );
	}

	/**
	 * Print the formatted date
	 */
	public function the_date_formatted() {
		$date = $this->get_the_date( 'U' );
		printf( '%s <span class="day">%s</span>', date( 'l, F', $date ), date( 'j', $date ) );
	}

	/**
	 * Print the lessons for this assignment
	 */
	public function the_lessons() {
		if ( ! $this->get_the_lessons() ) {
			return;
		} ?>

		<ul class="assignment-lessons">
			<?php foreach ( $this->get_the_lessons() as $lesson ) : ?>
				<li><a href="<?php echo studychurch()->assignments::get_group_link( $lesson, $this->get_group_id() ); ?>"><?php echo get_the_title( $lesson ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	public function get_group_id() {
		$group = wp_get_post_terms( $this->assignment->ID, 'sc_group' );

		if ( empty( $group[0]->slug ) ) {
			return false;
		}

		return absint( $group[0]->slug );
	}

	/**
	 * Get the lessons for this assignment
	 *
	 * @return array|bool
	 */
	public function get_the_lessons() {
		if ( ! $lessons = get_post_meta( $this->assignment->ID, 'lessons', true ) ) {
			return array();
		}

		return array_map( 'absint', $lessons );
	}
}
