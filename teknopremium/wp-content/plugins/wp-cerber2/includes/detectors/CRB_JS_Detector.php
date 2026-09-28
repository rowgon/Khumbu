<?php
/*
 * Copyright (C) 2015-2026 Cerber Tech Inc., https://wpcerber.com
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * This file is part of WP Cerber Security.
 *
 * WP Cerber Security is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Cerber Security is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Cerber Security. If not, see <https://www.gnu.org/licenses/>.
 */

final class CRB_JS_Detector {

	/**
	 * Minimum input length to consider JS obfuscation plausible.
	 * Shorter strings statistically produce only noise.
	 */
	private const MIN_INPUT_LENGTH = 32;

	/**
	 * Minimum number of numeric char-code tokens required to treat a sequence
	 * as intentional obfuscation.
	 */
	private const MIN_CHARCODE_TOKENS = 8;

	/**
	 * Minimum decoded output length to consider it meaningful JS.
	 */
	private const MIN_DECODED_LENGTH = 8;

	/**
	 * Minimum number of printable ASCII characters required in decoded output
	 * to treat it as non-binary noise.
	 */
	private const MIN_PRINTABLE_CHARS = 3;

	/**
	 * High-confidence JavaScript execution and DOM primitives. Matched token-aware
	 * (identifier boundaries) so ordinary words such as 'description' or
	 * 'evaluation' are not flagged when the pattern runs on decoded output. DOM
	 * method families are listed explicitly, longest first, so the trailing
	 * boundary does not truncate a longer real method such as createElementNS or
	 * querySelectorAll. Shared by the escape-decode and char-code heuristics.
	 */
	private const JS_PRIMITIVE_PATTERN = '/(?<![\w$])(?:fromCharCode|createElementNS|createElement|querySelectorAll|querySelector|appendChild|getElementById|getElementsByClassName|getElementsByTagNameNS|getElementsByTagName|getElementsByName|XMLHttpRequest|FileSystemObject|unescape|script|eval)(?![\w$])/i';

	/**
	 * Detect obfuscated JavaScript in a string.
	 *
	 * The detector is heuristic-based. It aims to be resilient to garbage input,
	 * while keeping false positives under control.
	 *
	 * @param mixed $string Input string to inspect.
	 *
	 * @return bool True if the string is likely to contain obfuscated JS payload.
	 */
	public static function cerber_detect_js_code( $string = '' ): bool {

		// Normalize input early. This detector operates only on non-empty textual data.
		$string = trim( (string) $string );

		// Pure numbers or empty strings cannot contain JS code.
		// Early exit avoids unnecessary regex work.
		if ( $string === '' || is_numeric( $string ) ) {
			return false;
		}

		// Very short strings statistically produce only noise for JS heuristics.
		// Length threshold is a cheap false-positive reducer.
		if ( strlen( $string ) < self::MIN_INPUT_LENGTH ) {
			return false;
		}

		// Remove whitespace only for the effective-length check. Detection still
		// operates on the original input so lexical token boundaries are preserved.
		$compact = preg_replace( '/\s+/', '', $string );
		if ( $compact === null || strlen( $compact ) < self::MIN_INPUT_LENGTH ) {
			return false;
		}

		// ---------------------------------------------------------------------
		// Heuristic 1: quoted strings built entirely from \x, \uNNNN, or \u{...}
		// escapes, such as "\x66\x6f\x6f" or "\u{66}\u{6f}\u{6f}", possibly
		// mixing the three formats.
		// ---------------------------------------------------------------------
		// We intentionally match ONLY quoted strings that consist entirely of
		// supported escape sequences. This runs on the raw $string, not the
		// whitespace-stripped $compact: stripping whitespace would merge escapes
		// separated by spaces in the source, whose real value contains those
		// spaces, and fabricate a primitive from a benign string. Whitespace
		// inside the quotes therefore correctly breaks the match, while whitespace
		// outside the quoted run does not affect it.
		if ( preg_match_all( '/(["\'])(?:\\\\x[0-9a-fA-F]{2}|\\\\u[0-9a-fA-F]{4}|\\\\u\{[0-9a-fA-F]{1,6}\})+\1/m', $string, $matches ) ) {

			foreach ( $matches[0] as $m ) {

				// The interior is fully escaped. Strip the surrounding quote pair
				// and decode the escapes to their ASCII characters.
				$decoded = self::cerber_decode_js_escapes( substr( $m, 1, -1 ) );

				// Look for high-confidence JS execution or DOM primitives.
				// This avoids running the heavier pattern engine unnecessarily.
				if ( preg_match( self::JS_PRIMITIVE_PATTERN, $decoded ) ) {
					return true;
				}
			}
		}

		// ---------------------------------------------------------------------
		// Heuristic 2: Numeric char codes inside explicit fromCharCode(...) calls
		// ---------------------------------------------------------------------
		// Supported argument formats:
		// - Decimal: 65
		// - Hex:     0x41
		//
		// Octal is intentionally NOT supported:
		// - Rare in modern JS malware
		// - Causes excessive noise and ambiguous parsing
		//
		// Every fromCharCode(...) call is inspected independently, so a short
		// decoy call cannot hide a later one. A call is decoded only when its
		// whole argument list is numeric literals: if it mixes in variables or
		// expressions the decoded value is not statically knowable, and dropping
		// the non-numeric parts could fabricate a primitive and block benign code.
		$calls = self::cerber_extract_fromcharcode_tokens( $string );
		foreach ( $calls as $tokens ) {

			// Require a minimum number of tokens.
			// Single or very short sequences are almost always accidental.
			if ( count( $tokens ) < self::MIN_CHARCODE_TOKENS ) {
				continue;
			}

			// Decode the same validated token list into a string. A structurally
			// valid but unsupported token becomes the underscore sentinel rather
			// than dropping the call, so appending a single out-of-range argument
			// cannot suppress detection of the rest.
			$decoded = self::cerber_fromcharcode( $tokens );

			// Count printable ASCII characters. Cheap sanity check against
			// binary-like noise; it tests plausibility, not correctness.
			$printable = preg_match_all( '/[A-Za-z0-9]/', $decoded );

			// Skip empty, trivially short, or binary-like decoded output.
			if ( $decoded === '' || strlen( $decoded ) < self::MIN_DECODED_LENGTH
			     || ( $printable !== false && $printable < self::MIN_PRINTABLE_CHARS ) ) {
				continue;
			}

			// High-confidence JS execution or DOM primitives in the payload.
			if ( preg_match( self::JS_PRIMITIVE_PATTERN, $decoded ) ) {
				return true;
			}

			// Existing signal: obfuscated external link or IP address.
			list( $xdata, $severity ) = cerber_process_patterns( $decoded, 'js' );
			if ( $xdata ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract statically decodable numeric tokens from fromCharCode() calls.
	 *
	 * This is intentionally a small grammar scanner, not a JavaScript parser. It
	 * recognizes the exact identifier, ASCII whitespace, comments, decimal and
	 * hexadecimal integer literals, commas, and the closing parenthesis. Any
	 * expression or malformed call is rejected without rewriting source tokens.
	 *
	 * @param string $str Input JavaScript-like text to inspect.
	 *
	 * @return array<int,array<int,string>> Validated token lists.
	 */
	private static function cerber_extract_fromcharcode_tokens( string $str ): array {
		$calls = array();
		$identifier = 'fromCharCode';
		$identifier_length = strlen( $identifier );
		$length = strlen( $str );
		$search_offset = 0;

		while ( $search_offset < $length ) {
			$identifier_offset = stripos( $str, $identifier, $search_offset );
			if ( $identifier_offset === false ) {
				break;
			}

			$after_identifier = $identifier_offset + $identifier_length;
			$search_offset = $after_identifier;

			// Do not match a suffix of another ASCII JavaScript identifier.
			if ( $identifier_offset > 0
			     && strpos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_$', $str[ $identifier_offset - 1 ] ) !== false ) {
				continue;
			}

			// JavaScript permits trivia between a function reference and its
			// opening parenthesis.
			$offset = self::cerber_skip_js_trivia( $str, $after_identifier, $length );
			if ( $offset < 0 ) {
				break;
			}

			if ( $offset >= $length || $str[ $offset ] !== '(' ) {
				$search_offset = max( $search_offset, $offset );
				continue;
			}

			$offset = self::cerber_skip_js_trivia( $str, $offset + 1, $length );
			if ( $offset < 0 ) {
				break;
			}

			$tokens = array();

			while ( $offset < $length ) {
				$token_start = $offset;

				// Read one complete hexadecimal or decimal literal.
				if ( $str[ $offset ] === '0'
				     && isset( $str[ $offset + 1 ] )
				     && ( $str[ $offset + 1 ] === 'x' || $str[ $offset + 1 ] === 'X' ) ) {
					$offset += 2;
					if ( $offset >= $length ) {
						break 2;
					}

					$digit_length = strspn( $str, '0123456789abcdefABCDEF', $offset );
				}
				else {
					$digit_length = strspn( $str, '0123456789', $offset );
				}

				if ( $digit_length === 0 ) {
					$search_offset = max( $search_offset, $offset );
					continue 2;
				}

				$offset += $digit_length;
				$tokens[] = substr( $str, $token_start, $offset - $token_start );

				// Trivia may occur only after a complete token. The next source
				// byte must be a comma or the real closing parenthesis.
				$offset = self::cerber_skip_js_trivia( $str, $offset, $length );
				if ( $offset < 0 ) {
					break 2;
				}

				if ( $offset >= $length ) {
					break 2;
				}

				if ( $str[ $offset ] === ')' ) {
					$calls[] = $tokens;
					$search_offset = $offset + 1;
					continue 2;
				}

				if ( $str[ $offset ] !== ',' ) {
					$search_offset = max( $search_offset, $offset );
					continue 2;
				}

				// A single trailing comma is valid JavaScript.
				$offset = self::cerber_skip_js_trivia( $str, $offset + 1, $length );
				if ( $offset < 0 ) {
					break 2;
				}

				if ( $offset < $length && $str[ $offset ] === ')' ) {
					$calls[] = $tokens;
					$search_offset = $offset + 1;
					continue 2;
				}
			}

			break;
		}

		return $calls;
	}

	/**
	 * Skip ASCII JavaScript whitespace and comments at a grammar boundary.
	 *
	 * The helper never removes trivia from the source. It returns the next byte
	 * offset so callers can enforce the delimiter required at that exact boundary.
	 *
	 * @param string $str Input JavaScript-like text.
	 * @param int    $offset Starting byte offset.
	 * @param int    $length Input length in bytes.
	 *
	 * @return int Next non-trivia offset, or -1 for an unterminated block comment.
	 */
	private static function cerber_skip_js_trivia( string $str, int $offset, int $length ): int {

		while ( $offset < $length ) {
			$whitespace_length = strspn( $str, " \t\n\r\f\v", $offset );
			if ( $whitespace_length > 0 ) {
				$offset += $whitespace_length;
				continue;
			}

			if ( $str[ $offset ] !== '/' || ! isset( $str[ $offset + 1 ] ) ) {
				return $offset;
			}

			if ( $str[ $offset + 1 ] === '*' ) {
				$comment_end = strpos( $str, '*/', $offset + 2 );
				if ( $comment_end === false ) {
					return -1;
				}

				$offset = $comment_end + 2;
				continue;
			}

			if ( $str[ $offset + 1 ] === '/' ) {
				$offset += 2;
				while ( $offset < $length && $str[ $offset ] !== "\n" && $str[ $offset ] !== "\r" ) {
					$offset++;
				}

				continue;
			}

			return $offset;
		}

		return $offset;
	}

	/**
	 * Decode JavaScript string escapes to their ASCII characters.
	 *
	 * Handles the three escape formats used for obfuscation: \xNN, \uNNNN, and
	 * \u{H..}. Only ASCII code points are decoded, since the primitive keywords
	 * this detector searches for are ASCII. Non-ASCII code points and any
	 * unsupported sequence are preserved verbatim, so decoding never fabricates
	 * characters that were not present in the input.
	 *
	 * @param string $str Input containing escape sequences.
	 *
	 * @return string Input with supported ASCII escapes decoded in place.
	 */
	private static function cerber_decode_js_escapes( string $str ): string {
		$decoded = preg_replace_callback(
			'/\\\\x([0-9a-fA-F]{2})|\\\\u\{([0-9a-fA-F]{1,6})\}|\\\\u([0-9a-fA-F]{4})/',
			static function ( array $match ): string {

				// Exactly one of the three hex groups is populated per match.
				$hex = $match[1] !== '' ? $match[1] : ( ( $match[2] ?? '' ) !== '' ? $match[2] : ( $match[3] ?? '' ) );
				if ( $hex === '' ) {
					return $match[0];
				}

				// Only ASCII is relevant to the primitive keywords; preserve the rest.
				$code = hexdec( $hex );
				if ( $code > 0x7F ) {
					return $match[0];
				}

				return chr( $code );
			},
			$str
		);

		return $decoded !== null ? $decoded : $str;
	}

	/**
	 * Decode a validated fromCharCode() argument list to its ASCII projection.
	 *
	 * The tokens must already be structurally validated as unsigned decimal or
	 * 0x/0X hexadecimal integer literals, exactly as produced by the argument-list
	 * guard in cerber_detect_js_code(). Validation and decoding therefore operate on
	 * one and the same token list.
	 *
	 * Each token is reduced to a JavaScript String.fromCharCode() ToUint16 code unit
	 * and its ASCII subset is emitted. Any code unit that is not ASCII, and any
	 * structurally valid but unsupported token (legacy-octal-ambiguous, or above
	 * Number.MAX_SAFE_INTEGER where JavaScript rounds under IEEE-754 before ToUint16),
	 * becomes an underscore: a word-character sentinel that neither joins adjacent
	 * ASCII runs into a keyword nor fabricates an identifier boundary next to one.
	 *
	 * The call is always decoded in full. An unsupported token localizes its own
	 * uncertainty to a single underscore instead of dropping the whole call, so
	 * appending one out-of-range argument cannot suppress detection of the rest.
	 *
	 * @param array<int,string> $tokens Validated numeric tokens from one fromCharCode() call.
	 *
	 * @return string ASCII decoding of the tokens, with non-ASCII and unsupported units replaced by underscore.
	 */
	private static function cerber_fromcharcode( array $tokens ): string {

		$out = '';

		foreach ( $tokens as $token ) {

			// Reduce the token to a JavaScript ToUint16 code unit. A negative result
			// marks a structurally valid but unsupported token (legacy-octal-ambiguous,
			// or above Number.MAX_SAFE_INTEGER). Emit the underscore sentinel and keep
			// decoding, so appending such a token cannot suppress detection of the rest
			// of the call.
			$unit = self::cerber_charcode_to_uint16( $token );
			if ( $unit < 0 ) {
				$out .= '_';
				continue;
			}

			// The detector only searches for ASCII primitives and ASCII link/IP
			// patterns. Keep the ASCII subset; replace any other code unit with an
			// underscore. As a word character it prevents two ASCII runs from
			// joining into a keyword and prevents a false identifier boundary next
			// to one, so a non-ASCII letter beside 'eval' does not read as 'eval'.
			$out .= $unit <= 0x7F ? chr( $unit ) : '_';
		}

		return $out;
	}

	/**
	 * Reduce a fromCharCode() token to its JavaScript ToUint16 code unit.
	 *
	 * The token must be a structurally valid unsigned decimal or 0x/0X hexadecimal
	 * integer literal. The argument-list guard no longer bounds its length, so the
	 * length bound is enforced here. Reduction is performed from the string
	 * representation so the result never depends on PHP integer size: a decimal token
	 * is reduced digit by digit modulo 2^16, a hexadecimal token by its least
	 * significant four hex digits. Both paths are exact for JavaScript because every
	 * integer up to Number.MAX_SAFE_INTEGER is represented exactly by a JavaScript
	 * Number.
	 *
	 * Returns -1 for a structurally valid but unsupported token, which the caller maps
	 * to the underscore sentinel: a decimal literal with a leading zero, which
	 * JavaScript may read as legacy octal; or a value above Number.MAX_SAFE_INTEGER
	 * (decimal 9007199254740991, hex 0x1FFFFFFFFFFFFF), which JavaScript rounds under
	 * IEEE-754 before ToUint16. Boundaries are compared as strings to stay independent
	 * of PHP_INT_MAX, and an over-length value returns -1 before any digit-by-digit
	 * work so a huge literal cannot drive unbounded processing.
	 *
	 * @param string $token Structurally validated numeric token.
	 *
	 * @return int Code unit in the range 0 to 65535, or -1 if the token is unsupported.
	 */
	private static function cerber_charcode_to_uint16( string $token ): int {

		// Hexadecimal literal. ToUint16 depends only on the least significant four
		// hex digits, but the full value must still be within Number.MAX_SAFE_INTEGER.
		if ( $token[0] === '0' && isset( $token[1] ) && ( $token[1] === 'x' || $token[1] === 'X' ) ) {

			// Count significant hex digits without copying the whole literal: skip the
			// 0x prefix and any leading zeros.
			$zero_run = strspn( $token, '0', 2 );
			$significant_length = strlen( $token ) - 2 - $zero_run;

			// Reject values above 0x1FFFFFFFFFFFFF. Fewer significant digits are always
			// in range; exactly fourteen are compared as an uppercased string.
			if ( $significant_length > 14
			     || ( $significant_length === 14
			          && strcmp( strtoupper( substr( $token, 2 + $zero_run ) ), '1FFFFFFFFFFFFF' ) > 0 ) ) {
				return -1;
			}

			// Low four hex digits, read past the 0x prefix so the 'x' is never handed
			// to hexdec(), which deprecated invalid characters in PHP 7.4. max() keeps
			// the offset at or after the first hex digit for tokens shorter than four
			// digits, and avoids duplicating the literal.
			$low_hex_offset = max( 2, strlen( $token ) - 4 );

			return (int) hexdec( substr( $token, $low_hex_offset ) );
		}

		// Decimal literal with a leading zero is legacy-octal-ambiguous in JavaScript.
		if ( $token[0] === '0' && isset( $token[1] ) ) {
			return -1;
		}

		// Reject values above 9007199254740991 before any digit-by-digit work. Fewer
		// than sixteen digits are always in range; sixteen are compared as strings.
		$length = strlen( $token );
		if ( $length > 16 || ( $length === 16 && strcmp( $token, '9007199254740991' ) > 0 ) ) {
			return -1;
		}

		// ToUint16 by digit-by-digit reduction modulo 2^16, bounded to sixteen digits.
		$unit = 0;
		for ( $offset = 0; $offset < $length; $offset++ ) {
			$unit = ( ( $unit * 10 ) + ( (int) $token[ $offset ] ) ) & 0xFFFF;
		}

		return $unit;
	}
}
