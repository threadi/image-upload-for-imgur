<?php
/**
 * File to handle a single checkbox for classic settings.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the field.
 */
class Checkbox {

	/**
	 * Get the output.
	 *
	 * @param array $attributes The settings for this field.
	 *
	 * @return void
	 */
	public static function get( array $attributes ): void {
		if ( ! empty( $attributes['fieldId'] ) ) {
			// get title.
			$title = '';
			if ( isset( $attributes['title'] ) ) {
				$title = $attributes['title'];
			}

			// set readonly attribute.
			$readonly = '';
			if ( isset( $attributes['readonly'] ) && false !== $attributes['readonly'] ) {
				$readonly = ' disabled';
				?>
				<input type="hidden" name="<?php echo esc_attr( $attributes['fieldId'] ); ?>_ro" value="<?php echo ( 1 === absint( get_option( $attributes['fieldId'], 0 ) ) || 1 === absint( filter_input( INPUT_POST, $attributes['fieldId'], FILTER_SANITIZE_NUMBER_INT ) ) ) ? '1' : '0'; ?>">
				<?php
			}

			?>
			<input type="checkbox" id="<?php echo esc_attr( $attributes['fieldId'] ); ?>"
					name="<?php echo esc_attr( $attributes['fieldId'] ); ?>"
					value="1"
				<?php
				echo ( 1 === absint( get_option( $attributes['fieldId'] ) ) || 1 === absint( filter_input( INPUT_GET, $attributes['fieldId'], FILTER_SANITIZE_NUMBER_INT ) ) ) ? ' checked="checked"' : '';
				?>
				<?php echo esc_attr( $readonly ); ?>
					class="image-upload-for-imgur-field-width"
					title="<?php echo esc_attr( $title ); ?>" data-depends="<?php echo esc_attr( wp_json_encode( $attributes['depends'] ) ); ?>"
			>
			<?php

			// show optional description for this checkbox.
			if ( ! empty( $attributes['description'] ) ) {
				echo '<p>' . wp_kses_post( $attributes['description'] ) . '</p>';
			}
		}
	}
}
