<?php
/**
 * File to handle the uninstallation of this plugin
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImageUploadImgur\Logging\Files;
use ImageUploadImgur\Logging\Log;

/**
 * Object to handle the uninstallation of this plugin.
 */
class Uninstaller {
	/**
	 * Instance of this object.
	 *
	 * @var ?Uninstaller
	 */
	private static ?Uninstaller $instance = null;

	/**
	 * Constructor for this handler.
	 */
	private function __construct() {
	}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Uninstaller {
		if ( ! static::$instance instanceof static ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Run the uninstallation of this plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( is_multisite() ) {
			// loop through the blogs.
			foreach ( Helper::get_blogs() as $blog_id ) {
				// switch to the blog.
				switch_to_blog( $blog_id->blog_id );

				// run tasks for activation in this single blog.
				$this->tasks();
			}

			// switch back to original blog.
			restore_current_blog();
		} else {
			// simply run the tasks on single-site-install.
			$this->tasks();
		}
	}

	/**
	 * Run the tasks for uninstallation.
	 *
	 * @return void
	 */
	private function tasks(): void {
		// remove our setup.
		Setup::get_instance()->remove_completion();

		// remove all options we have set und used.
		foreach ( Settings::get_instance()->get_settings() as $section_setting ) {
			foreach ( $section_setting['fields'] as $option_name => $field_settings ) {
				delete_option( $option_name );
			}
		}

		// delete log table.
		Log::get_instance()->delete_table();

		// delete file table.
		Files::get_instance()->delete_table();
	}
}
