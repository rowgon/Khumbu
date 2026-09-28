<?php
/**
 * Plugin Name: Ciscar MainWP Google Drive Sync
 * Plugin URI:  https://agenciaciscar.com
 * Description: Sincroniza automáticamente todos los informes en PDF generados por MainWP Pro Reports a Google Drive.
 * Version:     1.0.0
 * Author:      Agencia Císcar
 * Author URI:  https://agenciaciscar.com
 * Text Domain: ciscar-gdrive-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CISCAR_GDRIVE_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CISCAR_GDRIVE_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'CISCAR_GDRIVE_SYNC_VERSION', '1.0.0' );

require_once CISCAR_GDRIVE_SYNC_PATH . 'includes/class-gdrive-logger.php';
require_once CISCAR_GDRIVE_SYNC_PATH . 'includes/class-gdrive-client.php';
require_once CISCAR_GDRIVE_SYNC_PATH . 'includes/class-gdrive-hooks.php';
require_once CISCAR_GDRIVE_SYNC_PATH . 'includes/class-gdrive-admin.php';

register_activation_hook( __FILE__, function() {
	Ciscar_GDrive_Logger::create_table();
} );

add_action( 'plugins_loaded', function() {
	Ciscar_GDrive_Hooks::init();
	if ( is_admin() ) {
		Ciscar_GDrive_Admin::init();
	}
} );
