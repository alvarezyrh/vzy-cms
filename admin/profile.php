<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Profile';$currentPage='profile';$breadcrumb='Profile';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('profile.php');}
    $full_name=trim($_POST['full_name']??'');$email=trim($_POST['email']??'');
    update('users',$user['id'],['full_name'=>$full_name,'email'=>$email,'updated_at'=>date('Y-m-d H:i:s')]);
    $new_password=$_POST['new_password']??'';$current_password=$_POST['current_password']??'';$confirm=$_POST['confirm_password']??'';
    if($new_password){
        $full=findById('users',$user['id']);
        if(!password_verify($current_password,$full['password'])){flash('error','Password saat ini salah.');redirect('profile.php');}
        if($new_password!==$confirm){flash('error','Konfirmasi tidak cocok.');redirect('profile.php');}
        if(strlen($new_password)<6){flash('error','Minimal 6 karakter.');redirect('profile.php');}
        update('users',$user['id'],['password'=>password_hash($new_password,PASSWORD_DEFAULT)]);
        flash('success','Profile & password diperbarui.');
    } else { flash('success','Profile diperbarui.'); }
    redirect('profile.php');
}
$user=currentUser();
include __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px">
<div class="card" style="text-align:center">
<div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:white;font-size:36px;font-weight:700;margin:0 auto 16px;box-shadow:0 8px 24px rgba(13,148,136,.35)">
<?=strtoupper(substr($user['full_name']??$user['username'],0,1))?></div>
<h2 style="font-size:18px;margin-bottom:4px"><?=e($user['full_name']??$user['username'])?></h2>
<p style="color:var(--text-muted);font-size:13px;margin-bottom:8px"><?=e($user['email'])?></p>
<span class="badge badge-primary" style="text-transform:capitalize"><?=e($user['role'])?></span>
</div>
<div class="card"><div class="card-header"><h2 class="card-title">Edit Profile</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<div class="form-row">
<div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?=e($user['full_name']??'')?>"></div>
<div class="form-group"><label class="form-label">Username</label><input type="text" class="form-control" value="<?=e($user['username'])?>" disabled>
<div class="form-hint">Tidak bisa diubah</div></div></div>
<div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=e($user['email'])?>" required></div>
<hr style="border:none;border-top:1px solid var(--border);margin:24px 0">
<h3 style="font-size:14px;margin-bottom:16px;color:var(--text-secondary)">Ubah Password</h3>
<div class="form-group"><label class="form-label">Password Saat Ini</label><input type="password" name="current_password" class="form-control" placeholder="Kosongkan jika tidak ubah"></div>
<div class="form-row">
<div class="form-group"><label class="form-label">Password Baru</label><input type="password" name="new_password" class="form-control"></div>
<div class="form-group"><label class="form-label">Konfirmasi</label><input type="password" name="confirm_password" class="form-control"></div></div>
<button type="submit" class="btn btn-primary" style="margin-top:8px"><i class="fas fa-save"></i> Simpan Profile</button>
</form></div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
