<?php

/**
 * Light-weight parser (classifier) for HTTP User-Agent strings.
 *
 * Produces a stable, human-readable label intended for logs, alerts,
 * and quick administrative triage.
 *
 * This class prioritizes readability, low cardinality,
 * and predictable output over strict validation or full User-Agent parsing.
 *
 * IMPORTANT:
 * The returned value is raw text. Escaping or context-specific
 * sanitization must be handled by the caller.
 *
 * @version 5.1
 */
final class CRB_User_Agent_Parser {

	/**
	 * Map of AI Bots tokens to human-readable names.
	 *
	 * @var array<string, string>
	 */
	private const AI_BOTS = [
		'GPTBot'            => 'OpenAI (GPTBot)',
		'OAI-SearchBot'     => 'OpenAI (OAI-SearchBot)',
		'ChatGPT-User'      => 'OpenAI (ChatGPT-User)',
		'ClaudeBot'         => 'Anthropic (ClaudeBot)',
		'Claude-Web'        => 'Anthropic (Claude-Web)',
		'Google-Extended'   => 'Google (AI Extended)',
		'Applebot-Extended' => 'Apple (AI Extended)',
		'PerplexityBot'     => 'Perplexity (PerplexityBot)',
		'Perplexity-User'   => 'Perplexity (Perplexity-User)',
		'FacebookBot'       => 'Meta (FacebookBot)',
		'CCBot'             => 'Common Crawl',
	];

	/**
	 * Map of Service Agents (Webhooks, Payment Gateways, APIs).
	 *
	 * These are strictly checked by prefix to ensure semantic accuracy.
	 *
	 * @var array<string, string>
	 */
	private const SERVICE_AGENTS = [
		'PayPal IPN' => 'PayPal (IPN)',
		'Stripe/'    => 'Stripe',
	];

	/**
	 * Map of High-Traffic Bots tokens to human-readable names.
	 *
	 * @var array<string, string>
	 */
	private const KNOWN_BOTS = [
		'Mediapartners-Google'      => 'AdSense Crawler',
		'AdsBot-Google-Mobile-Apps' => 'Mobile Apps Android',
		'AdsBot-Google-Mobile'      => 'AdsBot-Google-Mobile',
		'AdsBot-Google'             => 'AdsBot-Google',
		'APIs-Google'               => 'APIs-Google',
		'FeedFetcher-Google'        => 'FeedFetcher-Google',
		'DuplexWeb-Google'          => 'Duplex on the Web by Google',
		'Google Favicon'            => 'Google Favicon',
		'Google-Read-Aloud'         => 'Google Read Aloud',
		'googleweblight'            => 'Web Light by Google',
		'Amazonbot'                 => 'Amazonbot',
		'bingbot'                   => 'Bingbot',
		'YandexBot'                 => 'YandexBot',
	];

	/**
	 * Map of Browser tokens to configuration.
	 *
	 * Key: The unique token to search for in the UA string.
	 * Value: Array containing 'name' (Human readable) and 'ver_token' (Token to extract version).
	 *
	 * ORDER MATTERS: Specific browsers (Edge, Opera) must precede generic ones (Chrome, Safari).
	 *
	 * @var array<string, array<string, string>>
	 */
	private const BROWSER_MAP = [
		'Firefox/'   => [ 'name' => 'Firefox', 'ver_token' => 'Firefox/' ],
		'OPR/'       => [ 'name' => 'Opera', 'ver_token' => 'OPR/' ],
		'Opera/'     => [ 'name' => 'Opera', 'ver_token' => 'Opera/' ],
		'YaBrowser/' => [ 'name' => 'Yandex Browser', 'ver_token' => 'YaBrowser/' ],
		'Edg/'       => [ 'name' => 'Microsoft Edge', 'ver_token' => 'Edg/' ],
		'Edge/'      => [ 'name' => 'Microsoft Edge', 'ver_token' => 'Edge/' ],
		'Trident/'   => [ 'name' => 'Internet Explorer', 'ver_token' => 'rv:' ],
		'IE/'        => [ 'name' => 'Internet Explorer', 'ver_token' => 'MSIE ' ],
		'Chrome/'    => [ 'name' => 'Chrome', 'ver_token' => 'Chrome/' ],
		'Safari/'    => [ 'name' => 'Safari', 'ver_token' => 'Version/' ],
		'Lynx/'      => [ 'name' => 'Lynx', 'ver_token' => '' ],
	];

	/**
	 * Map of Operating System tokens to human-readable names.
	 *
	 * @var array<string, string>
	 */
	private const PLATFORMS = [
		'Linux'     => 'Linux',
		'Macintosh' => 'Mac',
		'Windows'   => 'Windows',
		'OpenBSD'   => 'OpenBSD',
		'Unix'      => 'Unix',
	];

	/**
	 * Private constructor prevents instantiation of this utility class.
	 */
	private function __construct() {
	}

	/**
	 * Main entry point. Detects browser/bot and platform.
	 *
	 * Returns a string formatted as:
	 * - AI Bots:  "Vendor (BotName)" (e.g. "OpenAI (GPTBot)")
	 * - Service:  "Service Name" (e.g. "PayPal (IPN)")
	 * - Bots:     "Bot Name" (e.g. "Googlebot Mobile", "Amazonbot")
	 * - Tools:    "Tool/FullVersion" or "Human Name (Tool/Version)"
	 * - Browsers: "Browser Name MajorVersion on Platform"
	 *
	 * IMPORTANT: The returned string is a RAW value. The caller is RESPONSIBLE for
	 * escaping it correctly for the final output context.
	 *
	 * @param string $user_agent The raw User-Agent string.
	 *
	 * @return string Raw human-readable identifier.
	 */
	public static function detect( string $user_agent ): string {
		$ua = trim( $user_agent );

		if ( '' === $ua ) {
			return __( 'Not specified', 'wp-cerber' );
		}

		// 1. Priority: AI Agents & LLM Scrapers (High Relevance)
		$ai_bot = self::detect_ai_bot( $ua );
		if ( '' !== $ai_bot ) {
			return $ai_bot;
		}

		// 2. Priority: Service Agents (Webhooks, Payments, APIs)
		// Strict prefix check prevents false positives in "compatible" strings.
		$service = self::detect_service_agent( $ua );
		if ( '' !== $service ) {
			return $service;
		}

		// 3. Priority: Security context (Standard Crawlers & Spiders)
		$bot = self::detect_bot( $ua );
		if ( '' !== $bot ) {
			return $bot;
		}

		// 4. Priority: Scripting Tools (Strict Prefix Check)
		$tool = self::detect_tool( $ua );
		if ( '' !== $tool ) {
			return $tool;
		}

		// 5. Detect Platform (OS)
		$platform = self::detect_platform( $ua );

		// 6. Detect Browser Client & Version
		// We perform detection and version extraction in one pass based on the BROWSER_MAP.
		$browser_full = '';
		foreach ( self::BROWSER_MAP as $token => $config ) {
			if ( false !== mb_stripos( $ua, $token ) ) {
				$name      = $config['name'];
				$ver_token = $config['ver_token'];
				$version   = '';

				if ( '' !== $ver_token ) {
					// Use strict matching for IE11 'rv:' case, otherwise standard logic
					$major_only = true;
					$version    = self::extract_version( $ua, $ver_token, $major_only );
				}

				if ( '' !== $version ) {
					$browser_full = $name . ' ' . $version;
				} else {
					$browser_full = $name;
				}
				break; // Stop after first match (order in BROWSER_MAP handles precedence)
			}
		}

		// 7. Formulate Final Result
		if ( '' !== $browser_full && '' !== $platform ) {
			/* translators: %1$s is the web browser name (e.g. Firefox),  %2$s is the platform name (e.g. Windows) */
			return sprintf( __( '%1$s on %2$s', 'wp-cerber' ), $browser_full, $platform );
		}

		if ( '' !== $browser_full ) {
			return $browser_full;
		}

		if ( '' !== $platform ) {
			/* translators: %s is the platform name (e.g. Linux) */
			return sprintf( __( 'Unknown Browser on %s', 'wp-cerber' ), $platform );
		}

		return __( 'Unknown', 'wp-cerber' );
	}

	/**
	 * Detects declared AI bots and AI user agents.
	 *
	 * @param string $ua The User-Agent string.
	 *
	 * @return string Human-readable AI bot label or empty string.
	 */
	private static function detect_ai_bot( string $ua ): string {
		foreach ( self::AI_BOTS as $token => $label ) {
			if ( false !== mb_stripos( $ua, $token ) ) {
				return $label;
			}
		}

		return '';
	}

	/**
	 * Detects specific service agents (Webhooks, Payment Gateways).
	 *
	 * Uses strict prefix matching to ensure we are detecting the actual service
	 * agent and not a reference to it inside another UA string.
	 *
	 * @param string $ua The User-Agent string.
	 *
	 * @return string Human-readable service label or empty string.
	 */
	private static function detect_service_agent( string $ua ): string {
		foreach ( self::SERVICE_AGENTS as $token => $label ) {
			// Strict prefix check (0 position)
			if ( 0 === mb_strpos( $ua, $token ) ) {
				return $label;
			}
		}

		return '';
	}

	/**
	 * Detects common scripting tools (WordPress, Curl, Wget, Python).
	 *
	 * @param string $ua The User-Agent string.
	 *
	 * @return string Human-readable tool label or empty string.
	 */
	private static function detect_tool( string $ua ): string {
		// WordPress
		if ( 0 === mb_strpos( $ua, 'WordPress/' ) ) {
			$ver = self::extract_version( $ua, 'WordPress/' );

			return 'WordPress' . ( '' !== $ver ? '/' . $ver : '' );
		}

		// curl
		if ( 0 === mb_strpos( $ua, 'curl/' ) ) {
			$ver = self::extract_version( $ua, 'curl/' );

			return 'curl' . ( '' !== $ver ? '/' . $ver : '' );
		}

		// Wget
		if ( 0 === mb_strpos( $ua, 'Wget/' ) ) {
			$ver = self::extract_version( $ua, 'Wget/' );

			return 'Wget' . ( '' !== $ver ? '/' . $ver : '' );
		}

		// ApacheBench
		if ( 0 === mb_strpos( $ua, 'ApacheBench/' ) ) {
			$ver = self::extract_version( $ua, 'ApacheBench/' );

			return 'ApacheBench' . ( '' !== $ver ? '/' . $ver : '' );
		}

		// Python Requests
		if ( 0 === mb_strpos( $ua, 'python-requests' ) ) {
			$ver = self::extract_version( $ua, 'python-requests' );
			if ( '' !== $ver ) {
				return 'Python (python-requests/' . $ver . ')';
			}

			return 'Python (python-requests)';
		}

		return '';
	}

	/**
	 * Helper to extract version numbers following a specific token.
	 *
	 * @param string $ua The User-Agent string.
	 * @param string $token The token to search for.
	 * @param bool $major_only If true, returns only the first number.
	 *
	 * @return string The extracted version or empty string.
	 */
	private static function extract_version( string $ua, string $token, bool $major_only = false ): string {
		$quoted_token = preg_quote( $token, '/' );

		// Regex explanation:
		// 1. $quoted_token matches the prefix literally
		// 2. [\/\s]* matches zero or more slashes/spaces (lenient separator)
		// 3. Capture digits
		if ( $major_only ) {
			$pattern = '/' . $quoted_token . '[\/\s]*([0-9]+)/';
		} else {
			$pattern = '/' . $quoted_token . '[\/\s]*([0-9]+(?:\.[0-9]+)*)/';
		}

		if ( preg_match( $pattern, $ua, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Detects Bots, Crawlers, and Scrapers.
	 *
	 * @param string $ua The User-Agent string.
	 *
	 * @return string The bot name or empty string if not found.
	 */
	private static function detect_bot( string $ua ): string {
		// 1. Known High-Traffic Bots (Fast Token Match)
		foreach ( self::KNOWN_BOTS as $token => $name ) {
			if ( false !== mb_stripos( $ua, $token ) ) {
				return $name;
			}
		}

		// 2. Googlebot (Refined logic)
		if ( false !== mb_stripos( $ua, 'Googlebot' ) ) {
			if ( false !== mb_stripos( $ua, 'Android' ) ) {
				return 'Googlebot Mobile';
			}

			// Only claim Desktop if it explicitly behaves like a browser (Mozilla)
			if ( false !== mb_stripos( $ua, 'Mozilla' ) ) {
				return 'Googlebot Desktop';
			}

			return 'Googlebot';
		}

		// 3. Generic "compatible" bots (Strict Parenthesis Scope)
		// We first ensure we are inside a parenthesis block containing "compatible;",
		// then we extract the data.
		if ( false !== mb_stripos( $ua, 'compatible;' ) ) {
			// Regex finds the first (...) chunk that contains "compatible;"
			// Using standard preg_match as regex needs valid UTF-8 handling if 'u' modifier is added,
			// but here we work on ASCII control chars mostly.
			if ( preg_match( '/\([^)]*compatible;[^)]*\)/i', $ua, $matches_chunk ) ) {
				$chunk = $matches_chunk[0];

				// Now extract content after "compatible;" within that specific chunk
				if ( preg_match( '/compatible;([^)]*)/i', $chunk, $matches_content ) ) {
					$parts = explode( ';', $matches_content[1] );
					foreach ( $parts as $part ) {
						// Check for common bot identifiers
						if ( preg_match( '/bot|crawler|spider|Yandex|Yahoo! Slurp/i', $part ) ) {
							$bot_name = trim( $part );

							if ( false !== mb_stripos( $ua, 'Android' ) ) {
								$bot_name = $bot_name . ' Mobile';
							}

							return $bot_name;
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Detects the Operating System and its version for mobile devices.
	 *
	 * @param string $ua The User-Agent string.
	 *
	 * @return string The platform name (with version for mobile) or empty string.
	 */
	private static function detect_platform( string $ua ): string {
		// 1. Android
		if ( false !== mb_stripos( $ua, 'Android' ) ) {
			// Strict check: "Android" followed by a space and digits.
			if ( preg_match( '/Android\s+([0-9]+(?:\.[0-9]+)*)/i', $ua, $matches ) ) {
				return 'Android ' . $matches[1];
			}

			return 'Android';
		}

		// 2. iOS (iPhone / iPad)
		if ( false !== mb_stripos( $ua, 'iPhone' ) || false !== mb_stripos( $ua, 'iPad' ) ) {
			$name = ( false !== mb_stripos( $ua, 'iPad' ) ) ? 'iPad' : 'iPhone';

			if ( preg_match( '/OS\s+([0-9_]+)\s+like\s+Mac\s+OS\s+X/i', $ua, $matches ) ) {
				$ver = str_replace( '_', '.', $matches[1] );

				return 'iOS ' . $ver . ' (' . $name . ')';
			}

			return 'iOS (' . $name . ')';
		}

		// 3. Desktop / Others
		foreach ( self::PLATFORMS as $key => $name ) {
			if ( false !== mb_stripos( $ua, $key ) ) {
				return $name;
			}
		}

		if ( false !== mb_stripos( $ua, 'Lynx/' ) ) {
			return 'Linux';
		}

		return '';
	}
}