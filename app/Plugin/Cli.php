<?php
/**
 * File for cli-commands of this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Run tasks for image upload to Imgur.
 */
class Cli {
	/**
	 * Reset the settings of this plugin with one command.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function reset_plugin(): void {
		Uninstaller::get_instance()->run();
		Installer::get_instance()->activation();
	}
}
