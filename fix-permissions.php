<?php
/**
 * Run once after extract: open http://localhost/cms/fix-permissions.php
 * Then DELETE this file.
 */
$dirs = ['database', 'uploads'];
$ok = true;
echo "<pre style='font-family:monospace;padding:20px'>";
echo "CMS Permission Fixer\n====================\n\n";
foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    if (!is_dir($path)) mkdir($path, 0755, true);
    $writable = is_writable($path);
    if (!$writable) {
        @chmod($path, 0777);
        $writable = is_writable($path);
    }
    echo ($writable ? '[OK] ' : '[FAIL] ') . $d . '/ writable=' . ($writable ? 'yes' : 'no') . "\n";
    if (!$writable) $ok = false;
}
// touch test
$test = __DIR__ . '/database/_write_test.json';
$w = @file_put_contents($test, '{}');
echo ($w !== false ? '[OK] ' : '[FAIL] ') . "write test database/\n";
if ($w !== false) @unlink($test);

echo "\n";
if ($ok && $w !== false) {
    echo "SUCCESS — hapus file ini (fix-permissions.php) sekarang.\n";
    echo "Lanjut login: admin/login.php  (admin / admin123)\n";
} else {
    echo "GAGAL — jalankan manual di terminal:\n";
    echo "  chmod -R 775 database uploads\n";
    echo "  chown -R www-data:www-data database uploads\n";
}
echo "</pre>";
