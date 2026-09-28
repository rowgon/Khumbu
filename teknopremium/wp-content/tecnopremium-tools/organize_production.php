<?php
require __DIR__ . '/wp-load.php';

$root_dir = ABSPATH;
$wp_core_files = [
    'index.php',
    'wp-activate.php',
    'wp-blog-header.php',
    'wp-comments-post.php',
    'wp-config.php',
    'wp-config-sample.php',
    'wp-cron.php',
    'wp-links-opml.php',
    'wp-load.php',
    'wp-login.php',
    'wp-mail.php',
    'wp-settings.php',
    'wp-signup.php',
    'wp-trackback.php',
    'xmlrpc.php'
];

$tools_dir = ABSPATH . 'wp-content/tecnopremium-tools';
if (!file_exists($tools_dir)) {
    mkdir($tools_dir, 0755, true);
}

// 1. SCAN ROOT & MOVE NON-CORE PHP FILES TO wp-content/tecnopremium-tools
echo "1. Scanning root directory for custom/diagnostic PHP scripts...\n";
$files = scandir($root_dir);
$moved_count = 0;
foreach ($files as $f) {
    if ($f === '.' || $f === '..' || $f === 'organize_production.php') continue;
    if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
        if (!in_array($f, $wp_core_files)) {
            $src = $root_dir . $f;
            $dst = $tools_dir . '/' . $f;
            rename($src, $dst);
            echo "  Moved: $f -> wp-content/tecnopremium-tools/$f\n";
            $moved_count++;
        }
    }
}
if ($moved_count === 0) {
    echo "  Root directory is already clean! No extra PHP scripts found.\n";
}

// 2. CONSOLIDATE MASTER PRODUCTION CODE INTO mu-plugins/tecnopremium-master.php
echo "\n2. Consolidating Master Production Code into wp-content/mu-plugins/tecnopremium-master.php...\n";

global $wpdb;
$table_name = $wpdb->prefix . 'snippets';
$snippet_code = '';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $snippet_code = $wpdb->get_var("SELECT code FROM $table_name WHERE id = 5");
}

if (!file_exists(WPMU_PLUGIN_DIR)) {
    mkdir(WPMU_PLUGIN_DIR, 0755, true);
}

// Remove old duplicate file name if exists
if (file_exists(WPMU_PLUGIN_DIR . '/tecnopremium-shop-snippet.php')) {
    unlink(WPMU_PLUGIN_DIR . '/tecnopremium-shop-snippet.php');
}

$mu_file = WPMU_PLUGIN_DIR . '/tecnopremium-master.php';
$mu_content = <<<EOPHP
<?php
/**
 * Plugin Name: Tecnopremium Master Production Suite
 * Description: Sistema integral de diseño Luxury Dark-Mode, Ficha Técnica VIP, Grid Equalizer de Tienda y Maquetación B2B.
 * Version: 2.0.0
 * Author: Tecnopremium Engineering
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitamos duplicidad si el snippet de Code Snippets está ejecutándose
if (!defined('TECNOPREMIUM_MASTER_ACTIVE')) {
    define('TECNOPREMIUM_MASTER_ACTIVE', true);

{$snippet_code}
}
EOPHP;

file_put_contents($mu_file, $mu_content);
echo "  Saved production suite to: wp-content/mu-plugins/tecnopremium-master.php\n";

// Clear all caches
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_element_cache'");
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "\nSUCCESS: Production environment ready and WordPress root is 100% clean!\n";
