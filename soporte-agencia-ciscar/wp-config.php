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
define( 'DB_NAME', 'soporte_agencia_ciscar' );

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
define( 'AUTH_KEY',          'xw!vuEdf80|:$Dk|{;dMFX i;c2li@pc b^o0Z`Te=^l(rAjUx[kp5ikXoUOy%y/' );
define( 'SECURE_AUTH_KEY',   '/?mMH7rc+T[rfS]{9,CLzm{g4Cl|X5IPfIIH(_% W/QyEeNLv mHaa825&0DO=Gg' );
define( 'LOGGED_IN_KEY',     'Vyar<T Q&?6m`~x3@tr;e5wl}XWpSq$Gn)2$OtuxN6tj3%#7/mdzNr<}< !0jF1V' );
define( 'NONCE_KEY',         'Bi(BN/uR$mZR]nAcmHy!,*^d;B-LaaQ+A~d_[P:NQkR]58i(z}gBO)/OUjF~^E0y' );
define( 'AUTH_SALT',         '817CD;3`n6:>3=c~{!wy$@4qR5qF 2AGHpY+PYm8!^v<a^u<@u!!,Mbp[pUH4CFn' );
define( 'SECURE_AUTH_SALT',  'di*eABO/#o=d/N6/]|bu+!AOXruq}>S>2HIvJd&(fB[ :9B[gYTlQ2I#NI|-H`h_' );
define( 'LOGGED_IN_SALT',    '!p:aN2eiLG8~C[kDHq,< 41=&D@;p= 3sK|(5$e0NT3s:W&;-+Wcml8<v8GBE3vE' );
define( 'NONCE_SALT',        'fRJuVghz&7>`kUvokdlntLHDTb`;<o!4X)Jj4v7%>zkci^ftfjS?Sy<K!VJH+%Wm' );
define( 'WP_CACHE_KEY_SALT', 'M%&X])EBm{]sDn(4;`/7=9HaJ:,eoH]zC X9&vQ$q[<lL`Wu=U|,#E4v$~y<3W0J' );


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
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
