<?php
// Visual Page Builder (drag & drop, ala Elementor).
require_once __DIR__ . '/includes/auth.php';

function builderJson(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isUpload = ($_GET['action'] ?? '') === 'upload';
    $body = $isUpload ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
    if (!verifyCsrf($body['csrf_token'] ?? '')) builderJson(['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman.'], 403);

    if ($isUpload) {
        if (empty($_FILES['file'])) builderJson(['ok' => false, 'error' => 'Tidak ada file.'], 400);
        $path = uploadFile($_FILES['file'], 'uploads');
        if (!$path) builderJson(['ok' => false, 'error' => 'Upload gagal. Format yang didukung: jpg, png, gif, webp, svg.'], 400);
        insert('media', [
            'filename' => basename($path),
            'original_name' => $_FILES['file']['name'],
            'path' => $path,
            'mime' => $_FILES['file']['type'],
            'size' => $_FILES['file']['size'],
            'uploaded_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        builderJson(['ok' => true, 'url' => '/' . $path, 'name' => $_FILES['file']['name']]);
    }

    $pid = (int)($body['id'] ?? 0);
    $page = $pid ? findById('pages', $pid) : null;
    if (!$page) builderJson(['ok' => false, 'error' => 'Halaman tidak ditemukan.'], 404);

    $title = trim((string)($body['title'] ?? ''));
    if ($title === '') builderJson(['ok' => false, 'error' => 'Judul halaman wajib diisi.'], 422);
    $status = in_array($body['status'] ?? '', ['draft', 'published'], true) ? $body['status'] : ($page['status'] ?? 'draft');
    $layout = (string)($body['builder_data'] ?? '[]');
    if (json_decode($layout, true) === null && $layout !== 'null') builderJson(['ok' => false, 'error' => 'Data layout tidak valid.'], 422);

    update('pages', $pid, [
        'title' => $title,
        'status' => $status,
        'content' => (string)($body['content'] ?? ''),
        'builder_data' => $layout,
        'site_chrome' => !empty($body['site_chrome']) ? 1 : 0,
        'custom_css' => (string)($body['custom_css'] ?? ''),
        'seo_title' => trim((string)($body['seo_title'] ?? '')),
        'seo_description' => trim((string)($body['seo_description'] ?? '')),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $saved = findById('pages', $pid);
    $synced = $saved ? syncPageToPublic($saved) : false;
    if ($saved) syncPageMenuItem($saved);
    logActivity('builder_save', $title);
    builderJson(['ok' => true, 'synced' => $synced, 'saved_at' => date('H:i:s'), 'url' => $saved ? publicPageUrl($saved) : '']);
}

$id = (int)($_GET['id'] ?? 0);
$page = $id ? findById('pages', $id) : null;
if (!$page) { flash('error', 'Halaman tidak ditemukan.'); redirect('pages.php'); }

$media = [];
foreach (array_reverse(loadData('media')) as $m) {
    $ext = strtolower(pathinfo($m['path'] ?? '', PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
        $media[] = ['url' => '/' . ltrim($m['path'], '/'), 'name' => $m['original_name'] ?? $m['filename'] ?? ''];
    }
}

$boot = [
    'csrf' => csrfToken(),
    'page' => [
        'id' => (int)$page['id'],
        'title' => $page['title'] ?? '',
        'slug' => $page['slug'] ?? '',
        'status' => $page['status'] ?? 'draft',
        'site_chrome' => (int)($page['site_chrome'] ?? 1),
        'seo_title' => $page['seo_title'] ?? '',
        'seo_description' => $page['seo_description'] ?? '',
        'custom_css' => $page['custom_css'] ?? '',
        'is_home' => ($page['slug'] ?? '') === 'home',
    ],
    'layout' => isset($page['builder_data']) ? json_decode((string)$page['builder_data'], true) : null,
    'legacy' => (string)($page['content'] ?? ''),
    'media' => $media,
    'url' => publicPageUrl($page),
    'whatsapp' => '6281313616756',
];
$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Builder: <?= e($page['title'] ?? '') ?> - <?= e(getSetting('site_name', 'CMS')) ?></title>
<link rel="icon" type="image/svg+xml" href="/public/assets/brand/favicon.svg">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/css/builder.css?v=<?= @filemtime(__DIR__ . '/assets/css/builder.css') ?>">
</head>
<body>
<div id="bx-app">
  <header class="bx-top">
    <a href="pages.php" class="bx-iconbtn" title="Kembali ke daftar halaman" id="bxBack"><i class="fas fa-arrow-left"></i></a>
    <div class="bx-title"><i class="fas fa-wand-magic-sparkles"></i> <span id="bxPageTitle"><?= e($page['title'] ?? '') ?></span>
      <span class="bx-chip" id="bxStatus">Tersimpan</span></div>
    <div class="bx-devices" role="group" aria-label="Pratinjau perangkat">
      <button class="bx-iconbtn active" data-dev="desktop" title="Desktop"><i class="fas fa-desktop"></i></button>
      <button class="bx-iconbtn" data-dev="tablet" title="Tablet"><i class="fas fa-tablet-screen-button"></i></button>
      <button class="bx-iconbtn" data-dev="mobile" title="Mobile"><i class="fas fa-mobile-screen"></i></button>
    </div>
    <div class="bx-actions">
      <button class="bx-iconbtn" id="bxUndo" title="Undo (Ctrl+Z)" disabled><i class="fas fa-rotate-left"></i></button>
      <button class="bx-iconbtn" id="bxRedo" title="Redo (Ctrl+Y)" disabled><i class="fas fa-rotate-right"></i></button>
      <button class="bx-btn bx-btn-ghost" id="bxPreview" title="Sembunyikan kontrol editor"><i class="fas fa-eye"></i> Pratinjau</button>
      <a class="bx-btn bx-btn-ghost" id="bxView" href="<?= e(publicPageUrl($page)) ?>" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Lihat</a>
      <button class="bx-btn bx-btn-primary" id="bxSave" title="Simpan (Ctrl+S)"><i class="fas fa-floppy-disk"></i> Simpan</button>
    </div>
  </header>
  <div class="bx-main">
    <aside class="bx-panel" id="bxPanel"></aside>
    <section class="bx-stage" id="bxStage">
      <div class="bx-frame-wrap" id="bxFrameWrap"><iframe id="bxFrame" title="Kanvas halaman"></iframe></div>
    </section>
  </div>
</div>
<div id="bxToast" class="bx-toast" role="status"></div>
<div id="bxModal" class="bx-modal" hidden></div>
<script>window.BUILDER = <?= json_encode($boot, $flags) ?>;</script>
<script src="assets/js/builder-widgets.js?v=<?= @filemtime(__DIR__ . '/assets/js/builder-widgets.js') ?>"></script>
<script src="assets/js/builder.js?v=<?= @filemtime(__DIR__ . '/assets/js/builder.js') ?>"></script>
</body>
</html>
