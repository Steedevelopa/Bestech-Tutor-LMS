<?php
/**
 * Smart menu items, for any WordPress menu (including Elementor's Nav Menu widget).
 *
 * In Appearance > Menus, open "Screen Options" and tick "CSS Classes", then:
 * - Give an item the class `bestech-logged-in` to show it only to logged-in users.
 * - Give an item the class `bestech-logged-out` to show it only to visitors.
 * - Add a Custom Link with the URL `#bestech-login`: it becomes "Log in" for
 *   visitors and "Log out" for logged-in users.
 * - Add a Custom Link with the URL `#bestech-dashboard`: it points at the Tutor dashboard.
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'wp_nav_menu_objects',
	function ( $items ) {
		$logged_in = is_user_logged_in();

		foreach ( $items as $key => $item ) {
			$classes = (array) $item->classes;

			if ( ( $logged_in && in_array( 'bestech-logged-out', $classes, true ) ) || ( ! $logged_in && in_array( 'bestech-logged-in', $classes, true ) ) ) {
				unset( $items[ $key ] );
				continue;
			}

			if ( '#bestech-login' === $item->url ) {
				if ( $logged_in ) {
					$item->url   = wp_logout_url( home_url( '/' ) );
					$item->title = __( 'Log out', 'bestech-lms' );
				} else {
					$item->url   = wp_login_url( bestech_lms_dashboard_url() );
					$item->title = __( 'Log in', 'bestech-lms' );
				}
			} elseif ( '#bestech-dashboard' === $item->url ) {
				$item->url = bestech_lms_dashboard_url();
			}
		}

		// Drop children (and their children) whose parent was hidden.
		do {
			$removed = false;
			$ids     = array_map( 'intval', wp_list_pluck( $items, 'ID' ) );
			foreach ( $items as $key => $item ) {
				if ( $item->menu_item_parent && ! in_array( (int) $item->menu_item_parent, $ids, true ) ) {
					unset( $items[ $key ] );
					$removed = true;
				}
			}
		} while ( $removed );

		return $items;
	}
);
