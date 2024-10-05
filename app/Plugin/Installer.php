<?php
/**
 * File to handle the installation and activation of this plugin
 *
 * @package image-upload-for-imgur
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImgurImageUpload\Logging\Files;
use ImgurImageUpload\Logging\Log;

/**
 * Object to handle the initialization of this plugin.
 */
class Installer {
	/**
	 * Instance of this object.
	 *
	 * @var ?Installer
	 */
	private static ?Installer $instance = null;

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
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Installer {
		if ( ! static::$instance instanceof static ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Run during activation of this plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		if ( is_multisite() ) {
			// loop through the blogs.
			foreach ( Helper::get_blogs() as $blog_id ) {
				// switch to the blog.
				switch_to_blog( $blog_id->blog_id );

				// run tasks for activation in this single blog.
				$this->activation_tasks();
			}

			// switch back to original blog.
			restore_current_blog();
		} else {
			// simply run the tasks on single-site-install.
			$this->activation_tasks();
		}
	}

	/**
	 * Tasks to run during initialization of the plugin.
	 *
	 * @return void
	 */
	private function activation_tasks(): void {
		// add our options for credentials without active autoload (as it is only needed in rare actions and never in frontend).
		add_option( 'imgur_api_client_id', '', '', false );
		add_option( 'imgur_api_client_secret', '', '', false );

		// install log table.
		Log::get_instance()->create_table();

		// install file logging table.
		Files::get_instance()->create_table();

		// initialize settings.
		Settings::get_instance()->initialize_options();
	}
}
