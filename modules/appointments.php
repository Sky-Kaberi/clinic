<?php
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book'])) {
        $stmt = $pdo->prepare('INSERT INTO appointments(patient_id, doctor_id, appointment_date, slot_time, status, token_no, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
        $token = random_int(100,999);
        $stmt->execute([(int)$_POST['patient_id'], (int)$_POST['doctor_id'], $_POST['appointment_date'], $_POST['slot_time'] ?: null, $_POST['status'], $token, trim($_POST['notes']), (int)$user['id']]);
        audit($pdo,(int)$user['id'],'create','appointments','appointment',(int)$pdo->lastInsertId());
        flash('Appointment booked.');
    }
    if (isset($_POST['update'])) {
        $stmt = $pdo->prepare('UPDATE appointments SET appointment_date=?, slot_time=?, status=?, notes=? WHERE id=?');
        $stmt->execute([$_POST['appointment_date'], $_POST['slot_time'] ?: null, $_POST['status'], trim($_POST['notes']), (int)$_POST['id']]);
        audit($pdo,(int)$user['id'],'update','appointments','appointment',(int)$_POST['id']);
        flash('Appointment updated.');
    }
    header('Location: /public/index.php?module=appointments');
    exit;
}

$patients = $pdo->query('SELECT id,uhid,first_name,last_name,mobile FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$doctors = $pdo->query("SELECT id,name FROM doctors WHERE is_active=1 ORDER BY name")->fetchAll();
$rows = $pdo->query('SELECT a.*, CONCAT(p.first_name," ",p.last_name) patient_name, p.uhid, d.name doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id ORDER BY a.id DESC LIMIT 100')->fetchAll();
?>
<h4>Appointment Management</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<input type="hidden" name="book" value="1">
<div class="col-md-3"><select class="form-select" name="patient_id" required><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' - '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-select" name="doctor_id" required><?php foreach($doctors as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input class="form-control" name="appointment_date" type="date" required value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><input class="form-control" name="slot_time" type="time"></div>
<div class="col-md-2"><select class="form-select" name="status"><option>booked</option><option>walk_in</option><option>follow_up</option><option>cancelled</option></select></div>
<div class="col-md-6"><input class="form-control" name="notes" placeholder="Notes"></div>
<div class="col-md-2"><button class="btn btn-primary">Book</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Slot</th><th>Status</th><th>Token</th><th>Action</th></tr>
<?php foreach($rows as $r): ?><tr>
<td><?= $r['id'] ?></td><td><?= e($r['uhid'].' '.$r['patient_name']) ?></td><td><?= e($r['doctor_name']) ?></td><td><?= e($r['appointment_date']) ?></td><td><?= e((string)$r['slot_time']) ?></td><td><?= e($r['status']) ?></td><td><?= e((string)$r['token_no']) ?></td>
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="update" value="1"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input name="appointment_date" type="date" value="<?= e($r['appointment_date']) ?>" class="form-control form-control-sm"><input name="slot_time" type="time" value="<?= e((string)$r['slot_time']) ?>" class="form-control form-control-sm"><select name="status" class="form-select form-select-sm"><option <?= $r['status']==='booked'?'selected':'' ?>>booked</option><option <?= $r['status']==='rescheduled'?'selected':'' ?>>rescheduled</option><option <?= $r['status']==='cancelled'?'selected':'' ?>>cancelled</option><option <?= $r['status']==='completed'?'selected':'' ?>>completed</option></select><input name="notes" value="<?= e($r['notes']) ?>" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-primary">Save</button></form></td>
</tr><?php endforeach; ?>
</table>
