<?php
/**
 * File for handling file logging in this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImgurImageUpload\Logging;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Handler for logging in this plugin.
 */
class Files {
	/**
	 * Instance of actual object.
	 *
	 * @var Files|null
	 */
	private static ?Files $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Files
	 */
	public static function get_instance(): Files {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object if it is enabled in settings.
	 *
	 * @return void
	 */
	public function init(): void {
		// bail if logging is not enabled.
		if ( 1 !== absint( get_option( 'imgur_log_files' ) ) ) {
			return;
		}

		// add action to save added files in log.
		add_action( 'imgur_image_upload_file_saved', array( $this, 'add_log_via_api' ), 10, 3 );
	}

	/**
	 * Create the logging-table in the database.
	 *
	 * @return void
	 */
	public function create_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// table for import-log.
		$sql = 'CREATE TABLE ' . $wpdb->prefix . "imgur_image_upload_files (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            `filename_original` text DEFAULT '' NOT NULL,
            `imgur_url` text DEFAULT '' NOT NULL,
            `post_id` int(11) DEFAULT 0 NOT NULL,
            `user_id` int(11) DEFAULT 0 NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Delete the logging-table in the database.
	 *
	 * @return void
	 */
	public function delete_table(): void {
		global $wpdb;
		$wpdb->query( sprintf( 'DROP TABLE IF EXISTS %s', esc_sql( $wpdb->prefix . 'imgur_image_upload_files' ) ) );
	}

	/**
	 * Add a single log-entry.
	 *
	 * @param string $filename_original The original filename.
	 * @param string $imgur_url The imgur-link.
	 * @param int    $post_id The post it is uploaded to.
	 * @param int    $user_id The user who has it uploaded.
	 *
	 * @return void
	 */
	public function add_log( string $filename_original, string $imgur_url, int $post_id = 0, int $user_id = 0 ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'imgur_image_upload_files',
			array(
				'time'              => gmdate( 'Y-m-d H:i:s' ),
				'filename_original' => $filename_original,
				'imgur_url'         => $imgur_url,
				'post_id'           => $post_id,
				'user_id'           => $user_id,
			)
		);
	}

	/**
	 * Add via API uploaded file to file log.
	 *
	 * @param array $file_original The original file data.
	 * @param array $imgur_file    The imgur file data.
	 * @param int   $post_id The used post id.
	 *
	 * @return void
	 */
	public function add_log_via_api( array $file_original, array $imgur_file, int $post_id ): void {
		// get current user.
		$user    = wp_get_current_user();
		$user_id = 0;
		if ( ! is_null( $user ) ) {
			$user_id = $user->ID;
		}

		// add log.
		$this->add_log( $file_original['name'], $imgur_file['link'], $post_id, $user_id );
	}
}
