<?php
/**
 * File to handle a single select field for classic settings.
 *
 * @package image-upload-for-imgurs
 */

namespace ImageUploadImgur\Plugin\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the field.
 */
class Select {

	/**
	 * Get the output.
	 *
	 * @param array $attributes The settings for this field.
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
				$title = $attributes['title'];
			}

			// set readonly attribute.
			if ( isset( $attributes['readonly'] ) && false !== $attributes['readonly'] ) {
				?>
				<input type="hidden" name="<?php echo esc_attr( $attributes['fieldId'] ); ?>_ro" value="<?php echo esc_attr( $value ); ?>" />
				<?php
			}

			?>
			<select id="<?php echo esc_attr( $attributes['fieldId'] ); ?>" name="
									<?php
									echo esc_attr( $attributes['fieldId'] );
									echo isset( $attributes['multiple'] ) && false !== $attributes['multiple'] ? '[]' : '';
									?>
			" class="image-upload-for-imgur-field-width" title="<?php echo esc_attr( $title ); ?>"<?php echo isset( $attributes['readonly'] ) && false !== $attributes['readonly'] ? ' disabled="disabled"' : ''; ?><?php echo isset( $attributes['multiple'] ) && false !== $attributes['multiple'] ? ' multiple="multiple"' : ''; ?> data-depends="<?php echo esc_attr( wp_json_encode( $attributes['depends'] ) ); ?>">
				<?php
				if ( false === $attributes['hide_empty_option'] ) {
					?>
						<option value=""></option>
					<?php
				}
				foreach ( $attributes['options'] as $key => $label ) {
					?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php echo ( $value === $key || ( is_array( $value ) && in_array( $key, $value, true ) ) ? ' selected="selected"' : '' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
				}
				?>
			</select>
			<?php
			if ( ! empty( $attributes['description'] ) ) {
				echo '<p>' . wp_kses_post( $attributes['description'] ) . '</p>';
			}
		} elseif ( empty( $attributes['options'] ) && ! empty( $attributes['no_values'] ) ) {
			echo '<p>' . esc_html( $attributes['no_values'] ) . '</p>';
		}
	}
}
