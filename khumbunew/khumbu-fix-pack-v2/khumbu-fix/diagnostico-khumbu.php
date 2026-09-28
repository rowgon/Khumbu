<?php
/**
 * DIAGNÓSTICO KHUMBU — ejecutar con: php diagnostico-khumbu.php
 * Desde el directorio raíz de WordPress (donde está wp-load.php)
 * 
 * Ejemplo: cd /var/www/html/Khumbu/khumbunew && php diagnostico-khumbu.php
 */

// Cargar WordPress
if (!file_exists('./wp-load.php')) {
    echo "❌ ERROR: Ejecuta este script desde el directorio raíz de WordPress\n";
    echo "   Ejemplo: cd /var/www/html/Khumbu/khumbunew && php diagnostico-khumbu.php\n";
    exit(1);
}
require_once('./wp-load.php');

echo "\n" . str_repeat('=', 60) . "\n";
echo "  DIAGNÓSTICO KHUMBU — " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n\n";

$errors = [];
$warnings = [];
$ok = [];

// ── 1. WordPress básico ─────────────────────────────────────
echo "1. WORDPRESS\n";
echo "   Versión: " . get_bloginfo('version') . "\n";
echo "   URL: " . get_bloginfo('url') . "\n";
echo "   Tema activo: " . wp_get_theme()->get('Name') . " " . wp_get_theme()->get('Version') . "\n";

if (strpos(wp_get_theme()->get('Name'), 'Hello') !== false) {
    $ok[] = "Tema Hello Elementor activo";
    echo "   ✅ Hello Elementor activo\n";
} else {
    $warnings[] = "Tema no es Hello Elementor (actual: " . wp_get_theme()->get('Name') . ")";
    echo "   ⚠️  Se recomienda Hello Elementor\n";
}

// ── 2. Elementor ─────────────────────────────────────────────
echo "\n2. ELEMENTOR\n";
if (defined('ELEMENTOR_VERSION')) {
    echo "   Elementor: " . ELEMENTOR_VERSION . "\n";
    $ok[] = "Elementor activo v" . ELEMENTOR_VERSION;
    
    // Check V4/atomic editor
    $experiments = get_option('elementor_experiment-e_element_cache');
    echo "   Editor V4 (atómico): " . (class_exists('\Elementor\Modules\AtomicWidgets\Module') ? '✅ SÍ' : '⚠️  No detectado') . "\n";
} else {
    $errors[] = "Elementor NO está activo";
    echo "   ❌ Elementor NO está activo\n";
}

if (defined('ELEMENTOR_PRO_VERSION')) {
    echo "   Elementor Pro: " . ELEMENTOR_PRO_VERSION . "\n";
    $ok[] = "Elementor Pro activo v" . ELEMENTOR_PRO_VERSION;
} else {
    $errors[] = "Elementor Pro NO está activo — header/footer del Theme Builder no funcionarán";
    echo "   ❌ Elementor Pro NO está activo\n";
}

// ── 3. Design System — Variables globales ────────────────────
echo "\n3. DESIGN SYSTEM — Variables globales\n";

// Las variables se guardan en el kit de Elementor
$kit_id = get_option('elementor_active_kit');
echo "   Kit activo ID: $kit_id\n";

if ($kit_id) {
    $kit_meta = get_post_meta($kit_id, '_elementor_page_settings', true);
    
    // Global Colors
    $colors = isset($kit_meta['system_colors']) ? $kit_meta['system_colors'] : [];
    $custom_colors = isset($kit_meta['custom_colors']) ? $kit_meta['custom_colors'] : [];
    $all_colors = array_merge($colors, $custom_colors);
    
    echo "   Colores del sistema: " . count($colors) . "\n";
    echo "   Colores custom: " . count($custom_colors) . "\n";
    
    // Required variable IDs from the JSON templates
    $required_color_ids = [
        'e-gv-04cb4c1', 'e-gv-546eddc', 'e-gv-1f4162d', 'e-gv-3c50991',
        'e-gv-46ead6b', 'e-gv-68c8481', 'e-gv-6697b66', 'e-gv-a4db658', 'e-gv-f836207'
    ];
    $required_font_ids = ['e-gv-4599cec', 'e-gv-5a400a0'];
    
    echo "\n   Colores encontrados:\n";
    $found_color_ids = [];
    foreach ($all_colors as $c) {
        $gv_id = isset($c['_id']) ? 'e-gv-' . $c['_id'] : '?';
        $title = isset($c['title']) ? $c['title'] : '?';
        $color = isset($c['color']) ? $c['color'] : '?';
        echo "     $gv_id → $title = $color\n";
        $found_color_ids[] = $gv_id;
    }
    
    // Global Fonts
    $fonts = isset($kit_meta['system_typography']) ? $kit_meta['system_typography'] : [];
    $custom_fonts = isset($kit_meta['custom_typography']) ? $kit_meta['custom_typography'] : [];
    $all_fonts = array_merge($fonts, $custom_fonts);
    
    echo "\n   Fuentes encontradas:\n";
    $found_font_ids = [];
    foreach ($all_fonts as $f) {
        $gv_id = isset($f['_id']) ? 'e-gv-' . $f['_id'] : '?';
        $title = isset($f['title']) ? $f['title'] : '?';
        $family = isset($f['typography_font_family']) ? $f['typography_font_family'] : '?';
        echo "     $gv_id → $title = $family\n";
        $found_font_ids[] = $gv_id;
    }
    
    // Check required IDs
    echo "\n   Verificación de IDs requeridos por los JSON:\n";
    $missing_colors = [];
    foreach ($required_color_ids as $rid) {
        if (in_array($rid, $found_color_ids)) {
            echo "     ✅ $rid\n";
        } else {
            echo "     ❌ $rid — FALTA\n";
            $missing_colors[] = $rid;
        }
    }
    foreach ($required_font_ids as $rid) {
        if (in_array($rid, $found_font_ids)) {
            echo "     ✅ $rid (fuente)\n";
        } else {
            echo "     ❌ $rid (fuente) — FALTA\n";
            $missing_colors[] = $rid;
        }
    }
    
    if (!empty($missing_colors)) {
        $errors[] = count($missing_colors) . " variable(s) del Design System faltan — los JSON NO se verán bien";
    } else {
        $ok[] = "Todas las variables del Design System coinciden";
    }
    
    // Global Classes
    $classes = isset($kit_meta['custom_classes']) ? $kit_meta['custom_classes'] : 
              (isset($kit_meta['e_custom_css_class']) ? $kit_meta['e_custom_css_class'] : []);
    echo "\n   Clases globales:\n";
    if (!empty($classes)) {
        foreach ($classes as $cls) {
            echo "     " . json_encode($cls) . "\n";
        }
    } else {
        echo "     (ninguna encontrada — g-440fd6d puede estar en otro formato)\n";
    }
}

// ── 4. Attachment 7266 ───────────────────────────────────────
echo "\n4. ATTACHMENT PLACEHOLDER (7266)\n";
$att = get_post(7266);
if ($att && $att->post_type === 'attachment') {
    $att_url = wp_get_attachment_url(7266);
    echo "   ✅ Attachment 7266 existe: $att_url\n";
    $ok[] = "Attachment 7266 existe";
} else {
    echo "   ⚠️  Attachment 7266 NO existe en esta instalación\n";
    echo "   → Las imágenes de header, footer y blog necesitan URLs reales\n";
    $warnings[] = "Attachment 7266 no existe — imágenes de los JSON saldrán vacías";
}

// ── 5. Medios necesarios ─────────────────────────────────────
echo "\n5. MEDIOS NECESARIOS\n";
$required_media = [
    'logo-blanco' => ['ext' => ['svg', 'png'], 'for' => 'Header y Footer (bloques 01, 09)'],
    'hero-720' => ['ext' => ['mp4'], 'for' => 'Vídeo de apertura (bloque WOW)'],
    'metal' => ['ext' => ['jpg', 'jpeg', 'png', 'webp'], 'for' => 'Tarjeta blog (bloque 07)'],
    'factory' => ['ext' => ['jpg', 'jpeg', 'png', 'webp'], 'for' => 'Tarjeta podcast (bloque 07)'],
];

foreach ($required_media as $name => $info) {
    // Search in media library
    $found = false;
    foreach ($info['ext'] as $ext) {
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            's' => $name,
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            $att_post = $query->posts[0];
            $att_url = wp_get_attachment_url($att_post->ID);
            if (stripos($att_url, $name) !== false) {
                echo "   ✅ $name → $att_url (ID: {$att_post->ID})\n";
                $found = true;
                break;
            }
        }
    }
    if (!$found) {
        echo "   ❌ $name — NO encontrado en la biblioteca\n";
        echo "      Necesario para: {$info['for']}\n";
        $warnings[] = "$name no subido a la biblioteca de medios";
    }
}

// ── 6. Plantillas importadas ─────────────────────────────────
echo "\n6. PLANTILLAS ELEMENTOR IMPORTADAS\n";
$templates = get_posts([
    'post_type' => 'elementor_library',
    'posts_per_page' => -1,
    'post_status' => 'any',
]);

if (empty($templates)) {
    echo "   ⚠️  No hay plantillas importadas\n";
    $warnings[] = "Ninguna plantilla Elementor importada aún";
} else {
    foreach ($templates as $tpl) {
        $type = get_post_meta($tpl->ID, '_elementor_template_type', true);
        echo "   📄 [{$tpl->ID}] {$tpl->post_title} (tipo: $type, estado: {$tpl->post_status})\n";
    }
    echo "   Total: " . count($templates) . " plantillas\n";
}

// ── 7. Theme Builder (header/footer) ─────────────────────────
echo "\n7. THEME BUILDER\n";
if (defined('ELEMENTOR_PRO_VERSION')) {
    $theme_parts = get_posts([
        'post_type' => 'elementor_library',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_elementor_template_type',
                'value' => ['header', 'footer'],
                'compare' => 'IN',
            ],
        ],
    ]);
    if (empty($theme_parts)) {
        echo "   ⚠️  No hay Header ni Footer en Theme Builder\n";
        $warnings[] = "Header/Footer no configurados en Theme Builder";
    } else {
        foreach ($theme_parts as $tp) {
            $type = get_post_meta($tp->ID, '_elementor_template_type', true);
            $conditions = get_post_meta($tp->ID, '_elementor_conditions', true);
            echo "   $type: {$tp->post_title} (ID: {$tp->ID}, condiciones: " . json_encode($conditions) . ")\n";
        }
    }
} else {
    echo "   ⚠️  Sin Elementor Pro, no hay Theme Builder\n";
}

// ── 8. Páginas ───────────────────────────────────────────────
echo "\n8. PÁGINAS\n";
$front_page = get_option('page_on_front');
$show_on_front = get_option('show_on_front');
echo "   Portada: " . ($show_on_front === 'page' ? "Página estática (ID: $front_page)" : "Últimas entradas") . "\n";

$pages = get_pages(['sort_order' => 'ASC', 'sort_column' => 'post_title']);
foreach ($pages as $p) {
    $tpl = get_page_template_slug($p->ID);
    $elementor = get_post_meta($p->ID, '_elementor_edit_mode', true);
    echo "   [{$p->ID}] {$p->post_title} — tpl: " . ($tpl ?: 'default') . ($elementor ? ' [Elementor]' : '') . " ({$p->post_status})\n";
}

// ── 9. SVG Support ───────────────────────────────────────────
echo "\n9. SOPORTE SVG\n";
$svg_support = get_option('elementor_unfiltered_files_upload');
echo "   Subida de SVG (Elementor): " . ($svg_support ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
if (!$svg_support) {
    $warnings[] = "SVG deshabilitado — logo-blanco.svg no se podrá subir (Elementor → Ajustes → Avanzado → Subida de archivos sin filtrar)";
}

// ── RESUMEN ──────────────────────────────────────────────────
echo "\n" . str_repeat('=', 60) . "\n";
echo "  RESUMEN\n";
echo str_repeat('=', 60) . "\n";
echo "  ✅ OK: " . count($ok) . "\n";
foreach ($ok as $o) echo "     · $o\n";
echo "  ⚠️  Avisos: " . count($warnings) . "\n";
foreach ($warnings as $w) echo "     · $w\n";
echo "  ❌ Errores: " . count($errors) . "\n";
foreach ($errors as $e) echo "     · $e\n";

if (!empty($errors)) {
    echo "\n  ⛔ HAY ERRORES BLOQUEANTES — corrige antes de importar\n";
} elseif (!empty($warnings)) {
    echo "\n  ⚡ Avisos pendientes — revísalos pero puedes continuar\n";
} else {
    echo "\n  🎉 Todo listo para importar\n";
}
echo "\n";
