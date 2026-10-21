# Bestech Tutor LMS

Custom code for the Bestech learning site, which runs on WordPress with [Tutor LMS](https://wordpress.org/plugins/tutor/).

WordPress core, Tutor LMS and the site's content (courses, lessons, users, uploads) live on the site and in its database. This repo holds only the custom code, laid out like a `wp-content` folder so it can be copied straight onto the site.

```
wp-content/
  plugins/
    bestech-lms/      Site plugin (needs Tutor LMS)
```

## What the `bestech-lms` plugin does

| Feature | Details |
| --- | --- |
| Role-based login redirect | Admins and editors land in wp-admin. Students and teachers land on the Tutor dashboard, or on the page they were trying to open. |
| Front end only for students and teachers | Non-staff users who open `/wp-admin` are sent to the Tutor dashboard. AJAX and uploads keep working. The admin bar is hidden for them. |
| "Download video" button | Shown under each lesson video to enrolled students and staff. The link comes from the lesson's existing video: an uploaded video file, an external URL ending in `.mp4`/`.webm`, or a Bunny Stream embed (MP4 fallback). YouTube and Vimeo videos get no button. |
| Resources page | The `[bestech_resources]` shortcode lists every course and lesson attachment from the student's enrolled courses, grouped by course and lesson. Staff see all courses. |
| Smart menu | Works in any WordPress menu, including Elementor's Nav Menu widget. Give an item the CSS class `bestech-logged-in` to show it only to logged-in users, or `bestech-logged-out` to show it only to visitors. A Custom Link to `#bestech-login` shows "Log in" or "Log out" as needed, and `#bestech-dashboard` links to the Tutor dashboard. |
| Settings | **Settings > Bestech LMS**: turn downloads on or off, set the Bunny Stream CDN hostname, and pick the download quality. |

Developer hooks: `bestech_lms_video_download_url` filters the download link for a lesson. `bestech_lms_is_staff` decides who gets wp-admin. Return true for teachers if they should use wp-admin as well.

## Install on the site

1. Install and activate **Tutor LMS** (free) from Plugins > Add New.
2. Copy `wp-content/plugins/bestech-lms` into the site's `wp-content/plugins/` folder (SFTP, the host's file manager, or a zip upload).
3. Activate **Bestech LMS** under Plugins.
4. Create a page called **Resources** containing `[bestech_resources]` and add it to the main menu.
   Suggested menu: My Dashboard (`#bestech-dashboard`), My Courses, and Resources, each with the class `bestech-logged-in`, plus an Account link (`#bestech-login`). To set CSS classes, go to Appearance > Menus and tick "CSS Classes" under Screen Options.
5. Using Bunny Stream? Turn on **MP4 Fallback** in the Bunny library, then enter its CDN hostname (for example `vz-xxxxxxxx-xxx.b-cdn.net`) under Settings > Bestech LMS. In Tutor, add each lesson's video as **Embedded** and paste the Bunny embed code.

Note: a download link works for anyone who has it, so students could share it. Bunny token authentication can lock this down later if needed.

## Testing

This was tested on WordPress trunk and Tutor LMS 4.1.3, running locally on SQLite:

- Students and teachers are redirected away from wp-admin. Admins are not.
- The login redirect sends each role to the right place.
- The download button appears for enrolled students and admins, and not for logged-in users who aren't enrolled.
- Bunny, external MP4 and YouTube links resolve as expected.
- The Resources shortcode handles enrolled students, non-enrolled users and logged-out visitors.
