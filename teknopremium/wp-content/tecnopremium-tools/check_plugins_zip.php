<?php
$dir = 'C:/Users/roman/Downloads';
$files = glob("$dir/*.zip");
echo "=== ZIP FILES IN DOWNLOADS ===\n";
foreach ($files as $f) {
    echo "- " . basename($f) . "\n";
}

$plugins_zip = 'C:/Users/roman/Downloads/_plugins.zip';
if (file_exists($plugins_zip)) {
    echo "\n=== CONTENTS OF _plugins.zip ===\n";
    $zip = new ZipArchive();
    if ($zip->open($plugins_zip) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr_count($name, '/') <= 1) {
                echo "  $name\n";
            }
        }
        $zip->close();
    }
}
