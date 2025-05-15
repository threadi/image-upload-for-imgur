<?php
/**
 * File to handle each Imgur API request and response.
 *
 * @source https://github.com/j0k3r/php-imgur-api-client
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Imgur;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use Imgur\Client;
use ImageUploadImgur\Logging\Log;
use ImageUploadImgur\Plugin\Helper;
use WP_REST_Request;

/**
 * The API object for Imgur.
 */
class Api {
	/**
	 * Instance of actual object.
	 *
	 * @var Api|null
	 */
	private static ?Api $instance = null;

	/**
	 * List of allowed file types.
	 *
	 * @source: https://apidocs.imgur.com/#c85c9dfc-7487-4de2-9ecd-66f727cf3139
	 *
	 * @var array<int,string>
	 */
	private array $allowed_file_types = array( 'image/jpeg', 'image/jpg', 'image/gif', 'image/png', 'image/apng', 'image/tiff' );

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Api
	 */
	public static function get_instance(): Api {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add files from REST API request.
	 *
	 * @param WP_REST_Request $data The request object.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_files_from_rest( WP_REST_Request $data ): void {
		// bail if setting is not completed.
		if ( ! Helper::is_api_set() ) {
			wp_send_json(
				array(
					'error' => __( 'Imgur credentials missing.', 'image-upload-for-imgur' ),
				)
			);
			exit; // @phpstan-ignore deadCode.unreachable
		}

		// get list of files from request.
		$files = $data->get_file_params();

		// get the post id.
		$post_id = absint( $data->get_param( 'post' ) );

		// bail if file list is empty.
		if ( empty( $files ) ) {
			wp_send_json(
				array(
					'error' => __( 'No files uploaded.', 'image-upload-for-imgur' ),
				)
			);
			exit; // @phpstan-ignore deadCode.unreachable
		}

		// list of images we return.
		$images = array();

		// upload each given file to imgur.
		foreach ( $files as $file ) {
			// upload file to imgur and get the response.
			$img_data = $this->add_file( $file['tmp_name'] );

			// bail if result does not contain a link.
			if ( empty( $img_data['link'] ) ) {
				continue;
			}

			/**
			 * Run additional actions for the single file.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param array $file The original file data.
			 * @param array $img_data The imgur-response.
			 * @param int $post_id The used post id.
			 */
			do_action( 'iufi_file_saved', $file, $img_data, $post_id );

			// add resulting link to the list of images.
			$images[] = $img_data['link'];
		}

		// bail if list of images is empty.
		if ( empty( $images ) ) {
			wp_send_json(
				array(
					'error' => __( 'No images successfully transferred to Imgur.', 'image-upload-for-imgur' ),
				)
			);
			exit; // @phpstan-ignore deadCode.unreachable
		}

		// return list of images with imgur-urls.
		wp_send_json( $images );
	}

	/**
	 * Add single file from given path to imgur via API.
	 *
	 * @param string $path_to_file The path of the file to use.
	 *
	 * @return array<string,mixed>
	 */
	public function add_file( string $path_to_file ): array {
		// bail if setting is not completed.
		if ( ! Helper::is_api_set() ) {
			return array();
		}

		// get imgur client.
		$client = new Client();
		$client->setOption( 'client_id', get_option( 'iufi_api_client_id' ) );
		$client->setOption( 'client_secret', get_option( 'iufi_api_client_secret' ) );

		// add file setting.
		$image_data = array(
			'image' => $path_to_file,
			'type'  => 'file',
		);

		$results = array();

		// upload and the image data.
		try {
			$results = $client->api( 'image' )->upload( $image_data );
		} catch ( \Exception $e ) {
			// collect error in log.
			Log::get_instance()->add_log( __( 'Error during upload of image to Imgur: ', 'image-upload-for-imgur' ) . $e->getMessage(), 'error', 'system' );
		}

		return $results;
	}

	/**
	 * Return allowed file types.
	 *
	 * @return array<int,string>
	 */
	public function get_allowed_file_types(): array {
		return $this->allowed_file_types;
	}
}
