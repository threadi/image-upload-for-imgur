<?php
/**
 * File for handling logging in this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Logging;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Handler for logging in this plugin.
 */
class Log {
	/**
	 * Instance of actual object.
	 *
	 * @var Log|null
	 */
	private static ?Log $instance = null;

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
	 * @return Log
	 */
	public static function get_instance(): Log {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the logging functions.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_action_image_upload_for_imgur_log_empty', array( $this, 'empty_log_by_request' ) );
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
		$sql = 'CREATE TABLE ' . $wpdb->prefix . "iufi_logs (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            `log` text DEFAULT '' NOT NULL,
            `md5` text DEFAULT '' NOT NULL,
            `category` varchar(40) DEFAULT '' NOT NULL,
            `state` varchar(40) DEFAULT '' NOT NULL,
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
		$wpdb->query( sprintf( 'DROP TABLE IF EXISTS %s', esc_sql( $wpdb->prefix . 'iufi_upload_logs' ) ) );
	}

	/**
	 * Add a single log-entry.
	 *
	 * @param string $log   The text to log.
	 * @param string $state The state to log.
	 * @param string $category The category for this log entry (optional).
	 * @param string $md5 Marker to identify unique entries (optional).
	 *
	 * @return void
	 */
	public function add_log( string $log, string $state, string $category = '', string $md5 = '' ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'iufi_upload_logs',
			array(
				'time'     => gmdate( 'Y-m-d H:i:s' ),
				'log'      => $log,
				'md5'      => $md5,
				'category' => $category,
				'state'    => $state,
			)
		);
		$this->clean_log();
	}

	/**
	 * Delete all entries which are older than 7 days.
	 *
	 * @return void
	 */
	public function clean_log(): void {
		// get db connection.
		global $wpdb;

		// delete entries older than 7 days.
		$wpdb->query( sprintf( 'DELETE FROM %s WHERE `time` < DATE_SUB(NOW(), INTERVAL 7 DAY)', esc_sql( $wpdb->prefix . 'iufi_upload_logs' ) ) );
	}

	/**
	 * Delete all entries by request.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function empty_log_by_request(): void {
		global $wpdb;

		// check the nonce.
		check_admin_referer( 'image-upload-for-imgur-log-empty', 'nonce' );

		// empty the table.
		$wpdb->query( 'TRUNCATE TABLE `' . $wpdb->prefix . 'iufi_upload_logs`' );

		// redirect user.
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Return list of categories with internal name & its label.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		$list = array(
			'system' => __( 'System', 'image-upload-for-imgur' ),
		);

		/**
		 * Filter the list of possible log categories.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array $list List of categories.
		 */
		return apply_filters( 'iufi_log_categories', $list );
	}
}
