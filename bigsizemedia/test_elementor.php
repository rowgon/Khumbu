<?php
require_once('wp-load.php');
$doc = \Elementor\Plugin::$instance->documents->get( 985 );
if ( ! $doc ) {
    echo "Document not found\n";
} else {
    $elements = $doc->get_elements_data();
    echo "Elements count: " . count($elements) . "\n";
    if (empty($elements)) {
        echo "Raw meta: \n";
        echo get_post_meta(985, '_elementor_data', true);
    }
}
