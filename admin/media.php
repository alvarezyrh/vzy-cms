<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Media';
$currentPage = 'media';
$breadcrumb = 'Media Library';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { flash('error', 'Invalid CSRF'); redirect('media.php'); }
    $act = $_POST['action'] ?? '';

    if ($act === 'upload' && !empty($_FILES['files'])) {
        $files = $_FILES['files'];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        $uploaded = 0;
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $path = uploadFile($file, 'uploads');
            if ($path) {
                insert('media', [
                    'filename' => basename($path),
                    'original_name' => $file['name'],
                    'path' => $path,
                    'mime' => $file['type'],
                    'size' => $file['size'],
                    'uploaded_by' => $user['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $uploaded++;
            }
        }
        if ($uploaded) {
            logActivity('upload_media', "$uploaded file(s)");
            flash('success', "$uploaded file berhasil diupload.");
        } else {
            flash('error', 'Upload gagal. Format: jpg, png, gif, webp, svg.');
        }
        redirect('media.php');
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $m = findById('media', $id);
        if ($m) {
            $full = __DIR__ . '/../' . $m['path'];
            if (file_exists($full)) @unlink($full);
            deleteById('media', $id);
            logActivity('delete_media', $m['original_name'] ?? '');
            flash('success', 'Media dihapus.');
        }
        redirect('media.php');
    }
}

$all = loadData('media');
usort($all, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$page = max(1, (int)($_GET['p'] ?? 1));
$pag = paginate($all, $page, 24);

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Media Library</h2>
        <span class="badge badge-primary"><?= count($all) ?> files</span>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" style="margin-bottom:24px;padding:24px;border:2px dashed var(--border);border-radius:var(--radius);text-align:center;background:var(--bg-body)">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="upload">
            <i class="fas fa-cloud-upload-alt" style="font-size:36px;color:var(--primary);margin-bottom:12px;display:block"></i>
            <p style="margin-bottom:12px;color:var(--text-secondary);font-size:14px">Drag & drop atau pilih file (jpg, png, gif, webp, svg)</p>
            <input type="file" name="files[]" multiple accept="image/*" class="form-control" style="max-width:320px;margin:0 auto 12px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
        </form>

        <?php if (empty($pag['items'])): ?>
        <div class="empty-state"><i class="fas fa-images"></i><h3>Belum ada media</h3><p>Upload gambar pertama Anda</p></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
            <?php foreach ($pag['items'] as $m): ?>
            <div style="background:var(--bg-body);border-radius:12px;overflow:hidden;position:relative">
                <div style="aspect-ratio:1;background:#e2e8f0;display:flex;align-items:center;justify-content:center;overflow:hidden">
                    <img src="<?= e(mediaUrl($m['path'])) ?>" alt="<?= e($m['original_name'] ?? '') ?>"
                         style="width:100%;height:100%;object-fit:cover" loading="lazy"
                         onclick="navigator.clipboard.writeText('<?= e($m['path']) ?>');alert('Path disalin: <?= e($m['path']) ?>')"
                         title="Klik untuk copy path">
                </div>
                <div style="padding:10px">
                    <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($m['original_name'] ?? $m['filename']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= number_format(($m['size'] ?? 0) / 1024, 1) ?> KB</div>
                    <form method="POST" style="margin-top:8px">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" style="width:100%" data-confirm="Hapus media ini?"><i class="fas fa-trash"></i> Hapus</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?= paginationHtml($pag, 'media.php') ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
