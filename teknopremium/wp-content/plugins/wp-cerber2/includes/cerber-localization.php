<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licenced under the GNU GPL.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

const CRB_TRANSLATION_PATH = 'translations/wp-cerber/strata/' . CERBER_VER;

/**
 * Constructs a translation download URL for a specific WordPress locale from the official WP Cerber repository.
 *
 * @param string $wp_locale The WordPress locale identifier (e.g., 'es_ES', 'uk', or 'de_CH_informal').
 *
 * @return string The absolute HTTPS URL to a ZIP archive containing translation for the given locale.
 *
 * @since 9.6.11
 */
function crb_get_locale_url( string $wp_locale ): string {
	$clean_locale = preg_replace( '/[^a-zA-Z0-9_]/', '', $wp_locale );

	return 'https://downloads.wpcerber.com/' . CRB_TRANSLATION_PATH . '/' . $clean_locale . '.zip';
}

/**
 * Determines and loads translation files based on the plugin and user settings.
 *
 * @return void
 *
 * @since 9.6.6.15
 */
function crb_load_localization() {

	if ( nexus_is_valid_request() ) {

		$locale = crb_get_admin_locale();
		$mo_file = WP_LANG_DIR . '/plugins/wp-cerber-' . $locale . '.mo';

		load_textdomain( 'wp-cerber', $mo_file, $locale );

		return;
	}

	if ( is_admin() ) {

		$locale = crb_get_admin_locale();

		if ( $locale == 'en_US' ) {

			// Do not load translations, use untranslated English phrases from the plugin code

			add_filter( 'override_load_textdomain', function ( $val, $domain, $mofile ) {
				return ( $domain == 'wp-cerber' ) ? true : $val;
			}, 100, 3 );
		}
		else {

			// Set a proper translation file according to the plugin settings

			add_filter( 'load_textdomain_mofile', function ( $mofile, $domain ) {

				if ( $domain != 'wp-cerber' ) {
					return $mofile;
				}

				$locale = crb_get_admin_locale();
				$new_mofile = WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo';

				return file_exists( $new_mofile ) ? $new_mofile : $mofile;

			}, PHP_INT_MAX, 2 );
		}
	}

	// Force WP to always load translations from the WP Cerber folder

	/*
	add_filter( 'load_textdomain_mofile', function ( $mofile, $domain ) {

		if ( $domain == 'wp-cerber'
			 && strpos( $mofile, WP_LANG_DIR . '/plugins/' ) === 0 ) {

			$cerber_mofile = cerber_plugin_dir() . '/languages/' . basename( $mofile );

			if ( file_exists( $cerber_mofile ) ) {
				return $cerber_mofile;
			}
		}

		return $mofile;
	}, PHP_INT_MAX, 2 ); */


	// Important: this call is for loading translations if the language of the website is English, otherwise WordPress loads them automatically

	load_plugin_textdomain( 'wp-cerber', false, 'wp-cerber/languages' );
}

/**
 * Smart pluralization wrapper i18n with CONTEXT support.
 *
 * Handles the "Strictly One" case separately from the "Mathematical One" (Gettext).
 *
 * Note: this function requires to use a custom scanner to extract phrases for translation via the project .pot file.
 *
 * @param int $number The integer value to check.
 * @param string $text_strictly_one Text to use ONLY when number is exactly 1 (e.g. "One file").
 * @param string $singular Singular form for _nx(), MUST contain %d (e.g. "%d file").
 * @param string $plural Plural form for _nx(), MUST contain %d (e.g. "%d files").
 * @param string $context Context information (msgctxt).
 *
 * @return string Translated and formatted string based on the supplied number, with gettext context.
 * @see _nx()
 *
 * @since 9.6.10.2
 */
function crb__nx( int $number, string $text_strictly_one, string $singular, string $plural, string $context ): string {
	// Case 1: Human-friendly "One" (Literary singular)
	if ( 1 === $number ) {
		return _x( $text_strictly_one, $context, 'wp-cerber' );
	}

	// Case 2: Mathematical pluralization (includes 0, 2, 5, 21, 31...)
	// We use sprintf to automatically insert the number into the %d placeholder.
	return sprintf(
		_nx( $singular, $plural, $number, $context, 'wp-cerber' ),
		$number
	);
}

/**
 * Smart pluralization wrapper i18n.
 *
 * Handles the "Strictly One" case separately from the "Mathematical One" (Gettext).
 *
 * Note: this function requires to use a custom scanner to extract phrases for translation via the project .pot file.
 *
 * @param int $number The integer value to check.
 * @param string $text_strictly_one Text to use ONLY when number is exactly 1 (e.g. "One file").
 * @param string $singular Singular form for _n(), MUST contain %d (e.g. "%d file").
 * @param string $plural Plural form for _n(), MUST contain %d (e.g. "%d files").
 *
 * @return string Translated and formatted string based on the supplied number.
 *
 * @see _n()
 *
 * @since 9.6.10.2
 */
function crb__n( int $number, string $text_strictly_one, string $singular, string $plural ): string {
	// Case 1: Human-friendly "One" (Literary singular)
	if ( 1 === $number ) {
		return __( $text_strictly_one, 'wp-cerber' );
	}

	// Case 2: Mathematical pluralization (includes 0, 2, 5, 21, 31...)
	// We use sprintf to automatically insert the number into the %d placeholder.
	return sprintf(
		_n( $singular, $plural, $number, 'wp-cerber' ),
		$number
	);
}

add_filter( 'lang_dir_for_domain', 'crb_loc_exception_handler', 10, 3 );

/**
 * An exception handler to prevent the "doing it wrong" error caused by "too early translation requests" for wp-cerber text domain phrases.
 *
 * @param string $path
 * @param string $domain
 * @param string $locale
 *
 * @return string
 *
 * @see _load_textdomain_just_in_time()
 *
 * @since 9.6.5.9
 */
function crb_loc_exception_handler( $path, $domain, $locale ) {

	if ( $domain == 'wp-cerber'
	     && ( ! doing_action( 'after_setup_theme' ) && ! did_action( 'after_setup_theme' ) ) ) {

		$path = ''; // Prevent processing translation to early
	}

	return $path;
}

/**
 * Returns a version-specific list of locales available to download for the current version of WP Cerber.
 *
 * @return array
 *
 * @since 9.6.11
 */
function crb_fetch_translation_updates() {

	$response = wp_remote_get( 'https://downloads.wpcerber.com/' . CRB_TRANSLATION_PATH . '/_state.json' );

	if ( crb_is_wp_error( $response ) ) {
		return [];
	}

	if ( ! $http_body = crb_array_get( $response, 'body' ) ) {
		return [];
	}

	$decoded_data = json_decode( $http_body, true );

	return crb_array_get( $decoded_data, array( 'wp-cerber', 'translation_bucket' ), [] );
}

/**
 * Prepares the list of WP Cerber locales available to install or update on this website.
 * Determines the necessity of updating existing files using the hash data from the WP Cerber repo.
 *
 * @param array $wp_locales Website locales (languages) enabled in the global WordPress settings.
 * @param array $repo_locales Locales available to download from the WP Cerber repo with corresponding hashes.
 *
 * @return array
 *
 * @since 9.6.5.9
 */
function crb_prepare_locales( array $wp_locales, array $repo_locales ): array {

	if ( ! $wp_locales || ! $repo_locales ) {
		return array();
	}

	if ( $locale = crb_get_settings( 'admin_locale' ) ) {
		$wp_locales[] = $locale;
	}

	// Delete English locales and possible junk coming from WP hooks

	$wp_locales = array_filter( $wp_locales, function ( $value ) {

		if ( ! is_string( $value )
		     || strpos( $value, 'en_' ) === 0 ) {
			return false;
		}

		return true;
	});

	if ( ! $wp_locales ) {
		return array();
	}

	$locale_updates = array();

	foreach ( $wp_locales as $locale ) {

		if ( $hash = $repo_locales[ $locale ] ?? false ) {

			$mo_file = WP_LANG_DIR . '/plugins/wp-cerber-' . $locale . '.mo';

			if ( file_exists( $mo_file )
			     && $hash == sha1_file( $mo_file ) ) {

				continue;
			}
		}

		$locale_updates[] = array(
			'language'   => $locale,
			'package'    => crb_get_locale_url( $locale ),
			'autoupdate' => 1,
			'version'    => CERBER_VER,
			//'updated'    => date( 'Y-m-d H:i:s', $update['release_date'] ?? 0 ),
		);
	}

	return $locale_updates;
}

/**
 * Checks for the existence of installed WP Cerber translation files for a given WordPress locale.
 *
 * It checks files in the /wp-content/languages/plugins/ directory (see also WP_LANG_DIR).
 *
 * @param string $locale True if translation files exists for the given WP locale.
 *
 * @return bool
 *
 * @since 9.6.6.14
 */
function crb_is_translation_exists( string $locale ): bool {
	$installed = wp_get_installed_translations( 'plugins' );

	return ! empty( $installed['wp-cerber'][ $locale ] );
}

/**
 * Forces the download of translation files for a specified locale.
 *
 * This function forces WordPress to download the WP Cerber translation files (.mo and .po)
 * from the official WP Cerber translation repository.
 *
 * It bypasses any checks and always overwrites any existing translation files
 * in the /wp-content/languages/plugins/ directory (see also WP_LANG_DIR).
 *
 * @param string $locale The WordPress locale to download (e.g., 'es_ES', 'fr_FR').
 *
 * @return string|WP_Error String on success
 *
 * @since 9.6.6.14
 */
function crb_download_translations( string $locale = '' ) {

	if ( ! $locale ) {
		$locale = crb_get_admin_locale();
	}

	if ( $locale == 'en_US' ) {
		return 'Translations are not needed';
	}

	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-includes/pluggable.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	if ( ! function_exists( 'WP_Filesystem' ) || ! WP_Filesystem() ) {
		CRB_Issues::add( 'download_translation_error', 'WP_Filesystem initialization failed.', array( 'type' => \CRB_Issues::TYPE_EVENT ) );

		return new WP_Error( 'download_translation_error', 'WP_Filesystem initialization failed.' );
	}

	/**
	 * Capturing Skin.
	 *
	 * This anonymous class overrides the default skin behavior to silence
	 * direct output (echo/flush) and capture logs for error reporting.
	 */
	$capturing_skin = new class extends \WP_Upgrader_Skin {

		/**
		 * @var array<string> Internal storage for log messages.
		 */
		public $messages = [];

		/**
		 * Overrides the feedback method to capture strings instead of echoing them.
		 *
		 * @param string $feedback The feedback message.
		 * @param mixed ...$args Optional arguments for string formatting.
		 *
		 * @return void
		 */
		public function feedback( $feedback, ...$args ) {
			// Resolve the string from the upgrader if it exists (handles keys like 'downloading_package')
			if ( isset( $this->upgrader->strings[ $feedback ] ) ) {
				$feedback = $this->upgrader->strings[ $feedback ];
			}

			// Check if formatting is required (using strpos for PHP 7.4 compatibility)
			if ( strpos( $feedback, '%' ) !== false ) {
				if ( $args ) {
					// Apply standard WP sanitization to arguments
					$args = array_map( 'strip_tags', $args );
					$args = array_map( 'esc_html', $args );
					// Format the string
					$feedback = vsprintf( $feedback, $args );
				}
			}

			// Store the processed message if it is not empty
			if ( ! empty( $feedback ) ) {
				$this->messages[] = $feedback;
			}
		}

		/**
		 * Captures error messages.
		 *
		 * @param mixed $errors WP_Error object or error string.
		 *
		 * @return void
		 */
		public function error( $errors ) {
			if ( is_wp_error( $errors ) ) {
				$this->messages[] = 'Error: ' . $errors->get_error_message();
			}
			elseif ( is_string( $errors ) ) {
				$this->messages[] = 'Error: ' . $errors;
			}
		}

		/**
		 * Suppress header output.
		 *
		 * @return void
		 */
		public function header() {
		}

		/**
		 * Suppress footer output.
		 *
		 * @return void
		 */
		public function footer() {
		}

		/**
		 * Suppress 'after' hook output.
		 *
		 * @return void
		 */
		public function after() {
		}

		/**
		 * Suppress 'before' hook output.
		 *
		 * @return void
		 */
		public function before() {
		}
	};

	// Initialize the upgrader with the capturing skin dependency
	$upgrader = new \Language_Pack_Upgrader( $capturing_skin );

	$translation_data = (object) [
		'type'     => 'plugin',
		'slug'     => 'wp-cerber',
		'language' => $locale,
		'version'  => CERBER_VER,
		'package'  => crb_get_locale_url( $locale ),
	];

	/**
	 * Capture low-level network errors and HTTP status codes.
	 */
	$http_debug = function ( $response, $context, $class, $args, $url ) use ( &$http_debug_info, $translation_data ) {
		// Only capture if the URL matches our package URL
		if ( $url === $translation_data->package ) {
			if ( is_wp_error( $response ) ) {
				$http_debug_info = 'Network Error: ' . $response->get_error_message();
			}
			elseif ( is_array( $response ) ) {
				$code = $response['response']['code'] ?? 0;
				$msg = $response['response']['message'] ?? '';
				if ( $code >= 400 ) {
					$http_debug_info = 'HTTP ' . $code . ' (' . $msg . ')';
				}
			}
		}
	};

	add_action( 'http_api_debug', $http_debug, 10, 5 );

	// Download translations

	$result = $upgrader->upgrade( $translation_data );

	remove_action( 'http_api_debug', $http_debug, 10 );

	if ( ! is_wp_error( $result ) && $result ) {
		return 'OK!';
	}

	// Something went wrong

	$error_details = [];

	if ( is_wp_error( $result ) ) {
		$error_details[] = $result->get_error_message();
	}

	if ( $capturing_skin->messages ) {
		$error_details = array_merge( $error_details, $capturing_skin->messages );
	}

	if ( $http_debug_info ) {
		$error_details[] = $http_debug_info;
	}

	if ( empty( $error_details ) ) {
		$error_details[] = 'Unknown error (empty result and empty log).';
	}

	CRB_Issues::add( 'download_translation_error', 'Translation download failed for locale ' . crb_get_wp_locale_label( $locale ), array( 'context' => array( 'raw_diagnostic' => $error_details ), 'type' => CRB_Issues::TYPE_EVENT ) );

	return new WP_Error( 'download_translation_error', implode( ' | ', $error_details ) );
}

/**
 * Resolves a WordPress locale code to its display name without external API calls. This is a best effort implementation.
 *
 * Performance:
 * - Uses static internal cache to prevent redundant FS scans/DB queries.
 * - Does not trigger network requests; relies on local filesystem and DB transients.
 *
 * Limitations & Behavior:
 * - Returns original $locale_code if no translations are installed and the 'available_translations' transient is empty.
 * - In Multisite or environments with disabled updates, the transient may be absent indefinitely.
 * - Formal or custom variants (e.g., 'de_DE_formal') are only resolved if explicitly installed or cached.
 * - Specifically handles 'en_US' as it is often omitted from translation lists.
 *
 * @param string $locale_code Locale string (e.g., 'ru_RU', 'de_DE_formal').
 * @param string $name_field  Field to return: 'native_name' (default) or 'english_name'.
 * @return string The human-readable label or the original locale code on failure.
 *
 * @since 9.6.16
 */
function crb_get_wp_locale_label( $locale_code, $name_field = 'native_name' ) {
	static $internal_cache = [];

	$locale_code = trim( (string) $locale_code );
	if ( $locale_code === '' ) {
		return '';
	}

	// Default WordPress locale handling
	if ( $locale_code === 'en_US' ) {
		return 'English (United States)';
	}

	if ( isset( $internal_cache[ $locale_code ][ $name_field ] ) ) {
		return $internal_cache[ $locale_code ][ $name_field ];
	}

	$name_field = ( $name_field === 'english_name' ) ? 'english_name' : 'native_name';
	$sources = [];

	// Source 1: Physically installed translations
	if ( function_exists( 'wp_get_installed_translations' ) ) {
		$sources[] = wp_get_installed_translations( 'core' );
	}

	// Source 2: Cached available translations from WP API
	$sources[] = get_site_transient( 'available_translations' );

	foreach ( $sources as $data ) {
		if ( ! is_array( $data ) || ! isset( $data[ $locale_code ] ) ) {
			continue;
		}

		$row = (array) $data[ $locale_code ];
		$label = $row[ $name_field ] ?? $row['native_name'] ?? $row['english_name'] ?? '';

		if ( $label !== '' ) {
			$internal_cache[ $locale_code ][ $name_field ] = (string) $label;
			return (string) $label;
		}
	}

	return $locale_code;
}