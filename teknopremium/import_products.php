<?php
require_once 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

$json = file_get_contents('/tmp/products.json');
$products = json_decode($json, true);

if (!$products) {
    die("Error reading JSON.\n");
}

$count = 0;
foreach ($products as $p) {
    $title = sanitize_text_field($p['name']);
    $price = sanitize_text_field($p['price']);
    $sku = sanitize_text_field($p['sku']);
    $category_name = sanitize_text_field($p['category']);

    // Create Category if not exists
    $term = term_exists($category_name, 'product_cat');
    if ($term === 0 || $term === null) {
        $term = wp_insert_term($category_name, 'product_cat');
    }
    $term_id = is_array($term) ? $term['term_id'] : $term;

    // Check if product exists by title or SKU
    $existing = post_exists($title, '', '', 'product');
    
    if (!$existing) {
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => "product",
        ));

        if (!is_wp_error($post_id)) {
            wp_set_object_terms($post_id, 'simple', 'product_type');
            update_post_meta($post_id, '_visibility', 'visible');
            update_post_meta($post_id, '_stock_status', 'instock');
            update_post_meta($post_id, '_regular_price', $price);
            update_post_meta($post_id, '_price', $price);
            if ($sku) {
                update_post_meta($post_id, '_sku', $sku);
            }
            if ($term_id && !is_wp_error($term_id)) {
                wp_set_object_terms($post_id, intval($term_id), 'product_cat');
            }
            $count++;
        }
    }
}
echo "Imported $count products successfully.\n";
