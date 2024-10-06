<?php
/**
 * File to handle plugin-settings.
 *
 * @package image-upload-for-imgur
 */

namespace ImgurImageUpload\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImgurImageUpload\Imgur\Api;
use ImgurImageUpload\Logging\Tables\Files;
use ImgurImageUpload\Logging\Tables\Logs;

/**
 * Object to handle settings.
 */
class Settings {
	/**
	 * Instance of this object.
	 *
	 * @var ?Settings
	 */
	private static ?Settings $instance = null;

	/**
	 * Constructor for Settings-Handler.
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
	public static function get_instance(): Settings {
		if ( ! static::$instance instanceof static ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Variable for complete settings.
	 *
	 * @var array
	 */
	private array $settings = array();

	/**
	 * Variable for tab settings.
	 *
	 * @var array
	 */
	private array $tabs = array();

	/**
	 * Initialize the settings.
	 *
	 * @return void
	 */
	public function init(): void {
		// set all settings for this plugin.
		add_action( 'init', array( $this, 'set_settings' ) );
		add_action( 'init', array( $this, 'register_settings' ) );

		// register fields to manage the settings.
		add_action( 'admin_init', array( $this, 'register_fields' ) );

		// add admin-menu.
		add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );

		// secure our own plugin settings.
		add_action( 'updated_option', array( $this, 'check_settings' ) );
	}

	/**
	 * Define ALL settings for this plugin.
	 *
	 * @return void
	 */
	public function set_settings(): void {
		// set tabs.
		$this->tabs = array(
			array(
				'label'         => __( 'Basic Settings', 'image-upload-for-imgur' ),
				'key'           => '',
				'settings_page' => 'image_upload_for_imgur_settings',
				'page'          => 'image_upload_for_imgur_settings',
				'order'         => 10,
			),
			array(
				'label'         => __( 'Advanced Settings', 'image-upload-for-imgur' ),
				'key'           => 'advanced',
				'settings_page' => 'image_upload_for_imgur_settings_advanced',
				'page'          => 'image_upload_for_imgur_settings',
				'order'         => 20,
			),
			array(
				'label'    => __( 'Logs', 'image-upload-for-imgur' ),
				'key'      => 'logs',
				'callback' => array( $this, 'show_log' ),
				'page'     => 'image_upload_for_imgur_settings',
				'order'    => 900,
			),
			array(
				'label'      => __( 'Questions? Check our forum!', 'image-upload-for-imgur' ),
				'key'        => 'help',
				'url'        => Helper::get_plugin_support_url(),
				'url_target' => '_blank',
				'class'      => 'nav-tab-help nav-tab-active',
				'page'       => 'image_upload_for_imgur_settings',
				'order'      => 2000,
			),
		);

		// get allowed file types.
		$file_types       = Api::get_instance()->get_allowed_file_types();
		$file_types_array = array();
		foreach ( $file_types as $file_type ) {
			$file_types_array[ $file_type ] = $file_type;
		}

		// define settings for this plugin.
		$this->settings = array(
			'settings_section_main'     => array(
				'label'         => __( 'General Settings', 'image-upload-for-imgur' ),
				'settings_page' => 'image_upload_for_imgur_settings',
				'callback'      => '__return_true',
				'fields'        => array(
					'imgur_api_client_id'     => array(
						'label'               => __( 'Imgur API Client ID', 'image-upload-for-imgur' ),
						'description'         => __( 'Create a new API client ID and secret <a href="https://api.imgur.com/oauth2/addclient" target="_blank">here (opens new window)</a>. Choose "OAuth 2 authorization without a callback URL" in the form.<br>Alternative you could use <a href="https://imgur.com/account/settings/apps" target="_blank">one of your existing keys (opens new window)</a>.', 'image-upload-for-imgur' ),
						'field'               => array( 'ImgurImageUpload\Plugin\Fields\Text', 'get' ),
						'required'            => true,
						'register_attributes' => array(
							'default'           => '',
							'show_in_rest'      => true,
							'sanitize_callback' => array( 'ImgurImageUpload\Plugin\Fields\Text', 'validate' ),
							'type'              => 'string',
						),
						'callback'            => array( $this, 'check_credentials' ),
					),
					'imgur_api_client_secret' => array(
						'label'               => __( 'Imgur API Client Secret', 'image-upload-for-imgur' ),
						'field'               => array( 'ImgurImageUpload\Plugin\Fields\Text', 'get' ),
						'required'            => true,
						'register_attributes' => array(
							'default'           => '',
							'show_in_rest'      => true,
							'sanitize_callback' => array( 'ImgurImageUpload\Plugin\Fields\Text', 'validate' ),
							'type'              => 'string',
						),
						'callback'            => array( $this, 'check_credentials' ),
					),
				),
			),
			'settings_section_advanced' => array(
				'label'         => __( 'Advanced Settings', 'image-upload-for-imgur' ),
				'settings_page' => 'image_upload_for_imgur_settings_advanced',
				'callback'      => '__return_true',
				'fields'        => array(
					'imgur_allow_multiple_files' => array(
						'label'               => __( 'Allow multiple files per Block', 'image-upload-for-imgur' ),
						'field'               => array( 'ImgurImageUpload\Plugin\Fields\Checkbox', 'get' ),
						'required'            => true,
						'register_attributes' => array(
							'default'      => 0,
							'show_in_rest' => true,
							'type'         => 'integer',
						),
					),
					'imgur_file_types'           => array(
						'label'               => __( 'Choose allowed file types', 'image-upload-for-imgur' ),
						'field'               => array( 'ImgurImageUpload\Plugin\Fields\Select', 'get' ),
						'options'             => $file_types_array,
						'hide_empty_option'   => true,
						'multiple'            => true,
						'required'            => true,
						'register_attributes' => array(
							'default'      => Api::get_instance()->get_allowed_file_types(),
							'show_in_rest' => array(
								'name'   => 'imgur_file_types',
								'schema' => array(
									'type'  => 'array',
									'items' => array(
										'type' => 'string',
									),
								),
							),
							'type'         => 'array',
						),
						'callback'            => array( $this, 'check_file_types' ),
					),
					'imgur_log_files'            => array(
						'label'               => __( 'Log all uploaded files', 'image-upload-for-imgur' ),
						'field'               => array( 'ImgurImageUpload\Plugin\Fields\Checkbox', 'get' ),
						'required'            => true,
						'register_attributes' => array(
							'default' => 0,
							'type'    => 'integer',
						),
					),
				),
			),
		);

		// add file list, if enabled.
		if ( 1 === absint( get_option( 'imgur_log_files' ) ) ) {
			$this->tabs[] = array(
				'label'    => __( 'Files', 'image-upload-for-imgur' ),
				'key'      => 'files',
				'callback' => array( $this, 'show_files' ),
				'page'     => 'image_upload_for_imgur_settings',
				'order'    => 30,
			);
		}
	}

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		foreach ( $this->get_settings() as $section_settings ) {
			foreach ( $section_settings['fields'] as $field_name => $field_settings ) {
				if ( ! isset( $field_settings['do_not_register'] ) ) {
					$args = array();
					if ( ! empty( $field_settings['register_attributes'] ) ) {
						unset( $field_settings['register_attributes']['default'] );
						$args = $field_settings['register_attributes'];
					}
					register_setting(
						$section_settings['settings_page'],
						$field_name,
						$args
					);
				}
			}
		}
	}

	/**
	 * Register fields to manage the settings.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		foreach ( $this->get_settings() as $section_name => $section_settings ) {
			if ( ! empty( $section_settings ) && ! empty( $section_settings['settings_page'] ) && ! empty( $section_settings['label'] ) && ! empty( $section_settings['callback'] ) ) {
				// bail if fields is empty and callback is just true.
				if ( empty( $section_settings['fields'] ) && '__return_true' === $section_settings['callback'] ) {
					continue;
				}

				$args = array();
				if ( isset( $section_settings['before_section'] ) ) {
					$args['before_section'] = $section_settings['before_section'];
				}
				if ( isset( $section_settings['after_section'] ) ) {
					$args['after_section'] = $section_settings['after_section'];
				}

				// add section.
				add_settings_section(
					$section_name,
					$section_settings['label'],
					$section_settings['callback'],
					$section_settings['settings_page'],
					$args
				);

				// add fields in this section.
				foreach ( $section_settings['fields'] as $field_name => $field_settings ) {
					// get arguments for this field.
					$arguments = array(
						'label_for'         => $field_name,
						'fieldId'           => $field_name,
						'options'           => ! empty( $field_settings['options'] ) ? $field_settings['options'] : array(),
						'description'       => ! empty( $field_settings['description'] ) ? $field_settings['description'] : '',
						'placeholder'       => ! empty( $field_settings['placeholder'] ) ? $field_settings['placeholder'] : '',
						'readonly'          => ! empty( $field_settings['readonly'] ) ? $field_settings['readonly'] : false,
						'required'          => ! empty( $field_settings['required'] ) ? $field_settings['required'] : false,
						'hide_empty_option' => ! empty( $field_settings['hide_empty_option'] ) ? $field_settings['hide_empty_option'] : false,
						'multiple'          => ! empty( $field_settings['multiple'] ) ? $field_settings['multiple'] : false,
						'depends'           => ! empty( $field_settings['depends'] ) ? $field_settings['depends'] : array(),
						'class'             => ! empty( $field_settings['class'] ) ? $field_settings['class'] : array(),
					);

					/**
					 * Filter the arguments for this field.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 *
					 * @param array $arguments List of arguments.
					 * @param array $field_settings Setting for this field.
					 * @param string $field_name Internal name of the field.
					 */
					$arguments = apply_filters( 'image_upload_for_imgur_setting_field_arguments', $arguments, $field_settings, $field_name );

					// add the field.
					add_settings_field(
						$field_name,
						$field_settings['label'],
						$field_settings['field'],
						$section_settings['settings_page'],
						$section_name,
						$arguments
					);
				}
			}
		}
	}

	/**
	 * Add settings-page for the plugin if setup has been completed.
	 *
	 * @return void
	 */
	public function add_settings_menu(): void {
		// add our settings-page in menu.
		add_options_page(
			__( 'Image Upload for Imgur Settings', 'image-upload-for-imgur' ),
			__( 'Image Upload for Imgur Settings', 'image-upload-for-imgur' ),
			'manage_options',
			'image_upload_for_imgur_settings',
			array( $this, 'add_settings_content' ),
			10
		);
	}

	/**
	 * Create the admin-page with tab-navigation.
	 *
	 * @return void
	 */
	public function add_settings_content(): void {
		// check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// show setup if it is not completed.
		$setup_obj = Setup::get_instance();
		if ( ! $setup_obj->is_completed() ) {
			$setup_obj->display();
			return;
		}

		// get the active tab from the request-param.
		$tab = sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) );

		// set page to show.
		$page = 'image_upload_for_imgur_settings';

		// hide the save button.
		$hide_save_button = false;

		// set callback to use.
		$callback = '';

		// output wrapper.
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php
				foreach ( $this->get_tabs() as $tab_settings ) {
					// bail if tab-settings are not an array.
					if ( ! is_array( $tab_settings ) ) {
						continue;
					}

					// Set url.
					$url    = Helper::get_settings_url( $tab_settings['key'] );
					$target = '_self';
					if ( ! empty( $tab_settings['url'] ) ) {
						$url    = $tab_settings['url'];
						$target = $tab_settings['url_target'];
					}

					// Set class for tab and page for form-view.
					$class = '';
					if ( ! empty( $tab_settings['class'] ) ) {
						$class .= ' ' . $tab_settings['class'];
					}
					if ( $tab === $tab_settings['key'] ) {
						$class .= ' nav-tab-active';
						if ( ! empty( $tab_settings['settings_page'] ) ) {
							$page = $tab_settings['settings_page'];
						}
						if ( ! empty( $tab_settings['callback'] ) ) {
							$callback = $tab_settings['callback'];
							$page     = '';
						}
						if ( isset( $tab_settings['do_not_save'] ) ) {
							$hide_save_button = $tab_settings['do_not_save'];
						}
					}

					// decide which tab-type we want to output.
					if ( isset( $tab_settings['do_not_link'] ) && false !== $tab_settings['do_not_link'] ) {
						?>
						<span class="nav-tab"><?php echo esc_html( $tab_settings['label'] ); ?></span>
						<?php
					} else {
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $class ); ?>" target="<?php echo esc_attr( $target ); ?>"><?php echo esc_html( $tab_settings['label'] ); ?></a>
						<?php
					}
				}
				?>
			</nav>

			<div class="tab-content">
			<?php
			if ( ! empty( $page ) ) {
				?>
					<form method="post" action="<?php echo esc_url( get_admin_url() ); ?>options.php" class="image-upload-for-imgur-settings">
					<?php
					settings_fields( $page );
					do_settings_sections( $page );
					$hide_save_button ? '' : submit_button();
					?>
					</form>
					<?php
			}

			if ( ! empty( $callback ) ) {
				call_user_func( $callback );
			}
			?>
			</div>
		</div>
		<?php
	}

	/**
	 * Return the settings and save them on the object.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$settings = $this->settings;

		/**
		 * Filter the plugin-settings.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param array $settings The settings as array.
		 */
		$this->settings = apply_filters( 'image_upload_for_imgur_settings', $settings );

		// return the resulting settings.
		return $this->settings;
	}

	/**
	 * Return the value of a single actual setting.
	 *
	 * @param string $setting The requested setting as string.
	 *
	 * @return string
	 */
	public function get_setting( string $setting ): string {
		return get_option( $setting );
	}

	/**
	 * Return the tabs for the settings page.
	 *
	 * @return array
	 */
	public function get_tabs(): array {
		$tabs = $this->tabs;
		/**
		 * Filter the list of tabs.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param array $false Set true to hide the buttons.
		 */
		$tabs = apply_filters( 'image_upload_for_imgur_settings_tabs', $tabs );

		// sort them by 'order'-field.
		usort( $tabs, array( $this, 'sort_tabs' ) );

		// return resulting list of tabs.
		return $tabs;
	}

	/**
	 * Sort the tabs by 'order'-field.
	 *
	 * @param array $a Tab 1 to check.
	 * @param array $b Tab 2 to compare with tab 1.
	 *
	 * @return int
	 */
	public function sort_tabs( array $a, array $b ): int {
		if ( empty( $a['order'] ) ) {
			$a['order'] = 500;
		}
		if ( empty( $b['order'] ) ) {
			$b['order'] = 500;
		}
		return $a['order'] - $b['order'];
	}

	/**
	 * Initialize the options of this plugin, set its default values.
	 *
	 * Only used during installation.
	 *
	 * @return void
	 */
	public function initialize_options(): void {
		$this->set_settings();
		foreach ( $this->get_settings() as $section_settings ) {
			foreach ( $section_settings['fields'] as $field_name => $field_settings ) {
				if ( isset( $field_settings['register_attributes']['default'] ) && ! get_option( $field_name ) ) {
					add_option( $field_name, $field_settings['register_attributes']['default'], '', true );
				}
			}
		}
	}

	/**
	 * Check the settings.
	 *
	 * @param string $option The option which has been saved.
	 *
	 * @return void
	 */
	public function check_settings( string $option ): void {
		// bail if option is not part of our plugin.
		if ( false === stripos( $option, 'imgur_' ) ) {
			return;
		}

		// remove the callbacks from settings.
		foreach ( $this->get_settings() as $section_settings ) {
			if ( ! empty( $section_settings ) && ! empty( $section_settings['settings_page'] ) ) {
				if ( ! empty( $section_settings['fields'] ) ) {
					foreach ( $section_settings['fields'] as $field_name => $field_settings ) {
						if ( $option === $field_name && ! empty( $field_settings['callback'] ) ) {
							call_user_func( $field_settings['callback'], $field_name );
						}
					}
				}
			}
		}
	}

	/**
	 * Show log table.
	 *
	 * @return void
	 */
	public function show_log(): void {
		if ( current_user_can( 'manage_options' ) ) {
			// if WP_List_Table is not loaded automatically, we need to load it.
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			}
			$log = new Logs();
			$log->prepare_items();
			?>
			<div class="wrap">
				<h2><?php echo esc_html__( 'Logs', 'image-upload-for-imgur' ); ?></h2>
				<?php
				$log->views();
				$log->display();
				?>
			</div>
			<?php
		}
	}

	/**
	 * Check given credentials.
	 *
	 * @param string $field_name The settings field name.
	 *
	 * @return void
	 */
	public function check_credentials( string $field_name ): void {
		// bail if credentials are not set.
		if ( ! Helper::is_api_set() ) {
			return;
		}

		// bail if nothing has been set.
		if ( empty( $field_name ) ) {
			return;
		}

		// run check, if result is empty show error.
		$upload_result = Api::get_instance()->add_file( Helper::get_plugin_path() . 'gfx/imgur_logo.png' );
		if ( empty( $upload_result['link'] ) ) {
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'image_upload_for_imgur_credential_error' );
			$transient_obj->set_message( __( '<strong>Error during test of your API credentials</strong> Please check your entered API key and credential.', 'image-upload-for-imgur' ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->save();
		}
	}

	/**
	 * Prevent usage of none file types.
	 *
	 * @param string $field_name The name of the field.
	 *
	 * @return void
	 */
	public function check_file_types( string $field_name ): void {
		$value = get_option( $field_name );
		if ( empty( $value ) ) {
			update_option( $field_name, Api::get_instance()->get_allowed_file_types() );
		}
	}

	/**
	 * Show list of files as table.
	 *
	 * @return void
	 */
	public function show_files(): void {
		if ( current_user_can( 'manage_options' ) ) {
			// if WP_List_Table is not loaded automatically, we need to load it.
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			}
			$log = new Files();
			$log->prepare_items();
			?>
			<div class="wrap">
				<h2><?php echo esc_html__( 'Uploaded files', 'image-upload-for-imgur' ); ?></h2>
				<?php
				$log->views();
				$log->display();
				?>
			</div>
			<?php
		}
	}

	/**
	 * Return settings for single field.
	 *
	 * @param string $field The requested field.
	 * @param array  $settings The settings to use.
	 *
	 * @return array
	 */
	public function get_settings_for_field( string $field, array $settings = array() ): array {
		foreach ( ( empty( $settings ) ? $this->get_settings() : $settings ) as $section_settings ) {
			foreach ( $section_settings['fields'] as $field_name => $field_settings ) {
				if ( $field === $field_name ) {
					return $field_settings;
				}
			}
		}
		return array();
	}
}
