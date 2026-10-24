<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Tags';$currentPage='tags';$breadcrumb='Tags';
$action=$_GET['action']??'list';$id=(int)($_GET['id']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('tags.php');}
    $name=trim($_POST['name']??'');$slug=uniqueSlug('tags',trim($_POST['slug']??'')?:slugify($name),(int)($_POST['id']??0));
    $tag_id=(int)($_POST['id']??0);
    if(!$name){flash('error','Nama wajib.');redirect('tags.php');}
    if($tag_id){update('tags',$tag_id,['name'=>$name,'slug'=>$slug]);flash('success','Tag diperbarui.');}
    else{insert('tags',['name'=>$name,'slug'=>$slug,'created_at'=>date('Y-m-d H:i:s')]);flash('success','Tag ditambahkan.');}
    redirect('tags.php');
}
if($action==='delete'&&$id){deleteById('tags',$id);flash('success','Dihapus.');redirect('tags.php');}
$editTag=($action==='edit'&&$id)?findById('tags',$id):null;
$tags=loadData('tags');usort($tags,fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));
$pt=loadData('post_tags');
include __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px">
<div class="card"><div class="card-header"><h2 class="card-title"><?=$editTag?'Edit':'Tambah'?> Tag</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<?php if($editTag):?><input type="hidden" name="id" value="<?=$editTag['id']?>"><?php endif;?>
<div class="form-group"><label class="form-label">Nama *</label><input type="text" name="name" id="title" class="form-control" required value="<?=e($editTag['name']??'')?>"></div>
<div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" id="slug" class="form-control" value="<?=e($editTag['slug']??'')?>"></div>
<div style="display:flex;gap:8px"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
<?php if($editTag):?><a href="tags.php" class="btn btn-secondary">Batal</a><?php endif;?></div>
</form></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Daftar Tags</h2></div>
<div class="card-body"><?php if(empty($tags)):?><div class="empty-state"><i class="fas fa-tags"></i><h3>Belum ada tag</h3></div>
<?php else:?><div style="display:flex;flex-wrap:wrap;gap:10px">
<?php foreach($tags as $tag):$pc=count(array_filter($pt,fn($r)=>(int)$r['tag_id']===$tag['id']));?>
<div style="background:var(--bg-body);border-radius:50px;padding:8px 16px;display:flex;align-items:center;gap:10px">
<span style="font-weight:600;font-size:13px"><?=e($tag['name'])?></span><span class="badge badge-primary"><?=$pc?></span>
<a href="tags.php?action=edit&id=<?=$tag['id']?>" style="color:var(--text-muted);font-size:12px"><i class="fas fa-edit"></i></a>
<a href="tags.php?action=delete&id=<?=$tag['id']?>" style="color:var(--danger);font-size:12px" data-confirm="Hapus?"><i class="fas fa-times"></i></a>
</div><?php endforeach;?></div><?php endif;?>
</div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
