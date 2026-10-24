<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$pageTitle = 'Users';
$currentPage = 'users';
$breadcrumb = 'Users';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { flash('error', 'Invalid CSRF'); redirect('users.php'); }
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'editor']) ? $_POST['role'] : 'editor';
    $password = $_POST['password'] ?? '';
    $uid = (int)($_POST['id'] ?? 0);

    if (!$username || !$email) { flash('error', 'Username & email wajib.'); redirect('users.php'); }

    if ($uid) {
        $exists = findOne('users', fn($u) => ($u['username'] === $username || $u['email'] === $email) && (int)$u['id'] !== $uid);
        if ($exists) { flash('error', 'Username/email sudah dipakai.'); redirect("users.php?action=edit&id=$uid"); }
        $fields = ['username' => $username, 'email' => $email, 'full_name' => $full_name, 'role' => $role, 'updated_at' => date('Y-m-d H:i:s')];
        if ($password) {
            if (strlen($password) < 6) { flash('error', 'Password min 6 karakter.'); redirect("users.php?action=edit&id=$uid"); }
            $fields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        update('users', $uid, $fields);
        logActivity('update_user', $username);
        flash('success', 'User diperbarui.');
    } else {
        $exists = findOne('users', fn($u) => $u['username'] === $username || $u['email'] === $email);
        if ($exists) { flash('error', 'Username/email sudah dipakai.'); redirect('users.php?action=create'); }
        if (strlen($password) < 6) { flash('error', 'Password min 6 karakter.'); redirect('users.php?action=create'); }
        insert('users', [
            'username' => $username, 'email' => $email, 'full_name' => $full_name, 'role' => $role,
            'password' => password_hash($password, PASSWORD_DEFAULT), 'avatar' => null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        logActivity('create_user', $username);
        flash('success', 'User dibuat.');
    }
    redirect('users.php');
}

if ($action === 'delete' && $id) {
    if ($id === (int)$user['id']) { flash('error', 'Tidak bisa hapus akun sendiri.'); redirect('users.php'); }
    $u = findById('users', $id);
    if ($u) {
        deleteById('users', $id);
        logActivity('delete_user', $u['username'] ?? '');
        flash('success', 'User dihapus.');
    }
    redirect('users.php');
}

include __DIR__ . '/includes/header.php';

if ($action === 'create' || $action === 'edit'):
    $edit = null;
    if ($action === 'edit' && $id) {
        $edit = findById('users', $id);
        if (!$edit) { flash('error', 'User tidak ditemukan.'); redirect('users.php'); }
    }
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $edit ? 'Edit User' : 'Tambah User' ?></h2>
        <a href="users.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required value="<?= e($edit['username'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($edit['email'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($edit['full_name'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Role *</label>
                    <select name="role" class="form-control">
                        <option value="editor" <?= ($edit['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select></div>
            </div>
            <div class="form-group"><label class="form-label">Password <?= $edit ? '(kosongkan jika tidak ubah)' : '*' ?></label>
                <input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?> minlength="6"></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </form>
    </div>
</div>
<?php else:
$users = loadData('users');
usort($users, fn($a, $b) => strcmp($a['username'] ?? '', $b['username'] ?? ''));
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Semua Users</h2>
        <a href="users.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah User</a>
    </div>
    <div class="card-body">
        <div class="table-responsive"><table>
            <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="user-avatar" style="width:32px;height:32px;font-size:12px"><?= strtoupper(substr($u['full_name'] ?? $u['username'], 0, 1)) ?></div>
                        <div>
                            <div style="font-weight:600"><?= e($u['full_name'] ?? $u['username']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)">@<?= e($u['username']) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge-<?= ($u['role'] ?? '') === 'admin' ? 'primary' : 'secondary' ?>"><?= e($u['role'] ?? 'editor') ?></span></td>
                <td><?= formatDate($u['created_at'] ?? '') ?></td>
                <td><div class="btn-group">
                    <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary btn-icon"><i class="fas fa-edit"></i></a>
                    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                    <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-danger btn-icon" data-confirm="Hapus user ini?"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
