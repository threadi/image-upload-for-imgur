<?php
/**
 * File for handling table of logs in this plugin.
 *
 * @package image-upload-for-imgur
 */

namespace ImageUploadImgur\Logging\Tables;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ImageUploadImgur\Logging\Log;
use ImageUploadImgur\Plugin\Helper;
use WP_List_Table;
use WP_User;

/**
 * Handler for log-output in backend.
 */
class Files extends WP_List_Table {
	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array<string,string>
	 */
	public function get_columns(): array {
		return array(
			'date'              => __( 'Date', 'image-upload-for-imgur' ),
			'filename_original' => __( 'Filename original', 'image-upload-for-imgur' ),
			'imgur_url'         => __( 'URL', 'image-upload-for-imgur' ),
			'post_id'           => __( 'Uploaded in', 'image-upload-for-imgur' ),
			'user_id'           => __( 'Uploaded by', 'image-upload-for-imgur' ),
		);
	}

	/**
	 * Get the table data
	 *
	 * @return array<string,mixed>
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

		// get results and return them.
		if ( 'asc' === $order ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `time` AS `date`, `filename_original`, `imgur_url`, `post_id`, `user_id`
            			FROM `' . $wpdb->prefix . 'iufi_files`
                        WHERE 1 = %d ' . $where . '
                        ORDER BY ' . (string) esc_sql( (string) $order_by ) . ' ASC',
					$vars
				),
				ARRAY_A
			);
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `time` AS `date`, `filename_original`, `imgur_url`, `post_id`, `user_id`
            			FROM `' . $wpdb->prefix . 'iufi_files`
                        WHERE 1 = %d ' . $where . '
                        ORDER BY ' . (string) esc_sql( (string) $order_by ) . ' DESC',
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
	 * @return array<string,string>
	 */
	public function get_hidden_columns(): array {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array<string,mixed>
	 */
	public function get_sortable_columns(): array {
		return array( 'date' => array( 'date', false ) );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array<string,mixed> $item        Data.
	 * @param  String              $column_name - Current column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return match ( $column_name ) {
			'date' => Helper::get_format_date_time( $item[ $column_name ] ),
			'filename_original' => $item[ $column_name ],
			'imgur_url' => $this->show_link( $item[ $column_name ] ),
			'post_id' => $this->show_post( absint( $item[ $column_name ] ) ),
			'user_id' => $this->show_user( $item[ $column_name ] ),
			default => '',
		};
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since 1.0.0
	 */
	public function no_items(): void {
		// show default text.
		echo esc_html__( 'No files found.', 'image-upload-for-imgur' );
	}

	/**
	 * Show given string as link.
	 *
	 * @param string $column_name The given string.
	 *
	 * @return string
	 */
	private function show_link( string $column_name ): string {
		// bail if no link is given.
		if ( empty( $column_name ) ) {
			return '';
		}

		// show link.
		return '<a href="' . esc_url( $column_name ) . '" target="_blank">' . esc_url( $column_name ) . '</a>';
	}

	/**
	 * Show linked name of given post.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	private function show_post( int $post_id ): string {
		// bail if no id is given.
		if ( 0 === $post_id ) {
			return '<i>' . __( 'Unknown', 'image-upload-for-imgur' ) . '</i>';
		}

		// get the URL.
		$url = get_permalink( $post_id );
		if ( ! $url ) {
			return '';
		}

		// return the linked post title.
		return '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( get_post_field( 'post_title', $post_id ) ) . '</a>';
	}

	/**
	 * Show linked name of given user.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return string
	 */
	private function show_user( int $user_id ): string {
		// bail if no id is given.
		if ( 0 === $user_id ) {
			return '<i>' . __( 'Unknown', 'image-upload-for-imgur' ) . '</i>';
		}

		// get the user.
		$user      = get_user_by( 'ID', $user_id );
		$user_name = '';
		if ( $user instanceof WP_User ) {
			$user_name = $user->display_name;
		}

		// return the linked username.
		return '<a href="' . esc_url( get_edit_user_link( $user_id ) ) . '" target="_blank">' . esc_html( $user_name ) . '</a>';
	}
}
