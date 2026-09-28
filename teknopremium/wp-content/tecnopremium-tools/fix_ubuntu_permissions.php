<?php
require dirname(__DIR__, 2) . '/wp-load.php';

echo "=== 1. CREATING MISSING DIRECTORIES AND SETTING PERMISSIONS ===\n";

$dirs = [
    ABSPATH . 'wp-content/ai1wm-backups',
    ABSPATH . 'wp-content/plugins/all-in-one-wp-migration/storage',
    ABSPATH . 'wp-content/uploads'
];

foreach ($dirs as $d) {
    if (!file_exists($d)) {
        @mkdir($d, 0777, true);
        echo "Created directory: $d\n";
    }
    @chmod($d, 0777);
    echo "Permissions set (0777) on: $d\n";
}

// Ensure .htaccess files inside ai1wm directories exist and are writable
$htaccess1 = ABSPATH . 'wp-content/ai1wm-backups/.htaccess';
$htaccess2 = ABSPATH . 'wp-content/plugins/all-in-one-wp-migration/storage/.htaccess';

foreach ([$htaccess1, $htaccess2] as $ht) {
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Deny from all\n");
    }
    @chmod($ht, 0666);
}

$index1 = ABSPATH . 'wp-content/ai1wm-backups/index.php';
$index2 = ABSPATH . 'wp-content/plugins/all-in-one-wp-migration/storage/index.php';

foreach ([$index1, $index2] as $idx) {
    if (!file_exists($idx)) {
        @file_put_contents($idx, "<?php // Silence is golden.\n");
    }
    @chmod($idx, 0666);
}

echo "\n=== 2. CHECKING DEBUG.LOG FOR ERRORS ===\n";
$log_file = ABSPATH . 'debug.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -25);
    echo implode("", $last_lines);
} else {
    echo "No debug.log found in root.\n";
}

$wp_content_log = ABSPATH . 'wp-content/debug.log';
if (file_exists($wp_content_log)) {
    echo "\n=== WP-CONTENT/DEBUG.LOG ===\n";
    $lines = file($wp_content_log);
    $last_lines = array_slice($lines, -25);
    echo implode("", $last_lines);
}
