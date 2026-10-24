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
        flash('success','Status plugin diperbarui.');
    }
    redirect('plugins.php');
}
$plugins=loadData('plugins');usort($plugins,fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));
include __DIR__.'/includes/header.php';
?>
<div class="card"><div class="card-header"><h2 class="card-title"><i class="fas fa-puzzle-piece" style="margin-right:8px;color:var(--primary)"></i> Plugins</h2></div>
<div class="card-body">
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Sistem plugin sederhana. Toggle on/off di bawah.</div>
<div style="display:grid;gap:12px">
<?php foreach($plugins as $plugin):?>
<div style="background:var(--bg-body);border-radius:14px;padding:20px;display:flex;align-items:center;gap:16px">
<div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:white;font-size:20px;flex-shrink:0"><i class="fas fa-puzzle-piece"></i></div>
<div style="flex:1"><div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
<h3 style="font-size:15px;font-weight:700"><?=e($plugin['name'])?></h3>
<span class="badge badge-secondary">v<?=e($plugin['version']??'1.0')?></span>
<span class="badge badge-<?=!empty($plugin['is_active'])?'success':'secondary'?>"><?=!empty($plugin['is_active'])?'Active':'Inactive'?></span>
</div><p style="font-size:13px;color:var(--text-muted);margin:0"><?=e($plugin['description']??'')?></p></div>
<form method="POST"><?=csrfField()?><input type="hidden" name="plugin_id" value="<?=$plugin['id']?>">
<input type="hidden" name="plugin_action" value="toggle">
<label class="switch"><input type="checkbox" <?=!empty($plugin['is_active'])?'checked':''?> onchange="this.form.submit()"><span class="slider"></span></label>
</form></div>
<?php endforeach;?>
</div></div></div>
<?php include __DIR__.'/includes/footer.php';?>
