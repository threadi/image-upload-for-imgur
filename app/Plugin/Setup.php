<?php
/**
 * File to handle setup of this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImageUploadImgur\Imgur\Api;

/**
 * Object to handle the setup of this plugin.
 */
class Setup {
	/**
	 * Instance of this object.
	 *
	 * @var ?Setup
	 */
	private static ?Setup $instance = null;

	/**
	 * Define setup as array with steps.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $setup = array();

	/**
	 * Mark setup as error.
	 *
	 * @var bool
	 */
	private bool $error = false;

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
	public static function get_instance(): Setup {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the setup-object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'init_setup' ) );
	}

	/**
	 * Initialize the setup-object.
	 *
	 * @return void
	 */
	public function init_setup(): void {
		// check to show hint if setup should be run.
		$this->show_hint();

		// initialize the setup object.
		$setup_obj = \easySetupForWordPress\Setup::get_instance();
		$setup_obj->init();

		// configure settings for the setup.
		$setup_obj->set_url( Helper::get_plugin_url() );
		$setup_obj->set_path( Helper::get_plugin_path() );
		$setup_obj->set_texts(
			array(
				'title_error' => __( 'Error', 'image-upload-for-imgur' ),
				'txt_error_1' => __( 'The following error occurred:', 'image-upload-for-imgur' ),
				'txt_error_2' => '',
			)
		);
		$setup_obj->set_config( $this->get_config() );

		// only load setup if it is not completed.
		if ( ! $this->is_completed() ) {
			add_filter( 'esfw_completed', array( $this, 'check_completed_value' ), 10, 2 );
			add_action( 'esfw_set_completed', array( $this, 'forward_user_on_completion' ) );
			add_action( 'esfw_process', array( $this, 'run_process' ) );
			add_action( 'esfw_process', array( $this, 'show_process_end' ), PHP_INT_MAX );
		}
	}

	/**
	 * Return whether setup is completed.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return \easySetupForWordPress\Setup::get_instance()->is_completed( $this->get_setup_name() );
	}

	/**
	 * Check if setup should be run and show hint for it.
	 *
	 * @return void
	 */
	public function show_hint(): void {
		// get transients object.
		$transients_obj = Transients::get_instance();

		// check if setup should be run.
		if ( ! $this->is_completed() ) {
			// bail if hint is already set.
			if ( $transients_obj->get_transient_by_name( 'iufi_start_setup_hint' )->is_set() ) {
				return;
			}

			// delete all other transients.
			foreach ( $transients_obj->get_transients() as $transient_obj ) {
				$transient_obj->delete();
			}

			// add hint to run setup.
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'iufi_start_setup_hint' );
			$transient_obj->set_message( __( '<strong>You have installed Image Upload for Imgur - nice and thank you!</strong> Now run the setup to enter your Imgur credentials to use the possibilities this plugin adds to your project.', 'image-upload-for-imgur' ) . '<br><br>' . sprintf( '<a href="%1$s" class="button button-primary">' . __( 'Start setup', 'image-upload-for-imgur' ) . '</a>', esc_url( Helper::get_settings_url() ) ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->set_dismissible_days( 2 );
			$transient_obj->set_hide_on( array( Helper::get_settings_url() ) );
			$transient_obj->save();
		} else {
			$transients_obj->get_transient_by_name( 'iufi_start_setup_hint' )->delete();
		}
	}

	/**
	 * Return the configured setup.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_setup(): array {
		$setup = $this->setup;
		if ( empty( $setup ) ) {
			$this->set_config();
			$setup = $this->setup;
		}

		/**
		 * Filter the configured setup for this plugin.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<int,array<string,mixed>> $setup The setup-configuration.
		 */
		return apply_filters( 'iufi_setup', $setup );
	}

	/**
	 * Show setup dialog.
	 *
	 * @return void
	 */
	public function display(): void {
		echo wp_kses_post( \easySetupForWordPress\Setup::get_instance()->display( $this->get_setup_name() ) );
	}

	/**
	 * Return configuration for setup.
	 *
	 * Here we define which steps and texts are used by wp-easy-setup.
	 *
	 * @return array<string,mixed>
	 */
	private function get_config(): array {
		// get setup.
		$setup = $this->get_setup();

		// collect configuration for the setup.
		$config = array(
			'name'                  => $this->get_setup_name(),
			'title'                 => __( 'Image Upload for Imgur', 'image-upload-for-imgur' ) . ' ' . __( 'Setup', 'image-upload-for-imgur' ),
			'steps'                 => $setup,
			'back_button_label'     => __( 'Back', 'image-upload-for-imgur' ) . '<span class="dashicons dashicons-undo"></span>',
			'continue_button_label' => __( 'Continue', 'image-upload-for-imgur' ) . '<span class="dashicons dashicons-controls-play"></span>',
			'finish_button_label'   => __( 'Completed', 'image-upload-for-imgur' ) . '<span class="dashicons dashicons-saved"></span>',
		);

		/**
		 * Filter the setup configuration.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $config List of configuration for the setup.
		 */
		return apply_filters( 'iufi_setup_config', $config );
	}

	/**
	 * Set process label.
	 *
	 * @param string $label The label to process.
	 *
	 * @return void
	 */
	public function set_process_label( string $label ): void {
		update_option( 'esfw_step_label', $label );
	}

	/**
	 * Updates the process step.
	 *
	 * @param int $step Steps to add.
	 *
	 * @return void
	 */
	public function update_process_step( int $step = 1 ): void {
		update_option( 'esfw_steps', absint( get_option( 'esfw_step', 0 ) + $step ) );
	}

	/**
	 * Sets the setup configuration.
	 *
	 * @return void
	 */
	public function set_config(): void {
		// get properties from settings.
		$settings = Settings::get_instance();
		$settings->set_settings();

		// get the field config.
		$api_id_settings  = $settings->get_settings_for_field( 'iufi_api_client_id' );
		$api_key_settings = $settings->get_settings_for_field( 'iufi_api_client_secret' );

		// define setup.
		$this->setup = array(
			1 => array(
				'iufi_api_client_id'     => array(
					'type'                => 'TextControl',
					'label'               => $api_id_settings['label'],
					'help'                => $api_id_settings['description'],
					'required'            => true,
					'validation_callback' => 'ImageUploadImgur\Plugin\FieldValidation::rest_validate',
				),
				'iufi_api_client_secret' => array(
					'type'                => 'TextControl',
					'label'               => $api_key_settings['label'],
					'help'                => '',
					'required'            => true,
					'validation_callback' => 'ImageUploadImgur\Plugin\FieldValidation::rest_validate',
				),
				'help'                   => array(
					'type' => 'Text',
					/* translators: %1$s will be replaced by our support-forum-URL. */
					'text' => '<p><span class="dashicons dashicons-editor-help"></span> ' . sprintf( __( '<strong>Need help?</strong> Ask in <a href="%1$s" target="_blank">our forum (opens new window)</a>.', 'image-upload-for-imgur' ), esc_url( Helper::get_plugin_support_url() ) ) . '</p>',
				),
			),
			2 => array(
				'runSetup' => array(
					'type'  => 'ProgressBar',
					'label' => __( 'Setup checking your API credentials', 'image-upload-for-imgur' ),
				),
			),
		);
	}

	/**
	 * Update max count.
	 *
	 * @param int $max_count The value to add.
	 *
	 * @return void
	 */
	public function update_max_step( int $max_count ): void {
		update_option( 'esfw_max_steps', absint( get_option( 'esfw_max_steps' ) ) + $max_count );
	}

	/**
	 * Update count.
	 *
	 * @param int $count The value to add.
	 *
	 * @return void
	 */
	public function update_step( int $count ): void {
		update_option( 'esfw_steps', absint( get_option( 'esfw_step' ) ) + $count );
	}

	/**
	 * Run the process.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function run_process( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		// set max step count.
		$this->update_max_step( 2 );

		// set actual step count.
		update_option( 'esfw_steps', 1 );

		// check the credentials.
		$this->set_process_label( __( 'Checking your API credentials.', 'image-upload-for-imgur' ) );

		// test the api.
		$imgur_api_obj = Api::get_instance();
		$upload_result = $imgur_api_obj->add_file( Helper::get_plugin_path() . 'gfx/imgur_logo.png' );
		if ( empty( $upload_result['link'] ) ) {
			$this->set_process_label( __( 'Error during check of your credentials!', 'image-upload-for-imgur' ) );
			$this->set_error();
		}

		// set steps to max steps to end the process.
		$this->update_process_step( 1 );
	}

	/**
	 * Show process end text.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function show_process_end( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		if ( $this->has_error() ) {
			/* translators: %1$s will be replaced by the URL for the log. */
			$this->set_process_label( '<strong>' . __( 'Your Imgur API credentials could not be verified!', 'image-upload-for-imgur' ) . '</strong> ' . sprintf( __( 'Please take a look at <a href="%1$s">the log</a> to see the cause.', 'image-upload-for-imgur' ), esc_url( Helper::get_settings_url( 'logs' ) ) ) );
		} else {
			$this->set_process_label( __( 'Your Imgur API credentials has been successfully checked.', 'image-upload-for-imgur' ) );
		}
	}

	/**
	 * Forward user if setup is completed.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function forward_user_on_completion( string $config_name ): void {
		// bail if this is not our setup.
		if ( $this->get_setup_name() !== $config_name ) {
			return;
		}

		// bail if this is not an api request.
		if ( ! Helper::is_admin_api_request() ) {
			return;
		}

		// if credentials are not setup remove completion marker for setup.
		if ( ! Helper::is_api_set() ) {
			$this->remove_completion();
		} else {
			// add transient as hint, if API has been set.
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'iufi_intro' );
			$transient_obj->set_message( __( '<strong>Thanks for configuring the Imgur API.</strong> You are now able to use the Block "Image Upload via Imgur" in the Block editor. Just editor one of your entries and add the Block where you want.', 'image-upload-for-imgur' ) );
			$transient_obj->set_type( 'success' );
			$transient_obj->save();
		}

		// return JSON with forward to settings url (which is the same as the setup link).
		wp_send_json(
			array(
				'forward' => Helper::get_url_of_post_type_with_post_posts(),
			)
		);
	}

	/**
	 * If credentials are set do not run the setup.
	 *
	 * @param bool   $is_completed Whether to run setup (true) or not (false).
	 * @param string $config_name The name of the used setup-configuration.
	 *
	 * @return bool
	 */
	public function check_completed_value( bool $is_completed, string $config_name ): bool {
		// bail if this is not our setup.
		if ( $this->get_setup_name() !== $config_name ) {
			return $is_completed;
		}

		if ( Helper::is_api_set() ) {
			return true;
		}

		return $is_completed;
	}

	/**
	 * Return name for the setup configuration.
	 *
	 * @return string
	 */
	public function get_setup_name(): string {
		return 'image-upload-for-imgur';
	}

	/**
	 * Mark setup as error.
	 *
	 * @return void
	 */
	private function set_error(): void {
		$this->error = true;
	}

	/**
	 * Return whether setup has an error (true) or not (false).
	 *
	 * @return bool
	 */
	private function has_error(): bool {
		return $this->error;
	}

	/**
	 * Remove that setup has been completed.
	 *
	 * @return void
	 */
	public function remove_completion(): void {
		// get actual list of completed setups.
		$actual_completed = get_option( 'esfw_completed', array() );

		// get entry.
		$key = array_search( $this->get_setup_name(), $actual_completed, true );
		if ( false !== $key ) {
			unset( $actual_completed[ $key ] );
		}

		// add the actual setup to the list of completed setups.
		update_option( 'esfw_completed', $actual_completed );
	}
}
