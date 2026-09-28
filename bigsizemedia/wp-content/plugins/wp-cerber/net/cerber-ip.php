<?php

const CRB_UNSPECIFIED_IP_ADDRESSES = array(
	'0.0.0.0',
	'::',
);


/**
 * Determines whether an IP address belongs to an IPv4 or IPv6 loopback range.
 *
 * Accepts IPv4, IPv6, and IPv4-mapped IPv6 addresses. Invalid IP addresses
 * are treated as non-loopback addresses.
 *
 * Use it to detect localhost IPs: 127.0.0.1, ::1.
 *
 * @param string $ip IP address to check.
 *
 * @return bool True if the address is a loopback address.
 *
 * @since 9.9.1
 */
function crb_is_loopback_ip( string $ip ): bool {
	$packed_ip = @inet_pton( $ip );

	if ( false === $packed_ip ) {
		return false;
	}

	// IPv4 loopback range: 127.0.0.0/8.
	if ( strlen( $packed_ip ) === 4 ) {
		return ord( $packed_ip[0] ) === 127;
	}

	// Native IPv6 loopback address: ::1.
	if ( $packed_ip === inet_pton( '::1' ) ) {
		return true;
	}

	// IPv4-mapped IPv6 range: ::ffff:127.0.0.0/104.
	$mapped_ipv4_prefix = str_repeat( "\x00", 10 ) . "\xff\xff";

	if ( substr( $packed_ip, 0, 12 ) !== $mapped_ipv4_prefix ) {
		return false;
	}

	return ord( $packed_ip[12] ) === 127;
}
