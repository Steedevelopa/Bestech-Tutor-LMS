<?php
/**
 * [bestech_resources] shortcode: one page listing every downloadable file
 * (Tutor course and lesson attachments) from the courses a student is in.
 *
 * Staff see the files from every published course.
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course IDs whose resources the current user may see.
 *
 * @return int[]
 */
function bestech_lms_resource_course_ids() {
	if ( bestech_lms_is_staff() ) {
		return get_posts(
			array(
				'post_type'      => tutor()->course_post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}
	return array_map( 'intval', (array) tutor_utils()->get_enrolled_courses_ids_by_user( get_current_user_id() ) );
}

/**
 * Lessons of a course, in course order, that have attachments.
 *
 * @param int $course_id Course ID.
 * @return WP_Post[]
 */
function bestech_lms_course_lessons( $course_id ) {
	$topic_ids = get_posts(
		array(
			'post_type'      => tutor()->topics_post_type,
			'post_parent'    => $course_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	if ( ! $topic_ids ) {
		return array();
	}

	$lessons = get_posts(
		array(
			'post_type'       => tutor()->lesson_post_type,
			'post_status'     => 'publish',
			'post_parent__in' => $topic_ids,
			'posts_per_page'  => -1,
			'orderby'         => 'menu_order',
			'order'           => 'ASC',
			'meta_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_tutor_attachments',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	// Keep topic order, then lesson order within a topic.
	$topic_rank = array_flip( $topic_ids );
	usort(
		$lessons,
		function ( $a, $b ) use ( $topic_rank ) {
			$ta = $topic_rank[ $a->post_parent ] ?? 0;
			$tb = $topic_rank[ $b->post_parent ] ?? 0;
			return $ta === $tb ? $a->menu_order <=> $b->menu_order : $ta <=> $tb;
		}
	);
	return $lessons;
}

/**
 * Render a list of Tutor attachment objects.
 *
 * @param object[] $files Items from tutor_utils()->get_attachments().
 * @return string
 */
function bestech_lms_render_files( $files ) {
	$out = '<ul class="bestech-files">';
	foreach ( $files as $file ) {
		if ( empty( $file->url ) ) {
			continue;
		}
		$out .= sprintf(
			'<li><a href="%1$s" download>%2$s</a> <span class="bestech-file-meta">%3$s%4$s</span></li>',
			esc_url( $file->url ),
			esc_html( $file->title ? $file->title : $file->name ),
			esc_html( strtoupper( (string) $file->ext ) ),
			$file->size_bytes ? esc_html( ' · ' . $file->size ) : ''
		);
	}
	return $out . '</ul>';
}

add_shortcode(
	'bestech_resources',
	function () {
		if ( ! bestech_lms_tutor_active() ) {
			return '';
		}
		if ( ! is_user_logged_in() ) {
			return '<p class="bestech-resources-login">' . sprintf(
				/* translators: %s: login URL */
				wp_kses_post( __( 'Please <a href="%s">log in</a> to see your course resources.', 'bestech-lms' ) ),
				esc_url( wp_login_url( get_permalink() ) )
			) . '</p>';
		}

		$out = '';
		foreach ( bestech_lms_resource_course_ids() as $course_id ) {
			$section      = '';
			$course_files = tutor_utils()->get_attachments( $course_id );
			if ( $course_files ) {
				$section .= '<h4>' . esc_html__( 'Course files', 'bestech-lms' ) . '</h4>' . bestech_lms_render_files( $course_files );
			}
			foreach ( bestech_lms_course_lessons( $course_id ) as $lesson ) {
				$files = tutor_utils()->get_attachments( $lesson->ID );
				if ( $files ) {
					$section .= '<h4><a href="' . esc_url( get_permalink( $lesson ) ) . '">' . esc_html( get_the_title( $lesson ) ) . '</a></h4>' . bestech_lms_render_files( $files );
				}
			}
			if ( $section ) {
				$out .= '<section class="bestech-resources-course"><h3>' . esc_html( get_the_title( $course_id ) ) . '</h3>' . $section . '</section>';
			}
		}

		if ( ! $out ) {
			return '<p class="bestech-resources-empty">' . esc_html__( 'No resources yet. Files your teachers add to lessons will show up here.', 'bestech-lms' ) . '</p>';
		}
		return '<div class="bestech-resources">' . $out . '</div>';
	}
);
