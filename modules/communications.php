<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $pdo->prepare('INSERT INTO communication_logs(patient_id, channel, template_name, message_body, delivery_status, sent_by) VALUES (?,?,?,?,?,?)')->execute([($_POST['patient_id']?:null),$_POST['channel'],trim($_POST['template_name']),trim($_POST['message_body']),'queued',(int)$user['id']]);
    flash('Message queued (gateway integration placeholder).');
    header('Location:/public/index.php?module=communications'); exit;
}
$patients=$pdo->query('SELECT id,uhid,first_name,last_name,mobile FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$rows=$pdo->query('SELECT cl.*, CONCAT(p.first_name," ",p.last_name) patient_name FROM communication_logs cl LEFT JOIN patients p ON p.id=cl.patient_id ORDER BY cl.id DESC LIMIT 100')->fetchAll();
?>
<h4>Communication Module</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><div class="col-md-3"><select name="patient_id" class="form-select"><option value="">General broadcast / promo</option><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select name="channel" class="form-select"><option>SMS</option><option>Email</option><option>WhatsApp</option></select></div><div class="col-md-2"><input name="template_name" class="form-control" placeholder="Template"></div><div class="col-md-4"><input name="message_body" class="form-control" placeholder="Message"></div><div class="col-md-1"><button class="btn btn-primary">Queue</button></div></form></div></div>
<table class="table table-sm"><tr><th>ID</th><th>Patient</th><th>Channel</th><th>Template</th><th>Status</th><th>Created</th></tr><?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e((string)$r['patient_name']) ?></td><td><?= e($r['channel']) ?></td><td><?= e($r['template_name']) ?></td><td><?= e($r['delivery_status']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?></table>
