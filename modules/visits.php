<?php
$pdo = db();
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO visits(patient_id, doctor_id, appointment_id, symptoms, vitals, diagnosis, advice, follow_up_date, recommended_tests, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([(int)$_POST['patient_id'],(int)$_POST['doctor_id'],($_POST['appointment_id'] ?: null),(trim($_POST['symptoms'])),(trim($_POST['vitals'])),(trim($_POST['diagnosis'])),(trim($_POST['advice'])),($_POST['follow_up_date']?:null),(trim($_POST['recommended_tests'])),(int)$user['id']]);
    $visitId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO prescriptions(visit_id, medicines, notes, created_by) VALUES (?,?,?,?)');
    $stmt->execute([$visitId, trim($_POST['medicines']), trim($_POST['prescription_notes']), (int)$user['id']]);
    audit($pdo,(int)$user['id'],'create','visits','visit',$visitId);
    flash('OPD visit and prescription saved.');
    header('Location: ' . module_url('visits'));
    exit;
}
$patients=$pdo->query('SELECT id,uhid,first_name,last_name FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$doctors=$pdo->query('SELECT id,name FROM doctors WHERE is_active=1')->fetchAll();
$appointments=$pdo->query("SELECT id FROM appointments ORDER BY id DESC LIMIT 300")->fetchAll();
$rows=$pdo->query('SELECT v.*, CONCAT(p.first_name," ",p.last_name) patient_name, d.name doctor_name FROM visits v JOIN patients p ON p.id=v.patient_id JOIN doctors d ON d.id=v.doctor_id ORDER BY v.id DESC LIMIT 100')->fetchAll();
?>
<h4>Doctor Consultation / OPD</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<div class="col-md-3"><select name="patient_id" class="form-select" required><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="doctor_id" class="form-select" required><?php foreach($doctors as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="appointment_id" class="form-select"><option value="">No Appointment</option><?php foreach($appointments as $a): ?><option value="<?= $a['id'] ?>">#<?= $a['id'] ?></option><?php endforeach; ?></select></div>
<div class="col-md-5"><input name="symptoms" class="form-control" placeholder="Symptoms"></div>
<div class="col-md-4"><input name="vitals" class="form-control" placeholder="Vitals (BP/Temp/Pulse etc)"></div>
<div class="col-md-4"><input name="diagnosis" class="form-control" placeholder="Diagnosis"></div>
<div class="col-md-4"><input name="advice" class="form-control" placeholder="Advice"></div>
<div class="col-md-3"><input name="follow_up_date" type="date" class="form-control"></div>
<div class="col-md-5"><input name="recommended_tests" class="form-control" placeholder="Recommended tests"></div>
<div class="col-md-6"><textarea name="medicines" class="form-control" placeholder="Prescription medicines"></textarea></div>
<div class="col-md-4"><textarea name="prescription_notes" class="form-control" placeholder="Prescription notes"></textarea></div>
<div class="col-md-2"><button class="btn btn-primary">Save Visit</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Symptoms</th><th>Diagnosis</th><th>Follow-up</th><th>Date</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['patient_name']) ?></td><td><?= e($r['doctor_name']) ?></td><td><?= e($r['symptoms']) ?></td><td><?= e($r['diagnosis']) ?></td><td><?= e((string)$r['follow_up_date']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
</table>
