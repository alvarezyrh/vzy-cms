<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$pageTitle='Plugins';$currentPage='plugins';$breadcrumb='Plugins';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('plugins.php');}
    $pid=(int)($_POST['plugin_id']??0);
    if(($_POST['plugin_action']??'')==='toggle'&&$pid){
        $p=findById('plugins',$pid);
        if($p) update('plugins',$pid,['is_active'=>empty($p['is_active'])?1:0]);
        if($p && ($p['slug']??'')==='social-share') syncBlogToPublic();
        flash('success','Status plugin diperbarui.');
    }
    if(($_POST['plugin_action']??'')==='social_share_settings'){
        $allowed=array_keys(socialShareNetworks());
        $networks=array_values(array_intersect($allowed,(array)($_POST['networks']??[])));
        $position=$_POST['position']??'both';
        if(!in_array($position,['top','bottom','both'],true)) $position='both';
        $label=trim($_POST['label']??'')?:'Bagikan artikel ini';
        if(!$networks){flash('error','Pilih minimal satu jaringan social share.');redirect('plugins.php#social-share');}
        setSetting('social_share_networks',implode(',',$networks));
        setSetting('social_share_position',$position);
        setSetting('social_share_label',mb_substr($label,0,60));
        syncBlogToPublic();
        flash('success','Pengaturan Social Share disimpan dan artikel diperbarui.');
        redirect('plugins.php#social-share');
    }
    redirect('plugins.php');
}
$plugins=loadData('plugins');usort($plugins,fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));
include __DIR__.'/includes/header.php';
?>
<?php
$activeCount=count(array_filter($plugins,fn($p)=>!empty($p['is_active'])));
$inactiveCount=count($plugins)-$activeCount;
$pluginIcons=['contact-form'=>'fa-envelope','google-maps'=>'fa-map-marker-alt','social-share'=>'fa-share-alt','custom-code'=>'fa-code','maintenance-mode'=>'fa-tools'];
?>
<div class="card"><div class="card-header"><h2 class="card-title"><i class="fas fa-puzzle-piece" style="margin-right:8px;color:var(--primary)"></i> Plugins</h2>
<div class="plugin-summary">
<span class="plugin-summary-item is-active"><i class="fas fa-circle"></i> <?=$activeCount?> Aktif</span>
<span class="plugin-summary-item is-inactive"><i class="fas fa-circle"></i> <?=$inactiveCount?> Nonaktif</span>
</div></div>
<div class="card-body">
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Sistem plugin sederhana. Gunakan tombol di kanan untuk mengaktifkan atau menonaktifkan plugin.</div>
<div class="plugin-list">
<?php foreach($plugins as $plugin):
$isActive=!empty($plugin['is_active']);
$icon=$pluginIcons[$plugin['slug']??'']??'fa-puzzle-piece';
?>
<div class="plugin-item <?=$isActive?'is-active':'is-inactive'?>">
<div class="plugin-icon"><i class="fas <?=e($icon)?>"></i></div>
<div class="plugin-info">
<div class="plugin-title"><h3><?=e($plugin['name'])?></h3><span class="plugin-version">v<?=e($plugin['version']??'1.0')?></span></div>
<p><?=e($plugin['description']??'')?></p>
</div>
<span class="plugin-status"><i class="fas fa-circle"></i><?=$isActive?'Aktif':'Nonaktif'?></span>
<form method="POST" class="plugin-toggle"><?=csrfField()?><input type="hidden" name="plugin_id" value="<?=(int)$plugin['id']?>">
<input type="hidden" name="plugin_action" value="toggle">
<label class="switch" title="<?=$isActive?'Nonaktifkan':'Aktifkan'?>"><input type="checkbox" <?=$isActive?'checked':''?> aria-label="<?=($isActive?'Nonaktifkan ':'Aktifkan ').e($plugin['name'])?>" onchange="this.form.submit()"><span class="slider"></span></label>
</form>
<?php if(($plugin['slug']??'')==='social-share'): $share=socialShareSettings(); ?>
<details class="plugin-settings" id="social-share" <?=$isActive?'open':''?>>
<summary><i class="fas fa-sliders-h"></i> Pengaturan Social Share</summary>
<form method="POST" class="plugin-settings-form"><?=csrfField()?>
<input type="hidden" name="plugin_action" value="social_share_settings">
<div class="plugin-settings-group">
<span class="plugin-settings-label">Jaringan yang ditampilkan</span>
<div class="share-network-options">
<?php foreach(socialShareNetworks() as $key=>$net): ?>
<label class="share-network-option" style="--net-color:<?=e($net['color'])?>"><input type="checkbox" name="networks[]" value="<?=e($key)?>" <?=in_array($key,$share['networks'],true)?'checked':''?>><span><i class="<?=e($net['icon'])?>"></i><?=e($net['label'])?></span></label>
<?php endforeach; ?>
</div></div>
<div class="plugin-settings-row">
<div class="plugin-settings-group"><label class="plugin-settings-label" for="sharePosition">Posisi di artikel</label>
<select id="sharePosition" name="position" class="form-control">
<option value="both" <?=$share['position']==='both'?'selected':''?>>Atas &amp; bawah artikel</option>
<option value="top" <?=$share['position']==='top'?'selected':''?>>Atas (di bawah judul)</option>
<option value="bottom" <?=$share['position']==='bottom'?'selected':''?>>Bawah (setelah konten)</option>
</select></div>
<div class="plugin-settings-group"><label class="plugin-settings-label" for="shareLabel">Teks label</label>
<input id="shareLabel" type="text" name="label" class="form-control" maxlength="60" value="<?=e($share['label'])?>"></div>
</div>
<p class="plugin-settings-hint"><i class="fas fa-info-circle"></i> Tombol tampil di setiap artikel blog yang terbit. Matikan per artikel lewat opsi <strong>Social Share</strong> di form post, atau sematkan di tengah konten dengan tombol <strong>Sematkan Share</strong> (<code>[social_share]</code>).</p>
<div><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan Pengaturan</button></div>
</form>
</details>
<?php endif; ?>
</div>
<?php endforeach;?>
</div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
