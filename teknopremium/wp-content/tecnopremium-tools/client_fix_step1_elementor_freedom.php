<?php
require dirname(__DIR__, 2) . '/wp-load.php';

echo "=== STEP 1: FREEING ELEMENTOR CONTROLS (REMOVING RESTRICTIVE !IMPORTANT TYPOGRAPHY RULES) ===\n";

global $wpdb;
$table_name = $wpdb->prefix . 'snippets';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $code = $wpdb->get_var("SELECT code FROM $table_name WHERE id = 5");
    if ($code) {
        // We remove !important from font-size, color, line-height on general headings/paragraphs
        // so that any change the user makes in Elementor visual editor works immediately!
        
        // Let's replace overly aggressive global font rules
        $cleaned = preg_replace('/(font-size|font-family|line-height|letter-spacing)\s*:\s*[^;]+!important\s*;/i', '/* managed by Elementor */', $code);
        
        $wpdb->update($table_name, ['code' => $cleaned], ['id' => 5]);
        echo "Cleaned snippet ID 5 in database.\n";
    }
}

// Update mu-plugins/tecnopremium-master.php as well
$mu_file = WPMU_PLUGIN_DIR . '/tecnopremium-master.php';
if (file_exists($mu_file)) {
    $content = file_get_contents($mu_file);
    // Remove aggressive !important on font sizes and typography that prevent Elementor visual editor changes
    $cleaned_mu = preg_replace('/(font-size|font-family|line-height|letter-spacing)\s*:\s*[^;]+!important\s*;/i', '/* managed by Elementor */', $content);
    file_put_contents($mu_file, $cleaned_mu);
    echo "Cleaned mu-plugins/tecnopremium-master.php so Elementor visual controls work natively.\n";
}

echo "SUCCESS: Elementor visual editor controls unlocked!\n";
