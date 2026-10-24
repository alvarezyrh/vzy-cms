<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle='SEO';$currentPage='seo';$breadcrumb='SEO';
$seoList=loadData('seo_settings');$seo=$seoList[0]??[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrf($_POST['csrf_token']??'')){flash('error','Invalid CSRF');redirect('seo.php');}
    $fields=['meta_title'=>trim($_POST['meta_title']??''),'meta_description'=>trim($_POST['meta_description']??''),
        'meta_keywords'=>trim($_POST['meta_keywords']??''),'og_image'=>trim($_POST['og_image']??''),
        'google_analytics'=>trim($_POST['google_analytics']??''),'robots_txt'=>$_POST['robots_txt']??'',
        'sitemap_enabled'=>isset($_POST['sitemap_enabled'])?1:0];
    if(!empty($seo['id'])) update('seo_settings',$seo['id'],$fields);
    else insert('seo_settings',array_merge($fields,['id'=>1]));
    flash('success','SEO disimpan.');redirect('seo.php');
}
$seoList=loadData('seo_settings');$seo=$seoList[0]??[];
include __DIR__.'/includes/header.php';
?>
<div class="card"><div class="card-header"><h2 class="card-title"><i class="fas fa-search" style="margin-right:8px;color:var(--primary)"></i> SEO Settings</h2></div>
<div class="card-body"><form method="POST"><?=csrfField()?>
<div class="form-group"><label class="form-label">Default Meta Title</label>
<input type="text" name="meta_title" class="form-control" value="<?=e($seo['meta_title']??'')?>"></div>
<div class="form-group"><label class="form-label">Default Meta Description</label>
<textarea name="meta_description" class="form-control" rows="3"><?=e($seo['meta_description']??'')?></textarea></div>
<div class="form-group"><label class="form-label">Default Meta Keywords</label>
<input type="text" name="meta_keywords" class="form-control" value="<?=e($seo['meta_keywords']??'')?>"></div>
<div class="form-group"><label class="form-label">OG Image URL</label>
<input type="text" name="og_image" class="form-control" value="<?=e($seo['og_image']??'')?>"></div>
<div class="form-group"><label class="form-label">Google Analytics / Tracking Code</label>
<textarea name="google_analytics" class="code-editor" rows="6"><?=e($seo['google_analytics']??'')?></textarea></div>
<div class="form-group"><label class="form-label">robots.txt</label>
<textarea name="robots_txt" class="code-editor" rows="6"><?=e($seo['robots_txt']??"User-agent: *\nAllow: /")?></textarea></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<div class="switch"><input type="checkbox" name="sitemap_enabled" value="1" <?=($seo['sitemap_enabled']??1)?'checked':''?>><span class="slider"></span></div>
<span style="font-size:13px;font-weight:600">Enable XML Sitemap</span></label></div>
<button type="submit" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-save"></i> Simpan SEO</button>
</form></div></div>
<?php include __DIR__.'/includes/footer.php';?>
