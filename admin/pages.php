<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Pages';$currentPage='pages';$breadcrumb='Pages';
$action=$_GET['action']??'list';$id=(int)($_GET['id']??0);

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('pages.php');}
    $title=trim($_POST['title']??'');$slug=uniqueSlug('pages',trim($_POST['slug']??'')?:slugify($title),(int)($_POST['id']??0));
    $content=$_POST['content']??'';$custom_css=$_POST['custom_css']??'';$custom_js=$_POST['custom_js']??'';
    $status=$_POST['status']??'draft';$is_frontpage=isset($_POST['is_frontpage'])?1:0;
    $seo_title=trim($_POST['seo_title']??'');$seo_description=trim($_POST['seo_description']??'');$seo_keywords=trim($_POST['seo_keywords']??'');
    $page_id=(int)($_POST['id']??0);
    $oldPage=$page_id?findById('pages',$page_id):null;
    if(!$title){flash('error','Judul wajib.');redirect($page_id?"pages.php?action=edit&id=$page_id":'pages.php?action=create');}
    if($is_frontpage){foreach(loadData('pages') as $p){if(!empty($p['is_frontpage'])) update('pages',$p['id'],['is_frontpage'=>0]);}}
    $now=date('Y-m-d H:i:s');
    if($page_id){
        update('pages',$page_id,['title'=>$title,'slug'=>$slug,'content'=>$content,'custom_css'=>$custom_css,'custom_js'=>$custom_js,
            'status'=>$status,'is_frontpage'=>$is_frontpage,'seo_title'=>$seo_title,'seo_description'=>$seo_description,'seo_keywords'=>$seo_keywords,'updated_at'=>$now]);
        flash('success','Page diperbarui.');
        if($oldPage && ($oldPage['slug']??'')!==$slug) removePageFromPublic($oldPage['slug']);
        $savedPage=findById('pages',$page_id);
    } else {
        $newPageId=insert('pages',['title'=>$title,'slug'=>$slug,'content'=>$content,'custom_css'=>$custom_css,'custom_js'=>$custom_js,
            'status'=>$status,'is_frontpage'=>$is_frontpage,'author_id'=>$user['id'],'seo_title'=>$seo_title,
            'seo_description'=>$seo_description,'seo_keywords'=>$seo_keywords,'created_at'=>$now,'updated_at'=>$now]);
        flash('success','Page dibuat.');
        $savedPage=findById('pages',$newPageId);
    }
    if($savedPage){syncPageToPublic($savedPage);syncPageMenuItem($savedPage);}
    redirect('pages.php');
}
if($action==='delete'&&$id){$deletedPage=findById('pages',$id);update('pages',$id,['status'=>'trash']);if($deletedPage)removePageFromPublic($deletedPage['slug']??'');flash('success','Page ke trash.');redirect('pages.php');}
if($action==='set_frontpage'&&$id){
    foreach(loadData('pages') as $p){if(!empty($p['is_frontpage'])) update('pages',$p['id'],['is_frontpage'=>0]);}
    update('pages',$id,['is_frontpage'=>1,'status'=>'published']);$frontPage=findById('pages',$id);if($frontPage)syncPageToPublic($frontPage);flash('success','Jadi Frontpage.');redirect('pages.php');
}
include __DIR__.'/includes/header.php';

if($action==='create'||$action==='edit'):
$page=null;
if($action==='edit'&&$id){$page=findById('pages',$id);if(!$page){flash('error','Tidak ditemukan.');redirect('pages.php');}}
?>
<div class="card"><div class="card-header"><h2 class="card-title"><?=$action==='edit'?'Edit Page':'Buat Page Baru'?></h2>
<a href="pages.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<?php if($page):?><input type="hidden" name="id" value="<?=$page['id']?>"><?php endif;?>
<div class="form-group"><label class="form-label">Judul *</label><input type="text" name="title" id="title" class="form-control" required value="<?=e($page['title']??'')?>"></div>
<div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" id="slug" class="form-control" value="<?=e($page['slug']??'')?>"></div>
<div class="tabs">
<button type="button" class="tab-btn active" data-tab="tab-content">Content (HTML)</button>
<button type="button" class="tab-btn" data-tab="tab-css">Custom CSS</button>
<button type="button" class="tab-btn" data-tab="tab-js">Custom JS</button>
<button type="button" class="tab-btn" data-tab="tab-seo">SEO</button>
</div>
<div id="tab-content" class="tab-content active"><div class="form-group"><label class="form-label">HTML Content</label>
<textarea name="content" class="code-editor" rows="16"><?=e($page['content']??'')?></textarea>
<div class="form-hint">Full HTML untuk page ini</div></div></div>
<div id="tab-css" class="tab-content"><div class="form-group"><label class="form-label">Custom CSS</label>
<textarea name="custom_css" class="code-editor" rows="14"><?=e($page['custom_css']??'')?></textarea></div></div>
<div id="tab-js" class="tab-content"><div class="form-group"><label class="form-label">Custom JavaScript</label>
<textarea name="custom_js" class="code-editor" rows="14"><?=e($page['custom_js']??'')?></textarea></div></div>
<div id="tab-seo" class="tab-content">
<div class="form-group"><label class="form-label">SEO Title</label><input type="text" name="seo_title" class="form-control" value="<?=e($page['seo_title']??'')?>"></div>
<div class="form-group"><label class="form-label">SEO Description</label><textarea name="seo_description" class="form-control" rows="2"><?=e($page['seo_description']??'')?></textarea></div>
<div class="form-group"><label class="form-label">SEO Keywords</label><input type="text" name="seo_keywords" class="form-control" value="<?=e($page['seo_keywords']??'')?>"></div>
</div>
<div class="form-row" style="margin-top:16px">
<div class="form-group"><label class="form-label">Status</label>
<select name="status" class="form-control">
<option value="draft" <?=($page['status']??'')==='draft'?'selected':''?>>Draft</option>
<option value="published" <?=($page['status']??'')==='published'?'selected':''?>>Published</option>
</select></div>
<div class="form-group"><label class="form-label">Frontpage</label>
<div style="padding-top:8px"><label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<div class="switch"><input type="checkbox" name="is_frontpage" value="1" <?=($page['is_frontpage']??0)?'checked':''?>><span class="slider"></span></div>
<span style="font-size:13px">Jadikan Frontpage</span></label></div></div>
</div>
<div style="display:flex;gap:10px;margin-top:20px">
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Page</button>
<a href="pages.php" class="btn btn-secondary">Batal</a></div>
</form></div></div>
<?php else:
$pages=array_values(array_filter(loadData('pages'),fn($p)=>($p['status']??'')!=='trash'));
usort($pages, function($a,$b){ if(($b['is_frontpage']??0)!=($a['is_frontpage']??0)) return ($b['is_frontpage']??0)<=>($a['is_frontpage']??0); return strcmp($b['created_at']??'',$a['created_at']??''); });
?>
<div class="card"><div class="card-header"><h2 class="card-title">Semua Pages</h2>
<a href="pages.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Page</a></div>
<div class="card-body"><?php if(empty($pages)):?>
<div class="empty-state"><i class="fas fa-file-alt"></i><h3>Belum ada page</h3>
<a href="pages.php?action=create" class="btn btn-primary btn-sm" style="margin-top:12px"><i class="fas fa-plus"></i> Buat Page</a></div>
<?php else:?><div class="table-responsive"><table>
<thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Frontpage</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php foreach($pages as $p):?>
<tr><td style="font-weight:600"><?=e($p['title'])?><?php if(!empty($p['is_frontpage'])):?><span class="badge badge-info" style="margin-left:6px">Frontpage</span><?php endif;?></td>
<td><code style="font-size:12px;color:var(--text-muted)"><?=e($p['slug'])?></code></td>
<td><span class="badge badge-<?=($p['status']??'')==='published'?'success':'warning'?>"><?=e($p['status']??'')?></span></td>
<td><?php if(!empty($p['is_frontpage'])):?><a href="<?=e(publicPageUrl($p))?>" target="_blank" rel="noopener noreferrer" title="Buka halaman di tab baru"><i class="fas fa-home" style="color:var(--primary)"></i></a>
<?php else:?><a href="pages.php?action=set_frontpage&id=<?=$p['id']?>" class="btn btn-sm btn-secondary" title="Set Frontpage"><i class="fas fa-home"></i></a><?php endif;?></td>
<td><?=formatDate($p['created_at']??'')?></td>
<td><div class="btn-group">
<a href="pages.php?action=edit&id=<?=$p['id']?>" class="btn btn-sm btn-secondary btn-icon"><i class="fas fa-edit"></i></a>
<a href="pages.php?action=delete&id=<?=$p['id']?>" class="btn btn-sm btn-danger btn-icon" data-confirm="Trash?"><i class="fas fa-trash"></i></a>
</div></td></tr>
<?php endforeach;?></tbody></table></div><?php endif;?>
</div></div>
<?php endif;?>
<?php include __DIR__.'/includes/footer.php';?>
