<?php
$pdo=db();
$daily=$pdo->query("SELECT DATE(created_at) d, SUM(paid_amount) collection, SUM(total_amount) revenue FROM bills GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 30")->fetchAll();
$testWise=$pdo->query("SELECT tm.name, COUNT(*) volume, SUM(tm.price) revenue FROM diagnostic_booking_tests dbt JOIN test_master tm ON tm.id=dbt.test_id GROUP BY tm.id ORDER BY revenue DESC LIMIT 20")->fetchAll();
$doctorWise=$pdo->query("SELECT d.name, COUNT(v.id) visits FROM visits v JOIN doctors d ON d.id=v.doctor_id GROUP BY d.id ORDER BY visits DESC")->fetchAll();
$pendingReports=$pdo->query("SELECT s.id sample_id, s.barcode, r.status FROM samples s LEFT JOIN reports r ON r.sample_id=s.id WHERE r.id IS NULL OR r.status!='approved' ORDER BY s.id DESC LIMIT 50")->fetchAll();
$cancelled=$pdo->query("SELECT * FROM appointments WHERE status='cancelled' ORDER BY appointment_date DESC LIMIT 50")->fetchAll();
$visitSummary=$pdo->query("SELECT p.uhid, CONCAT(p.first_name,' ',p.last_name) patient_name, COUNT(v.id) visit_count FROM patients p LEFT JOIN visits v ON v.patient_id=p.id GROUP BY p.id ORDER BY visit_count DESC LIMIT 50")->fetchAll();
?>
<h4>MIS Reports</h4>
<div class="row">
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Daily Collection / Revenue</h6><table class="table table-sm"><tr><th>Date</th><th>Collection</th><th>Revenue</th></tr><?php foreach($daily as $r): ?><tr><td><?= e($r['d']) ?></td><td><?= money((float)$r['collection']) ?></td><td><?= money((float)$r['revenue']) ?></td></tr><?php endforeach; ?></table></div></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Test-wise Revenue</h6><table class="table table-sm"><tr><th>Test</th><th>Volume</th><th>Revenue</th></tr><?php foreach($testWise as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= $r['volume'] ?></td><td><?= money((float)$r['revenue']) ?></td></tr><?php endforeach; ?></table></div></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Doctor-wise Consultation Count</h6><table class="table table-sm"><tr><th>Doctor</th><th>Visits</th></tr><?php foreach($doctorWise as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= $r['visits'] ?></td></tr><?php endforeach; ?></table></div></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Pending Reports</h6><table class="table table-sm"><tr><th>Sample</th><th>Barcode</th><th>Status</th></tr><?php foreach($pendingReports as $r): ?><tr><td><?= $r['sample_id'] ?></td><td><?= e($r['barcode']) ?></td><td><?= e((string)$r['status']) ?></td></tr><?php endforeach; ?></table></div></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Cancelled Appointments</h6><table class="table table-sm"><tr><th>ID</th><th>Date</th><th>Notes</th></tr><?php foreach($cancelled as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['appointment_date']) ?></td><td><?= e((string)$r['notes']) ?></td></tr><?php endforeach; ?></table></div></div></div>
<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6>Patient Visit Summary</h6><table class="table table-sm"><tr><th>UHID</th><th>Patient</th><th>Visits</th></tr><?php foreach($visitSummary as $r): ?><tr><td><?= e($r['uhid']) ?></td><td><?= e($r['patient_name']) ?></td><td><?= $r['visit_count'] ?></td></tr><?php endforeach; ?></table></div></div></div>
</div>
