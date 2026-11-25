<?php
require_once __DIR__.'/../../config/auth.php';admin_require_login();require_once __DIR__.'/../../config/database.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){$_SESSION['promotion_toast']=['type'=>'error','message'=>'Invalid promotion.'];header('Location: '.BASE_URL.'/admin/promotions/index.php');exit;}
$s=$pdo->prepare('SELECT * FROM promotions WHERE id=:id');$s->execute(['id'=>$id]);$p=$s->fetch();
if(!$p){$_SESSION['promotion_toast']=['type'=>'error','message'=>'Promotion not found.'];header('Location: '.BASE_URL.'/admin/promotions/index.php');exit;}
$old=['title'=>$p['title'],'description'=>$p['description']??'','discount_value'=>$p['discount_value'],'status'=>$p['status'],'start_date'=>$p['start_date']?date('Y-m-d\\TH:i',strtotime($p['start_date'])):'','end_date'=>$p['end_date']?date('Y-m-d\\TH:i',strtotime($p['end_date'])):''];$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
foreach($old as $k=>$v)$old[$k]=trim((string)($_POST[$k]??$v));
if(!admin_verify_csrf_token($_POST['csrf_token']??''))$errors[]='Session expired. Refresh and try again.';
if($old['title']===''||mb_strlen($old['title'])>180)$errors[]='Title is required (maximum 180 characters).';
if(strlen($old['description'])>65535)$errors[]='Description is too long.';
if(!is_numeric($old['discount_value'])||(float)$old['discount_value']<=0||(float)$old['discount_value']>100)$errors[]='Discount must be greater than 0 and no more than 100.';
if(!in_array($old['status'],['draft','active','expired','archived'],true))$errors[]='Invalid status.';
$dates=[];foreach(['start_date','end_date'] as $k){$dates[$k]=null;if($old[$k]!==''){$d=DateTime::createFromFormat('Y-m-d\\TH:i',$old[$k]);if(!$d||$d->format('Y-m-d\\TH:i')!==$old[$k])$errors[]='Enter valid dates.';else $dates[$k]=$d->format('Y-m-d H:i:s');}}
if($dates['start_date']&&$dates['end_date']&&strtotime($dates['end_date'])<=strtotime($dates['start_date']))$errors[]='End date must be later than start date.';
if(!$errors){$u=$pdo->prepare('UPDATE promotions SET title=:t,description=:d,discount_value=:v,status=:s,start_date=:a,end_date=:b WHERE id=:id');$u->execute(['t'=>$old['title'],'d'=>$old['description']?:null,'v'=>number_format((float)$old['discount_value'],2,'.',''),'s'=>$old['status'],'a'=>$dates['start_date'],'b'=>$dates['end_date'],'id'=>$id]);$_SESSION['promotion_toast']=['type'=>'success','message'=>'Promotion updated successfully.'];header('Location: '.BASE_URL.'/admin/promotions/index.php');exit;}
}
$csrf=admin_csrf_token();function promotion_edit_escape($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Promotion | Sweet Spot</title><link rel="stylesheet" href="<?=BASE_URL?>/assets/css/admin.css"></head><body class="dashboard-page">
<?php if($errors):?><div class="auth-toast is-visible" role="alert"><?=promotion_edit_escape($errors[0])?></div><?php endif;?>
<div class="admin-layout"><main class="admin-main"><header class="admin-topbar"><div class="breadcrumb"><a href="<?=BASE_URL?>/admin/promotions/index.php">Promotions</a> / <strong>Edit promotion</strong></div></header><div class="admin-content">
<div class="page-heading add-announcement-heading"><div><p class="admin-kicker">A LITTLE EXTRA SWEETNESS</p><h1>Edit <em>promotion.</em></h1><p>Update the offer details and schedule.</p></div><a class="admin-secondary-btn" href="<?=BASE_URL?>/admin/promotions/index.php">← Back</a></div>
<form class="announcement-editor-form" method="POST"><input type="hidden" name="csrf_token" value="<?=promotion_edit_escape($csrf)?>"><section class="admin-card editor-section announcement-editor-card"><div class="editor-section-heading"><span class="editor-step">01</span><div><h2>Promotion details</h2><p>Update the offer information.</p></div></div>
<div class="admin-field"><label for="title">Promotion title *</label><input id="title" name="title" maxlength="180" value="<?=promotion_edit_escape($old['title'])?>" required></div>
<div class="admin-field"><label for="description">Description</label><textarea id="description" name="description" rows="7"><?=promotion_edit_escape($old['description'])?></textarea><small class="field-hint">Informational only; this does not change product prices.</small></div>
<div class="admin-field promotion-discount-field"><label for="discount_value">Discount percentage *</label><div class="promotion-discount-input"><input id="discount_value" type="number" name="discount_value" min=".01" max="100" step=".01" value="<?=promotion_edit_escape($old['discount_value'])?>" required><span>% OFF</span></div></div></section>
<aside class="announcement-editor-side"><section class="admin-card editor-section announcement-settings-card"><div class="editor-section-heading"><span class="editor-step">02</span><div><h2>Promotion settings</h2><p>Status and offer period.</p></div></div>
<div class="admin-field"><label for="status">Status</label><select id="status" name="status"><?php foreach(['draft'=>'Draft','active'=>'Active','expired'=>'Expired','archived'=>'Archived'] as $v=>$label):?><option value="<?=$v?>" <?=$old['status']===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<div class="admin-field"><label for="start_date">Start date and time</label><input id="start_date" type="datetime-local" name="start_date" value="<?=promotion_edit_escape($old['start_date'])?>"></div>
<div class="admin-field"><label for="end_date">End date and time</label><input id="end_date" type="datetime-local" name="end_date" value="<?=promotion_edit_escape($old['end_date'])?>"></div></section></aside>
<div class="editor-form-actions announcement-form-actions"><a href="<?=BASE_URL?>/admin/promotions/index.php" class="admin-secondary-btn">Cancel</a><button class="admin-primary-btn" type="submit">Save changes ↗</button></div></form></div></main></div></body></html>