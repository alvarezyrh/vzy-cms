<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$pageTitle='Settings';$currentPage='settings';$breadcrumb='Settings';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('settings.php');}
    foreach(['site_name','site_tagline','site_url','posts_per_page','timezone','date_format'] as $key){
        if(isset($_POST[$key])) setSetting($key,trim($_POST[$key]));
    }
    setSetting('allow_comments',isset($_POST['allow_comments'])?'1':'0');
    flash('success','Settings disimpan.');redirect('settings.php');
}
include __DIR__.'/includes/header.php';
?>
<div class="card"><div class="card-header"><h2 class="card-title"><i class="fas fa-cog" style="margin-right:8px;color:var(--primary)"></i> General Settings</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<div class="form-row">
<div class="form-group"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?=e(getSetting('site_name'))?>"></div>
<div class="form-group"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?=e(getSetting('site_tagline'))?>"></div></div>
<div class="form-group"><label class="form-label">Site URL</label><input type="url" name="site_url" class="form-control" value="<?=e(getSetting('site_url'))?>" placeholder="https://example.com"></div>
<div class="form-row">
<div class="form-group"><label class="form-label">Posts Per Page</label><input type="number" name="posts_per_page" class="form-control" min="1" max="100" value="<?=e(getSetting('posts_per_page','10'))?>"></div>
<div class="form-group"><label class="form-label">Date Format</label>
<select name="date_format" class="form-control">
<?php $formats=['d M Y'=>date('d M Y'),'Y-m-d'=>date('Y-m-d'),'d/m/Y'=>date('d/m/Y'),'M d, Y'=>date('M d, Y')];$cur=getSetting('date_format','d M Y');
foreach($formats as $fmt=>$ex):?><option value="<?=$fmt?>" <?=$cur===$fmt?'selected':''?>><?=$ex?> (<?=$fmt?>)</option><?php endforeach;?>
</select></div></div>
<div class="form-group"><label class="form-label">Timezone</label>
<select name="timezone" class="form-control">
<?php $tzs=['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC','America/New_York','Europe/London'];$ctz=getSetting('timezone','Asia/Jakarta');
foreach($tzs as $tz):?><option value="<?=$tz?>" <?=$ctz===$tz?'selected':''?>><?=$tz?></option><?php endforeach;?>
</select></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<div class="switch"><input type="checkbox" name="allow_comments" value="1" <?=getSetting('allow_comments','1')==='1'?'checked':''?>><span class="slider"></span></div>
<span style="font-size:13px;font-weight:600">Allow Comments</span></label></div>
<button type="submit" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-save"></i> Simpan Settings</button>
</form></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">System Info</h2></div>
<div class="card-body"><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
<div style="padding:14px;background:var(--bg-body);border-radius:10px"><div style="font-size:12px;color:var(--text-muted)">PHP Version</div><div style="font-weight:600"><?=PHP_VERSION?></div></div>
<div style="padding:14px;background:var(--bg-body);border-radius:10px"><div style="font-size:12px;color:var(--text-muted)">Storage</div><div style="font-weight:600">JSON Files</div></div>
<div style="padding:14px;background:var(--bg-body);border-radius:10px"><div style="font-size:12px;color:var(--text-muted)">Server</div><div style="font-weight:600"><?=$_SERVER['SERVER_SOFTWARE']??'CLI'?></div></div>
<div style="padding:14px;background:var(--bg-body);border-radius:10px"><div style="font-size:12px;color:var(--text-muted)">CMS Version</div><div style="font-weight:600">1.0.0 by vzy</div></div>
</div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
