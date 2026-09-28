<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ciscar_GDrive_Client {

	private $credentials;
	private $access_token = null;

	public function __construct( $credentials_json = null ) {
		if ( empty( $credentials_json ) ) {
			$credentials_json = get_option( 'ciscar_gdrive_service_account_json', '' );
		}
		if ( ! empty( $credentials_json ) ) {
			$this->credentials = is_array( $credentials_json ) ? $credentials_json : json_decode( $credentials_json, true );
		}
	}

	public function is_configured() {
		return ! empty( $this->credentials ) && ! empty( $this->credentials['client_email'] ) && ! empty( $this->credentials['private_key'] );
	}

	public function get_client_email() {
		return isset( $this->credentials['client_email'] ) ? $this->credentials['client_email'] : '';
	}

	/**
	 * Obtener Access Token mediante Service Account JWT (RS256)
	 */
	public function get_access_token() {
		if ( $this->access_token ) {
			return $this->access_token;
		}

		$transient_token = get_transient( 'ciscar_gdrive_access_token' );
		if ( $transient_token ) {
			$this->access_token = $transient_token;
			return $this->access_token;
		}

		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', 'Credenciales de Google Service Account no configuradas.' );
		}

		$now    = time();
		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT',
		);
		$claim  = array(
			'iss'   => $this->credentials['client_email'],
			'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive',
			'aud'   => 'https://oauth2.googleapis.com/token',
			'exp'   => $now + 3600,
			'iat'   => $now,
		);

		$base64_header = $this->base64url_encode( json_encode( $header ) );
		$base64_claim  = $this->base64url_encode( json_encode( $claim ) );
		$data_to_sign  = $base64_header . '.' . $base64_claim;

		$private_key = $this->credentials['private_key'];
		$signature   = '';
		$success     = openssl_sign( $data_to_sign, $signature, $private_key, 'SHA256' );

		if ( ! $success ) {
			return new WP_Error( 'openssl_error', 'Error al firmar la solicitud JWT con OpenSSL.' );
		}

		$jwt = $data_to_sign . '.' . $this->base64url_encode( $signature );

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			$err = isset( $body['error_description'] ) ? $body['error_description'] : ( isset( $body['error'] ) ? $body['error'] : 'Error al obtener token.' );
			return new WP_Error( 'token_error', 'Google Auth Error: ' . $err );
		}

		$this->access_token = $body['access_token'];
		$expires_in         = isset( $body['expires_in'] ) ? intval( $body['expires_in'] ) - 60 : 3500;
		set_transient( 'ciscar_gdrive_access_token', $this->access_token, $expires_in );

		return $this->access_token;
	}

	/**
	 * Buscar o crear carpeta en Google Drive
	 */
	public function get_or_create_folder( $folder_name, $parent_folder_id = '' ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$folder_name_escaped = str_replace( "'", "\\'", $folder_name );
		$q = "mimeType = 'application/vnd.google-apps.folder' and name = '{$folder_name_escaped}' and trashed = false";
		if ( ! empty( $parent_folder_id ) ) {
			$q .= " and '{$parent_folder_id}' in parents";
		}

		$search_url = add_query_arg(
			array(
				'q'                           => $q,
				'fields'                      => 'files(id, name)',
				'supportsAllDrives'           => 'true',
				'includeItemsFromAllDrives'   => 'true',
			),
			'https://www.googleapis.com/drive/v3/files'
		);

		$search_res = wp_remote_get(
			$search_url,
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);

		if ( ! is_wp_error( $search_res ) ) {
			$body = json_decode( wp_remote_retrieve_body( $search_res ), true );
			if ( ! empty( $body['files'][0]['id'] ) ) {
				return $body['files'][0]['id'];
			}
		}

		// Crear carpeta si no existe
		$meta = array(
			'name'     => $folder_name,
			'mimeType' => 'application/vnd.google-apps.folder',
		);
		if ( ! empty( $parent_folder_id ) ) {
			$meta['parents'] = array( $parent_folder_id );
		}

		$create_url = add_query_arg(
			array( 'supportsAllDrives' => 'true' ),
			'https://www.googleapis.com/drive/v3/files'
		);

		$create_res = wp_remote_post(
			$create_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $meta ),
			)
		);

		if ( is_wp_error( $create_res ) ) {
			return $create_res;
		}

		$create_body = json_decode( wp_remote_retrieve_body( $create_res ), true );
		if ( ! empty( $create_body['id'] ) ) {
			return $create_body['id'];
		}

		$api_err = isset( $create_body['error']['message'] ) ? $create_body['error']['message'] : ( isset( $create_body['error'] ) ? json_encode( $create_body['error'] ) : 'Sin respuesta de ID' );
		return new WP_Error( 'folder_create_failed', 'Google Drive API Error: ' . $api_err );
	}

	/**
	 * Subir archivo PDF a Google Drive
	 */
	public function upload_file( $file_path, $file_name, $parent_folder_id = '' ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', 'El archivo local no existe: ' . $file_path );
		}

		$file_content = file_get_contents( $file_path );
		$boundary     = '-------CiscarGDriveBoundary' . md5( time() );

		$meta = array(
			'name'     => $file_name,
			'mimeType' => 'application/pdf',
		);
		if ( ! empty( $parent_folder_id ) ) {
			$meta['parents'] = array( $parent_folder_id );
		}

		$body  = "--{$boundary}\r\n";
		$body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
		$body .= json_encode( $meta ) . "\r\n";
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Type: application/pdf\r\n\r\n";
		$body .= $file_content . "\r\n";
		$body .= "--{$boundary}--";

		$upload_url = add_query_arg(
			array(
				'uploadType'        => 'multipart',
				'fields'            => 'id, name, webViewLink',
				'supportsAllDrives' => 'true',
			),
			'https://www.googleapis.com/upload/drive/v3/files'
		);

		$upload_res = wp_remote_post(
			$upload_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'multipart/related; boundary=' . $boundary,
				),
				'body'    => $body,
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $upload_res ) ) {
			return $upload_res;
		}

		$res_body = json_decode( wp_remote_retrieve_body( $upload_res ), true );
		if ( ! empty( $res_body['id'] ) ) {
			return array(
				'id'             => $res_body['id'],
				'name'           => isset( $res_body['name'] ) ? $res_body['name'] : $file_name,
				'web_view_link'  => isset( $res_body['webViewLink'] ) ? $res_body['webViewLink'] : 'https://drive.google.com/file/d/' . $res_body['id'] . '/view',
			);
		}

		$err = isset( $res_body['error']['message'] ) ? $res_body['error']['message'] : 'Error desconocido en carga.';
		return new WP_Error( 'upload_failed', 'Google Drive Upload Error: ' . $err );
	}

	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}
}
