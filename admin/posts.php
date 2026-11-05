<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Posts'; $currentPage = 'posts'; $breadcrumb = 'Posts';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { flash('error','Invalid CSRF'); redirect('posts.php'); }
    if (($_POST['action'] ?? '') === 'upload_post_image') {
        header('Content-Type: application/json; charset=utf-8');
        $file = $_FILES['image'] ?? null;
        $path = $file ? uploadFile($file, 'uploads') : null;
        if (!$path) {
            echo json_encode(['success' => false, 'message' => 'Upload gagal. Gunakan JPG, PNG, GIF, WEBP, atau SVG.']);
            exit;
        }
        insert('media', [
            'filename' => basename($path), 'original_name' => $file['name'] ?? basename($path),
            'path' => $path, 'mime' => $file['type'] ?? '', 'size' => $file['size'] ?? 0,
            'uploaded_by' => $user['id'], 'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true, 'path' => $path, 'url' => '../' . ltrim($path, '/')]);
        exit;
    }
    $title = trim($_POST['title'] ?? '');
    $slug = uniqueSlug('posts', trim($_POST['slug'] ?? '') ?: slugify($title), (int)($_POST['id'] ?? 0));
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $featured_image = trim($_POST['featured_image'] ?? '');
    $social_share = isset($_POST['social_share']) ? 1 : 0;
    $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
    $post_id = (int)($_POST['id'] ?? 0);
    if (!$title) { flash('error','Judul wajib diisi.'); redirect($post_id ? "posts.php?action=edit&id=$post_id" : 'posts.php?action=create'); }

    $now = date('Y-m-d H:i:s');
    if ($post_id) {
        $existing = findById('posts', $post_id);
        update('posts', $post_id, [
            'title'=>$title,'slug'=>$slug,'content'=>$content,'excerpt'=>$excerpt,'status'=>$status,
            'category_id'=>$category_id,'seo_title'=>$seo_title,'seo_description'=>$seo_description,
            'seo_keywords'=>$seo_keywords,'featured_image'=>$featured_image,'social_share'=>$social_share,'updated_at'=>$now,
            'published_at'=>($status==='published' && empty($existing['published_at'])) ? $now : ($existing['published_at'] ?? null),
        ]);
        // clear old tags
        $pt = loadData('post_tags');
        saveData('post_tags', array_values(array_filter($pt, fn($r) => (int)$r['post_id'] !== $post_id)));
        flash('success','Post berhasil diperbarui.');
    } else {
        $post_id = insert('posts', [
            'title'=>$title,'slug'=>$slug,'content'=>$content,'excerpt'=>$excerpt,'status'=>$status,
            'category_id'=>$category_id,'author_id'=>$user['id'],'views'=>0,
            'seo_title'=>$seo_title,'seo_description'=>$seo_description,'seo_keywords'=>$seo_keywords,'featured_image'=>$featured_image,'social_share'=>$social_share,
            'created_at'=>$now,'updated_at'=>$now,
            'published_at'=>$status==='published' ? $now : null,

        ]);
        flash('success','Post berhasil dibuat.');
    }
    // tags
    foreach ($tags as $tagName) {
        if (!$tagName) continue;
        $tagSlug = slugify($tagName);
        $tag = findOne('tags', fn($t) => $t['slug'] === $tagSlug);
        if (!$tag) {
            $tagId = insert('tags', ['name'=>$tagName,'slug'=>$tagSlug,'created_at'=>$now]);
        } else {
            $tagId = $tag['id'];
        }
        $pt = loadData('post_tags');
        $exists = false;
        foreach ($pt as $r) { if ((int)$r['post_id']===$post_id && (int)$r['tag_id']===$tagId) { $exists=true; break; } }
        if (!$exists) {
            $pt[] = ['post_id'=>$post_id,'tag_id'=>$tagId];
            saveData('post_tags', $pt);
        }
    }
    syncBlogToPublic();
    redirect('posts.php');
}

if ($action === 'delete' && $id) { update('posts',$id,['status'=>'trash']); syncBlogToPublic(); flash('success','Post dipindahkan ke trash.'); redirect('posts.php'); }
if ($action === 'restore' && $id) { update('posts',$id,['status'=>'draft']); syncBlogToPublic(); flash('success','Post dipulihkan.'); redirect('posts.php'); }
if ($action === 'force_delete' && $id) { deleteById('posts',$id); saveData('post_tags', array_values(array_filter(loadData('post_tags'), fn($r) => (int)$r['post_id'] !== $id))); syncBlogToPublic(); flash('success','Post dihapus permanen.'); redirect('posts.php?status=trash'); }

include __DIR__ . '/includes/header.php';
$categories = loadData('categories');
usort($categories, fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));

if ($action === 'create' || $action === 'edit'):
    $post = null; $postTags = '';
    if ($action === 'edit' && $id) {
        $post = findById('posts', $id);
        if (!$post) { flash('error','Post tidak ditemukan.'); redirect('posts.php'); }
        $pt = findWhere('post_tags', fn($r) => (int)$r['post_id'] === $id);
        $tagNames = [];
        foreach ($pt as $r) {
            $t = findById('tags', (int)$r['tag_id']);
            if ($t) $tagNames[] = $t['name'];
        }
        $postTags = implode(', ', $tagNames);
    }
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $action==='edit'?'Edit Post':'Buat Post Baru' ?></h2>
        <a href="posts.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($post): ?><input type="hidden" name="id" value="<?= $post['id'] ?>"><?php endif; ?>
            <div class="form-group"><label class="form-label">Judul *</label>
                <input type="text" name="title" id="title" class="form-control" required value="<?= e($post['title']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" value="<?= e($post['slug']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Konten</label>
                <div class="editor-toolbar" data-editor="postContentEditor">
                    <select data-command="fontName" title="Jenis font"><option value="Inter">Inter</option><option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Verdana">Verdana</option></select>
                    <select data-command="fontSize" title="Ukuran huruf"><option value="2">Normal</option><option value="3">Besar</option><option value="4">Lebih besar</option><option value="5">Judul kecil</option><option value="6">Judul</option></select>
                    <button type="button" data-command="bold" title="Tebal"><i class="fas fa-bold"></i></button>
                    <button type="button" data-command="italic" title="Miring"><i class="fas fa-italic"></i></button>
                    <button type="button" data-command="underline" title="Garis bawah"><i class="fas fa-underline"></i></button>
                    <span class="editor-divider"></span>
                    <button type="button" data-command="justifyLeft" title="Rata kiri"><i class="fas fa-align-left"></i></button>
                    <button type="button" data-command="justifyCenter" title="Rata tengah"><i class="fas fa-align-center"></i></button>
                    <button type="button" data-command="justifyRight" title="Rata kanan"><i class="fas fa-align-right"></i></button>
                    <button type="button" data-command="insertUnorderedList" title="Daftar"><i class="fas fa-list-ul"></i></button>
                    <button type="button" data-command="createLink" title="Tambah link"><i class="fas fa-link"></i></button>
                    <button type="button" class="editor-upload-image" title="Tambah gambar"><i class="fas fa-image"></i> Gambar</button>
                    <button type="button" class="editor-insert-share" title="Sematkan tombol social share di posisi kursor"><i class="fas fa-share-alt"></i> Sematkan Share</button>
                </div>
                <div id="postContentEditor" class="rich-editor" contenteditable="true"><?= $post['content']??'' ?></div>
                <textarea name="content" id="postContent" hidden><?= e($post['content']??'') ?></textarea>
                <div class="form-hint">Gunakan toolbar untuk mengatur font, ukuran teks, format, link, dan gambar.</div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Pilih —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($post['category_id']??'')==$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?= ($post['status']??'')==='draft'?'selected':'' ?>>Draft</option>
                        <option value="published" <?= ($post['status']??'')==='published'?'selected':'' ?>>Published</option>
                    </select></div>
            </div>
            <div class="form-group"><label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-control" value="<?= e($postTags) ?>" placeholder="pisahkan dengan koma">
                <div class="form-hint">Pisahkan tag dengan koma</div></div>
            <div class="form-group"><label class="form-label">Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3"><?= e($post['excerpt']??'') ?></textarea></div>
            <div class="form-group"><label class="form-label">Featured Image URL</label>
                <div class="input-with-action"><input type="text" name="featured_image" id="featuredImage" class="form-control" value="<?= e($post['featured_image']??'') ?>" placeholder="uploads/xxx.jpg atau URL">
                <button type="button" class="btn btn-secondary" id="uploadFeaturedImage"><i class="fas fa-upload"></i> Upload</button></div>
                <input type="file" id="featuredImageFile" accept="image/*" hidden>
                <div class="form-hint">Upload gambar langsung atau masukkan URL gambar.</div></div>
            <?php $sharePluginActive = isPluginActive('social-share'); $shareChecked = $post ? postShareEnabled($post) : true; ?>
            <div class="card share-option-card" style="background:var(--bg-body);box-shadow:none;margin-top:8px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
                    <div>
                        <h3 style="font-size:14px;margin-bottom:4px;color:var(--text-primary)"><i class="fas fa-share-alt" style="color:var(--primary);margin-right:6px"></i> Social Share</h3>
                        <div class="form-hint" style="margin:0">Tampilkan tombol bagikan (WhatsApp, Facebook, X, dll.) di artikel ini. Tombol <strong>Sematkan Share</strong> di toolbar menyisipkan <code>[social_share]</code> di posisi mana pun dalam konten.</div>
                    </div>
                    <label class="switch" style="flex-shrink:0" title="Tampilkan social share"><input type="checkbox" name="social_share" value="1" <?= $shareChecked ? 'checked' : '' ?> aria-label="Tampilkan social share di artikel ini"><span class="slider"></span></label>
                </div>
                <?php if (!$sharePluginActive): ?>
                <div class="alert alert-warning" style="margin:14px 0 0;display:flex"><i class="fas fa-exclamation-triangle"></i>&nbsp;Plugin Social Share sedang nonaktif, jadi tombol belum tampil di website. <?php if (isAdmin()): ?><a href="plugins.php" style="text-decoration:underline;margin-left:4px">Aktifkan di Plugins</a><?php endif; ?></div>
                <?php endif; ?>
            </div>
            <div class="card" style="background:var(--bg-body);box-shadow:none;margin-top:8px">
                <h3 style="font-size:14px;margin-bottom:16px;color:var(--text-secondary)"><i class="fas fa-search"></i> SEO</h3>
                <div class="form-group"><label class="form-label">SEO Title</label>
                    <input type="text" name="seo_title" class="form-control" value="<?= e($post['seo_title']??'') ?>"></div>
                <div class="form-group"><label class="form-label">SEO Description</label>
                    <textarea name="seo_description" class="form-control" rows="2"><?= e($post['seo_description']??'') ?></textarea></div>
                <div class="form-group"><label class="form-label">SEO Keywords</label>
                    <input type="text" name="seo_keywords" class="form-control" value="<?= e($post['seo_keywords']??'') ?>"></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:24px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="posts.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php else:
    $statusFilter = $_GET['status'] ?? 'all';
    $all = loadData('posts');
    $posts = match($statusFilter) {
        'published' => array_filter($all, fn($p)=>($p['status']??'')==='published'),
        'draft' => array_filter($all, fn($p)=>($p['status']??'')==='draft'),
        'trash' => array_filter($all, fn($p)=>($p['status']??'')==='trash'),
        default => array_filter($all, fn($p)=>($p['status']??'')!=='trash'),
    };
    $posts = array_values($posts);
    usort($posts, fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??''));
    $users = loadData('users'); $userMap=[]; foreach($users as $u) $userMap[$u['id']]=$u['username']??'';
    $cats = loadData('categories'); $catMap=[]; foreach($cats as $c) $catMap[$c['id']]=$c['name']??'';
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Semua Posts</h2>
        <div style="display:flex;gap:10px;align-items:center">
            <div class="tabs" style="margin:0">
                <a href="posts.php" class="tab-btn <?= $statusFilter==='all'?'active':'' ?>">All</a>
                <a href="posts.php?status=published" class="tab-btn <?= $statusFilter==='published'?'active':'' ?>">Published</a>
                <a href="posts.php?status=draft" class="tab-btn <?= $statusFilter==='draft'?'active':'' ?>">Draft</a>
                <a href="posts.php?status=trash" class="tab-btn <?= $statusFilter==='trash'?'active':'' ?>">Trash</a>
            </div>
            <a href="posts.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($posts)): ?>
        <div class="empty-state"><i class="fas fa-newspaper"></i><h3>Tidak ada post</h3>
            <a href="posts.php?action=create" class="btn btn-primary btn-sm" style="margin-top:12px"><i class="fas fa-plus"></i> Buat Post</a></div>
        <?php else: ?>
        <div class="table-responsive"><table>
            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td style="font-weight:600"><?= e($p['title']) ?></td>
                <td><?= e($userMap[$p['author_id']??0]??'-') ?></td>
                <td><?= e($catMap[$p['category_id']??0]??'-') ?></td>
                <td><span class="badge badge-<?= match($p['status']??''){'published'=>'success','draft'=>'warning','trash'=>'danger',default=>'secondary'} ?>"><?= e($p['status']??'') ?></span></td>
                <td><?= formatDate($p['created_at']??'') ?></td>
                <td><div class="btn-group">
                    <?php if (($p['status']??'')==='trash'): ?>
                    <a href="posts.php?action=restore&id=<?= $p['id'] ?>" class="btn btn-sm btn-success btn-icon"><i class="fas fa-undo"></i></a>
                    <a href="posts.php?action=force_delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger btn-icon" data-confirm="Hapus permanen?"><i class="fas fa-trash"></i></a>
                    <?php else: ?>
                    <?php if (($p['status']??'')==='published'): ?>
                    <a href="<?= e(postPublicUrl($p)) ?>" class="btn btn-sm btn-secondary btn-icon" target="_blank" rel="noopener noreferrer" title="Lihat artikel"><i class="fas fa-eye"></i></a>
                    <?php endif; ?>
                    <a href="posts.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary btn-icon"><i class="fas fa-edit"></i></a>
                    <a href="posts.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger btn-icon" data-confirm="Pindahkan ke trash?"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
