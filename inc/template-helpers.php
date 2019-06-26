<?php

function sc_add_button( $link = '#', $text = '', $classes = '', $data_attr = '' ) {
	printf(
		'<a href="%s" class="%s" %s><span class="screen-reader">%s</span>+</a>',
		esc_url( $link ),
		$classes . ' add-button',
		$data_attr,
		esc_html__( $text, 'sc' )
	);
}

/**
 * Is the current user online
 *
 * @param $user_id
 *
 * @return bool
 */
function sc_is_user_online( $user_id ) {
	$last_activity = strtotime( bp_get_user_last_activity( $user_id ) );

	if ( empty( $last_activity ) ) {
		return false;
	}

	// the activity timeframe is 15 minutes
	$activity_timeframe = 15 * MINUTE_IN_SECONDS;

	return ( time() - $last_activity <= $activity_timeframe );
}

/**
 * Can the current user moderate the current group?
 *
 * @return bool
 */
function sc_user_can_manage_group() {
	return ( bp_group_is_mod() || bp_group_is_admin() );
}

/**
 * Return data type for this element
 *
 * @param $id
 *
 * @return mixed
 */
function sc_get_data_type( $id = null ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}

	return get_post_meta( $id, '_sc_data_type', true );
}

/**
 * Save data type
 *
 * @param      $data_type
 * @param null $id
 *
 * @return bool|int
 */
function sc_save_data_type( $data_type, $id = null ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}

	return update_post_meta( $id, '_sc_data_type', sanitize_text_field( $data_type ) );
}

/**
 * Get valid data types
 *
 * @return array
 */
function sc_get_data_types() {
	return array(
		'question_short' => __( 'Short Answer Question', 'sc' ),
		'question_long'  => __( 'Long Answer Question', 'sc' ),
		'content'        => __( 'Content', 'sc' ),
		'assignment'     => __( 'Assignment', 'sc' ),
	);
}

/**
 * Get the top level id for this study
 *
 * @param null $id
 *
 * @return bool|int|mixed|null
 */
function sc_get_study_id( $id = null ) {
	return studychurch()->study::get_study_id( $id );
}

function sc_study_index( $id = null ) {
	echo walk_page_tree( sc_study_get_navigation( $id ), 0, get_queried_object_id(), array() );
}

function sc_study_manage_index( $study_id, $current_item = 0 ) {
	echo walk_page_tree( sc_study_get_navigation( $study_id ), 0, $current_item, array( 'walker' => new SC_Study_Manage_Walker ) );
}

/**
 * Return list of study navigation elements (everything that is not a bottom level
 * item) in a hierachical list
 *
 * @param null $id
 *
 * @return array
 */
function sc_study_get_navigation( $id = null ) {

	$study_id = sc_get_study_id( $id );

	$elements = get_pages( array(
		'sort_column' => 'menu_order',
		'post_type'   => 'sc_study',
		'child_of'    => sc_get_study_id( $study_id ),
	) );

	if ( ! $elements ) {
		return array();
	}

	// unset study elements
	foreach ( (array) $elements as $key => $element ) {
		if ( get_post_meta( $element->ID, '_sc_data_type', true ) ) {
			unset( $elements[ $key ] );
		}
	}

	return array_values( $elements );
}

/**
 * Print out next/prev page links
 *
 * @param null $id
 */
function sc_study_navigation( $id = null ) {
	$output = '';

	if ( ! $id ) {
		$id = get_the_ID();
	}

	$navigation = sc_study_get_navigation( $id );

	$key = - 1;
	if ( $id != sc_get_study_id( $id ) ) {
		foreach ( $navigation as $key => $item ) {
			if ( get_queried_object_id() == $item->ID ) {
				break;
			}
		}
	}


	if ( isset( $navigation[ $key - 1 ] ) ) {
		$prev   = $navigation[ $key - 1 ];
		$output .= sprintf( '<span class="left prev"><a href="%s" title="%s"><i class="fa fa-caret-left"></i> %s</a></span>', get_the_permalink( $prev->ID ), the_title_attribute( 'echo=0&post=' . $prev->ID ), get_the_title( $prev->ID ) );
	}


	if ( isset( $navigation[ $key + 1 ] ) ) {
		$next   = $navigation[ $key + 1 ];
		$output .= sprintf( '<span class="right next"><a href="%s" title="%s">%s <i class="fa fa-caret-right"></i></a></span>', get_the_permalink( $next->ID ), the_title_attribute( 'echo=0&post=' . $next->ID ), get_the_title( $next->ID ) );
	}

	printf( '<div class="clearfix lesson-nav">%s</div>', $output );

}

/**
 * Return the current users comment answer to the current element
 *
 * @param null $post_id
 * @param null $group_id
 * @param null $user_id
 *
 * @return bool
 * @author Tanner Moushey
 */
function sc_study_get_answer( $post_id = null, $group_id = null, $user_id = null ) {

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $group_id ) {
		$group_id = bp_get_group_id();
	}

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$answer = get_comments( array(
		'post_id'    => $post_id,
		'meta_key'   => 'group_id',
		'meta_value' => absint( $group_id ),
		'number'     => 1,
		'author__in' => $user_id,
	) );

	if ( isset( $answer[0] ) ) {
		return $answer[0];
	} else {
		return false;
	}

}

/**
 * Sort the member query by last_activity
 */
function sc_sort_member_query() {
	global $members_template;

	usort( $members_template->members, function ( $a, $b ) {
		return strtotime( $b->last_activity ) - strtotime( $a->last_activity );
	} );
}


/**
 * Get study id for group
 *
 * @param null $group_id
 *
 * @return array
 */
function sc_get_group_study_id( $group_id = null ) {
	return studychurch()->study::get_group_studies( $group_id );
}

function sc_get_group_invite_key( $group_id = null ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	$group = groups_get_group( 'group_id=' . $group_id );

	return md5( $group->date_created );
}

/**
 * Is this answer private?
 *
 * @param $post_id
 *
 * @return bool
 */
function sc_answer_is_private( $post_id ) {
	return apply_filters( 'sc_answer_is_private', ( 'private' == get_post_meta( $post_id, '_sc_privacy', true ) ), $post_id );
}

function sc_get_privacy( $post_id ) {
	return get_post_meta( $post_id, '_sc_privacy', true );
}

function sc_set_privacy( $privacy, $post_id ) {
	if ( 'private' != $privacy ) {
		return delete_post_meta( $post_id, '_sc_privacy' );
	}

	return update_post_meta( $post_id, '_sc_privacy', 'private' );
}

function sc_answer_get_activity_id( $comment_id ) {
	return get_comment_meta( $comment_id, 'activity_id', true );
}


if ( ! function_exists( 'sc_content_nav' ) ) :
	/**
	 * Display navigation to next/previous pages when applicable
	 */
	function sc_content_nav( $nav_id ) {
		global $wp_query, $post;

		// Don't print empty markup on single pages if there's nowhere to navigate.
		if ( is_single() ) {
			$previous = ( is_attachment() ) ? get_post( $post->post_parent ) : get_adjacent_post( false, '', true );
			$next     = get_adjacent_post( false, '', false );

			if ( ! $next && ! $previous ) {
				return;
			}
		}

		// Don't print empty markup in archives if there's only one page.
		if ( $wp_query->max_num_pages < 2 && ( is_home() || is_archive() || is_search() ) ) {
			return;
		}

		$nav_class = ( is_single() ) ? 'post-navigation' : 'paging-navigation';

		?>
		<nav role="navigation" id="<?php echo esc_attr( $nav_id ); ?>" class="<?php echo $nav_class; ?>">
			<h1 class="screen-reader-text"><?php _e( 'Post navigation', 'sc' ); ?></h1>

			<?php if ( is_single() ) : // navigation links for single posts ?>

				<?php previous_post_link( '<div class="nav-previous">%link</div>', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'sc' ) . '</span> %title' ); ?>
				<?php next_post_link( '<div class="nav-next">%link</div>', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'sc' ) . '</span>' ); ?>

			<?php elseif ( $wp_query->max_num_pages > 1 && ( is_home() || is_archive() || is_search() ) ) : // navigation links for home, archive, and search pages ?>

				<?php if ( get_next_posts_link() ) : ?>
					<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'sc' ) ); ?></div>
				<?php endif; ?>

				<?php if ( get_previous_posts_link() ) : ?>
					<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'sc' ) ); ?></div>
				<?php endif; ?>

			<?php endif; ?>

		</nav><!-- #<?php echo esc_html( $nav_id ); ?> -->
		<?php
	}
endif; // sc_content_nav

if ( ! function_exists( 'sc_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time and author.
	 */
	function sc_posted_on() {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( 'c' ) ),
			esc_html( get_the_modified_date() )
		);

		printf( __( '<span class="posted-on">Posted on %1$s</span><span class="byline"> by %2$s</span>', 'sc' ),
			sprintf( '<a href="%1$s" title="%2$s" rel="bookmark">%3$s</a>',
				esc_url( get_permalink() ),
				esc_attr( get_the_time() ),
				$time_string
			),
			sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
				esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
				esc_attr( sprintf( __( 'View all posts by %s', 'sc' ), get_the_author() ) ),
				esc_html( get_the_author() )
			)
		);
	}
endif;

/**
 * Returns true if a blog has more than 1 category
 */
function sc_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'all_the_cool_cats' ) ) ) {
		// Create an array of all the categories that are attached to posts
		$all_the_cool_cats = get_categories( array(
			'hide_empty' => 1,
		) );

		// Count the number of categories that are attached to the posts
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'all_the_cool_cats', $all_the_cool_cats );
	}

	if ( '1' != $all_the_cool_cats ) {
		// This blog has more than 1 category so sc_categorized_blog should return true
		return true;
	} else {
		// This blog has only 1 category so sc_categorized_blog should return false
		return false;
	}
}

/**
 * Flush out the transients used in sc_categorized_blog
 */
function sc_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'all_the_cool_cats' );
}

add_action( 'edit_category', 'sc_category_transient_flusher' );
add_action( 'save_post', 'sc_category_transient_flusher' );

/**
 * Get query var, return $default if not found
 *
 * @param      $var
 * @param bool $default
 *
 * @return bool
 */
function sc_get( $var, $default = false ) {
	if ( isset( $_GET[ $var ] ) ) {
		return $_GET[ $var ];
	}

	if ( isset( $_POST[ $var ] ) ) {
		return $_POST[ $var ];
	}

	return $default;
}

/**
 * Prints HTML with meta information for the current post-date/time and author.
 */
function sc_posted_on() {
	$time_string = sprintf( '<time class="entry-date published" datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);

	$posted_on = sprintf( '<span class="posted-on"><i class="icon-filled-clock-2"></i> %1$s</span>', $time_string );

	if ( $link = get_the_author_meta( 'googleplus' ) ) {
		$byline = sprintf( '<a class="byline" href="%1$s" title="%2$s" target="_blank"><i class="icon-filled-pencil-2"></i> %3$s</span></a>', esc_url( $link ), esc_attr( get_the_author() ), esc_html( get_the_author() ) );
	} else {
		$byline = sprintf( '<span class="byline"><i class="icon-filled-pencil-2"></i> %1$s</span>', esc_html( get_the_author() ) );
	}

	printf( '<p class="post-meta">%1$s %2$s</p>', $posted_on, $byline );

}

function sc_category() {
	if ( ! $categories = get_the_category() ) {
		return;
	} ?>

	<p class="post-meta post-cats">
		<i class="icon-filled-tag-1"></i> <?php the_category( ', ' ); ?>
	</p>

	<?php
}

function sc_has_featured_size() {
	global $_wp_additional_image_sizes;

	if ( ! $size = image_get_intermediate_size( get_post_thumbnail_id(), 'post-header' ) ) {
		return false;
	}

	if ( ! isset ( $_wp_additional_image_sizes['post-header'] ) ) {
		return false;
	}

	if ( $size['width'] != $_wp_additional_image_sizes['post-header']['width'] ) {
		return false;
	}

	if ( $size['height'] != $_wp_additional_image_sizes['post-header']['height'] ) {
		return false;
	}

	return true;
}

/**
 * Print the assignment permalink for the current group
 *
 * @param $group
 */
function sc_the_assignment_permalink( $group = false ) {
	echo esc_url( sc_get_the_assignment_permalink( $group ) );
}

/**
 * Get the permalink for assignments
 *
 * @param $group
 *
 * @return string
 */
function sc_get_the_assignment_permalink( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	return esc_url( trailingslashit( bp_get_group_permalink( $group ) . 'assignments' ) );
}


/**
 * Get group assignments
 *
 * @param array $args
 *
 * @return mixed | array | false - array of assignments or false if none exist
 */
function sc_get_group_assignments( $args = array() ) {
	return new StudyChurch\Assignments\Query( $args );
}

function sc_get_group_assignment( $id ) {
	if ( ! $assignment = get_post( $id ) ) {
		return false;
	}

	return array(
		'id'      => $id,
		'content' => $assignment->post_content,
		'lessons' => get_post_meta( $id, 'lessons', true ),
		'date'    => get_the_date( '', $id ),
	);
}

/**
 * Add an assignment to the group
 *
 * @param $assignment
 * @param $group_id
 * @param $disable_reminders
 *
 * @return bool|int
 */
function sc_add_group_assignment( $assignment, $group_id, $disable_reminders = false ) {

	if ( ( empty( $assignment['content'] ) && empty( $assignment['lessons'] ) ) || empty( $assignment['date'] ) ) {
		return false;
	}

	if ( ! $timezone = get_option( 'timezone_string', 'America/Los_Angeles' ) ) {
		$timezone = 'America/Los_Angeles';
	}

	$date = new DateTime( $assignment['date'] . ' 23:59:59', new DateTimeZone( $timezone ) );

	$assignment['id'] = wp_insert_post( array(
		'post_author'  => get_current_user_id(),
		'post_type'    => 'sc_assignment',
		'post_status'  => 'publish',
		'post_title'   => 'Assignment',
		'post_content' => $assignment['content'],
		'post_date'    => $date->format( 'Y-m-d H:i:s' ),
	) );

	if ( ! $assignment['id'] ) {
		return false;
	}

	wp_set_post_terms( $assignment['id'], $group_id, 'sc_group' );

	if ( ! empty( $assignment['lessons'] ) ) {
		update_post_meta( $assignment['id'], 'lessons', array_map( 'absint', (array) $assignment['lessons'] ) );
	}

	if ( $disable_reminders ) {
		update_post_meta( $assignment['id'], 'disable_reminders', true );
	}

	do_action( 'sc_assignment_create', $assignment, $group_id );

	return $assignment['id'];
}

/**
 * Delete a group assignment
 *
 * @param $assignment
 *
 * @return bool|int
 */
function sc_delete_group_assignment( $assignment ) {
	return wp_delete_post( $assignment, true );
}

function sc_update_group_assignment( $request ) {

    $assignment = array();

    $assignment['ID'] = $request['id'];
    $assignment['post_content'] = $request['content'];

    if ( isset( $request['date'] ) ) {
        if ( ! $timezone = get_option( 'timezone_string', 'America/Los_Angeles' ) ) {
            $timezone = 'America/Los_Angeles';
        }

        $date = new DateTime( $request['date'] . ' 23:59:59', new DateTimeZone( $timezone ) );

        $assignment['post_date'] = $date->format( 'Y-m-d H:i:s' );
    }

    if ( isset( $request['lessons'] ) && ! empty( $request['lessons'] ) ) {
        update_post_meta( $assignment['ID'], 'lessons', array_map( 'absint', (array) $request['lessons'] ) );
    }

    return wp_update_post( $assignment );
}