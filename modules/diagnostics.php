<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt=$pdo->prepare('INSERT INTO diagnostic_bookings(patient_id, booking_type, priority, fasting_note, sample_schedule_at, status, created_by) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([(int)$_POST['patient_id'],$_POST['booking_type'],$_POST['priority'],trim($_POST['fasting_note']),($_POST['sample_schedule_at']?:null),'booked',(int)$user['id']]);
    $bookingId=(int)$pdo->lastInsertId();
    foreach($_POST['test_ids'] ?? [] as $tid){
        $pdo->prepare('INSERT INTO diagnostic_booking_tests(booking_id,test_id) VALUES(?,?)')->execute([$bookingId,(int)$tid]);
    }
    audit($pdo,(int)$user['id'],'create','diagnostics','diagnostic_booking',$bookingId);
    flash('Diagnostics booking created.'); header('Location:/public/index.php?module=diagnostics'); exit;
}
$patients=$pdo->query('SELECT id,uhid,first_name,last_name FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$tests=$pdo->query('SELECT id,name,category,price FROM test_master WHERE is_active=1 ORDER BY category,name')->fetchAll();
$rows=$pdo->query('SELECT b.*, CONCAT(p.first_name," ",p.last_name) patient_name,p.uhid FROM diagnostic_bookings b JOIN patients p ON p.id=b.patient_id ORDER BY b.id DESC LIMIT 100')->fetchAll();
?>
<h4>Diagnostics Booking</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<div class="col-md-3"><select class="form-select" name="patient_id" required><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="booking_type" class="form-select"><option>pathology</option><option>imaging</option><option>ecg</option><option>package</option></select></div>
<div class="col-md-2"><select name="priority" class="form-select"><option>normal</option><option>priority</option></select></div>
<div class="col-md-3"><input type="datetime-local" name="sample_schedule_at" class="form-control"></div>
<div class="col-md-2"><input name="fasting_note" class="form-control" placeholder="Fasting note"></div>
<div class="col-md-12"><label class="form-label small">Select Tests / Package Components</label><div class="row"><?php foreach($tests as $t): ?><div class="col-md-3"><label><input type="checkbox" name="test_ids[]" value="<?= $t['id'] ?>"> <?= e($t['name']) ?> (<?= money((float)$t['price']) ?>)</label></div><?php endforeach; ?></div></div>
<div class="col-md-2"><button class="btn btn-primary">Book</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>ID</th><th>Patient</th><th>Type</th><th>Priority</th><th>Schedule</th><th>Status</th></tr><?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['uhid'].' '.$r['patient_name']) ?></td><td><?= e($r['booking_type']) ?></td><td><?= e($r['priority']) ?></td><td><?= e((string)$r['sample_schedule_at']) ?></td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?></table>
