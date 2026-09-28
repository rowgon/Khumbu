import subprocess
import os

php_trace = """<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Khumbu/teknopremium/';
$_SERVER['REQUEST_METHOD'] = 'GET';

define('WP_USE_THEMES', true);
require __DIR__ . '/wp-load.php';

add_filter('template_include', function($template) {
    echo "TEMPLATE SELECTED: " . $template . "\\n";
    return $template;
}, 9999);

wp();
require_once ABSPATH . WPINC . '/template-loader.php';
"""

with open(r"C:\xampp\htdocs\Khumbu\teknopremium\trace_tpl.php", "w") as f:
    f.write(php_trace)

res = subprocess.run(
    [r"C:\xampp\php\php.exe", "trace_tpl.php"],
    cwd=r"C:\xampp\htdocs\Khumbu\teknopremium",
    capture_output=True,
    text=True
)
print("STDOUT:", res.stdout)
print("STDERR:", res.stderr)
os.remove(r"C:\xampp\htdocs\Khumbu\teknopremium\trace_tpl.php")
