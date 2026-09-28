import subprocess
import json
import os

MYSQL_EXE = r"C:\xampp\mysql\bin\mysql.exe"
PHP_EXE = r"C:\xampp\php\php.exe"

print("1. Reading current _elementor_data...")
res = subprocess.run([
    MYSQL_EXE, "-u", "root", "teknopremium", "-e",
    "SELECT meta_value FROM wp_postmeta WHERE post_id = 9 AND meta_key = '_elementor_data';"
], capture_output=True, text=True, encoding="utf-8", errors="replace")

clean_json = res.stdout.split('\n', 1)[1]
clean_json = clean_json[clean_json.find('['):clean_json.rfind(']')+1]
data = json.loads(clean_json)

# Now every string in `data` might either have literal unicode characters OR literal \uXXXX strings if they were double-escaped.
# Let's walk `data` and make sure any string is fully decoded to clean Spanish UTF-8 unicode!
def decode_str(s):
    if not isinstance(s, str):
        return s
    # If s contains literal \u00ed or \u00f3 (e.g. backslash u 0 0 f 3), decode it
    try:
        # Replace common unicode escape sequences if stored as literal backslashes
        s = s.encode('utf-8').decode('unicode_escape')
    except Exception:
        pass
    return s

def clean_node(node):
    if isinstance(node, dict):
        for k, v in node.items():
            if isinstance(v, str):
                node[k] = decode_str(v)
            else:
                clean_node(v)
    elif isinstance(node, list):
        for i in range(len(node)):
            if isinstance(node[i], str):
                node[i] = decode_str(node[i])
            else:
                clean_node(node[i])

clean_node(data)

# Let's verify a few strings
print("Sample cleaned string section 1:")
print("Title:", data[0]['elements'][0]['elements'][0]['elements'][0]['settings'].get('title'))

# Now let's write a PHP script that loads this JSON cleanly and saves it via update_post_meta()
# We save `data` to a clean utf-8 json file first
json_path = r"C:\xampp\htdocs\Khumbu\teknopremium\home_data_clean.json"
with open(json_path, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

php_updater = """<?php
require __DIR__ . '/wp-load.php';

$json_file = __DIR__ . '/home_data_clean.json';
$raw_json = file_get_contents($json_file);
$data = json_decode($raw_json, true);

if (!$data) {
    echo "ERROR decoding JSON file!\\n";
    exit(1);
}

// In Elementor, _elementor_data is stored as a JSON string (unescaped unicode)
$json_str = json_encode($data, JSON_UNESCAPED_UNICODE);

// Save using native WordPress update_post_meta
update_post_meta(9, '_elementor_data', wp_slash($json_str));

// Also update post_content html fallback
$html_fallback = '<div class="luxury-home-fallback">
    <h1>TECNOPREMIUM · El Arte de Vivir el Exterior sin Límites</h1>
    <p>Ingeniería arquitectónica y diseño de excelencia en Jacuzzis de hidromasaje, Pérgolas bioclimáticas y Cocinas outdoor para villas residenciales y proyectos de alta gama.</p>
    <h2>FILOSOFÍA TECNOPREMIUM · Redefiniendo el Lujo en Espacios Exteriores</h2>
    <p>Concebimos cada área exterior como una extensión natural de la arquitectura interior. Seleccionamos piezas excepcionales que combinan hidromasaje terapéutico, aislamiento térmico ProLast™ y estética contemporánea para proyectos de nivel internacional.</p>
</div>';

wp_update_post([
    'ID' => 9,
    'post_content' => $html_fallback
]);

// Clear Elementor CSS cache
if (class_exists('\\Elementor\\Plugin')) {
    \\Elementor\\Plugin::$instance->files_manager->clear_cache();
}

echo "SUCCESS: Updated _elementor_data and cleared Elementor cache with pristine UTF-8 characters!\\n";
"""

php_path = r"C:\xampp\htdocs\Khumbu\teknopremium\update_home_utf8.php"
with open(php_path, "w", encoding="utf-8") as f:
    f.write(php_updater)

res_php = subprocess.run([PHP_EXE, "update_home_utf8.php"], cwd=r"C:\xampp\htdocs\Khumbu\teknopremium", capture_output=True, text=True, encoding="utf-8", errors="replace")
print("PHP STDOUT:", res_php.stdout)
if res_php.stderr:
    print("PHP STDERR:", res_php.stderr)

os.remove(json_path)
os.remove(php_path)
