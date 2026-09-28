<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'bigsizemedia' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'MZxT@-(7:OTY;*#vQ={;_$wz+on6rv^ Ate9ju8neH|Xbgw-:2BMz#|(w&[Nc<1<' );
define( 'SECURE_AUTH_KEY',   'L,T{IYgos]=#usNva,G|`*GB&`.XTzFTPoMXD4Yu*dv;&]**)w(<,^R<Pi`u7JQ@' );
define( 'LOGGED_IN_KEY',     'pg%jL8KTyfuNHR4/ClIG=F/=H9cSjANeduyQsHMNW(Flc[N9tQPOTE{Glj`ABd}d' );
define( 'NONCE_KEY',         '<j/al2[f?9_:S6F`>S2]Y[(BpM)^Lc$uT$=I(8VrR$M<Z^+A. SEy[]U7iBm9uOf' );
define( 'AUTH_SALT',         '8Mg%qQ)T4>/. Eu:NxMJxj[Wgl^3[@_M+CV_[4x<Tw;jCUM2;I1pmSdcD:5d)jwv' );
define( 'SECURE_AUTH_SALT',  'l)<U%I!!v~)2v%GZ9-%PB.f$kO)%c-3alEZK9EG0.,|9EcRi3E(CK)I<hNO;=k(p' );
define( 'LOGGED_IN_SALT',    's(nNA?}e1g/).nG]OX)H0!oZb%ocD),+QWZ$]I`14!vZOqxG,ZD&d7(4*7c8amQ7' );
define( 'NONCE_SALT',        'puqMJ4@;/0z{97O$R6,QZg}X`&~s{o-9gF Hb{|jt<{so:Upk3hv`51+qVv1v-).' );
define( 'WP_CACHE_KEY_SALT', '+5m8]NzI3S#$w{!Y0^)-QwZkEAVnx.h?c:C245pkh=IW7J8zl4@x(?13p)ob{AT0' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'FS_METHOD', 'direct' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
