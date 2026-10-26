<?php
/**
 * Shared helpers.
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether Tutor LMS is loaded.
 *
 * @return bool
 */
function bestech_lms_tutor_active() {
	return function_exists( 'tutor' ) && function_exists( 'tutor_utils' );
}

/**
 * Whether a user should get the full wp-admin (admins and editors).
 *
 * Teachers and students use Tutor's front-end dashboard instead.
 *
 * @param WP_User|int|null $user User, ID, or null for the current user.
 * @return bool
 */
function bestech_lms_is_staff( $user = null ) {
	$user = $user ? ( $user instanceof WP_User ? $user : get_user_by( 'id', $user ) ) : wp_get_current_user();
	$is_staff = $user && $user->exists() && user_can( $user, 'edit_others_posts' );

	/**
	 * Filter whether a user gets wp-admin. Return true for teachers to let them use wp-admin too.
	 *
	 * @param bool         $is_staff Whether the user is staff.
	 * @param WP_User|false $user    The user.
	 */
	return (bool) apply_filters( 'bestech_lms_is_staff', $is_staff, $user );
}

/**
 * URL of the Tutor front-end dashboard, falling back to the home page.
 *
 * @return string
 */
function bestech_lms_dashboard_url() {
	if ( bestech_lms_tutor_active() ) {
		return tutor_utils()->tutor_dashboard_url();
	}
	return home_url( '/' );
}
