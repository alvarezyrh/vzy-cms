<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Comments';
$currentPage = 'comments';
$breadcrumb = 'Comments';
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'approve' && $id) {
    update('comments', $id, ['status' => 'approved']);
    flash('success', 'Komentar disetujui.');
    redirect('comments.php');
}
if ($action === 'spam' && $id) {
    update('comments', $id, ['status' => 'spam']);
    flash('success', 'Ditandai spam.');
    redirect('comments.php');
}
if ($action === 'delete' && $id) {
    deleteById('comments', $id);
    flash('success', 'Komentar dihapus.');
    redirect('comments.php');
}

$statusFilter = $_GET['status'] ?? 'all';
$all = loadData('comments');
$comments = match ($statusFilter) {
    'pending' => array_filter($all, fn($c) => ($c['status'] ?? '') === 'pending'),
    'approved' => array_filter($all, fn($c) => ($c['status'] ?? '') === 'approved'),
    'spam' => array_filter($all, fn($c) => ($c['status'] ?? '') === 'spam'),
    default => $all,
};
$comments = array_values($comments);
usort($comments, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$posts = loadData('posts');
$postMap = [];
foreach ($posts as $p) $postMap[$p['id']] = $p['title'] ?? '';

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Comments</h2>
        <div class="tabs" style="margin:0">
            <a href="comments.php" class="tab-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
            <a href="comments.php?status=pending" class="tab-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="comments.php?status=approved" class="tab-btn <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
            <a href="comments.php?status=spam" class="tab-btn <?= $statusFilter === 'spam' ? 'active' : '' ?>">Spam</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($comments)): ?>
        <div class="empty-state"><i class="fas fa-comments"></i><h3>Tidak ada komentar</h3></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($comments as $c): ?>
            <div style="background:var(--bg-body);border-radius:12px;padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px">
                    <div>
                        <strong><?= e($c['author_name'] ?? 'Anonim') ?></strong>
                        <span style="color:var(--text-muted);font-size:12px;margin-left:8px"><?= e($c['author_email'] ?? '') ?></span>
                        <span class="badge badge-<?= match($c['status'] ?? '') { 'approved' => 'success', 'pending' => 'warning', 'spam' => 'danger', default => 'secondary' } ?>" style="margin-left:8px">
                            <?= e($c['status'] ?? 'pending') ?>
                        </span>
                    </div>
                    <span style="font-size:12px;color:var(--text-muted)"><?= timeAgo($c['created_at'] ?? '') ?></span>
                </div>
                <p style="font-size:14px;margin-bottom:8px"><?= e($c['content'] ?? '') ?></p>
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">
                    Pada: <strong><?= e($postMap[$c['post_id'] ?? 0] ?? 'Post #' . ($c['post_id'] ?? '?')) ?></strong>
                </div>
                <div class="btn-group">
                    <?php if (($c['status'] ?? '') !== 'approved'): ?>
                    <a href="comments.php?action=approve&id=<?= $c['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</a>
                    <?php endif; ?>
                    <?php if (($c['status'] ?? '') !== 'spam'): ?>
                    <a href="comments.php?action=spam&id=<?= $c['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-ban"></i> Spam</a>
                    <?php endif; ?>
                    <a href="comments.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Hapus komentar?"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
