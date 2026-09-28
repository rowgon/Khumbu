<?php

class MainWP_Pro_Reports_Key {

	private static $instance = null;

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// constructor.
	}

	/**
	 * Method get_key
	 */
	public function get_key() {
		$kd = get_option( 'mainwp_pro_reports_graph_kdata' );
		$this->key_check( $kd );
		if ( ! empty( $kd ) && is_array( $kd ) && ! empty( $kd['kvalue']['encrypted_val'] ) ) {
			$decr = $this->decrypt_keys( $kd['kvalue'] );
			if ( is_array( $decr ) && isset( $decr['kgraph'] ) ) {
				return $decr['kgraph'];
			}
		}
		return '';
	}

	/**
	 * Method key_check
	 */
	public function key_check( &$kd ) {
		$ki            = '4Ft7q_pXPepadls6Vw4me97Zvjpn/dsepkdtifp6C656Tco7SksQ==';
		list($kp, $ks) = explode( '_', $ki );
		if ( ! is_array( $kd ) || empty( $kd['kvalue']['encrypted_val'] ) || empty( $kd['kpass'] ) || $kp !== $kd['kpass'] ) {
			$kv = $this->decrypt( $ks, $kp );
			if ( ! empty( $kv ) ) {

				$saved = get_option( 'mainwp_pro_reports_graph_kdata' );
				$en_kf = false;
				if ( is_array( $saved ) && ! empty( $saved['kvalue']['file_key'] ) ) {
					$en_kf = $saved['kvalue']['file_key'];
				}

				$v = $this->encrypt_keys( array( 'kgraph' => $kv ), false, $en_kf );

				if ( is_array( $v ) && isset( $v['encrypted_val'] ) ) {
					$kd = array(
						'kvalue' => $v,
						'kpass'  => $kd['kpass'],
					);
					update_option( 'mainwp_pro_reports_graph_kdata', $kd );
				}
			}
		}
	}

	/**
	 * Method decrypt_api_keys().
	 *
	 * Decrypt the encrypted data.
	 */
	public function decrypt_keys( $encrypted_data, $def_val = false ) {
		if ( ! empty( $encrypted_data ) && is_array( $encrypted_data ) && ! empty( $encrypted_data['encrypted_val'] ) ) { // old format.
			$result = apply_filters( 'mainwp_decrypt_key_value', false, $encrypted_data, $def_val );
			if ( ! empty( $result ) && is_string( $result ) ) {
				$result = json_decode( $result, true );
				if ( is_array( $result ) ) {
					return $result;
				}
			}
		}
		return $def_val;
	}

	/**
	 * Method encrypt_keys
	 *
	 * Encrypt data.
	 */
	public function encrypt_keys( $data, $siteid = false, $file_key = false, $def_val = '' ) {
		if ( ! empty( $data ) && is_array( $data ) ) {
			$prefix = 'pro_reports_graph_';
			if ( ! empty( $siteid ) ) {
				$prefix = $prefix . intval( $siteid ) . '_';
			}
			$data   = wp_json_encode( $data ); // must in string format.
			$result = apply_filters( 'mainwp_encrypt_key_value', false, $data, $prefix, $file_key );
			if ( is_array( $result ) && ! empty( $result['encrypted_val'] ) ) {
				return $result;
			}
		}
		return $def_val;
	}

	/**
	 * Decrypt.
	 *
	 * @param string $str String to Decrypt.
	 * @param string $pass String.
	 *
	 * @return string Decrypted string.
	 */
	public static function decrypt( $str, $pass ) {
		if ( ! is_string( $str ) ) {
			return '';
		}
		$str  = base64_decode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode used for http encoding compatible.
		$pass = str_split( str_pad( '', strlen( $str ), $pass, STR_PAD_RIGHT ) );
		$stra = str_split( $str );
		foreach ( $stra as $k => $v ) {
			$tmp        = ord( $v ) - ord( $pass[ $k ] );
			$stra[ $k ] = chr( 0 > $tmp ? ( $tmp + 256 ) : $tmp );
		}

		return join( '', $stra );
	}
}
