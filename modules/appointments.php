<?php
$pdo = db();
$user = current_user();
require_roles(['Super Admin', 'Receptionist', 'Front Desk Executive', 'Doctor']);

$validCreateStatus = ['booked', 'walk_in', 'follow_up', 'cancelled'];
$validUpdateStatus = ['booked', 'rescheduled', 'cancelled', 'completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['book'])) {
            $patientId = (int)($_POST['patient_id'] ?? 0);
            $doctorId = (int)($_POST['doctor_id'] ?? 0);
            $appointmentDate = trim((string)($_POST['appointment_date'] ?? ''));
            $slotTime = normalize_optional($_POST['slot_time'] ?? null);
            $status = trim((string)($_POST['status'] ?? ''));
            $notes = normalize_optional($_POST['notes'] ?? null);

            if ($patientId <= 0 || $doctorId <= 0 || $appointmentDate === '') {
                throw new InvalidArgumentException('Patient, doctor and appointment date are required.');
            }
            if (!in_array($status, $validCreateStatus, true)) {
                throw new InvalidArgumentException('Invalid appointment status selected.');
            }

            $patientCheck = $pdo->prepare('SELECT id FROM patients WHERE id = ? LIMIT 1');
            $patientCheck->execute([$patientId]);
            if (!$patientCheck->fetch()) {
                throw new InvalidArgumentException('Selected patient does not exist.');
            }

            $doctorCheck = $pdo->prepare('SELECT id FROM doctors WHERE id = ? AND is_active = 1 LIMIT 1');
            $doctorCheck->execute([$doctorId]);
            if (!$doctorCheck->fetch()) {
                throw new InvalidArgumentException('Selected doctor is unavailable.');
            }

            $duplicateStmt = $pdo->prepare('SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND ((slot_time IS NULL AND ? IS NULL) OR slot_time = ?) AND status <> "cancelled" LIMIT 1');
            $duplicateStmt->execute([$patientId, $doctorId, $appointmentDate, $slotTime, $slotTime]);
            if ($duplicateStmt->fetch()) {
                throw new InvalidArgumentException('Duplicate appointment detected for the same patient, doctor, date and time.');
            }

            if ($slotTime !== null) {
                $overlapStmt = $pdo->prepare('SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND slot_time = ? AND status <> "cancelled" LIMIT 1');
                $overlapStmt->execute([$doctorId, $appointmentDate, $slotTime]);
                if ($overlapStmt->fetch()) {
                    throw new InvalidArgumentException('Doctor already has an appointment in the selected time slot.');
                }
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO appointments(patient_id, doctor_id, appointment_date, slot_time, status, token_no, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
            $token = random_int(100, 999);
            $stmt->execute([$patientId, $doctorId, $appointmentDate, $slotTime, $status, $token, $notes, (int)$user['id']]);
            $appointmentId = (int)$pdo->lastInsertId();
            audit($pdo, (int)$user['id'], 'create', 'appointments', 'appointment', $appointmentId);
            $pdo->commit();
            flash('Appointment booked.');
        }

        if (isset($_POST['update'])) {
            $appointmentId = (int)($_POST['id'] ?? 0);
            $appointmentDate = trim((string)($_POST['appointment_date'] ?? ''));
            $slotTime = normalize_optional($_POST['slot_time'] ?? null);
            $status = trim((string)($_POST['status'] ?? ''));
            $notes = normalize_optional($_POST['notes'] ?? null);

            if ($appointmentId <= 0 || $appointmentDate === '') {
                throw new InvalidArgumentException('Appointment ID and date are required.');
            }
            if (!in_array($status, $validUpdateStatus, true)) {
                throw new InvalidArgumentException('Invalid appointment status selected.');
            }

            $existingStmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? LIMIT 1');
            $existingStmt->execute([$appointmentId]);
            $existing = $existingStmt->fetch();
            if (!$existing) {
                throw new InvalidArgumentException('Appointment not found.');
            }

            $duplicateStmt = $pdo->prepare('SELECT id FROM appointments WHERE id <> ? AND patient_id = ? AND doctor_id = ? AND appointment_date = ? AND ((slot_time IS NULL AND ? IS NULL) OR slot_time = ?) AND status <> "cancelled" LIMIT 1');
            $duplicateStmt->execute([$appointmentId, (int)$existing['patient_id'], (int)$existing['doctor_id'], $appointmentDate, $slotTime, $slotTime]);
            if ($duplicateStmt->fetch()) {
                throw new InvalidArgumentException('Duplicate appointment exists for the same patient, doctor, date and time.');
            }

            if ($slotTime !== null) {
                $overlapStmt = $pdo->prepare('SELECT id FROM appointments WHERE id <> ? AND doctor_id = ? AND appointment_date = ? AND slot_time = ? AND status <> "cancelled" LIMIT 1');
                $overlapStmt->execute([$appointmentId, (int)$existing['doctor_id'], $appointmentDate, $slotTime]);
                if ($overlapStmt->fetch()) {
                    throw new InvalidArgumentException('Doctor already has an appointment in this slot.');
                }
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE appointments SET appointment_date=?, slot_time=?, status=?, notes=? WHERE id=?');
            $stmt->execute([$appointmentDate, $slotTime, $status, $notes, $appointmentId]);
            audit($pdo, (int)$user['id'], 'update', 'appointments', 'appointment', $appointmentId);
            $pdo->commit();
            flash('Appointment updated.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash($e instanceof InvalidArgumentException ? $e->getMessage() : 'Could not save appointment. Please try again.', 'danger');
    }

    header('Location: ' . module_url('appointments'));
    exit;
}

$patients = $pdo->query('SELECT id,uhid,first_name,last_name,mobile FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$doctors = $pdo->query('SELECT id,name FROM doctors WHERE is_active=1 ORDER BY name')->fetchAll();
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
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="update" value="1"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input name="appointment_date" type="date" value="<?= e($r['appointment_date']) ?>" class="form-control form-control-sm"><input name="slot_time" type="time" value="<?= e((string)$r['slot_time']) ?>" class="form-control form-control-sm"><select name="status" class="form-select form-select-sm"><option <?= $r['status']==='booked'?'selected':'' ?>>booked</option><option <?= $r['status']==='rescheduled'?'selected':'' ?>>rescheduled</option><option <?= $r['status']==='cancelled'?'selected':'' ?>>cancelled</option><option <?= $r['status']==='completed'?'selected':'' ?>>completed</option></select><input name="notes" value="<?= e((string)$r['notes']) ?>" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-primary">Save</button></form></td>
</tr><?php endforeach; ?>
</table>
