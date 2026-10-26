<?php
/**
 * "Download video" button under each Tutor lesson video.
 *
 * The download link is worked out from the lesson's existing video, so teachers
 * do not have to enter anything extra:
 * - Uploaded (HTML5) video: the media file itself.
 * - External URL that points at a video file (.mp4, .webm, ...): that URL.
 * - Bunny Stream embed: the MP4 fallback on the Bunny CDN host set under
 *   Settings > Bestech LMS ("MP4 Fallback" must be on in the Bunny library).
 *
 * The button only shows to users enrolled in the course, and to staff.
 *
 * @package BestechLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Work out a downloadable file URL for a lesson's video.
 *
 * @param int $lesson_id Lesson post ID.
 * @return string Empty string when there is nothing to download.
 */
function bestech_lms_video_download_url( $lesson_id ) {
	$video = maybe_unserialize( get_post_meta( $lesson_id, '_video', true ) );
	$url   = '';

	if ( is_array( $video ) ) {
		$source = isset( $video['source'] ) ? $video['source'] : '';

		if ( 'html5' === $source && ! empty( $video['source_video_id'] ) ) {
			$url = (string) wp_get_attachment_url( (int) $video['source_video_id'] );
		} elseif ( 'external_url' === $source && ! empty( $video['source_external_url'] ) ) {
			$path = (string) wp_parse_url( $video['source_external_url'], PHP_URL_PATH );
			if ( preg_match( '/\.(mp4|webm|ogg|mov|m4v)$/i', $path ) ) {
				$url = $video['source_external_url'];
			}
		}

		if ( ! $url ) {
			$url = bestech_lms_bunny_mp4_url( $video );
		}
	}

	/**
	 * Filter the download URL for a lesson video.
	 *
	 * @param string $url       Download URL, or '' for none.
	 * @param int    $lesson_id Lesson ID.
	 * @param mixed  $video     Raw Tutor `_video` meta.
	 */
	return (string) apply_filters( 'bestech_lms_video_download_url', $url, $lesson_id, $video );
}

/**
 * Build a Bunny Stream MP4 fallback URL from any Bunny embed in the video meta.
 *
 * @param array $video Raw Tutor `_video` meta.
 * @return string
 */
function bestech_lms_bunny_mp4_url( $video ) {
	$host = trim( (string) get_option( 'bestech_lms_bunny_cdn_host', '' ) );
	if ( ! $host ) {
		return '';
	}
	$host = preg_replace( '#^https?://#', '', untrailingslashit( $host ) );

	$haystack = implode(
		' ',
		array_filter(
			array_map(
				'strval',
				array(
					isset( $video['source_bunnynet'] ) ? $video['source_bunnynet'] : '',
					isset( $video['source_embedded'] ) ? $video['source_embedded'] : '',
					isset( $video['source_external_url'] ) ? $video['source_external_url'] : '',
				)
			)
		)
	);

	// e.g. https://iframe.mediadelivery.net/embed/12345/<video-guid>.
	if ( ! preg_match( '#(?:iframe|player)\.mediadelivery\.net/(?:embed|play)/\d+/([0-9a-f-]{36})#i', $haystack, $m ) ) {
		return '';
	}

	$quality = get_option( 'bestech_lms_bunny_quality', '720p' );
	return 'https://' . $host . '/' . strtolower( $m[1] ) . '/play_' . $quality . '.mp4';
}

add_action(
	'tutor_lesson/single/after/video',
	function () {
		if ( ! bestech_lms_tutor_active() || ! get_option( 'bestech_lms_allow_downloads', 1 ) || ! is_user_logged_in() ) {
			return;
		}

		$lesson_id = get_the_ID();
		if ( ! bestech_lms_is_staff() && ! tutor_utils()->has_enrolled_content_access( 'lesson', $lesson_id ) ) {
			return;
		}

		$url = bestech_lms_video_download_url( $lesson_id );
		if ( ! $url ) {
			return;
		}

		printf(
			'<div class="bestech-video-download"><a class="tutor-btn tutor-btn-outline-primary" href="%1$s" download rel="nofollow">%2$s</a><span class="bestech-hint">%3$s</span></div>',
			esc_url( $url ),
			esc_html__( 'Download video', 'bestech-lms' ),
			esc_html__( 'If the video opens instead, right-click the button and choose "Save link as".', 'bestech-lms' )
		);
	}
);

/*
 * Settings > Bestech LMS.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'bestech_lms',
			'bestech_lms_allow_downloads',
			array(
				'type'              => 'boolean',
				'default'           => 1,
				'sanitize_callback' => function ( $v ) {
					return $v ? 1 : 0;
				},
			)
		);
		register_setting(
			'bestech_lms',
			'bestech_lms_bunny_cdn_host',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					return preg_replace( '/[^a-z0-9.\-]/i', '', preg_replace( '#^https?://#', '', trim( (string) $v ) ) );
				},
			)
		);
		register_setting(
			'bestech_lms',
			'bestech_lms_bunny_quality',
			array(
				'type'              => 'string',
				'default'           => '720p',
				'sanitize_callback' => function ( $v ) {
					return in_array( $v, array( '240p', '360p', '480p', '720p', '1080p' ), true ) ? $v : '720p';
				},
			)
		);
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Bestech LMS', 'bestech-lms' ),
			__( 'Bestech LMS', 'bestech-lms' ),
			'manage_options',
			'bestech-lms',
			'bestech_lms_render_settings'
		);
	}
);

/**
 * Render the settings page.
 */
function bestech_lms_render_settings() {
	$quality = get_option( 'bestech_lms_bunny_quality', '720p' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bestech LMS', 'bestech-lms' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'bestech_lms' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Video downloads', 'bestech-lms' ); ?></th>
					<td><label><input type="checkbox" name="bestech_lms_allow_downloads" value="1" <?php checked( get_option( 'bestech_lms_allow_downloads', 1 ) ); ?>> <?php esc_html_e( 'Show a "Download video" button under lesson videos', 'bestech-lms' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="bestech_lms_bunny_cdn_host"><?php esc_html_e( 'Bunny Stream CDN hostname', 'bestech-lms' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="bestech_lms_bunny_cdn_host" name="bestech_lms_bunny_cdn_host" value="<?php echo esc_attr( get_option( 'bestech_lms_bunny_cdn_host', '' ) ); ?>" placeholder="vz-xxxxxxxx-xxx.b-cdn.net">
						<p class="description"><?php esc_html_e( 'From your Bunny Stream library > API. Leave empty if you do not use Bunny.', 'bestech-lms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bestech_lms_bunny_quality"><?php esc_html_e( 'Download quality', 'bestech-lms' ); ?></label></th>
					<td>
						<select id="bestech_lms_bunny_quality" name="bestech_lms_bunny_quality">
							<?php foreach ( array( '240p', '360p', '480p', '720p', '1080p' ) as $q ) : ?>
								<option value="<?php echo esc_attr( $q ); ?>" <?php selected( $quality, $q ); ?>><?php echo esc_html( $q ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Must be one of the MP4 fallback resolutions enabled in Bunny.', 'bestech-lms' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Resources page', 'bestech-lms' ); ?></h2>
		<p><?php esc_html_e( 'Add the shortcode [bestech_resources] to any page to list every downloadable file from the courses a student is enrolled in.', 'bestech-lms' ); ?></p>
	</div>
	<?php
}
