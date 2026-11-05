<?php
/**
 * Router untuk server bawaan PHP (khusus pengembangan lokal).
 * Meniru aturan .htaccess karena `php -S` tidak membaca .htaccess.
 *
 *   php -S 127.0.0.1:8000 dev-router.php
 *
 * Tidak dipakai di hosting (Apache/Nginx) dan tidak berfungsi di sana.
 */
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$root = __DIR__;
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Folder sensitif & file PHP di uploads tidak boleh diakses dari browser.
if (preg_match('#^/(database|config|includes)(/|$)#', $path) || preg_match('#^/uploads/.*\.php$#i', $path)
    || preg_match('#/\.(?!well-known)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

$file = realpath($root . $path);
if ($path !== '/' && $file !== false && str_starts_with($file, $root)) {
    if (is_dir($file)) {
        foreach (['index.php', 'index.html'] as $index) {
            if (!is_file("$file/$index")) continue;
            if ($index === 'index.html') return false;
            chdir($file);
            require "$file/$index";
            return true;
        }
    }
    return false; // file statis/PHP biasa: biarkan server bawaan menanganinya
}

chdir($root);
require $root . '/index.php';
