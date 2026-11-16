<?php
// Front controller publik: / dan /index.php => beranda, /{slug}.php => halaman lain (about.php, produk.php, dst).
// Halaman tetap digenerate ke public/home/index.html & public/pages/{slug}.html, lalu disajikan lewat URL .php.

function servePublicHtml(string $file, string $dir): void {
    $html = (string)file_get_contents($file);

    // Tautan/asset relatif -> absolut, karena URL sekarang berada di root.
    $html = preg_replace_callback('/\b(href|src)="([^"]*)"/i', function ($m) use ($dir) {
        $u = $m[2];
        if ($u === '' || preg_match('#^([a-z][a-z0-9+.-]*:|//|/|\#|\?)#i', $u)) return $m[0];
        $parts = [];
        foreach (explode('/', $dir . $u) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($parts); continue; }
            $parts[] = $seg;
        }
        return $m[1] . '="/' . implode('/', $parts) . (substr($u, -1) === '/' ? '/' : '') . '"';
    }, $html);

    // URL lama -> URL baru (.php)
    $html = preg_replace('#(["\'(])/public/home/(?:index\.html)?(?=["\'\#?)])#', '$1/index.php', $html);
    $html = preg_replace('#(["\'(])/public/pages/([A-Za-z0-9_%.-]+)\.html(?=["\'\#?)])#', '$1/$2.php', $html);

    // Form "Hubungi Kami" (tombol .nav-cta) -> WhatsApp
    $tag = '<script src="/public/assets/contact-modal.js" defer></script>';
    $html = stripos($html, '</body>') !== false ? preg_replace('#</body>#i', $tag . '</body>', $html, 1) : $html . $tag;

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$name = basename($path);
$slug = ($name === '' || $name === 'index.php') ? 'home' : (preg_match('/^([A-Za-z0-9_-]+)\.php$/', $name, $m) ? $m[1] : '');

if ($slug === 'home' && is_file(__DIR__ . '/public/home/index.html')) {
    servePublicHtml(__DIR__ . '/public/home/index.html', '/public/home/');
}
if ($slug !== '' && $slug !== 'home' && is_file($f = __DIR__ . '/public/pages/' . $slug . '.html')) {
    servePublicHtml($f, '/public/pages/');
}

http_response_code(404);
echo '<!DOCTYPE html><meta charset="utf-8"><title>404</title><body style="font-family:sans-serif;text-align:center;padding:80px"><h1>404</h1><p>Halaman tidak ditemukan. <a href="/index.php">Kembali ke beranda</a></p>';
