<?php
$storage = __DIR__ . '/wp-content/plugins/all-in-one-wp-migration/storage';
$backups = __DIR__ . '/wp-content/ai1wm-backups';

if (!file_exists($backups)) {
    mkdir($backups, 0777, true);
}

chmod($storage, 0777);
chmod($storage . '/.htaccess', 0666);
chmod($storage . '/index.php', 0666);
chmod($storage . '/index.html', 0666);
chmod($storage . '/web.config', 0666);
chmod($backups, 0777);

echo "Permissions fixed.";
