<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$breadcrumb = '';

$totalPosts = countWhere('posts', fn($p) => ($p['status'] ?? '') !== 'trash');
$publishedPosts = countWhere('posts', fn($p) => ($p['status'] ?? '') === 'published');
$draftPosts = countWhere('posts', fn($p) => ($p['status'] ?? '') === 'draft');
$totalPages = countWhere('pages', fn($p) => ($p['status'] ?? '') !== 'trash');
$totalCategories = countWhere('categories');
$totalTags = countWhere('tags');
$totalUsers = countWhere('users');
$totalMedia = countWhere('media');
$pendingComments = countWhere('comments', fn($c) => ($c['status'] ?? '') === 'pending');

$allPosts = loadData('posts');
usort($allPosts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recentPosts = array_slice(array_filter($allPosts, fn($p) => ($p['status'] ?? '') !== 'trash'), 0, 5);

$users = loadData('users');
$cats = loadData('categories');
$userMap = []; foreach ($users as $u) $userMap[$u['id']] = $u['username'] ?? '';
$catMap = []; foreach ($cats as $c) $catMap[$c['id']] = $c['name'] ?? '';

$activity = array_slice(loadData('activity'), 0, 8);

include __DIR__ . '/includes/header.php';
?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-newspaper"></i></div><div class="stat-info"><h3><?= $totalPosts ?></h3><p>Total Posts</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?= $publishedPosts ?></h3><p>Published</p></div></div>
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-file-alt"></i></div><div class="stat-info"><h3><?= $totalPages ?></h3><p>Pages</p></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-images"></i></div><div class="stat-info"><h3><?= $totalMedia ?></h3><p>Media</p></div></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Posts</h2>
            <a href="posts.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentPosts)): ?>
            <div class="empty-state"><i class="fas fa-newspaper"></i><h3>Belum ada post</h3></div>
            <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentPosts as $post): ?>
                <tr>
                    <td><a href="posts.php?action=edit&id=<?= $post['id'] ?>" style="font-weight:600;color:var(--text-primary)"><?= e($post['title']) ?></a></td>
                    <td><?= e($catMap[$post['category_id'] ?? 0] ?? '-') ?></td>
                    <td><span class="badge badge-<?= ($post['status'] ?? '') === 'published' ? 'success' : 'warning' ?>"><?= e($post['status'] ?? 'draft') ?></span></td>
                    <td><?= timeAgo($post['created_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="card dashboard-widget weather-widget">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-cloud-sun" style="color:var(--primary);margin-right:8px"></i> Cuaca & Waktu</h2></div>
            <div class="card-body">
                <div class="weather-main">
                    <div class="weather-icon" id="weatherIcon"><i class="fas fa-cloud-sun"></i></div>
                    <div>
                        <div class="weather-temp" id="weatherTemp">--°C</div>
                        <div class="weather-condition" id="weatherCondition">Memuat cuaca...</div>
                        <div class="weather-location" id="weatherLocation">Jakarta, Indonesia</div>
                    </div>
                </div>
                <div class="dashboard-datetime">
                    <div><i class="far fa-clock"></i><strong id="dashboardClock"><?= e(date('H:i:s')) ?></strong></div>
                    <div><i class="far fa-calendar"></i><span id="dashboardDate"><?= e(date('l, d F Y')) ?></span></div>
                </div>
            </div>
        </div>

        <div class="card dashboard-widget server-widget">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-server" style="color:var(--primary);margin-right:8px"></i> Status Server</h2><span class="server-status-badge"><span></span> Online</span></div>
            <div class="card-body">
                <div class="server-status-grid">
                    <div><span>Status aplikasi</span><strong class="status-ok"><i class="fas fa-check-circle"></i> Berjalan</strong></div>
                    <div><span>PHP Version</span><strong><?= e(PHP_VERSION) ?></strong></div>
                    <div><span>Penyimpanan database</span><strong class="status-ok"><i class="fas fa-check-circle"></i> <?= is_writable(DATA_DIR) ? 'Writable' : 'Read-only' ?></strong></div>
                    <div><span>Waktu server</span><strong><?= e(date('H:i:s')) ?></strong></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Overview</h2></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;justify-content:space-between;padding:10px 12px;background:var(--bg-body);border-radius:10px">
                        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-file" style="margin-right:8px;color:var(--warning)"></i> Drafts</span><strong><?= $draftPosts ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 12px;background:var(--bg-body);border-radius:10px">
                        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-comments" style="margin-right:8px;color:var(--info)"></i> Pending Comments</span><strong><?= $pendingComments ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 12px;background:var(--bg-body);border-radius:10px">
                        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-tags" style="margin-right:8px;color:var(--primary)"></i> Tags</span><strong><?= $totalTags ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 12px;background:var(--bg-body);border-radius:10px">
                        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-users" style="margin-right:8px;color:var(--secondary)"></i> Users</span><strong><?= $totalUsers ?></strong>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px">
                    <a href="posts.php?action=create" class="btn btn-secondary" style="justify-content:flex-start"><i class="fas fa-plus"></i> Buat Post</a>
                    <a href="media.php" class="btn btn-secondary" style="justify-content:flex-start"><i class="fas fa-upload"></i> Upload Media</a>
                    <a href="comments.php?status=pending" class="btn btn-secondary" style="justify-content:flex-start"><i class="fas fa-comments"></i> Moderasi Komentar</a>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:20px">
            <div class="card-header"><h2 class="card-title">Activity</h2></div>
            <div class="card-body">
                <?php if (empty($activity)): ?>
                <p style="color:var(--text-muted);font-size:13px">Belum ada aktivitas</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <?php foreach ($activity as $a): ?>
                    <div style="font-size:13px;padding-bottom:10px;border-bottom:1px solid var(--border)">
                        <strong><?= e($a['username'] ?? 'system') ?></strong>
                        <span style="color:var(--text-secondary)"><?= e($a['action'] ?? '') ?></span>
                        <?php if (!empty($a['detail'])): ?><span style="color:var(--text-muted)">— <?= e(truncate($a['detail'], 40)) ?></span><?php endif; ?>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= timeAgo($a['created_at'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
