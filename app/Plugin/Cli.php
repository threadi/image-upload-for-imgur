<?php
/**
 * File for cli-commands of this plugin.
 *
 * @package imgur-image-upload
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Handler for cli commands.
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
