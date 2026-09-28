<?php
require __DIR__ . '/wp-load.php';

$pages = [
    120 => 'About',
    112 => 'Contact',
    128 => 'FAQ'
];

function inspect_widgets($node, &$texts, &$images) {
    if (is_array($node)) {
        if (isset($node['widgetType'])) {
            $wt = $node['widgetType'];
            $st = $node['settings'] ?? [];
            if (isset($st['title'])) {
                $texts[] = "[$wt title]: " . $st['title'];
            }
            if (isset($st['editor'])) {
                $texts[] = "[$wt editor]: " . strip_tags($st['editor']);
            }
            if (isset($st['tabs'])) {
                foreach ($st['tabs'] as $t) {
                    $texts[] = "[$wt tab title]: " . ($t['tab_title'] ?? '');
                    $texts[] = "[$wt tab content]: " . strip_tags($t['tab_content'] ?? '');
                }
            }
            if (isset($st['image']['id'])) {
                $images[] = "[$wt image]: ID " . $st['image']['id'];
            }
        }
        foreach ($node as $v) {
            inspect_widgets($v, $texts, $images);
        }
    }
}

foreach ($pages as $pid => $name) {
    echo "\n=== INSPECTING PAGE: $name (ID $pid) ===\n";
    $json = get_post_meta($pid, '_elementor_data', true);
    if ($json) {
        $data = json_decode($json, true);
        $texts = [];
        $images = [];
        inspect_widgets($data, $texts, $images);
        echo "Texts found (" . count($texts) . "):\n";
        foreach (array_slice($texts, 0, 15) as $t) {
            echo "  - " . substr($t, 0, 120) . "\n";
        }
        echo "Images found: " . implode(', ', array_unique($images)) . "\n";
    }
}
