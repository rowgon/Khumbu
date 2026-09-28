<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ciscar_GDrive_Hooks {

	public static function init() {
		add_filter( 'mainwp_pro_reports_email_attachments', array( __CLASS__, 'handle_report_attachments' ), 10, 4 );
		add_filter( 'mainwp_pro_reports_send_mail_data', array( __CLASS__, 'handle_send_mail_data' ), 10, 1 );
		add_filter( 'wp_mail', array( __CLASS__, 'handle_wp_mail' ), 10, 1 );
	}

	/**
	 * Hook 0: Captura universal a nivel de wp_mail para todos los correos con adjuntos PDF
	 */
	public static function handle_wp_mail( $args ) {
		if ( empty( $args ) || empty( $args['attachments'] ) ) {
			return $args;
		}

		$enabled = get_option( 'ciscar_gdrive_sync_enabled', 'yes' );
		if ( 'yes' !== $enabled ) {
			return $args;
		}

		$subject = ! empty( $args['subject'] ) ? $args['subject'] : 'Informe Ejecutivo';
		
		// Deducir el sitio web a partir del asunto (ej. "Informe... - agenciaciscar.com (01/07/2026...)")
		$site_name = 'General';
		if ( preg_match( '/-\s*([a-zA-Z0-9\.\-]+)\s*\(/i', $subject, $matches ) ) {
			$site_name = trim( $matches[1] );
		} elseif ( preg_match( '/-\s*([a-zA-Z0-9\.\-]+)$/i', $subject, $matches ) ) {
			$site_name = trim( $matches[1] );
		}

		$attachments = is_array( $args['attachments'] ) ? $args['attachments'] : array( $args['attachments'] );
		foreach ( $attachments as $pdf_path ) {
			if ( file_exists( $pdf_path ) && 'pdf' === strtolower( pathinfo( $pdf_path, PATHINFO_EXTENSION ) ) ) {
				self::process_pdf_upload( $pdf_path, $subject, $site_name );
			}
		}

		return $args;
	}

	/**
	 * Hook 1: Intercepta adjuntos PDF generados por MainWP Pro Reports
	 */
	public static function handle_report_attachments( $attachments, $html_to_pdf, $report, $site_id ) {
		if ( empty( $attachments ) || ! is_array( $attachments ) ) {
			return $attachments;
		}

		$enabled = get_option( 'ciscar_gdrive_sync_enabled', 'yes' );
		if ( 'yes' !== $enabled ) {
			return $attachments;
		}

		$site_name = self::get_site_name( $site_id );
		$report_title = is_object( $report ) ? ( isset( $report->title ) ? $report->title : $report->heading ) : 'Informe';

		foreach ( $attachments as $pdf_path ) {
			if ( file_exists( $pdf_path ) && 'pdf' === strtolower( pathinfo( $pdf_path, PATHINFO_EXTENSION ) ) ) {
				self::process_pdf_upload( $pdf_path, $report_title, $site_name );
			}
		}

		return $attachments;
	}

	/**
	 * Hook 2: Intercepta el payload de correo de MainWP Pro Reports si contiene adjuntos
	 */
	public static function handle_send_mail_data( $data ) {
		if ( empty( $data ) || empty( $data['attachments'] ) ) {
			return $data;
		}

		$enabled = get_option( 'ciscar_gdrive_sync_enabled', 'yes' );
		if ( 'yes' !== $enabled ) {
			return $data;
		}

		$report_title = isset( $data['subject'] ) ? $data['subject'] : 'Informe MainWP';
		$site_name    = isset( $data['site_name'] ) ? $data['site_name'] : ( isset( $data['site_id'] ) ? self::get_site_name( $data['site_id'] ) : 'General' );

		$attachments = is_array( $data['attachments'] ) ? $data['attachments'] : array( $data['attachments'] );
		foreach ( $attachments as $pdf_path ) {
			if ( file_exists( $pdf_path ) && 'pdf' === strtolower( pathinfo( $pdf_path, PATHINFO_EXTENSION ) ) ) {
				self::process_pdf_upload( $pdf_path, $report_title, $site_name );
			}
		}

		return $data;
	}

	/**
	 * Procesar la subida del PDF a Google Drive
	 */
	public static function process_pdf_upload( $pdf_path, $report_title, $site_name ) {
		// Evitar subidas duplicadas del mismo archivo en los últimos 2 minutos
		$file_md5 = md5_file( $pdf_path );
		$cache_key = 'ciscar_gdrive_up_' . $file_md5;
		if ( get_transient( $cache_key ) ) {
			return;
		}
		set_transient( $cache_key, true, 120 );

		$client = new Ciscar_GDrive_Client();
		if ( ! $client->is_configured() ) {
			Ciscar_GDrive_Logger::log( array(
				'report_title'  => $report_title,
				'site_name'     => $site_name,
				'file_name'     => basename( $pdf_path ),
				'status'        => 'error',
				'error_message' => 'Credenciales de Google Service Account no configuradas en el panel.',
			) );
			return;
		}

		$root_folder_id = get_option( 'ciscar_gdrive_root_folder_id', '' );
		$target_folder_id = $root_folder_id;

		// Si se activa organizar por subcarpetas de sitio
		$organize_by_site = get_option( 'ciscar_gdrive_organize_by_site', 'yes' );
		if ( 'yes' === $organize_by_site && ! empty( $site_name ) ) {
			$folder_res = $client->get_or_create_folder( $site_name, $root_folder_id );
			if ( ! is_wp_error( $folder_res ) ) {
				$target_folder_id = $folder_res;

				// Subcarpeta por año
				$year_folder = date( 'Y' );
				$year_res = $client->get_or_create_folder( $year_folder, $target_folder_id );
				if ( ! is_wp_error( $year_res ) ) {
					$target_folder_id = $year_res;
				}
			}
		}

		$file_name = basename( $pdf_path );
		$upload_res = $client->upload_file( $pdf_path, $file_name, $target_folder_id );

		if ( is_wp_error( $upload_res ) ) {
			Ciscar_GDrive_Logger::log( array(
				'report_title'  => $report_title,
				'site_name'     => $site_name,
				'file_name'     => $file_name,
				'status'        => 'error',
				'error_message' => $upload_res->get_error_message(),
			) );
		} else {
			Ciscar_GDrive_Logger::log( array(
				'report_title'   => $report_title,
				'site_name'      => $site_name,
				'file_name'      => $file_name,
				'gdrive_file_id' => $upload_res['id'],
				'gdrive_file_url'=> $upload_res['web_view_link'],
				'status'         => 'success',
			) );
		}
	}

	private static function get_site_name( $site_id ) {
		if ( empty( $site_id ) ) {
			return 'Sitio General';
		}
		global $wpdb;
		$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}mainwp_wp WHERE id = %d", $site_id ) );
		return ! empty( $name ) ? $name : 'Sitio #' . $site_id;
	}
}
