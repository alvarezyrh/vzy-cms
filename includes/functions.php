<?php
/**
 * Core Helper Functions — File-based storage
 */

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getSetting(string $key, string $default = ''): string {
    $row = findOne('settings', fn($r) => $r['setting_key'] === $key);
    return $row ? (string)$row['setting_value'] : $default;
}

function setSetting(string $key, string $value): void {
    $data = loadData('settings');
    $found = false;
    foreach ($data as &$row) {
        if ($row['setting_key'] === $key) {
            $row['setting_value'] = $value;
            $row['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    unset($row);
    if (!$found) {
        $data[] = [
            'id' => nextId('settings'),
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
    saveData('settings', $data);
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $user = findById('users', (int)$_SESSION['user_id']);
        if ($user) {
            unset($user['password']);
        }
    }
    return $user;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        flash('error', 'Akses ditolak. Hanya Admin yang bisa mengakses halaman ini.');
        redirect('index.php');
    }
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', $time);
}

function formatDate(?string $datetime): string {
    if (!$datetime) return '-';
    return date(getSetting('date_format', 'd M Y'), strtotime($datetime));
}

function truncate(string $text, int $length = 100): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

function uploadFile(array $file, string $folder = 'uploads'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    $dir = __DIR__ . '/../' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $path = $dir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $folder . '/' . $filename;
    }
    return null;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function uniqueSlug(string $table, string $slug, int $excludeId = 0): string {
    $base = $slug;
    $i = 1;
    while (findOne($table, fn($r) => $r['slug'] === $slug && (int)$r['id'] !== $excludeId)) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function logActivity(string $action, string $detail = '', ?string $username = null): void {
    try {
        $user = currentUser();
        $data = loadData('activity');
        array_unshift($data, [
            'id' => empty($data) ? 1 : (max(array_column($data, 'id') ?: [0]) + 1),
            'user_id' => $user['id'] ?? 0,
            'username' => $username ?? ($user['username'] ?? 'system'),
            'action' => $action,
            'detail' => $detail,
            'created_at' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $data = array_slice($data, 0, 200);
        saveData('activity', $data);
    } catch (Throwable $e) {
        // never block login/actions because of logging
    }
}

function paginate(array $items, int $page = 1, int $perPage = 10): array {
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return [
        'items' => array_slice($items, $offset, $perPage),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
    ];
}

function paginationHtml(array $pag, string $baseUrl): string {
    if ($pag['pages'] <= 1) return '';
    $html = '<div class="btn-group" style="margin-top:16px;flex-wrap:wrap">';
    for ($i = 1; $i <= $pag['pages']; $i++) {
        $active = $i === $pag['page'] ? 'btn-primary' : 'btn-secondary';
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        $html .= '<a href="' . e($baseUrl . $sep . 'p=' . $i) . '" class="btn btn-sm ' . $active . '">' . $i . '</a>';
    }
    $html .= '</div>';
    return $html;
}

function mediaUrl(?string $path): string {
    if (!$path) return '';
    if (str_starts_with($path, 'http')) return $path;
    return '../' . ltrim($path, '/');
}

/**
 * Persist dashboard pages as standalone public HTML files.
 */
function publicPagePath(string $slug): string {
    return __DIR__ . '/../public/pages/' . slugify($slug) . '.html';
}

function publicPageUrl(array $page): string {
    return ($page['slug'] ?? '') === 'home'
        ? '/public/home/'
        : '/public/pages/' . rawurlencode($page['slug'] ?? '') . '.html';
}

function publicCatalogNavigation(): string {
    $brand = getSetting('nav_brand', 'PT MBG');
    $logoMark = getSetting('nav_logo_mark', 'MBG');
    $ctaLabel = getSetting('nav_cta_label', 'Hubungi Kami');
    $ctaUrl = getSetting('nav_cta_url', '#contact');
    if (str_starts_with($ctaUrl, '#')) $ctaUrl = '/public/home/index.html' . $ctaUrl;

    $menus = loadData('menus');
    $menu = $menus[0] ?? null;
    $items = $menu ? findWhere('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === (int)$menu['id']) : [];
    usort($items, fn($a, $b) => ((int)($a['order_num'] ?? 0)) <=> ((int)($b['order_num'] ?? 0)));

    $links = '';
    foreach ($items as $item) {
        $url = (string)($item['url'] ?? '');
        if ($url === '') continue;
        if (str_starts_with($url, '#')) $url = '/public/home/index.html' . $url;
        $links .= '<a href="' . e($url) . '">' . e((string)($item['title'] ?? '')) . '</a>';
    }
    $links .= '<a href="' . e($ctaUrl) . '" class="nav-cta">' . e($ctaLabel) . '</a>';

    return '<div class="nav-wrap"><nav class="nav" aria-label="Navigasi utama">'
        . '<a href="/public/home/" class="nav-logo"><span class="nav-logo-mark">' . e($logoMark) . '</span>' . e($brand) . '</a>'
        . '<div class="nav-links" id="navLinks">' . $links . '</div>'
        . '<button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><i class="fas fa-bars"></i></button>'
        . '</nav></div>'
        . '<script>(function(){var toggle=document.querySelector(".nav-toggle");var links=document.querySelector(".nav-links");var nav=document.querySelector(".nav");if(toggle&&links){toggle.addEventListener("click",function(){var open=links.classList.toggle("open");toggle.setAttribute("aria-expanded",open?"true":"false");});links.querySelectorAll("a").forEach(function(link){link.addEventListener("click",function(){links.classList.remove("open");toggle.setAttribute("aria-expanded","false");});});}if(nav){var update=function(){nav.classList.toggle("scrolled",window.scrollY>40);};window.addEventListener("scroll",update,{passive:true});update();}})();</script>';
}

function publicCatalogFooter(): string {
    $brand = getSetting('nav_brand', 'PT MBG');
    $logoMark = getSetting('nav_logo_mark', 'MBG');
    $whatsappNumber = '6281313616756';

    return '<footer class="footer"><div class="container"><div class="footer-grid">'
        . '<div class="footer-brand"><a href="/public/home/" class="nav-logo"><span class="nav-logo-mark">' . e($logoMark) . '</span>' . e($brand) . '</a>'
        . '<p>Mitra teknologi untuk solusi software, konsultasi IT, dan kebutuhan produk digital bisnis Anda.</p></div>'
        . '<div><h4>Navigasi</h4><ul><li><a href="/public/home/index.html#about">Tentang Kami</a></li><li><a href="/public/home/index.html#services">Layanan</a></li><li><a href="/public/pages/produk.html">Katalog Produk</a></li></ul></div>'
        . '<div><h4>Produk</h4><ul><li><a href="/public/pages/produk.html">Windows 11</a></li><li><a href="/public/pages/produk.html">Kaspersky Antivirus</a></li><li><a href="/public/pages/produk.html">Google Cloud &amp; Microsoft 365</a></li></ul></div>'
        . '<div><h4>Kontak</h4><ul><li><a href="mailto:hello@vzytech.id">hello@vzytech.id</a></li><li><a href="https://wa.me/' . e($whatsappNumber) . '" target="_blank" rel="noopener noreferrer">WhatsApp</a></li><li>Jakarta, Indonesia</li></ul></div>'
        . '</div><div class="footer-bottom"><span>&copy; ' . date('Y') . ' PT MBG. All rights reserved.</span></div></div></footer>';
}

function syncPageToPublic(array $page): bool {
    $slug = trim((string)($page['slug'] ?? ''));
    if ($slug === '') return false;

    $dir = __DIR__ . '/../public/pages';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return false;

    $title = e((string)($page['seo_title'] ?? $page['title'] ?? 'Page'));
    $description = e((string)($page['seo_description'] ?? ''));
    $hasProductCatalog = stripos((string)($page['content'] ?? ''), '[product_catalog]') !== false;
    $content = str_ireplace('[product_catalog]', renderProductCatalog(), (string)($page['content'] ?? ''));
    $css = (string)($page['custom_css'] ?? '');
    $js = (string)($page['custom_js'] ?? '');
    $catalogNavigation = $hasProductCatalog ? publicCatalogNavigation() . "\n" : '';
    $catalogFooter = $hasProductCatalog ? "\n" . publicCatalogFooter() : '';
    $bodyClass = $hasProductCatalog ? ' class="catalog-page"' : '';
    $html = "<!DOCTYPE html>\n<html lang=\"id\">\n<head>\n"
        . "<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
        . '<title>' . $title . "</title>\n"
        . '<meta name="description" content="' . $description . '">' . "\n"
        . "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css\">\n"
        . "<link rel=\"stylesheet\" href=\"../home/css/style.css\">\n"
        . '<style>' . $css . "</style>\n</head>\n<body" . $bodyClass . ">\n"
        . $catalogNavigation
        . $content
        . $catalogFooter
        . "\n<script>" . $js . "</script>\n</body>\n</html>\n";

    return file_put_contents(publicPagePath($slug), $html, LOCK_EX) !== false;
}

function productImageUrlForPage(string $image): string {
    $image = trim($image);
    if (preg_match('#^https?://#i', $image) || str_starts_with($image, '/')) return $image;
    if (str_starts_with($image, 'uploads/')) return '../../' . $image;
    return '';
}

function renderProductCatalog(): string {
    $products = findWhere('products', fn($product) => ($product['status'] ?? '') === 'published');
    usort($products, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $whatsappNumber = '6281313616756';

    $html = '<style>'
        . '.catalog-page{padding-top:88px;background:var(--off-white)}'
        // Background: soft color blobs + dot grid so the page doesn't look plain.
        . '.cms-product-catalog{position:relative;isolation:isolate;overflow:hidden;padding:56px 0 80px;background-color:#f0fdfa;background-image:radial-gradient(circle,rgba(13,148,136,.16) 1.5px,transparent 1.75px),linear-gradient(180deg,#ecfeff 0%,#f0fdfa 45%,#ffffff 100%);background-size:24px 24px,100% 100%}'
        . '.cms-product-catalog::before,.cms-product-catalog::after{content:"";position:absolute;z-index:-1;border-radius:50%;filter:blur(70px);pointer-events:none}'
        . '.cms-product-catalog::before{width:520px;height:520px;top:-180px;left:-160px;background:rgba(45,212,191,.35)}'
        . '.cms-product-catalog::after{width:560px;height:560px;bottom:-220px;right:-180px;background:rgba(74,222,128,.25)}'
        . '.cms-product-catalog .section-header{margin-bottom:40px}'
        . '.cms-product-catalog .section-desc{max-width:560px;margin-left:auto;margin-right:auto}'
        // Grid: 4 columns on wide screens, 2 on tablet, 1 on phone.
        . '.cms-product-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px;align-items:stretch}'
        . '@media(max-width:1100px){.cms-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}'
        . '@media(max-width:600px){.cms-product-grid{grid-template-columns:1fr}}'
        . '.cms-product-card{display:flex;flex-direction:column;padding:0;min-width:0;overflow:hidden;border-radius:20px;background:rgba(255,255,255,.92);border:1px solid rgba(13,148,136,.14);box-shadow:0 12px 32px rgba(15,23,42,.06);backdrop-filter:blur(6px)}'
        . '.cms-product-catalog .cms-product-card{transition:transform .25s ease,box-shadow .25s ease}'
        . '.cms-product-catalog .cms-product-card:hover{transform:translateY(-4px);box-shadow:0 20px 44px rgba(13,148,136,.16)}'
        . '.cms-product-catalog .cms-product-card::before{display:none;animation:none}'
        . '.cms-product-image,.cms-product-placeholder{display:block;width:100%;aspect-ratio:16/10;object-fit:cover;background:linear-gradient(135deg,rgba(13,148,136,.12),rgba(22,163,74,.1));border-bottom:1px solid rgba(13,148,136,.1)}'
        . '.cms-product-placeholder{display:grid;place-items:center;color:var(--turquoise);font-size:42px}'
        . '.cms-product-info{display:flex;flex:1;flex-direction:column;padding:20px 20px 22px}'
        . '.cms-product-info h3{font-family:var(--font-display);font-size:1.12rem;line-height:1.35;margin:0 0 8px;color:var(--slate)}'
        . '.cms-product-description{color:var(--muted);font-size:.9rem;line-height:1.6;margin:0}'
        . '.cms-product-price{display:none}'
        // Order row: quantity stepper (fixed width) + order button (fills the rest), same height.
        . '.cms-product-order{margin-top:auto;padding-top:18px}'
        . '.cms-product-order-label{display:block;margin:0 0 8px;padding-top:14px;border-top:1px dashed rgba(13,148,136,.18);color:var(--muted);font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}'
        . '.cms-product-order-row{display:grid;grid-template-columns:116px minmax(0,1fr);gap:10px;align-items:stretch}'
        . '.cms-qty{display:grid;grid-template-columns:34px minmax(0,1fr) 34px;height:44px;border:1px solid rgba(13,148,136,.22);border-radius:12px;background:#fff;overflow:hidden}'
        . '.cms-qty:focus-within{border-color:var(--turquoise);box-shadow:0 0 0 3px rgba(13,148,136,.12)}'
        . '.cms-qty-btn{border:0;background:#f0fdfa;color:var(--turquoise-dark);font-size:1.05rem;font-weight:700;cursor:pointer;line-height:1}'
        . '.cms-qty-btn:hover{background:#ccfbf1}'
        . '.cms-product-quantity{width:100%;min-width:0;height:100%;padding:0 2px;border:0;background:transparent;font:700 .95rem var(--font);color:var(--slate);text-align:center;outline:none;-moz-appearance:textfield;appearance:textfield}'
        . '.cms-product-quantity::-webkit-outer-spin-button,.cms-product-quantity::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}'
        . '.cms-product-order-link{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:44px;padding:0 14px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--turquoise),#2dd4bf);color:#fff;text-decoration:none;font:700 .9rem var(--font);white-space:nowrap;cursor:pointer;box-shadow:0 10px 20px rgba(13,148,136,.2);transition:transform .2s ease,box-shadow .2s ease,background .2s ease}'
        . '.cms-product-order-link i{font-size:1.05rem}'
        . '.cms-product-catalog .cms-product-order-link:hover{background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;transform:translateY(-1px);box-shadow:0 12px 24px rgba(13,148,136,.26)}'
        . '.cms-product-empty{grid-column:1/-1;color:var(--muted);padding:28px;border:1px dashed var(--border);border-radius:var(--radius-sm);text-align:center}'
        . '.cms-order-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.45);z-index:1000;padding:20px}'
        . '.cms-order-modal.is-open{display:flex}'
        . '.cms-order-modal-panel{position:relative;width:min(100%,480px);background:linear-gradient(180deg,#ffffff,#f8fafc);border:1px solid rgba(148,163,184,.25);border-radius:22px;padding:24px;box-shadow:0 28px 70px rgba(15,23,42,.2)}'
        . '.cms-order-modal-close{position:absolute;top:12px;right:12px;width:36px;height:36px;border:0;border-radius:50%;background:#e2e8f0;color:#0f172a;font-size:1.5rem;cursor:pointer;line-height:1}'
        . '.cms-order-form{display:grid;gap:16px}'
        . '.cms-order-form h3{margin:0 0 8px;font-size:1.45rem;color:#0f172a}'
        . '.cms-order-form .field{display:grid;gap:7px}'
        . '.cms-order-form label{font-size:.78rem;font-weight:700;color:#334155}'
        . '.cms-order-form input,.cms-order-form textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:10px 12px;font:inherit;background:#fff;box-shadow:inset 0 1px 2px rgba(15,23,42,.04)}'
        . '.cms-order-form textarea{min-height:96px;resize:vertical}'
        . '.cms-order-form .submit-row{display:flex;justify-content:flex-end;margin-top:8px}'
        . '.cms-order-form .btn-submit{border:0;border-radius:12px;background:linear-gradient(135deg,var(--turquoise),#2dd4bf);color:#fff;padding:12px 18px;font-weight:700;cursor:pointer;box-shadow:0 12px 24px rgba(13,148,136,.2)}'
        . '@media(max-width:600px){.cms-product-catalog{padding:40px 0 64px}.cms-product-info{padding:18px}.cms-order-modal-panel{padding:20px}}'
        . '</style><section class="section cms-product-catalog"><div class="container">'
        . '<header class="section-header center"><div class="section-label">Solusi Digital</div>'
        . '<h1 class="section-title">Katalog Produk</h1>'
        . '<p class="section-desc">Temukan produk dan layanan digital yang tepat untuk kebutuhan Anda.</p></header>';

    if (!$products) return $html . '<p class="cms-product-empty">Produk belum tersedia.</p></div></section>';

    $html .= '<div class="cms-product-grid">';
    foreach ($products as $product) {
        $name = e((string)($product['name'] ?? ''));
        $image = productImageUrlForPage((string)($product['image'] ?? ''));
        $html .= '<article class="cms-product-card service-card">';
        if ($image !== '') {
            $html .= '<img class="cms-product-image" src="' . e($image) . '" alt="' . $name . '" loading="lazy">';
        } else {
            $html .= '<div class="cms-product-placeholder" aria-hidden="true"><i class="fas fa-cube"></i></div>';
        }
        $html .= '<div class="cms-product-info"><h3>' . $name . '</h3>';
        if (!empty($product['description'])) {
            $html .= '<p class="cms-product-description">' . nl2br(e((string)$product['description'])) . '</p>';
        }
        $html .= '<div class="cms-product-order">'
            . '<span class="cms-product-order-label">Jumlah pesanan</span>'
            . '<div class="cms-product-order-row">'
            . '<div class="cms-qty">'
            . '<button class="cms-qty-btn" type="button" data-step="-1" aria-label="Kurangi jumlah ' . $name . '">&minus;</button>'
            . '<input class="cms-product-quantity" type="number" min="1" step="1" value="1" inputmode="numeric" aria-label="Jumlah pesanan ' . $name . '">'
            . '<button class="cms-qty-btn" type="button" data-step="1" aria-label="Tambah jumlah ' . $name . '">+</button>'
            . '</div>'
            . '<button class="cms-product-order-link" type="button" data-product="' . $name . '"><i class="fab fa-whatsapp" aria-hidden="true"></i>Pesan</button>'
            . '</div></div>';
        $html .= '</div></article>';
    }
    $html .= '</div>'
        . '<div class="cms-order-modal" id="cmsOrderModal" aria-hidden="true">'
        . '<div class="cms-order-modal-panel" role="dialog" aria-modal="true" aria-labelledby="cmsOrderModalTitle">'
        . '<button type="button" class="cms-order-modal-close" aria-label="Tutup">&times;</button>'
        . '<form class="cms-order-form">'
        . '<h3 id="cmsOrderModalTitle">Pesan Produk</h3>'
        . '<div class="field"><label for="cmsOrderProduct">Produk</label><input id="cmsOrderProduct" name="product" type="text" readonly></div>'
        . '<div class="field"><label for="cmsOrderQuantity">Jumlah</label><input id="cmsOrderQuantity" name="quantity" type="number" min="1" step="1" value="1" required></div>'
        . '<div class="field"><label for="cmsOrderName">Nama Anda</label><input id="cmsOrderName" name="name" type="text" placeholder="Masukkan nama" required></div>'
        . '<div class="field"><label for="cmsOrderPhone">WhatsApp / Nomor HP</label><input id="cmsOrderPhone" name="phone" type="tel" placeholder="08xxxxxxxxxx" required></div>'
        . '<div class="field"><label for="cmsOrderNotes">Catatan (opsional)</label><textarea id="cmsOrderNotes" name="notes" placeholder="Tulis catatan tambahan..."></textarea></div>'
        . '<div class="submit-row"><button type="submit" class="btn-submit">Kirim ke WhatsApp</button></div>'
        . '</form></div></div>'
        . '<script>(function(){var modal=document.getElementById("cmsOrderModal");var form=modal?modal.querySelector(".cms-order-form"):null;var productInput=modal?modal.querySelector("input[name=\"product\"]"):null;var quantityInput=modal?modal.querySelector("input[name=\"quantity\"]"):null;var nameInput=modal?modal.querySelector("input[name=\"name\"]"):null;var phoneInput=modal?modal.querySelector("input[name=\"phone\"]"):null;var notesInput=modal?modal.querySelector("textarea[name=\"notes\"]"):null;var whatsappNumber="' . e($whatsappNumber) . '";function closeModal(){if(!modal)return;modal.classList.remove("is-open");modal.setAttribute("aria-hidden","true");}document.querySelectorAll(".cms-qty-btn").forEach(function(btn){btn.addEventListener("click",function(){var input=btn.parentNode.querySelector(".cms-product-quantity");var next=(parseInt(input.value,10)||1)+parseInt(btn.dataset.step,10);input.value=Math.max(1,next);});});document.querySelectorAll(".cms-product-order-link").forEach(function(link){link.addEventListener("click",function(){if(!modal||!productInput||!quantityInput||!nameInput||!phoneInput)return;var quantity=Math.max(1,parseInt(link.closest(".cms-product-order").querySelector(".cms-product-quantity").value,10)||1);productInput.value=link.dataset.product||"Produk";quantityInput.value=quantity;nameInput.value="";phoneInput.value="";if(notesInput){notesInput.value="";}modal.classList.add("is-open");modal.setAttribute("aria-hidden","false");setTimeout(function(){nameInput.focus();},50);});});modal&&modal.querySelector(".cms-order-modal-close")&&modal.querySelector(".cms-order-modal-close").addEventListener("click",closeModal);modal&&modal.addEventListener("click",function(event){if(event.target===modal){closeModal();}});form&&form.addEventListener("submit",function(event){event.preventDefault();var product=(productInput.value||"Produk").trim();var quantity=Math.max(1,parseInt(quantityInput.value||"1",10)||1);var name=(nameInput.value||"").trim();var phone=(phoneInput.value||"").trim();var notes=(notesInput?notesInput.value:" ").trim();var message=["Halo, saya ingin memesan *"+product+"* sebanyak "+quantity+".","Nama: "+name,"WhatsApp: "+phone,notes?"Catatan: "+notes:""];window.open("https://wa.me/"+whatsappNumber+"?text="+encodeURIComponent(message.filter(Boolean).join("\n")),"_blank","noopener,noreferrer");closeModal();});})();</script>';
    return $html . '</div></section>';
}

function syncProductCatalogPages(): bool {
    $success = true;
    foreach (loadData('pages') as $page) {
        if (($page['status'] ?? '') === 'trash' || stripos((string)($page['content'] ?? ''), '[product_catalog]') === false) continue;
        if (!syncPageToPublic($page)) $success = false;
    }
    return $success;
}

function removePageFromPublic(string $slug): void {
    $path = publicPagePath($slug);
    if (is_file($path)) unlink($path);
}

function syncPageMenuItem(array $page): void {
    foreach (findWhere('menu_items', fn($item) => ($item['type'] ?? '') === 'page'
        && (int)($item['object_id'] ?? 0) === (int)($page['id'] ?? 0)) as $item) {
        update('menu_items', (int)$item['id'], [
            'title' => $page['title'] ?? '',
            'url' => publicPageUrl($page),
        ]);
    }
}

function ensureHomePageMenu(): void {
    $home = findOne('pages', fn($page) => ($page['slug'] ?? '') === 'home');
    if (!$home) {
        $now = date('Y-m-d H:i:s');
        $homeId = insert('pages', [
            'title' => 'Home',
            'slug' => 'home',
            'content' => '<p>Homepage utama PT MBG.</p>',
            'custom_css' => '',
            'custom_js' => '',
            'status' => 'published',
            'is_frontpage' => 1,
            'author_id' => 1,
            'seo_title' => 'PT MBG — Software · Consultant · Education',
            'seo_description' => 'PT MBG — Solusi IT Software, IT Consultant, dan Pendidikan Digital.',
            'seo_keywords' => 'IT software, IT consultant, pendidikan digital',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $home = findById('pages', $homeId);
    }

    $menus = loadData('menus');
    $menu = $menus[0] ?? null;
    if (!$menu) {
        $menuId = insert('menus', ['name' => 'Primary Menu', 'location' => 'primary', 'created_at' => date('Y-m-d H:i:s')]);
    } else {
        $menuId = (int)$menu['id'];
    }

    $homeItem = findOne('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === $menuId
        && (($item['type'] ?? '') === 'page' && (int)($item['object_id'] ?? 0) === (int)$home['id']
            || ($item['url'] ?? '') === '/public/home/'));
    if (!$homeItem) {
        $items = findWhere('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === $menuId);
        foreach ($items as $item) update('menu_items', (int)$item['id'], ['order_num' => (int)($item['order_num'] ?? 0) + 1]);
        insert('menu_items', [
            'menu_id' => $menuId,
            'parent_id' => 0,
            'title' => 'Home',
            'url' => '/public/home/',
            'type' => 'page',
            'object_id' => (int)$home['id'],
            'order_num' => 1,
            'target' => '_self',
        ]);
    }
}

function syncWebsiteNavigation(): void {
    $path = __DIR__ . '/../public/home/index.html';
    if (!is_file($path)) return;

    $html = file_get_contents($path);
    if ($html === false) return;

    $brand = getSetting('nav_brand', 'PT MBG');
    $logoMark = getSetting('nav_logo_mark', 'MBG');
    $ctaLabel = getSetting('nav_cta_label', 'Hubungi Kami');
    $ctaUrl = getSetting('nav_cta_url', '#contact');
    $brandHtml = '<span class="nav-logo-mark">' . e($logoMark) . '</span>' . "\n        " . e($brand);

    $menus = loadData('menus');
    $menu = $menus[0] ?? null;
    $items = $menu ? findWhere('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === (int)$menu['id']) : [];
    usort($items, fn($a, $b) => ((int)($a['order_num'] ?? 0)) <=> ((int)($b['order_num'] ?? 0)));
    $links = '';
    foreach ($items as $item) {
        if (($item['url'] ?? '') === '') continue;
        $links .= '<a href="' . e($item['url']) . '">' . e($item['title'] ?? '') . '</a>' . "\n        ";
    }
    $links .= '<a href="' . e($ctaUrl) . '" class="nav-cta">' . e($ctaLabel) . '</a>';

    $html = preg_replace(
        '/<!-- CMS_NAV_BRAND_START -->.*?<!-- CMS_NAV_BRAND_END -->/s',
        '<!-- CMS_NAV_BRAND_START -->' . $brandHtml . '<!-- CMS_NAV_BRAND_END -->',
        $html,
        1
    );
    $html = preg_replace(
        '/<!-- CMS_NAV_LINKS_START -->.*?<!-- CMS_NAV_LINKS_END -->/s',
        '<!-- CMS_NAV_LINKS_START -->' . "\n        " . $links . "\n        " . '<!-- CMS_NAV_LINKS_END -->',
        $html,
        1
    );
    file_put_contents($path, $html, LOCK_EX);
}

function seedWebsiteNavigation(): void {
    if (getSetting('navigation_seeded', '0') === '1') return;
    $menus = loadData('menus');
    $menu = $menus[0] ?? null;
    if (!$menu) return;
    $menuId = (int)$menu['id'];
    $defaults = [
        ['title' => 'Tentang', 'url' => '#about'],
        ['title' => 'Layanan', 'url' => '#services'],
        ['title' => 'Proses', 'url' => '#process'],
        ['title' => 'Klien', 'url' => '#testimonials'],
    ];
    $items = findWhere('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === $menuId);
    $order = empty($items) ? 0 : max(array_map(fn($item) => (int)($item['order_num'] ?? 0), $items));
    foreach ($defaults as $default) {
        if (findOne('menu_items', fn($item) => (int)($item['menu_id'] ?? 0) === $menuId && ($item['url'] ?? '') === $default['url'])) continue;
        insert('menu_items', [
            'menu_id' => $menuId, 'parent_id' => 0, 'title' => $default['title'],
            'url' => $default['url'], 'type' => 'custom', 'object_id' => null,
            'order_num' => ++$order, 'target' => '_self',
        ]);
    }
    setSetting('navigation_seeded', '1');
}
