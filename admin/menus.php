<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Menu';$currentPage='menus';$breadcrumb='Menu';
$menus=loadData('menus');$menu=$menus[0]??null;
if(!$menu){insert('menus',['name'=>'Primary Menu','location'=>'primary','created_at'=>date('Y-m-d H:i:s')]);$menus=loadData('menus');$menu=$menus[0];}
$menuId=$menu['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('menus.php');}
    $act=$_POST['form_action']??'';
    if($act==='navigation_settings'){
        setSetting('nav_brand',trim($_POST['nav_brand']??'PT MBG'));
        setSetting('nav_cta_label',trim($_POST['nav_cta_label']??'Hubungi Kami'));
        setSetting('nav_cta_url',trim($_POST['nav_cta_url']??'#contact'));
        syncWebsiteNavigation();
        flash('success','Pengaturan navigation website disimpan.');
    }
    if($act==='add_item'){
        $title=trim($_POST['title']??'');$url=trim($_POST['url']??'#');$type=$_POST['type']??'custom';$object_id=(int)($_POST['object_id']??0)?:null;
        if($title){$items=loadData('menu_items');$max=0;foreach($items as $i){if((int)$i['menu_id']===$menuId)$max=max($max,(int)($i['order_num']??0));}
            insert('menu_items',['menu_id'=>$menuId,'parent_id'=>0,'title'=>$title,'url'=>$url,'type'=>$type,'object_id'=>$object_id,'order_num'=>$max+1,'target'=>'_self']);
            flash('success','Item ditambahkan.');}
    }
    if($act==='update_items'){
        foreach(($_POST['items']??[]) as $itemId=>$data){
            update('menu_items',(int)$itemId,['title'=>trim($data['title']??''),'url'=>trim($data['url']??'#'),'order_num'=>(int)($data['order']??0)]);
        }
        flash('success','Menu disimpan.');
    }
    if($act==='delete_item'){deleteById('menu_items',(int)($_POST['item_id']??0));flash('success','Item dihapus.');}
    syncWebsiteNavigation();
    redirect('menus.php');
}
$menuItems=findWhere('menu_items',fn($r)=>(int)$r['menu_id']===$menuId);
usort($menuItems,fn($a,$b)=>($a['order_num']??0)<=>($b['order_num']??0));
$availablePages=findWhere('pages',fn($p)=>($p['status']??'')==='published');
$availablePosts=array_slice(findWhere('posts',fn($p)=>($p['status']??'')==='published'),0,50);
include __DIR__.'/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px">
<div class="card" style="grid-column:1/-1"><div class="card-header"><h2 class="card-title">Pengaturan Navigation Website</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?><input type="hidden" name="form_action" value="navigation_settings">
<div class="form-row">
<div class="form-group"><label class="form-label">Logo</label><div style="display:flex;align-items:center;gap:12px;min-height:44px"><img src="/public/assets/brand/mbg-mark.svg" alt="Logo PT MBG" width="40" height="40" style="border-radius:11px"><span class="form-hint" style="margin:0">Logo dipasang otomatis dari <code>public/assets/brand/</code>. Ganti file SVG di sana untuk memperbarui logo di seluruh website.</span></div></div>
<div class="form-group"><label class="form-label">Nama Brand</label><input type="text" name="nav_brand" class="form-control" value="<?=e(getSetting('nav_brand','PT MBG'))?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label class="form-label">Label Tombol CTA</label><input type="text" name="nav_cta_label" class="form-control" value="<?=e(getSetting('nav_cta_label','Hubungi Kami'))?>"></div>
<div class="form-group"><label class="form-label">URL Tombol CTA</label><input type="text" name="nav_cta_url" class="form-control" value="<?=e(getSetting('nav_cta_url','#contact'))?>"></div>
</div>
<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan Pengaturan</button></form></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Tambah Menu Item</h2></div>
<div class="card-body">
<div class="tabs"><button type="button" class="tab-btn active" data-tab="tab-custom">Custom</button>
<button type="button" class="tab-btn" data-tab="tab-pages">Pages</button>
<button type="button" class="tab-btn" data-tab="tab-posts">Posts</button></div>
<div id="tab-custom" class="tab-content active"><form method="POST"><?=csrfField()?>
<input type="hidden" name="form_action" value="add_item"><input type="hidden" name="type" value="custom">
<div class="form-group"><label class="form-label">Label *</label><input type="text" name="title" class="form-control" required placeholder="Home"></div>
<div class="form-group"><label class="form-label">URL</label><input type="text" name="url" class="form-control" value="/"></div>
<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah</button></form></div>
<div id="tab-pages" class="tab-content"><?php if(empty($availablePages)):?><p style="color:var(--text-muted);font-size:13px">Belum ada page.</p>
<?php else:foreach($availablePages as $pg):?><form method="POST" style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
<?=csrfField()?><input type="hidden" name="form_action" value="add_item"><input type="hidden" name="type" value="page">
<input type="hidden" name="object_id" value="<?=$pg['id']?>"><input type="hidden" name="title" value="<?=e($pg['title'])?>">
<input type="hidden" name="url" value="<?=e(publicPageUrl($pg))?>">
<span style="flex:1;font-size:13px"><?=e($pg['title'])?></span>
<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-plus"></i></button></form><?php endforeach;endif;?></div>
<div id="tab-posts" class="tab-content"><?php if(empty($availablePosts)):?><p style="color:var(--text-muted);font-size:13px">Belum ada post.</p>
<?php else:foreach($availablePosts as $pt):?><form method="POST" style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
<?=csrfField()?><input type="hidden" name="form_action" value="add_item"><input type="hidden" name="type" value="post">
<input type="hidden" name="object_id" value="<?=$pt['id']?>"><input type="hidden" name="title" value="<?=e($pt['title'])?>">
<input type="hidden" name="url" value="/post/<?=e($pt['slug'])?>">
<span style="flex:1;font-size:13px"><?=e($pt['title'])?></span>
<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-plus"></i></button></form><?php endforeach;endif;?></div>
</div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Primary Menu Structure</h2></div>
<div class="card-body"><?php if(empty($menuItems)):?><div class="empty-state"><i class="fas fa-bars"></i><h3>Menu kosong</h3></div>
<?php else:?><form method="POST"><?=csrfField()?><input type="hidden" name="form_action" value="update_items">
<?php foreach($menuItems as $item):?>
<div style="background:var(--bg-body);border-radius:12px;padding:14px;margin-bottom:10px;display:flex;align-items:center;gap:12px">
<i class="fas fa-grip-vertical" style="color:var(--text-muted)"></i>
<div style="flex:1"><input type="text" name="items[<?=$item['id']?>][title]" class="form-control" value="<?=e($item['title'])?>" style="margin-bottom:6px">
<input type="text" name="items[<?=$item['id']?>][url]" class="form-control" value="<?=e($item['url'])?>" style="font-size:12px">
<input type="hidden" name="items[<?=$item['id']?>][order]" value="<?=$item['order_num']??0?>"></div>
<button type="submit" name="form_action" value="delete_item" formaction="menus.php" class="btn btn-sm btn-danger btn-icon" data-confirm="Hapus?"
onclick="this.form.item_id.value=<?=$item['id']?>"><i class="fas fa-trash"></i></button>
</div><?php endforeach;?>
<input type="hidden" name="item_id" value="">
<button type="submit" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-save"></i> Simpan Menu</button>
</form><?php endif;?></div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
