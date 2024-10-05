<?php
/**
 * File to handle initialization of this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImgurImageUpload\Imgur\Rest;
use ImgurImageUpload\Logging\Files;
use ImgurImageUpload\Logging\Log;

/**
 * Object to handle the initialization of this plugin.
 */
class Init {
	/**
	 * Instance of this object.
	 *
	 * @var ?Init
	 */
	private static ?Init $instance = null;

	/**
	 * Constructor for this handler.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Init {
		if ( ! static::$instance instanceof static ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// init transients.
		Transients::get_instance()->init();

		// init settings.
		Settings::get_instance()->init();

		// check setup state.
		Setup::get_instance()->init();

		// init API rest.
		Rest::get_instance()->init();

		// init logging.
		Log::get_instance()->init();

		// init file logging.
		Files::get_instance()->init();

		// initialize our block.
		add_action( 'init', array( $this, 'initialize_block' ) );

		// register cli.
		add_action( 'cli_init', array( $this, 'register_cli' ) );

		// misc.
		add_filter( 'plugin_action_links_' . plugin_basename( IMAGE_UPLOAD_FOR_IMGUR_PLUGIN ), array( $this, 'add_setting_link_for_plugin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_styles_and_js' ), PHP_INT_MAX );
	}

	/**
	 * Initialize the block.
	 *
	 * @return void
	 */
	public function initialize_block(): void {
		// bail if setting is not completed.
		if ( ! Helper::is_api_set() ) {
			return;
		}

		// embed the block.
		register_block_type( dirname( IMAGE_UPLOAD_FOR_IMGUR_PLUGIN ) );
	}

	/**
	 * Add link to settings in plugin list.
	 *
	 * @param array $links List of links for our plugin.
	 *
	 * @return array
	 */
	public function add_setting_link_for_plugin( array $links ): array {
		// adds the link to for settings.
		$links[] = "<a href='" . esc_url( Helper::get_settings_url() ) . "'>" . __( 'Settings', 'image-upload-for-imgur' ) . '</a>';

		// return resulting links.
		return $links;
	}

	/**
	 * Register WP-CLI.
	 *
	 * @return void
	 */
	public function register_cli(): void {
		\WP_CLI::add_command( 'imgur', 'ImgurImageUpload\Plugin\Cli' );
	}

	/**
	 * Add own CSS and JS for backend.
	 *
	 * @return void
	 */
	public function add_styles_and_js(): void {
		// admin-specific styles.
		wp_enqueue_style(
			'image-upload-for-imgur',
			Helper::get_plugin_url() . 'admin/styles.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'admin/styles.css' ),
		);

		// backend-JS.
		wp_enqueue_script(
			'image-upload-for-imgur',
			Helper::get_plugin_url() . 'admin/js.js',
			array( 'jquery' ),
			Helper::get_file_version( Helper::get_plugin_path() . 'admin/js.js' ),
			true
		);

		// add php-vars to our js-script.
		wp_localize_script(
			'image-upload-for-imgur',
			'imgurImageUploadJsVars',
			array(
				'review_url'    => Helper::get_review_url(),
				'title_rate_us' => __( 'Rate us!', 'image-upload-for-imgur' ),
			)
		);
	}
}
