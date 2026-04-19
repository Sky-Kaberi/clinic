<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['collect'])){
        $barcode='SMP'.date('Ymd').random_int(1000,9999);
        $pdo->prepare('INSERT INTO samples(booking_id,barcode,status,collected_by,collected_at) VALUES(?,?,?,?,NOW())')->execute([(int)$_POST['booking_id'],$barcode,'collected',(int)$user['id']]);
        flash('Sample collected and barcode generated: '.$barcode);
    }
    if(isset($_POST['result'])){
        $pdo->prepare('INSERT INTO reports(sample_id,result_text,status,entered_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE result_text=VALUES(result_text), status=VALUES(status), entered_by=VALUES(entered_by)')->execute([(int)$_POST['sample_id'],trim($_POST['result_text']),$_POST['status'],(int)$user['id']]);
        $pdo->prepare('UPDATE samples SET status=? WHERE id=?')->execute([$_POST['status']==='approved'?'reported':'processed',(int)$_POST['sample_id']]);
        flash('Lab result saved.');
    }
    header('Location:/public/index.php?module=lab'); exit;
}
$bookings=$pdo->query("SELECT b.id, p.uhid, CONCAT(p.first_name,' ',p.last_name) patient_name FROM diagnostic_bookings b JOIN patients p ON p.id=b.patient_id ORDER BY b.id DESC LIMIT 100")->fetchAll();
$samples=$pdo->query("SELECT s.*, r.status report_status, r.result_text FROM samples s LEFT JOIN reports r ON r.sample_id=s.id ORDER BY s.id DESC LIMIT 100")->fetchAll();
?>
<h4>Laboratory Workflow</h4>
<div class="card mb-3"><div class="card-body"><h6>1) Sample Collection</h6><form method="post" class="row g-2"><input type="hidden" name="collect" value="1"><div class="col-md-4"><select name="booking_id" class="form-select" required><?php foreach($bookings as $b): ?><option value="<?= $b['id'] ?>">Booking #<?= $b['id'] ?> - <?= e($b['uhid'].' '.$b['patient_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary">Collect Sample</button></div></form></div></div>
<div class="card"><div class="card-body"><h6>2-6) Processing, Result Entry, Verification & Release</h6>
<table class="table table-sm"><tr><th>Sample</th><th>Barcode</th><th>Status</th><th>Result Entry</th></tr>
<?php foreach($samples as $s): ?><tr><td>#<?= $s['id'] ?></td><td><?= e($s['barcode']) ?></td><td><?= e($s['status']) ?></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="result" value="1"><input type="hidden" name="sample_id" value="<?= $s['id'] ?>"><input name="result_text" class="form-control form-control-sm" value="<?= e((string)$s['result_text']) ?>" placeholder="Result / panic value notes"><select name="status" class="form-select form-select-sm"><option <?= ($s['report_status']??'draft')==='draft'?'selected':'' ?>>draft</option><option <?= ($s['report_status']??'')==='verified'?'selected':'' ?>>verified</option><option <?= ($s['report_status']??'')==='approved'?'selected':'' ?>>approved</option></select><button class="btn btn-sm btn-outline-primary">Save</button></form></td></tr><?php endforeach; ?>
</table></div></div>
