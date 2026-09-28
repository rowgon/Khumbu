<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ciscar_GDrive_Logger {

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ciscar_gdrive_logs';
	}

	public static function create_table() {
		global $wpdb;
		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			report_title varchar(255) NOT NULL DEFAULT '',
			site_name varchar(255) NOT NULL DEFAULT '',
			file_name varchar(255) NOT NULL DEFAULT '',
			gdrive_file_id varchar(128) NOT NULL DEFAULT '',
			gdrive_file_url text NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			error_message text NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY site_name (site_name),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function log( $data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$wpdb->insert(
			$table_name,
			array(
				'report_title'   => isset( $data['report_title'] ) ? sanitize_text_field( $data['report_title'] ) : '',
				'site_name'      => isset( $data['site_name'] ) ? sanitize_text_field( $data['site_name'] ) : '',
				'file_name'      => isset( $data['file_name'] ) ? sanitize_text_field( $data['file_name'] ) : '',
				'gdrive_file_id' => isset( $data['gdrive_file_id'] ) ? sanitize_text_field( $data['gdrive_file_id'] ) : '',
				'gdrive_file_url'=> isset( $data['gdrive_file_url'] ) ? esc_url_raw( $data['gdrive_file_url'] ) : '',
				'status'         => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'success',
				'error_message'  => isset( $data['error_message'] ) ? sanitize_textarea_field( $data['error_message'] ) : '',
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function get_logs( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table_name = self::get_table_name();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_name} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset )
		);
	}

	public static function get_total_count() {
		global $wpdb;
		$table_name = self::get_table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}
}
