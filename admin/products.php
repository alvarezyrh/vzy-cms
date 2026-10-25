<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Products';
$currentPage = 'products';
$breadcrumb = 'Product Catalog';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

function productCatalogSaveMessage(): void {
    if (syncProductCatalogPages()) {
        flash('success', 'Produk tersimpan dan katalog pada page terkait telah diperbarui.');
        return;
    }
    flash('error', 'Produk tersimpan, tetapi katalog pada salah satu page gagal diperbarui. Simpan kembali page tersebut untuk mencoba lagi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? 'save';
    if ($postAction === 'upload_product_image') {
        header('Content-Type: application/json; charset=utf-8');
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.']);
            exit;
        }

        $file = $_FILES['image'] ?? null;
        $allowedTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
        ];
        $mime = $file && isset($file['tmp_name']) && is_file($file['tmp_name'])
            ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])
            : false;
        $extension = $file ? strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) : '';

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Pilih gambar yang valid untuk diunggah.']);
            exit;
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ukuran gambar maksimal 5 MB.']);
            exit;
        }
        if (!isset($allowedTypes[$mime]) || !in_array($extension, $allowedTypes[$mime], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Gunakan gambar JPG, PNG, GIF, atau WEBP yang valid.']);
            exit;
        }

        $path = uploadFile($file, 'uploads');
        if (!$path) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gambar gagal disimpan ke server.']);
            exit;
        }
        insert('media', [
            'filename' => basename($path),
            'original_name' => $file['name'],
            'path' => $path,
            'mime' => $mime,
            'size' => $file['size'],
            'uploaded_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true, 'path' => $path, 'url' => '../' . ltrim($path, '/')]);
        exit;
    }

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid CSRF');
        redirect('products.php');
    }

    $productId = (int)($_POST['id'] ?? 0);
    if ($postAction === 'delete') {
        if ($productId && deleteById('products', $productId)) {
            logActivity('delete_product', 'Product #' . $productId);
            productCatalogSaveMessage();
        } else {
            flash('error', 'Produk tidak ditemukan.');
        }
        redirect('products.php');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $validImage = $image === ''
        || preg_match('#^(https?://|/|uploads/)#i', $image) === 1;

    if ($name === '') {
        flash('error', 'Nama produk wajib diisi.');
        redirect($productId ? "products.php?action=edit&id=$productId" : 'products.php?action=create');
    }
    if ($price !== '' && (!is_numeric($price) || (float)$price < 0)) {
        flash('error', 'Harga harus berupa angka nol atau lebih.');
        redirect($productId ? "products.php?action=edit&id=$productId" : 'products.php?action=create');
    }
    if (!$validImage) {
        flash('error', 'Gambar harus berupa URL http(s), path uploads/, atau path absolut situs.');
        redirect($productId ? "products.php?action=edit&id=$productId" : 'products.php?action=create');
    }
    if (!in_array($status, ['draft', 'published'], true)) {
        $status = 'draft';
    }

    $now = date('Y-m-d H:i:s');
    $fields = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'image' => $image,
        'status' => $status,
        'updated_at' => $now,
    ];
    if ($productId) {
        if (!findById('products', $productId)) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('products.php');
        }
        update('products', $productId, $fields);
        logActivity('update_product', $name);
    } else {
        $fields['created_at'] = $now;
        $productId = insert('products', $fields);
        logActivity('create_product', $name);
    }
    productCatalogSaveMessage();
    redirect('products.php');
}

$product = null;
if ($action === 'create' || $action === 'edit') {
    if ($action === 'edit' && $id) {
        $product = findById('products', $id);
        if (!$product) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('products.php');
        }
    }
}

$products = loadData('products');
usort($products, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$catalogPage = findOne('pages', fn($page) => ($page['status'] ?? '') === 'published'
    && stripos((string)($page['content'] ?? ''), '[product_catalog]') !== false);
$catalogUrl = $catalogPage ? publicPageUrl($catalogPage) : null;
include __DIR__ . '/includes/header.php';
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $product ? 'Edit Produk' : 'Tambah Produk' ?></h2>
        <a href="products.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($product): ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Nama Produk *</label>
                <input type="text" name="name" class="form-control" required value="<?= e($product['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4"><?= e($product['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="price" class="form-control" min="0" step="1" value="<?= e((string)($product['price'] ?? '')) ?>">
                    <div class="form-hint">Kosongkan jika harga ditampilkan sebagai “Hubungi kami”.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?= ($product['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($product['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">URL atau Path Gambar</label>
                <div style="display:flex;gap:10px;align-items:center">
                    <input type="text" name="image" id="productImage" class="form-control" placeholder="uploads/nama-gambar.jpg atau https://..." value="<?= e($product['image'] ?? '') ?>">
                    <button type="button" id="uploadProductImage" class="btn btn-secondary" style="flex-shrink:0"><i class="fas fa-upload"></i> Upload Gambar</button>
                    <input type="file" id="productImageFile" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                </div>
                <div class="form-hint">Unggah JPG, PNG, GIF, atau WEBP (maks. 5 MB), atau masukkan URL/path gambar.</div>
                <?php if (!empty($product['image'])):
                    $imageValue = (string)$product['image'];
                    $imagePreviewUrl = preg_match('#^(https?://|/)#i', $imageValue) ? $imageValue : '../' . ltrim($imageValue, '/');
                ?>
                <img id="productImagePreview" src="<?= e($imagePreviewUrl) ?>" alt="Pratinjau gambar produk" style="display:block;margin-top:12px;max-width:220px;max-height:160px;object-fit:contain;border:1px solid var(--border);border-radius:8px">
                <?php else: ?>
                <img id="productImagePreview" alt="Pratinjau gambar produk" style="display:none;margin-top:12px;max-width:220px;max-height:160px;object-fit:contain;border:1px solid var(--border);border-radius:8px">
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Produk</button>
                <a href="products.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info" style="display:block">
    <strong>Pasang katalog pada page tertentu:</strong> edit page pilihan, lalu sisipkan
    <code>[product_catalog]</code> pada konten. Hanya produk berstatus Published yang akan tampil.
    <a href="pages.php" style="text-decoration:underline">Pilih/edit page</a>.
</div>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Katalog Produk</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if ($catalogUrl): ?>
            <a href="<?= e($catalogUrl) ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye"></i> Lihat Katalog</a>
            <?php endif; ?>
            <a href="products.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Produk</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!$products): ?>
        <div class="empty-state"><i class="fas fa-box-open"></i><h3>Belum ada produk</h3><p>Tambahkan produk untuk mulai membuat katalog.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Produk</th><th>Harga</th><th>Status</th><th>Terakhir diubah</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($products as $item): ?>
                <tr>
                    <td style="font-weight:600"><?= e($item['name'] ?? '') ?></td>
                    <td><?= isset($item['price']) && $item['price'] !== '' ? 'Rp ' . number_format((float)$item['price'], 0, ',', '.') : 'Hubungi kami' ?></td>
                    <td><span class="badge badge-<?= ($item['status'] ?? '') === 'published' ? 'success' : 'warning' ?>"><?= e($item['status'] ?? 'draft') ?></span></td>
                    <td><?= formatDate($item['updated_at'] ?? $item['created_at'] ?? '') ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="products.php?action=edit&id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Hapus" data-confirm="Hapus produk ini?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadButton = document.getElementById('uploadProductImage');
    const fileInput = document.getElementById('productImageFile');
    const imageInput = document.getElementById('productImage');
    const preview = document.getElementById('productImagePreview');
    if (!uploadButton || !fileInput || !imageInput || !preview) return;

    uploadButton.addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', async function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const payload = new FormData();
        payload.append('action', 'upload_product_image');
        payload.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
        payload.append('image', file);
        uploadButton.disabled = true;
        try {
            const response = await fetch('products.php', { method: 'POST', body: payload });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Upload gambar gagal.');
            imageInput.value = result.path;
            preview.src = result.url;
            preview.style.display = 'block';
        } catch (error) {
            alert(error.message || 'Upload gagal. Periksa koneksi atau ukuran file.');
        } finally {
            uploadButton.disabled = false;
            fileInput.value = '';
        }
    });

    imageInput.addEventListener('input', function () {
        const value = this.value.trim();
        if (!value) {
            preview.removeAttribute('src');
            preview.style.display = 'none';
            return;
        }
        preview.src = /^(https?:\/\/|\/)/i.test(value) ? value : '../' + value.replace(/^\/+/, '');
        preview.style.display = 'block';
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
