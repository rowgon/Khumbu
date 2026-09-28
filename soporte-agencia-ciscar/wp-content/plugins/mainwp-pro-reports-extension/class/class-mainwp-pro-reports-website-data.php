<?php

class MainWP_Pro_Reports_Website_Data {

	private static $buffer = array();

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// construct.
	}

	/**
	 * Virusdie data.
	 *
	 * @param int    $site_id Child site ID.
	 * @param string $start_date Report start date.
	 * @param string $end_date Report end date.
	 * @param array  $sections Sections.
	 * @param array  $other_tokens Other tokens.
	 * @param string $data_path Data to get.
	 *
	 * @return array|false|mixed Return Virusdie data or FALSE on failure.
	 */
	public function get_website_reports_tokens_data( $site_id, $start_date, $end_date, $sections, $other_tokens, $data_path ) {

		if ( empty( $site_id ) ) {
			return array();
		}

		// Dashboard hook, cached.
		$group_data = apply_filters( 'mainwp_get_reports_group_values_website', array(), array(), array(), $site_id ); // get all.

		if ( empty( $group_data ) || ! is_array( $group_data ) ) {
			$group_data = array();
		}

		$array_tmp = explode( '.', $data_path );

		list( $group, $type ) = $array_tmp;
		$records              = array();

		if ( isset( $group_data[ $group ] ) && is_array( $group_data[ $group ] ) && isset( $group_data[ $group ][ $type ] ) ) {
			$records = $group_data[ $group ][ $type ];
		}

		if ( ! is_array( $records ) ) {
			$records = array();
		}

		$other_tokens_data = $this->get_website_other_tokens_data( $records, $other_tokens );
		$sections_data     = $this->get_website_sections_data( $records, $sections );

		$information = array(
			'other_tokens_data' => $other_tokens_data,
			'sections_data'     => $sections_data,
		);

		return $information;
	}

	/**
	 * Get the other tokens data.
	 *
	 * @param array $records An array containg actions records.
	 * @param array $tokens  An array containg the tokens list.
	 *
	 * @return array An array containg the tokens values.
	 */
	public function get_website_other_tokens_data( $records, $tokens ) {

		$token_values = array();

		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		foreach ( $tokens as $token ) {

			if ( isset( $token_values[ $token ] ) ) {
				continue;
			}

			$str_tmp   = str_replace( array( '[', ']' ), '', $token );
			$array_tmp = explode( '.', $str_tmp );
			if ( is_array( $array_tmp ) ) {
				$context = '';
				$action  = '';
				$data    = '';
				if ( 2 === count( $array_tmp ) ) {
					list( $context, $data ) = $array_tmp;
				} elseif ( 3 === count( $array_tmp ) ) {
					list( $context, $action, $data ) = $array_tmp;
				}

				switch ( $data ) {
					case 'count':
						$token_values[ $token ] = count( $records );
						break;
				}
			}
		}

		return $token_values;
	}

	/**
	 * Get the website sections data.
	 *
	 * @param array $records  An array containg actions records.
	 * @param array $sections An array containing sections.
	 *
	 * @return array Sections data.
	 */
	public function get_website_sections_data( $records, $sections ) {
		$sections_data = array();
		if ( isset( $sections['section_token'] ) && is_array( $sections['section_token'] ) && ! empty( $sections['section_token'] ) ) {
			foreach ( $sections['section_token'] as $index => $sec ) {
				$tokens                  = $sections['section_content_tokens'][ $index ];
				$sections_data[ $index ] = $this->get_website_section_loop_data( $records, $tokens, $sec );
			}
		}
		return $sections_data;
	}



	/**
	 * Get the website section loop data.
	 *
	 * @param object $records Object containng reports records.
	 * @param array  $tokens  An array containing report tokens.
	 * @param string $section Section name.
	 *
	 * @return array Section loop records.
	 */
	public function get_website_section_loop_data( $records, $tokens, $section ) {

		$context = '';
		$action  = '';

		$str_tmp   = str_replace( array( '[', ']' ), '', $section );
		$array_tmp = explode( '.', $str_tmp );
		if ( is_array( $array_tmp ) ) {
			if ( 3 === count( $array_tmp ) ) {
				list( $str1, $context, $action ) = $array_tmp;
			}
		}

		return $this->get_section_loop_records( $records, $tokens );
	}


	/**
	 * Get the section loop records.
	 *
	 * @param object $records Object containng reports records.
	 * @param array  $tokens  An array containing report tokens.
	 *
	 * @return array Loops.
	 */
	public function get_section_loop_records( $records, $tokens ) {  // phpcs:ignore -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.
		$loops      = array();
		$loop_count = 0;
		foreach ( $records as $record ) {
			$token_values = $this->get_section_loop_token_values( $record, $tokens );
			if ( ! empty( $token_values ) ) {
				$loops[ $loop_count ] = $token_values;
				$loop_count ++;
			}
		}
		return $loops;
	}



	/**
	 * Get the section loop token values.
	 *
	 * @param object $record Object containing the record data.
	 * @param array  $tokens An array containg the report tokens.
	 *
	 * @return array Token values.
	 *
	 * @uses \MainWP\Child\MainWP_Helper::log_debug()
	 */
	private function get_section_loop_token_values( $record, $tokens ) {

		$token_values = array();
		foreach ( $tokens as $token ) {
			$data       = '';
			$token_name = str_replace( array( '[', ']' ), '', $token );
			$array_tmp  = explode( '.', $token_name );

			if ( 1 === count( $array_tmp ) ) {
				list( $data ) = $array_tmp;
			} elseif ( 2 === count( $array_tmp ) ) {
				list( $str1, $data ) = $array_tmp;
			} elseif ( 3 === count( $array_tmp ) ) {
				list( $str1, $str2, $data ) = $array_tmp;
			}

			if ( 'version' === $data ) {
				if ( 'wordpress' === $str1 ) { // phpcs:ignore -- wordpress -> WordPress.
					if ( 'new' === $str2 ) {
						$data = 'new_wp_version';
					} elseif ( 'current' === $str2 ) {
						$data = 'current_wp_version';
					}
				} elseif ( 'new' === $str2 ) {
					$data = 'new_version';
                } elseif ( 'current' === $str2 && 'wordpress' === $str1 ) { // phpcs:ignore -- wordpress -> WordPress.
					$data = 'new_version';
				}
			}

			if ( 'role' === $data ) {
				$data = 'roles';
			}

			$tok_value = $this->get_section_token_value( $record, $data );

			$token_values[ $token ] = $tok_value;
		}
		return $token_values;
	}

	/**
	 * Get the section token value.
	 *
	 * @param object $record  Object containing the record data.
	 * @param string $data    Data to process.
	 *
	 * @return array Token value.
	 */
	public function get_section_token_value( $record, $data ) {
		$tok_value = 'N/A';

		if ( ! is_array( $record ) ) {
			return $tok_value;
		}

		$tok_value = '';
		switch ( $data ) {
			case 'name':
				$tok_value = isset( $record['Name'] ) ? esc_html( $record['Name'] ) : $tok_value;
				break;
			case 'version':
				$tok_value = isset( $record['Version'] ) ? esc_html( $record['Version'] ) : $tok_value;
				break;
			case 'new_version':
				$tok_value = isset( $record['update'] ) && is_array( $record['update'] ) && isset( $record['update']['new_version'] ) ? esc_html( $record['update']['new_version'] ) : $tok_value;
				break;
			case 'current_wp_version':
				$tok_value = isset( $record['current'] ) ? esc_html( $record['current'] ) : '';
				break;
			case 'new_wp_version':
				$tok_value = isset( $record['new'] ) ? esc_html( $record['new'] ) : '';
				break;
			case 'lastupdated':
				$tok_value = MainWP_Pro_Reports_Utility::format_datestamp( $record['last_updated'], true );
				break;
			default:
				break;
		}
		return $tok_value;
	}

}
