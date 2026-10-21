<?php
/**
 * Plugin Name:       Bestech LMS
 * Description:       Site tweaks for the Bestech Tutor LMS site: role redirects, a student-friendly front end, video download buttons and a Resources page.
 * Version:           0.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Requires Plugins:  tutor
 * Author:            Bestech
 * Text Domain:       bestech-lms
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BESTECH_LMS_VERSION', '0.1.0' );
define( 'BESTECH_LMS_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/access.php';
require_once __DIR__ . '/includes/video-download.php';
require_once __DIR__ . '/includes/resources.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'bestech-lms', BESTECH_LMS_URL . 'assets/bestech-lms.css', array(), BESTECH_LMS_VERSION );
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! bestech_lms_tutor_active() && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Bestech LMS needs the Tutor LMS plugin. Install and activate Tutor LMS to use it.', 'bestech-lms' ) . '</p></div>';
		}
	}
);
