<?php
/**
 * File with general helper tasks for the plugin.
 *
 * @package imgur-image-upload
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_Post;
use WP_Post_Type;
use WP_Rewrite;

/**
 * The helper class itself.
 */
class Helper {
	/**
	 * Return the absolute URL to the plugin (already trailed with slash).
	 *
	 * @return string
	 */
	public static function get_plugin_url(): string {
		return trailingslashit( plugin_dir_url( IMGUR_IMAGE_UPLOAD_PLUGIN ) );
	}

	/**
	 * Return the absolute local filesystem-path (already trailed with slash) to the plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_path(): string {
		return trailingslashit( plugin_dir_path( IMGUR_IMAGE_UPLOAD_PLUGIN ) );
	}

	/**
	 * Return the plugin support url: the forum on WordPress.org.
	 *
	 * @return string
	 */
	public static function get_plugin_support_url(): string {
		return 'https://wordpress.org/support/plugin/imgur-image-upload/';
	}

	/**
	 * Return the logo as img
	 *
	 * @return string
	 */
	public static function get_logo_img(): string {
		return '<img src="' . self::get_plugin_url() . 'gfx/imgur_logo.svg" alt="Imgur Logo" class="logo">';
	}

	/**
	 * Return the name of this plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_name(): string {
		$plugin_data = get_plugin_data( IMGUR_IMAGE_UPLOAD_PLUGIN );
		if ( ! empty( $plugin_data ) && ! empty( $plugin_data['Name'] ) ) {
			return $plugin_data['Name'];
		}
		return '';
	}

	/**
	 * Get current URL in frontend and backend.
	 *
	 * @return string
	 */
	public static function get_current_url(): string {
		if ( is_admin() && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			return admin_url( basename( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		}

		// set return value for page url.
		$page_url = '';

		// get actual object.
		$object = get_queried_object();
		if ( $object instanceof WP_Post_Type ) {
			$page_url = get_post_type_archive_link( $object->name );
		}
		if ( $object instanceof WP_Post ) {
			$page_url = get_permalink( $object->ID );
		}

		// return result.
		return $page_url;
	}

	/**
	 * Check if imgur API credentials are set.
	 *
	 * @return bool
	 */
	public static function is_api_set(): bool {
		return ! empty( get_option( 'imgur_api_client_id' ) ) && ! empty( get_option( 'imgur_api_client_secret' ) );
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
	 * Case #3: It can happen that WP_Rewrite is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in sub-folders
	 *
	 * @returns boolean
	 * @author matzeeable
	 */
	public static function is_admin_api_request(): bool {
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) // Case #1.
			|| ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) // (#2)
					&& str_starts_with( $GLOBALS['wp']->query_vars['rest_route'], '/' ) ) ) {
			return true;
		}

		// Case #3.
		global $wp_rewrite;
		if ( is_null( $wp_rewrite ) ) {
			$wp_rewrite = new WP_Rewrite();
		}

		// Case #4.
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		if ( is_array( $current_url ) && isset( $current_url['path'] ) ) {
			return str_starts_with( $current_url['path'], $rest_url['path'] );
		}
		return false;
	}

	/**
	 * Format a given datetime with WP-settings and functions.
	 *
	 * @param string $date The date as YYYY-MM-DD.
	 * @return string
	 */
	public static function get_format_date_time( string $date ): string {
		$dt = get_date_from_gmt( $date );
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $dt ) );
	}

	/**
	 * Return the settings-URL.
	 *
	 * @param string $tab String which represents the tab to link to.
	 *
	 * @return string
	 */
	public static function get_settings_url( string $tab = '' ): string {
		$params = array(
			'page' => 'imgur_image_upload_settings',
		);
		if ( ! empty( $tab ) ) {
			$params['tab'] = $tab;
		}
		return add_query_arg( $params, get_admin_url() . 'options-general.php' );
	}

	/**
	 * Get list of blogs in a multisite-installation.
	 *
	 * @return array
	 */
	public static function get_blogs(): array {
		if ( false === is_multisite() ) {
			return array();
		}

		// Get DB-connection.
		global $wpdb;

		// get blogs in this site-network.
		return $wpdb->get_results(
			"
            SELECT blog_id
            FROM {$wpdb->blogs}
            WHERE site_id = '{$wpdb->siteid}'
            AND spam = '0'
            AND deleted = '0'
            AND archived = '0'
        "
		);
	}

	/**
	 * Checks whether a given plugin is active.
	 *
	 * Used because WP's own function is_plugin_active() is not accessible everywhere.
	 *
	 * @param string $plugin Path to the requested plugin relative to plugin-directory.
	 * @return bool
	 */
	public static function is_plugin_active( string $plugin ): bool {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Return the version of the given file.
	 *
	 * With WP_DEBUG is enabled its @filemtime().
	 * Without this it's the plugin-version.
	 *
	 * @param string $filepath The absolute path to the requested file.
	 *
	 * @return string
	 */
	public static function get_file_version( string $filepath ): string {
		// check for WP_DEBUG.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return filemtime( $filepath );
		}

		$plugin_version = IMGUR_IMAGE_UPLOAD_PLUGIN_VERSION;

		/**
		 * Filter the used file version (for JS- and CSS-files which get enqueued).
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $plugin_version The plugin-version.
		 * @param string $filepath The absolute path to the requested file.
		 */
		return apply_filters( 'imgur_image_upload_file_version', $plugin_version, $filepath );
	}

	/**
	 * Return the review-URL.
	 *
	 * @return string
	 */
	public static function get_review_url(): string {
		return 'https://wordpress.org/plugins/imgur-image-upload/#reviews';
	}

	/**
	 * Get backend URL of the post type with the most posts.
	 *
	 * @return string
	 */
	public static function get_url_of_post_type_with_post_posts(): string {
		$counters = array();
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type_name ) {
			// skip attachments.
			if ( 'attachment' === $post_type_name ) {
				continue;
			}

			// get the count of posts of this post type.
			$counts = wp_count_posts( $post_type_name );

			// sum them.
			$counters[ $post_type_name ] = $counts->publish + $counts->future + $counts->draft + $counts->pending + $counts->private;
		}

		// bail if list is empty.
		if ( empty( $counters ) ) {
			// return the settings url instead.
			return self::get_settings_url();
		}

		// sort by most count of posts first.
		arsort( $counters );

		// get first entry.
		$post_name = array_key_first( $counters );

		// return the edit url of this post type.
		return add_query_arg( array( 'post_type' => $post_name ), get_admin_url() . 'edit.php' );
	}
}
