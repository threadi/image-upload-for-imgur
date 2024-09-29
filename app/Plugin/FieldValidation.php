<?php
/**
 * File to validate a text field setting for API.
 *
 * @package imgur-image-upload.
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object which validates the given URL.
 */
class FieldValidation {
	/**
	 * Validate given string from REST API.
	 *
	 * Returns an array with list of errors.
	 * Returns empty array if all is ok.
	 *
	 * @param string $value The configured URL.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	public static function rest_validate( string $value ): array {
		if ( 0 === strlen( $value ) ) {
			return array(
				'error' => 'no_string_given',
				'text'  => __( 'Please enter a valid value.', 'imgur-image-upload' ),
			);
		}

		return array();
	}
}
