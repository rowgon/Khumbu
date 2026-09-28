<?php
$pages = [
    '/about/' => 'Arquitectura & Bienestar',
    '/contact/' => 'Atención Arquitectónica',
    '/faq/' => 'Preguntas Frecuentes'
];

foreach ($pages as $uri => $check_str) {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/Khumbu/teknopremium' . $uri;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    ob_start();
    require __DIR__ . '/index.php';
    $html = ob_get_clean();

    echo "Page ($uri): " . (strpos($html, $check_str) !== false ? "SUCCESS ('$check_str' found!)" : "MISSING") . "\n";
}
