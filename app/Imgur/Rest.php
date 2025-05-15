<?php
/**
 * File to handle the REST API for imgur-requests.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Imgur;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_REST_Server;

/**
 * Object to handle the initialization of this plugin.
 */
class Rest {
	/**
	 * Instance of this object.
	 *
	 * @var ?Rest
	 */
	private static ?Rest $instance = null;

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
	public static function get_instance(): Rest {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the endpoint.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_endpoint' ) );
	}

	/**
	 * Register the endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		// get the API object.
		$imgur_api_obj = Api::get_instance();

		// add the route.
		register_rest_route(
			'image-upload-for-imgur/v1',
			'/files/',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $imgur_api_obj, 'add_files_from_rest' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
