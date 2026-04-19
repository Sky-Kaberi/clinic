<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $pdo->prepare('INSERT INTO radiology_workflow(booking_id, technician_id, report_text, status, approved_by) VALUES (?,?,?,?,?)')->execute([(int)$_POST['booking_id'], ($_POST['technician_id']?:null), trim($_POST['report_text']), $_POST['status'], $_POST['status']==='approved'?(int)$user['id']:null]);
    flash('Radiology entry saved.');
    header('Location:/public/index.php?module=radiology'); exit;
}
$bookings=$pdo->query("SELECT b.id, p.uhid, CONCAT(p.first_name,' ',p.last_name) patient_name FROM diagnostic_bookings b JOIN patients p ON p.id=b.patient_id WHERE b.booking_type IN ('imaging','ecg') ORDER BY b.id DESC LIMIT 100")->fetchAll();
$techs=$pdo->query("SELECT id,name FROM users WHERE role IN ('Technician','Radiologist')")->fetchAll();
$rows=$pdo->query('SELECT rw.*, u.name tech_name FROM radiology_workflow rw LEFT JOIN users u ON u.id=rw.technician_id ORDER BY rw.id DESC LIMIT 100')->fetchAll();
?>
<h4>Radiology / Imaging Workflow</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<div class="col-md-3"><select name="booking_id" class="form-select"><?php foreach($bookings as $b): ?><option value="<?= $b['id'] ?>">Booking #<?= $b['id'] ?> - <?= e($b['uhid'].' '.$b['patient_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="technician_id" class="form-select"><option value="">Assign Technician</option><?php foreach($techs as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-5"><input name="report_text" class="form-control" placeholder="Report findings"></div>
<div class="col-md-2"><select name="status" class="form-select"><option>draft</option><option>verified</option><option>approved</option></select></div>
<div class="col-md-2"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<table class="table table-sm"><tr><th>ID</th><th>Booking</th><th>Technician</th><th>Status</th><th>Report</th></tr><?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= $r['booking_id'] ?></td><td><?= e((string)$r['tech_name']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['report_text']) ?></td></tr><?php endforeach; ?></table>
