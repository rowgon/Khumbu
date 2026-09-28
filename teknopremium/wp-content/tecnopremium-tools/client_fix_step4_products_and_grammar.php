<?php
require dirname(__DIR__, 2) . '/wp-load.php';

echo "=== 1. RENAMING WOOCOMMERCE PRODUCTS TO NATURAL SPANISH & ASSIGNING REAL ARCHITECTURAL IMAGES ===\n";

$products_data = [
    162 => [
        'name' => 'Spa de hidromasaje exterior J215 Horizon Gray',
        'desc' => 'Spa exterior de hidromasaje para 3 personas con aislamiento térmico ProLast, jets de hidroterapia ajustables y acabado exterior en color Horizon Gray.',
        'image_id' => 205, // 1foto-home-Venice-w.jpg (stunning spa/outdoor photo)
        'gallery' => [206, 207]
    ],
    163 => [
        'name' => 'Spa de hidromasaje exterior J215 acabado Pecan',
        'desc' => 'Spa exterior de alta gama con revestimiento en madera sintética Pecan de larga duración, filtración de última generación y control bioclimático.',
        'image_id' => 204, // Elton_Foto-Home.jpg
        'gallery' => [203, 207]
    ],
    164 => [
        'name' => 'Spa exterior wellness J225 Horizon Gray',
        'desc' => 'Modelo familiar de hidromasaje ergonómico de 4 a 5 plazas con iluminación perimetral LED y sistema de purificación continua.',
        'image_id' => 206, // 2foto-home-Tresse-w.jpg
        'gallery' => [205, 211]
    ],
    165 => [
        'name' => 'Spa exterior wellness J225 acabado Pecan',
        'desc' => 'Diseño arquitectónico con acabados cálidos Pecan, jets terapéuticos de precisión y aislamiento hermético para invierno.',
        'image_id' => 203, // foto-home-Cliff.jpg
        'gallery' => [204, 212]
    ],
    166 => [
        'name' => 'Escalera de acceso para Swimspa 5 peldaños color negro',
        'desc' => 'Escalera estructural antideslizante con marco de aluminio marino lacado en negro para acceso cómodo y seguro al Swimspa exterior.',
        'image_id' => 216, // 3-1.jpg
        'gallery' => [223]
    ],
    167 => [
        'name' => 'Cobertor térmico ProLast para Spa J508L color negro',
        'desc' => 'Tapa térmica de sellado hermético diseñada a medida para reducir hasta un 75% las pérdidas de temperatura y evaporación en exteriores.',
        'image_id' => 212, // 10-1.jpg
        'gallery' => [211]
    ],
    168 => [
        'name' => 'Cobertor térmico ProLast para Spa J509 color negro',
        'desc' => 'Cubierta exterior reforzada impermeable a la lluvia y nieve, con cierres de seguridad y aislamiento térmico multicapa.',
        'image_id' => 211, // 1-4-scaled.jpg
        'gallery' => [212]
    ]
];

foreach ($products_data as $pid => $pinfo) {
    $product = wc_get_product($pid);
    if ($product) {
        $product->set_name($pinfo['name']);
        $product->set_description($pinfo['desc']);
        $product->set_short_description($pinfo['desc']);
        $product->set_image_id($pinfo['image_id']);
        $product->set_gallery_image_ids($pinfo['gallery']);
        $product->save();
        echo "Updated Product ID $pid: '{$pinfo['name']}' with Image ID {$pinfo['image_id']}\n";
    }
}

echo "\n=== 2. UPDATING NAVIGATION MENU TO NATURAL SPANISH CAPITALIZATION ===\n";

$menu_items = wp_get_nav_menu_items(28); // Menu header ID 28
if ($menu_items) {
    $natural_names = [
        149 => 'Inicio',
        154 => 'Catálogo y tienda',
        151 => 'Sobre nosotros',
        152 => 'Contacto',
        150 => 'Blog y actualidad',
        153 => 'Preguntas frecuentes'
    ];
    foreach ($menu_items as $item) {
        if (isset($natural_names[$item->ID])) {
            wp_update_post([
                'ID' => $item->ID,
                'post_title' => $natural_names[$item->ID]
            ]);
            echo "Updated Menu Item ID {$item->ID} -> '{$natural_names[$item->ID]}'\n";
        }
    }
}

echo "\n=== 3. CLEANING NONSENSICAL STOCK PHOTOS ON HOME PAGE ID 53 ===\n";

function tp_save_el($post_id, $data) {
    $json = wp_slash(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    update_post_meta($post_id, '_elementor_data', $json);
}

$home_json = get_post_meta(53, '_elementor_data', true);
if ($home_json) {
    $hdata = json_decode($home_json, true);
    // Replace any stock person/carpenter photos or inappropriate images on Home ID 53
    function clean_home_images(&$node) {
        if (is_array($node)) {
            if (isset($node['widgetType']) && $node['widgetType'] === 'image' && isset($node['settings']['image']['id'])) {
                $img_id = (int)$node['settings']['image']['id'];
                // IDs 65, 66, 67 are carpenter/person stock photos; IDs 44-73 are indoor living room furniture
                if (in_array($img_id, [65, 66, 67, 44, 48, 56, 57, 63, 64])) {
                    // Replace with architectural outdoor photos
                    $node['settings']['image']['id'] = 205;
                }
            }
            foreach ($node as &$v) clean_home_images($v);
        }
    }
    clean_home_images($hdata);
    tp_save_el(53, $hdata);
    echo "Cleaned Home Page ID 53 images.\n";
}

// Clear all Elementor caches
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_element_cache'");
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "\nSUCCESS: Products renamed to natural Spanish, correct spa images assigned, menu naturalized, and stock photos cleaned!\n";
