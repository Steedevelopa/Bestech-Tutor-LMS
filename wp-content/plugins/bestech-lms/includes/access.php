<?php
/**
 * Keep students and teachers on the front end.
 *
 * - After login, staff go to wp-admin and everyone else to the Tutor dashboard.
 * - Non-staff are sent from wp-admin to the Tutor dashboard (AJAX and uploads still work).
 * - The admin bar is hidden for non-staff.
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'login_redirect',
	function ( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User || bestech_lms_is_staff( $user ) ) {
			return $redirect_to;
		}
		// Respect a specific page the user was heading to, unless it is wp-admin.
		if ( $requested && false === strpos( $requested, admin_url() ) ) {
			return $requested;
		}
		return bestech_lms_dashboard_url();
	},
	20,
	3
);

add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || bestech_lms_is_staff() ) {
			return;
		}
		// Tutor's own front-end screens post to admin-post.php; leave that alone.
		global $pagenow;
		if ( in_array( $pagenow, array( 'admin-post.php', 'async-upload.php', 'media-upload.php' ), true ) ) {
			return;
		}
		wp_safe_redirect( bestech_lms_dashboard_url() );
		exit;
	}
);

add_filter(
	'show_admin_bar',
	function ( $show ) {
		return bestech_lms_is_staff() ? $show : false;
	}
);
