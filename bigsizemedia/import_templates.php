<?php
// Script para importar JSONs extraídos como plantillas de Elementor
$dir = '/var/www/html/Khumbu/bigsizemedia/elementor_templates/';
$files = glob($dir . '*.json');
$count = 0;

echo "Iniciando importación de " . count($files) . " archivos JSON...\n";

foreach ($files as $file) {
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (!$data || !is_array($data)) continue;
    
    $filename = basename($file, '.json');
    
    // Crear el post como plantilla de Elementor
    $post_data = array(
        'post_title'    => $filename,
        'post_type'     => 'elementor_library',
        'post_status'   => 'publish',
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (!is_wp_error($post_id)) {
        update_post_meta($post_id, '_elementor_data', wp_slash(json_encode($data)));
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', 'page');
        $count++;
    }
}

echo "Importación completada. Se importaron $count plantillas exitosamente.\n";
