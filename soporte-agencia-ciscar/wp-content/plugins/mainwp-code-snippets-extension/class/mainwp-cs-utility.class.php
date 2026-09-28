<?php
/**
 * MainWP Code Snippets Extension - Utility Class
 *
 * @package MainWP\CodeSnippets
 */

/**
 * Class MainWP_CS_Utility
 *
 * General-purpose utility helpers for the Code Snippets extension.
 *
 * @since 1.0.0
 */
class MainWP_CS_Utility {

	/**
	 * Generate a unique slug string for a snippet.
	 *
	 * Combines a current timestamp with a random alphanumeric suffix to
	 * produce a collision-resistant identifier suitable for use as a file slug.
	 *
	 * @param int $length Length of the random suffix (1–10, default 5).
	 * @return string Timestamp-prefixed random slug (e.g. '20240115143022abcde').
	 */
	public static function rand_string( $length = 5 ) {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$count   = strlen( $charset );
		$length  = max( 1, min( 10, (int) $length ) );
		$str     = '';

		while ( $length-- ) {
			$str .= $charset[ wp_rand( 0, $count - 1 ) ];
		}

		return gmdate( 'YmdHis' ) . $str;
	}

	/**
	 * Returns date in time ago format
	 *
	 * @param  mixed $ptime Date stamp.
	 * @return string $string   Time elapsed string.
	 */
	public static function time_elapsed_string( $ptime ) {
		$etime = time() - $ptime;

		if ( $etime < 1 ) {
			return '0 seconds';
		}

		$a        = array(
			365 * 24 * 60 * 60 => 'year',
			30 * 24 * 60 * 60  => 'month',
			24 * 60 * 60       => 'day',
			60 * 60            => 'hour',
			60                 => 'minute',
			1                  => 'second',
		);
		$a_plural = array(
			'year'   => 'years',
			'month'  => 'months',
			'day'    => 'days',
			'hour'   => 'hours',
			'minute' => 'minutes',
			'second' => 'seconds',
		);

		foreach ( $a as $secs => $str ) {
			$d = $etime / $secs;
			if ( $d >= 1 ) {
				$r = round( $d );
				return $r . ' ' . ( $r > 1 ? $a_plural[ $str ] : $str ) . ' ago';
			}
		}

		return '0 seconds';
	}
}
