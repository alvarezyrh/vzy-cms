<?php
// Direct visitors to the admin login instead of rendering the public homepage.
header('Location: admin/login.php');
exit;

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
initDatabase();

$seoList = loadData('seo_settings');
$seo = $seoList[0] ?? [];
$siteName = getSetting('site_name', 'My CMS');
$siteTagline = getSetting('site_tagline', '');

$menus = loadData('menus');
$menu = $menus[0] ?? null;
$menuItems = [];
if ($menu) {
    $menuItems = findWhere('menu_items', fn($r) => (int)$r['menu_id'] === (int)$menu['id']);
    usort($menuItems, fn($a, $b) => ($a['order_num'] ?? 0) <=> ($b['order_num'] ?? 0));
}

$uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$base = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base && str_starts_with($uri, $base)) {
    $uri = trim(substr($uri, strlen($base)), '/');
}
$parts = $uri ? explode('/', $uri) : [];
$query = trim($_GET['q'] ?? '');

$page = null;
$post = null;
$posts = null;
$archiveTitle = null;
$view = 'home';

// Handle comment submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'comment') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid token');
    } else {
        $pid = (int)($_POST['post_id'] ?? 0);
        $name = trim($_POST['author_name'] ?? '');
        $email = trim($_POST['author_email'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($pid && $name && $content) {
            insert('comments', [
                'post_id' => $pid,
                'author_name' => $name,
                'author_email' => $email,
                'content' => $content,
                'status' => getSetting('allow_comments', '1') === '1' ? 'pending' : 'spam',
                'created_at' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            flash('success', 'Komentar terkirim dan menunggu moderasi.');
        }
    }
    redirect($_SERVER['REQUEST_URI'] ?? '/');
}

if (($parts[0] ?? '') === 'search' || $query !== '') {
    $view = 'search';
    $q = $query ?: ($parts[1] ?? '');
    $archiveTitle = 'Search: ' . $q;
    $all = findWhere('posts', function ($p) use ($q) {
        if (($p['status'] ?? '') !== 'published') return false;
        $hay = strtolower(($p['title'] ?? '') . ' ' . ($p['content'] ?? '') . ' ' . ($p['excerpt'] ?? ''));
        return $q !== '' && str_contains($hay, strtolower($q));
    });
    usort($all, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
    $posts = $all;
} elseif (($parts[0] ?? '') === 'category' && isset($parts[1])) {
    $view = 'category';
    $cat = findOne('categories', fn($c) => ($c['slug'] ?? '') === $parts[1]);
    $archiveTitle = $cat['name'] ?? 'Category';
    $cid = $cat['id'] ?? 0;
    $all = findWhere('posts', fn($p) => ($p['status'] ?? '') === 'published' && (int)($p['category_id'] ?? 0) === (int)$cid);
    usort($all, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
    $posts = $all;
} elseif (($parts[0] ?? '') === 'tag' && isset($parts[1])) {
    $view = 'tag';
    $tag = findOne('tags', fn($t) => ($t['slug'] ?? '') === $parts[1]);
    $archiveTitle = 'Tag: ' . ($tag['name'] ?? $parts[1]);
    $tid = $tag['id'] ?? 0;
    $pt = findWhere('post_tags', fn($r) => (int)$r['tag_id'] === (int)$tid);
    $pids = array_map(fn($r) => (int)$r['post_id'], $pt);
    $all = findWhere('posts', fn($p) => ($p['status'] ?? '') === 'published' && in_array((int)$p['id'], $pids, true));
    usort($all, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
    $posts = $all;
} elseif (empty($parts) || $parts[0] === '' || $parts[0] === 'index.php') {
    $page = findOne('pages', fn($p) => !empty($p['is_frontpage']) && ($p['status'] ?? '') === 'published');
    if (!$page) {
        $all = findWhere('posts', fn($p) => ($p['status'] ?? '') === 'published');
        usort($all, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
        $posts = array_slice($all, 0, (int)getSetting('posts_per_page', 10));
    }
} elseif (($parts[0] ?? '') === 'post' && isset($parts[1])) {
    $view = 'single';
    $post = findOne('posts', fn($p) => ($p['slug'] ?? '') === $parts[1] && ($p['status'] ?? '') === 'published');
    if ($post) update('posts', $post['id'], ['views' => ((int)($post['views'] ?? 0)) + 1]);
} elseif (($parts[0] ?? '') === 'page' && isset($parts[1])) {
    $page = findOne('pages', fn($p) => ($p['slug'] ?? '') === $parts[1] && ($p['status'] ?? '') === 'published');
} else {
    $page = findOne('pages', fn($p) => ($p['slug'] ?? '') === ($parts[0] ?? '') && ($p['status'] ?? '') === 'published');
}

$users = loadData('users');
$userMap = []; foreach ($users as $u) $userMap[$u['id']] = $u['full_name'] ?? $u['username'] ?? '';
$cats = loadData('categories');
$catMap = []; $catSlug = [];
foreach ($cats as $c) { $catMap[$c['id']] = $c['name'] ?? ''; $catSlug[$c['id']] = $c['slug'] ?? ''; }

$postComments = [];
if ($post) {
    $postComments = findWhere('comments', fn($c) => (int)$c['post_id'] === (int)$post['id'] && ($c['status'] ?? '') === 'approved');
    usort($postComments, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
}

$pageTitle = $page['seo_title'] ?? $page['title'] ?? $post['seo_title'] ?? $post['title'] ?? $archiveTitle ?? $seo['meta_title'] ?? $siteName;
$metaDesc = $page['seo_description'] ?? $post['seo_description'] ?? $seo['meta_description'] ?? '';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?><?= $pageTitle !== $siteName ? ' — ' . e($siteName) : '' ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<?php if (!empty($seo['meta_keywords'])): ?><meta name="keywords" content="<?= e($seo['meta_keywords']) ?>"><?php endif; ?>
<?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= e($seo['og_image']) ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;line-height:1.7;color:#1e293b;background:#f8fafc}
a{color:#6366f1;text-decoration:none}a:hover{text-decoration:underline}
.container{max-width:960px;margin:0 auto;padding:0 20px}
header{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:14px 0;position:sticky;top:0;z-index:50}
.header-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.logo{font-size:20px;font-weight:700;color:#1e293b}.logo span{color:#6366f1}
nav{display:flex;gap:20px;flex-wrap:wrap;align-items:center}
nav a{color:#64748b;font-size:14px;font-weight:500}nav a:hover{color:#6366f1;text-decoration:none}
.search-form{display:flex;gap:6px}
.search-form input{border:1px solid #e2e8f0;border-radius:50px;padding:8px 14px;font-size:13px;outline:none;width:160px}
.search-form button{background:#6366f1;color:#fff;border:none;border-radius:50px;padding:8px 14px;cursor:pointer}
main{padding:40px 0 80px}
.post-card{background:#fff;border-radius:16px;padding:0;margin-bottom:24px;box-shadow:0 4px 20px rgba(0,0,0,.04);overflow:hidden}
.post-card .thumb{width:100%;height:200px;object-fit:cover;display:block;background:#e2e8f0}
.post-card .body{padding:24px}
.post-card h2{font-size:22px;margin-bottom:8px}.post-card h2 a{color:#1e293b}
.post-meta{font-size:13px;color:#94a3b8;margin-bottom:12px}.post-excerpt{color:#64748b;font-size:15px}
article h1{font-size:32px;margin-bottom:12px}.content{margin-top:24px;font-size:16px}.content img{max-width:100%;border-radius:12px}
.featured{width:100%;max-height:400px;object-fit:cover;border-radius:16px;margin-bottom:20px}
.not-found{text-align:center;padding:80px 20px}.not-found h1{font-size:48px;color:#cbd5e1}
.sidebar-cats{display:flex;flex-wrap:wrap;gap:8px;margin:20px 0}
.sidebar-cats a{background:#fff;border:1px solid #e2e8f0;padding:6px 14px;border-radius:50px;font-size:13px;color:#64748b}
.sidebar-cats a:hover{border-color:#6366f1;color:#6366f1;text-decoration:none}
.comment{background:#fff;border-radius:12px;padding:16px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.comment-form{background:#fff;border-radius:16px;padding:24px;margin-top:24px;box-shadow:0 4px 20px rgba(0,0,0,.04)}
.form-control{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;margin-bottom:12px;font-family:inherit}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:50px;border:none;background:#6366f1;color:#fff;font-weight:600;cursor:pointer;font-size:14px}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px}
.alert-success{background:#d1fae5;color:#065f46}.alert-error{background:#fee2e2;color:#991b1b}
footer{text-align:center;padding:24px;color:#94a3b8;font-size:13px;border-top:1px solid #e2e8f0;background:#fff}
footer strong{color:#6366f1}
.layout{display:grid;grid-template-columns:1fr 260px;gap:32px}
@media(max-width:768px){.layout{grid-template-columns:1fr}}
.widget{background:#fff;border-radius:16px;padding:20px;margin-bottom:20px;box-shadow:0 4px 20px rgba(0,0,0,.04)}
.widget h3{font-size:14px;margin-bottom:12px;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
</style>
<?php if ($page && !empty($page['custom_css'])): ?><style><?= $page['custom_css'] ?></style><?php endif; ?>
<?php if (!empty($seo['google_analytics'])): ?><?= $seo['google_analytics'] ?><?php endif; ?>
</head>
<body>
<header><div class="container header-inner">
<a href="/" class="logo"><?= e($siteName) ?> <span>.</span></a>
<nav>
<?php foreach ($menuItems as $item): ?><a href="<?= e($item['url']) ?>"><?= e($item['title']) ?></a><?php endforeach; ?>
<?php if (empty($menuItems)): ?><a href="/">Home</a><?php endif; ?>
<form class="search-form" action="/" method="get"><input type="search" name="q" placeholder="Cari..." value="<?= e($query) ?>"><button type="submit"><i class="fas fa-search"></i></button></form>
</nav>
</div></header>
<main><div class="container">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<?php if ($page): ?>
<article><?php if (empty($page['is_frontpage'])): ?><h1><?= e($page['title']) ?></h1><?php endif; ?>
<div class="content"><?= $page['content'] ?? '' ?></div></article>

<?php elseif ($post): ?>
<div class="layout">
<div>
<article>
<?php if (!empty($post['featured_image'])): ?><img class="featured" src="<?= e($post['featured_image']) ?>" alt=""><?php endif; ?>
<h1><?= e($post['title']) ?></h1>
<div class="post-meta">
<?= e($userMap[$post['author_id'] ?? 0] ?? 'Admin') ?> · <?= formatDate($post['published_at'] ?? $post['created_at'] ?? '') ?>
<?php if (!empty($catMap[$post['category_id'] ?? 0])): ?> · <a href="/category/<?= e($catSlug[$post['category_id']] ?? '') ?>"><?= e($catMap[$post['category_id']]) ?></a><?php endif; ?>
 · <?= (int)($post['views'] ?? 0) ?> views
</div>
<div class="content"><?= $post['content'] ?? '' ?></div>
</article>

<section style="margin-top:40px">
<h2 style="font-size:20px;margin-bottom:16px">Komentar (<?= count($postComments) ?>)</h2>
<?php foreach ($postComments as $c): ?>
<div class="comment">
<strong><?= e($c['author_name'] ?? '') ?></strong>
<span style="font-size:12px;color:#94a3b8;margin-left:8px"><?= timeAgo($c['created_at'] ?? '') ?></span>
<p style="margin-top:8px;font-size:14px"><?= e($c['content'] ?? '') ?></p>
</div>
<?php endforeach; ?>
<?php if (getSetting('allow_comments', '1') === '1'): ?>
<div class="comment-form">
<h3 style="margin-bottom:12px;font-size:16px">Tulis Komentar</h3>
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="form" value="comment">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input class="form-control" name="author_name" placeholder="Nama *" required>
<input class="form-control" name="author_email" type="email" placeholder="Email">
<textarea class="form-control" name="content" rows="4" placeholder="Komentar *" required></textarea>
<button class="btn" type="submit"><i class="fas fa-paper-plane"></i> Kirim</button>
</form>
</div>
<?php endif; ?>
</section>
</div>
<aside>
<div class="widget"><h3>Kategori</h3>
<div class="sidebar-cats">
<?php foreach ($cats as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
<?php if (empty($cats)): ?><span style="font-size:13px;color:#94a3b8">Belum ada</span><?php endif; ?>
</div></div>
</aside>
</div>

<?php elseif ($posts !== null): ?>
<div class="layout">
<div>
<?php if ($archiveTitle): ?><h1 style="margin-bottom:24px;font-size:28px"><?= e($archiveTitle) ?></h1><?php else: ?>
<h1 style="margin-bottom:8px;font-size:28px">Latest Posts</h1>
<?php if ($siteTagline): ?><p style="color:#94a3b8;margin-bottom:24px"><?= e($siteTagline) ?></p><?php endif; ?>
<?php endif; ?>
<?php if (empty($posts)): ?>
<div class="not-found"><h1>📝</h1><p style="color:#94a3b8;margin-top:12px">Tidak ada post ditemukan.</p></div>
<?php else: foreach ($posts as $p): ?>
<div class="post-card">
<?php if (!empty($p['featured_image'])): ?><img class="thumb" src="<?= e($p['featured_image']) ?>" alt=""><?php endif; ?>
<div class="body">
<h2><a href="/post/<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h2>
<div class="post-meta"><?= e($userMap[$p['author_id'] ?? 0] ?? 'Admin') ?> · <?= formatDate($p['published_at'] ?? $p['created_at'] ?? '') ?>
<?php if (!empty($catMap[$p['category_id'] ?? 0])): ?> · <a href="/category/<?= e($catSlug[$p['category_id']] ?? '') ?>"><?= e($catMap[$p['category_id']]) ?></a><?php endif; ?></div>
<div class="post-excerpt"><?= e($p['excerpt'] ?? truncate(strip_tags($p['content'] ?? ''), 160)) ?></div>
</div></div>
<?php endforeach; endif; ?>
</div>
<aside>
<div class="widget"><h3>Kategori</h3>
<div class="sidebar-cats">
<?php foreach ($cats as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
</div></div>
<div class="widget"><h3>Cari</h3>
<form class="search-form" action="/" method="get" style="width:100%"><input type="search" name="q" placeholder="Cari post..." style="flex:1;width:100%"><button type="submit"><i class="fas fa-search"></i></button></form>
</div>
</aside>
</div>

<?php else: ?>
<div class="not-found"><h1>404</h1><p style="color:#94a3b8;margin-top:12px">Halaman tidak ditemukan. <a href="/">Kembali</a></p></div>
<?php endif; ?>
</div></main>
<footer>Made by <strong>vzy</strong> &copy; <?= date('Y') ?> — <?= e($siteName) ?></footer>
<?php if ($page && !empty($page['custom_js'])): ?><script><?= $page['custom_js'] ?></script><?php endif; ?>
</body></html>
