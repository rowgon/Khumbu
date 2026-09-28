<?php
define( 'WP_CACHE', true ); // Added by WP Rocket

define( 'WPCACHEHOME', __DIR__ . '/wp-content/plugins/wp-super-cache/' );

define( 'DB_NAME', 'teknopremium' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_HOME', 'http://localhost/Khumbu/teknopremium' );
define( 'WP_SITEURL', 'http://localhost/Khumbu/teknopremium' );

define( 'AUTH_KEY',         'Z:1$Guxlo==-R0Upq*-!yKRu%Ps3!EtuBB|wJf8g =:5!8`z_3x[}lp.B62fK4_1' );
define( 'SECURE_AUTH_KEY',  ';{s!uQ8|Hbh_7vcw%zPcPLNc76*b|r2e|NQL]]g&Fi-.#%prd@f!<rU66_2V`/{:' );
define( 'LOGGED_IN_KEY',    'D]eFBad6~RXt~)Cu?W35p2T>f#S.+CIQ~>hZ{-&!A4bU-Q[c}N$HJV1!9H?.9C;A' );
define( 'NONCE_KEY',        '7mu%c1E_B83)E3]&dN<KBsvZ)mpHjWq^D5tOwl3I=JIti:a><(}gQf@x/,`$FVD`' );
define( 'AUTH_SALT',        '8Zys_D,Szjma<UM01tVa 6zF5}l5d%uRM5EdzE!Z6$0]D9Vv!Hbst]03hW9^RF+:' );
define( 'SECURE_AUTH_SALT', '[i22jK}U9K=V_cai)GK))e`zk1>gFR4VK%iTX_n3LQp(-gYxqbvTrWou*zpO =`-' );
define( 'LOGGED_IN_SALT',   '~;c>iplv!LoL,+i2IT5D0$9tj2Y5QEG2$?D`u_HQQOC,F^2gNpM1;~JIxvzwlSo|' );
define( 'NONCE_SALT',       '4#kb2Oji7*4=3joXpw^4qj2gB;P[A.tl9z3dxoLlHbkZt.i$nf%ygnt.]HM`SEZa' );

$table_prefix = 'wp_';

define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'FS_METHOD', 'direct' );

define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
define( 'DISABLE_WP_CRON', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
