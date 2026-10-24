<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Categories';$currentPage='categories';$breadcrumb='Categories';
$action=$_GET['action']??'list';$id=(int)($_GET['id']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('categories.php');}
    $name=trim($_POST['name']??'');$slug=uniqueSlug('categories',trim($_POST['slug']??'')?:slugify($name),(int)($_POST['id']??0));
    $desc=trim($_POST['description']??'');$cat_id=(int)($_POST['id']??0);
    if(!$name){flash('error','Nama wajib.');redirect('categories.php');}
    if($cat_id){update('categories',$cat_id,['name'=>$name,'slug'=>$slug,'description'=>$desc]);flash('success','Kategori diperbarui.');}
    else{insert('categories',['name'=>$name,'slug'=>$slug,'description'=>$desc,'created_at'=>date('Y-m-d H:i:s')]);flash('success','Kategori ditambahkan.');}
    redirect('categories.php');
}
if($action==='delete'&&$id){deleteById('categories',$id);flash('success','Dihapus.');redirect('categories.php');}
$editCat=($action==='edit'&&$id)?findById('categories',$id):null;
$categories=loadData('categories');usort($categories,fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));
$posts=loadData('posts');
include __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px">
<div class="card"><div class="card-header"><h2 class="card-title"><?=$editCat?'Edit':'Tambah'?> Category</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<?php if($editCat):?><input type="hidden" name="id" value="<?=$editCat['id']?>"><?php endif;?>
<div class="form-group"><label class="form-label">Nama *</label><input type="text" name="name" id="title" class="form-control" required value="<?=e($editCat['name']??'')?>"></div>
<div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" id="slug" class="form-control" value="<?=e($editCat['slug']??'')?>"></div>
<div class="form-group"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="3"><?=e($editCat['description']??'')?></textarea></div>
<div style="display:flex;gap:8px"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
<?php if($editCat):?><a href="categories.php" class="btn btn-secondary">Batal</a><?php endif;?></div>
</form></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Daftar Categories</h2></div>
<div class="card-body"><?php if(empty($categories)):?><div class="empty-state"><i class="fas fa-folder"></i><h3>Belum ada kategori</h3></div>
<?php else:?><div class="table-responsive"><table><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
<?php foreach($categories as $cat):$pc=count(array_filter($posts,fn($p)=>($p['category_id']??0)==$cat['id']&&($p['status']??'')!=='trash'));?>
<tr><td style="font-weight:600"><?=e($cat['name'])?></td><td><code style="font-size:12px;color:var(--text-muted)"><?=e($cat['slug'])?></code></td>
<td><span class="badge badge-primary"><?=$pc?></span></td>
<td><div class="btn-group">
<a href="categories.php?action=edit&id=<?=$cat['id']?>" class="btn btn-sm btn-secondary btn-icon"><i class="fas fa-edit"></i></a>
<a href="categories.php?action=delete&id=<?=$cat['id']?>" class="btn btn-sm btn-danger btn-icon" data-confirm="Hapus?"><i class="fas fa-trash"></i></a>
</div></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
</div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
