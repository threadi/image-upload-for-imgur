<?php
/**
 * File for handling table of logs in this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImgurImageUpload\Logging\Tables;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImgurImageUpload\Logging\Log;
use ImgurImageUpload\Plugin\Helper;
use WP_List_Table;

/**
 * Handler for log-output in backend.
 */
class Logs extends WP_List_Table {
	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'state'    => __( 'State', 'image-upload-for-imgur' ),
			'date'     => __( 'Date', 'image-upload-for-imgur' ),
			'log'      => __( 'Log', 'image-upload-for-imgur' ),
			'category' => __( 'Category', 'image-upload-for-imgur' ),
		);
	}

	/**
	 * Get the table data
	 *
	 * @return array
	 */
	private function table_data(): array {
		global $wpdb;

		// order table.
		$order_by = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $order_by ) ) {
			$order_by = 'date';
		}
		$order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! is_null( $order ) ) {
			$order = sanitize_sql_orderby( $order );
		} else {
			$order = 'ASC';
		}

		// collect vars for statement.
		$vars = array( 1 );

		// collect restrictions.
		$where = '';

		// get filter.
		$category = $this->get_category_filter();
		if ( ! empty( $category ) ) {
			$where .= ' AND `category` = "%s"';
			$vars[] = $category;
		}

		// get md5.
		$md5 = $this->get_md5_filter();
		if ( ! empty( $md5 ) ) {
			$where .= ' AND `md5` = "%s"';
			$vars[] = $md5;
		}

		// get results and return them.
		if ( 'asc' === $order ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `state`, `time` AS `date`, `log`, `category`
            			FROM `' . $wpdb->prefix . 'imgur_image_upload_logs`
                        WHERE 1 = %d ' . $where . '
                        ORDER BY ' . esc_sql( $order_by ) . ' ASC',
					$vars
				),
				ARRAY_A
			);
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `state`, `time` AS `date`, `log`, `category`
            			FROM `' . $wpdb->prefix . 'imgur_image_upload_logs`
                        WHERE 1 = %d ' . $where . '
                        ORDER BY ' . esc_sql( $order_by ) . ' DESC',
				$vars
			),
			ARRAY_A
		);
	}

	/**
	 * Get the log-table for the table-view.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();

		$per_page     = 100;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return array
	 */
	public function get_hidden_columns(): array {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array( 'date' => array( 'date', false ) );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array  $item        Data.
	 * @param  String $column_name - Current column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return match ( $column_name ) {
			'date' => Helper::get_format_date_time( $item[ $column_name ] ),
			'state' => $this->get_status_icon( $item[ $column_name ] ),
			'log' => nl2br( $item[ $column_name ] ),
			'category' => empty( $item[ $column_name ] ) ? '<i>' . esc_html__( 'not defined', 'image-upload-for-imgur' ) . '</i>' : $this->get_category( $item[ $column_name ] ),
			default => '',
		};
	}

	/**
	 * Get a single category.
	 *
	 * @param string $category The searched category.
	 *
	 * @return string
	 */
	private function get_category( string $category ): string {
		// get list of categories.
		$categories = Log::get_instance()->get_categories();

		// bail if search category is not found.
		if ( empty( $categories[ $category ] ) ) {
			return '<i>' . esc_html__( 'Unknown', 'image-upload-for-imgur' ) . '</i>';
		}

		// return the category-label.
		return $categories[ $category ];
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since 1.0.0
	 */
	public function no_items(): void {
		// get actual filter.
		$category = $this->get_category_filter();

		// if filter is set show other text.
		if ( ! empty( $category ) ) {
			// get all categories to get the title.
			$categories = Log::get_instance()->get_categories();

			// show text.
			/* translators: %1$s will be replaced by the category name. */
			printf( esc_html__( 'No log entries for %1$s found.', 'image-upload-for-imgur' ), esc_html( $categories[ $category ] ) );
			return;
		}

		// show default text.
		echo esc_html__( 'No log entries found.', 'image-upload-for-imgur' );
	}

	/**
	 * Define filter for categories.
	 *
	 * @return array
	 */
	protected function get_views(): array {
		// get main url without filter.
		$url = remove_query_arg( array( 'category', 'md5' ) );

		// get actual filter.
		$category = $this->get_category_filter();

		// define initial list.
		$list = array(
			'all' => '<a href="' . esc_url( $url ) . '"' . ( empty( $category ) ? ' class="current"' : '' ) . '>' . esc_html__( 'All', 'image-upload-for-imgur' ) . '</a>',
		);

		// get all log categories.
		$log_obj = Log::get_instance();
		foreach ( $log_obj->get_categories() as $key => $label ) {
			$url          = add_query_arg( array( 'category' => $key ) );
			$list[ $key ] = '<a href="' . esc_url( $url ) . '"' . ( $category === $key ? ' class="current"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}

		/**
		 * Filter the list before output.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array $list List of filter.
		 */
		return apply_filters( 'image_upload_for_imgur_table_filter', $list );
	}

	/**
	 * Get actual category-filter-value.
	 *
	 * @return string
	 */
	private function get_category_filter(): string {
		$category = filter_input( INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $category ) ) {
			return '';
		}
		return $category;
	}

	/**
	 * Get actual category-filter-value.
	 *
	 * @return string
	 */
	private function get_md5_filter(): string {
		$md5 = filter_input( INPUT_GET, 'md5', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $md5 ) ) {
			return '';
		}
		return $md5;
	}

	/**
	 * Return HTML-code for icon of the given status.
	 *
	 * @param string $status The requested status.
	 *
	 * @return string
	 */
	private function get_status_icon( string $status ): string {
		$list = array(
			'success' => '<span class="dashicons dashicons-yes"></span>',
			'error'   => '<span class="dashicons dashicons-no"></span>',
		);

		// bail if status is unknown.
		if ( empty( $list[ $status ] ) ) {
			return '';
		}

		// return the HTML-code for the icon of this status.
		return $list[ $status ];
	}

	/**
	 * Add export- and delete-buttons on top of table.
	 *
	 * @param string $which The position.
	 * @return void
	 */
	public function extra_tablenav( $which ): void {
		if ( 'top' === $which ) {
			// define empty-URL.
			$empty_url = add_query_arg(
				array(
					'action' => 'image_upload_for_imgur_log_empty',
					'nonce'  => wp_create_nonce( 'image-upload-for-imgur-log-empty' ),
				),
				get_admin_url() . 'admin.php'
			);

			?>
			<a href="<?php echo esc_url( $empty_url ); ?>" class="button button-secondary"><?php echo esc_html__( 'Empty the log', 'image-upload-for-imgur' ); ?></a>
			<?php
		}
	}
}
