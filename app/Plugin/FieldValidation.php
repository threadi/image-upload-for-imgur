<?php
/**
 * File to validate a text field setting for API.
 *
 * @package image-upload-for-imgur.
 */

namespace ImageUploadImgur\Plugin;

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
	 * @return array<string,string>
	 * @noinspection PhpUnused
	 */
	public static function rest_validate( string $value ): array {
		if ( '' === $value ) {
			return array(
				'error' => 'no_string_given',
				'text'  => __( 'Please enter a valid value.', 'image-upload-for-imgur' ),
			);
		}

		return array();
	}
}
