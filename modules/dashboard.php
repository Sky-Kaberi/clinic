<?php
$pdo = db();
$today = date('Y-m-d');
$cards = [
    'Today Patients' => (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()")?->fetchColumn(),
    'Appointments Today' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")?->fetchColumn(),
    'Pending Reports' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status != 'approved'")?->fetchColumn(),
    'Outstanding (INR)' => (float)$pdo->query("SELECT IFNULL(SUM(total_amount - paid_amount),0) FROM bills")?->fetchColumn(),
];
?>
<h4>Admin Dashboard</h4>
<div class="row">
<?php foreach ($cards as $k => $v): ?>
  <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= e($k) ?></div><div class="h4"><?= is_float($v) ? money($v) : e((string)$v) ?></div></div></div></div>
<?php endforeach; ?>
</div>
<?php
$rev = $pdo->query("SELECT DATE(created_at) d, SUM(total_amount) t FROM bills GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll();
?>
<div class="card"><div class="card-body"><h6>Last 7 Billing Days</h6><table class="table table-sm"><tr><th>Date</th><th>Total</th></tr>
<?php foreach ($rev as $r): ?><tr><td><?= e($r['d']) ?></td><td><?= money((float)$r['t']) ?></td></tr><?php endforeach; ?>
</table></div></div>
