<?php
/**
 * Cek & perbaiki izin folder. Jalankan SEKALI setelah upload:
 *   https://domain-anda.com/fix-permissions.php
 * Setelah semua [OK], HAPUS file ini dari server.
 */
header('Content-Type: text/html; charset=utf-8');

// Folder yang ditulis oleh CMS:
// - database/      data JSON (pengganti MySQL)
// - uploads/       gambar yang diunggah
// - public/pages/  halaman hasil generate (produk, blog)
// - public/blog/   halaman artikel hasil generate
// - public/home/   navbar beranda diperbarui otomatis (index.html)
$dirs = ['database', 'uploads', 'public/pages', 'public/blog', 'public/home'];
$ok = true;

echo "<pre style='font-family:monospace;padding:20px;line-height:1.6'>";
echo "PT MBG CMS — Pemeriksa Izin Folder\n==================================\n\n";
echo 'PHP ' . PHP_VERSION . (version_compare(PHP_VERSION, '8.1.0', '>=') ? '  [OK]' : '  [FAIL] butuh PHP 8.1 atau lebih baru') . "\n";
if (version_compare(PHP_VERSION, '8.1.0', '<')) $ok = false;
foreach (['json', 'mbstring', 'fileinfo', 'session'] as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '[OK]   ' : '[FAIL] ') . "ekstensi $ext\n";
    if (!$loaded) $ok = false;
}
echo "\n";

foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    if (!is_dir($path)) @mkdir($path, 0755, true);
    if (!is_writable($path)) @chmod($path, 0775);
    $writable = is_dir($path) && is_writable($path);
    $test = $path . '/_write_test.tmp';
    $canWrite = $writable && @file_put_contents($test, 'ok') !== false;
    if ($canWrite) @unlink($test);
    echo ($canWrite ? '[OK]   ' : '[FAIL] ') . str_pad($d . '/', 16) . ($canWrite ? 'bisa ditulis' : 'TIDAK bisa ditulis') . "\n";
    if (!$canWrite) $ok = false;
}

echo "\n";
if ($ok) {
    echo "BERHASIL — hapus file ini (fix-permissions.php) sekarang.\n";
    echo "Lanjut login: /admin/login.php\n";
} else {
    echo "Ada yang GAGAL. Perbaiki lewat File Manager (izin 755/775) atau terminal:\n";
    echo "  chmod -R 775 database uploads public/pages public/blog public/home\n";
    echo "  chown -R www-data:www-data database uploads public   # sesuaikan user web server\n";
}
echo "</pre>";
