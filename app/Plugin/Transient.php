<?php
/**
 * This file contains the handling of a single transient for this plugin in wp-admin.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize a single transient-object.
 */
class Transient {
	/**
	 * The transient message.
	 *
	 * @var string
	 */
	private string $message = '';

	/**
	 * The internal name for this transient.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The transient type.
	 *
	 * @var string
	 */
	private string $type = '';

	/**
	 * Set the dismissible days.
	 *
	 * @var int
	 */
	private int $dismissible_days = 0;

	/**
	 * Action-callback-array.
	 *
	 * @var array<int,mixed>
	 */
	private array $action = array();

	/**
	 * List of URLs where this transient should not be visible.
	 *
	 * @var array<int,string>
	 */
	private array $hide_on = array();

	/**
	 * Constructor for this object.
	 *
	 * If $transient is given, fill the object with its data.
	 *
	 * @param string $transient The transient-name we use for this object.
	 */
	public function __construct( string $transient = '' ) {
		$this->set_name( $transient );
	}

	/**
	 * Get the message for this transient.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Set the message for this transient.
	 *
	 * @param string $message The text-message for the transient.
	 *
	 * @return void
	 */
	public function set_message( string $message ): void {
		$this->message = $message;
	}

	/**
	 * Save the transient in WP.
	 *
	 * @return void
	 */
	public function save(): void {
		// save the internal name to our own list of transients.
		Transients::get_instance()->add_transient( $this );

		// save the transient itself in WP.
		set_transient( $this->get_name(), $this->get_entry() );
	}

	/**
	 * Get the internal name of this transient.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the internal name of this transient.
	 *
	 * @param string $name The internal name for this transient.
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Collect the entry for this transient.
	 *
	 * @return array<string,mixed>
	 */
	private function get_entry(): array {
		return array(
			'message'          => $this->get_message(),
			'type'             => $this->get_type(),
			'dismissible_days' => $this->get_dismissible_days(),
			'action'           => $this->get_action(),
			'hide_on'          => $this->get_hide_on(),
		);
	}

	/**
	 * Check if this transient is set in WP.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function is_set(): bool {
		$transient = get_transient( $this->get_name() );
		if ( null === $transient ) {
			return false;
		}
		if ( false === $transient ) {
			return false;
		}
		return true;
	}

	/**
	 * Output the content of this transient.
	 *
	 * @return void
	 */
	public function display(): void {
		// check if this transient is dismissed.
		if ( false !== $this->is_dismissed() ) {
			return;
		}

		// get the transients contents.
		$entry = get_transient( $this->get_name() );

		// bail if entry is empty.
		if ( empty( $entry ) || ! isset( $entry['message'] ) ) {
			return;
		}

		// get attributes from entry and set them in object.
		$this->set_message( $entry['message'] );
		$this->set_type( $entry['type'] );
		$this->set_dismissible_days( $entry['dismissible_days'] );
		$this->set_action( $entry['action'] );
		$this->set_hide_on( $entry['hide_on'] );

		// bail if called URL is on hide-list.
		if ( $this->is_hidden() ) {
			return;
		}

		// output, if message is given.
		if ( $this->has_message() ) {
			?>
			<div class="image-upload-for-imgur-transient updated <?php echo esc_attr( $this->get_type() ); ?>" data-dismissible="<?php echo esc_attr( $this->get_name() ); ?>-<?php echo absint( $this->get_dismissible_days() ); ?>">
				<h3><?php echo wp_kses_post( Helper::get_logo_img() ); ?> <?php echo esc_html( Helper::get_plugin_name() ); ?></h3>
				<?php
				echo wp_kses_post( wpautop( $this->get_message() ) );
				if ( $this->get_dismissible_days() > 0 ) {
					/* translators: %1$d will be replaced by the days this message will be hidden. */
					$title = sprintf( __( 'Hide this message for %1$d days.', 'image-upload-for-imgur' ), $this->get_dismissible_days() );
					?>
					<button type="button" class="notice-dismiss" title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html__( 'Dismiss', 'image-upload-for-imgur' ); ?><span class="screen-reader-text"><?php echo esc_html( $title ); ?></span></button>
					<?php
				}
				?>
			</div>
			<?php
		}

		// call action, if set.
		if ( $this->has_action() ) {
			$action = $this->get_action();
			if ( method_exists( $action[0], $action[1] ) ) {
				$action(); // @phpstan-ignore callable.nonCallable
			}
		}

		// remove the transient if no dismiss is set.
		if ( 0 === $this->get_dismissible_days() ) {
			$this->delete();
		}
	}

	/**
	 * Get the message-type.
	 *
	 * @return string
	 */
	private function get_type(): string {
		return $this->type;
	}

	/**
	 * Set the message-type.
	 *
	 * @param string $type The type of this transient (e.g. error or success).
	 *
	 * @return void
	 */
	public function set_type( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Delete this transient from WP and our own list if it exists there.
	 *
	 * This does not remove the dismiss-marker as it should be independent of the settings itself.
	 *
	 * @return void
	 */
	public function delete(): void {
		$transients_obj = Transients::get_instance();

		if ( $transients_obj->is_transient_set( $this->get_name() ) ) {
			// delete from our own list.
			Transients::get_instance()->delete_transient( $this );

			// delete from WP.
			delete_transient( $this->get_name() );
		}
	}

	/**
	 * Return whether this transient is dismissed (true) or not (false).
	 *
	 * @return bool
	 */
	public function is_dismissed(): bool {
		// get value from cache, if set.
		$db_record = $this->get_admin_transient_dismiss_cache();

		// return bool depending on value.
		if ( 'forever' === $db_record || absint( $db_record ) >= time() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get transient-dismiss-cache.
	 *
	 * @return string|int|false
	 */
	private function get_admin_transient_dismiss_cache(): string|int|false {
		$cache_key = 'iufi-dismissed-' . md5( $this->get_name() );
		$timeout   = get_option( $cache_key );
		$timeout   = 'forever' === $timeout ? time() + 60 : $timeout;

		if ( empty( $timeout ) || time() > $timeout ) {
			return false;
		}

		return $timeout;
	}

	/**
	 * Delete dismiss-marker.
	 *
	 * @return void
	 */
	public function delete_dismiss(): void {
		delete_option( 'iufi-dismissed-' . md5( $this->get_name() ) );
	}

	/**
	 * Get the dismissible days.
	 *
	 * @return int
	 */
	private function get_dismissible_days(): int {
		return $this->dismissible_days;
	}

	/**
	 * Set the dismissible days.
	 *
	 * @param int $days The days for the dismissible-function.
	 *
	 * @return void
	 */
	public function set_dismissible_days( int $days ): void {
		$this->dismissible_days = $days;
	}

	/**
	 * Return the defined action for this transient.
	 *
	 * @return array<int,mixed>
	 */
	private function get_action(): array {
		return $this->action;
	}

	/**
	 * Add an action to run. This is meant to be a callback as array like: array( 'class-name', 'function' );
	 *
	 * @param array<int,mixed> $action The action as array.
	 * @return void
	 */
	public function set_action( array $action ): void {
		$this->action = $action;
	}

	/**
	 * Return whether this transient has a message set.
	 *
	 * @return bool
	 */
	private function has_message(): bool {
		return ! empty( $this->get_message() );
	}

	/**
	 * Return whether this transient has an action set.
	 *
	 * @return bool
	 */
	private function has_action(): bool {
		return ! empty( $this->get_action() );
	}

	/**
	 * Hide this transient on specified pages (its URLs).
	 *
	 * @return array<int,string>
	 */
	public function get_hide_on(): array {
		$hide_on  = $this->hide_on;
		$instance = $this;
		/**
		 * Filter where a single transient should be hidden.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<int,string> $hide_on List of absolute URLs.
		 * @param Transient $instance The actual transient object.
		 */
		return apply_filters( 'iufi_transient_hide_on', $hide_on, $instance );
	}

	/**
	 * Hide this transient on specified pages (its URLs).
	 *
	 * @param array<int,string> $hide_on List of URLs where this transient should not be visible.
	 *
	 * @return void
	 */
	public function set_hide_on( array $hide_on ): void {
		$this->hide_on = $hide_on;
	}

	/**
	 * Check if called URL is on list where this transient should not be visible.
	 *
	 * @return bool
	 */
	private function is_hidden(): bool {
		return in_array( Helper::get_current_url(), $this->get_hide_on(), true );
	}
}
