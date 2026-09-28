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
define( 'DB_NAME', 'khumbunew_db' );

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
define('AUTH_KEY',         'HKj -(,7dq8?HWtiDY ]8|AZ<r.&>+Hp:C0u]DlTgjD{~kt&RK-^iY-ruA-~-*w+');
define('SECURE_AUTH_KEY',  'OQlDdIhKu7en+MEeARaduxQD,+uv{!!w7#N0,w8_qdR6UeQGn.OS RIh7D]Z{|T5');
define('LOGGED_IN_KEY',    '+I5SbT^xjlW1xe`~3QedtUtb,I`937~.&8V4Au|MO3h3=hH9+!sX=1;Qcn<z_(St');
define('NONCE_KEY',        'D+CU#4+ -/[&?2pm4mD`+i9-QC+!LtPILfgAT/JPd2NUL6x;|>wIoH9!r^d+(rrj');
define('AUTH_SALT',        '0l$4||LtM(ha4]]V1~yT,?zliOYp3[+~,X`j|M4Cw.k+w!xCL&5G|D~dpHnpZXSl');
define('SECURE_AUTH_SALT', '3FNfp&~@+r-1}ZHARmX~>,89*S-NjZTw~Fcub%?0RU|c@*1m,?P|=lfSdvxjj@{[');
define('LOGGED_IN_SALT',   'hcs5?V/ZVGiUjKL{&#-a]s9R*{6M[Yn,2,K>!^;:i=a.bdpn` -)1NUPVZ_^:xSy');
define('NONCE_SALT',       'RTb`s8RG]VbnaKY_;<~U3d-nJ>Z_dF4u~Rm/^webg+no<`Q7gr]g2-2Mb/t{UrTz');
define( 'WP_CACHE_KEY_SALT', '^GrFMPBicuJ}>4}6V#%jac}]ijiLXbBh%f@5)e*AjQrE9D9eW07)9)L6?$x%y(q/' );


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

define( 'FS_METHOD', 'direct' );
define( 'ALLOW_UNFILTERED_UPLOADS', true );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
