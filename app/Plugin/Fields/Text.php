<?php
/**
 * File to handle a single text field for classic settings.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the field.
 */
class Text {

	/**
	 * Get the output.
	 *
	 * @param array<string,mixed> $attributes The settings for this field.
	 *
	 * @return void
	 */
	public static function get( array $attributes ): void {
		if ( ! empty( $attributes['fieldId'] ) ) {
			// get value from config.
			$value = get_option( $attributes['fieldId'] );

			// or get it from request.
			$request_value = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, $attributes['fieldId'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) );
			if ( ! empty( $request_value ) ) {
				$value = $request_value;
			}

			// get title.
			$title = '';
			if ( isset( $attributes['title'] ) ) {
				$title = (string) $attributes['title'];
			}

			// set readonly attribute.
			$readonly = '';
			if ( isset( $attributes['readonly'] ) && false !== $attributes['readonly'] ) {
				$readonly = ' disabled';
				?>
				<input type="hidden" name="<?php echo esc_attr( $attributes['fieldId'] ); ?>_ro" value="<?php echo esc_attr( $value ); ?>">
				<?php
			}

			// get depends.
			$depends = wp_json_encode( $attributes['depends'] );
			if ( ! $depends ) {
				$depends = '';
			}

			// output.
			?>
			<input type="text" id="<?php echo esc_attr( $attributes['fieldId'] ); ?>" name="<?php echo esc_attr( $attributes['fieldId'] ); ?>" value="<?php echo esc_attr( $value ); ?>"
				<?php
				echo ! empty( $attributes['placeholder'] ) ? ' placeholder="' . esc_attr( $attributes['placeholder'] ) . '"' : '';
				echo ! empty( $attributes['required'] ) ? ' required="' . esc_attr( $attributes['required'] ) . '"' : '';
				?>
				<?php echo esc_attr( $readonly ); ?> class="widefat" title="<?php echo esc_attr( $title ); ?>" data-depends="<?php echo esc_attr( $depends ); ?>">
			<?php
			if ( ! empty( $attributes['description'] ) ) {
				echo '<p>' . wp_kses_post( $attributes['description'] ) . '</p>';
			}
		}
	}

	/**
	 * Validate the value from this field.
	 *
	 * @param mixed $value The given value.
	 *
	 * @return string
	 */
	public static function validate( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return $value;
	}
}
