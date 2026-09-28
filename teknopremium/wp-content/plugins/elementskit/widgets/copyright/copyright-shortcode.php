<?php
namespace ElementsKit\Widgets\Copyright;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Helper class for the Copyright shortcodes.
 *
 * @since 4.2.1
 */
class Copyright_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'ekit_current_year', [ $this, 'display_current_year' ] );
		add_shortcode( 'ekit_site_title', [ $this, 'display_site_title' ] );
	}

	/**
	 * Get the current year.
	 *
	 * @return string Current year.
	 */
	public function display_current_year() {
		$current_year = gmdate( 'Y' );
		$current_year = do_shortcode( shortcode_unautop( $current_year ) );
		
		if ( ! empty( $current_year ) ) {
			return $current_year;
		}
	}

	/**
	 * Get site title.
	 *
	 * @return string Site title.
	 */
	public function display_site_title() {
		$site_title = get_bloginfo( 'name' );

		if ( ! empty( $site_title ) ) {
			return $site_title;
		}
	}
}
