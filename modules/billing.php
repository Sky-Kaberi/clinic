<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['create_bill'])){
        $total=(float)$_POST['total_amount']; $paid=(float)$_POST['paid_amount'];
        $pdo->prepare('INSERT INTO bills(patient_id, bill_type, total_amount, paid_amount, status, created_by) VALUES (?,?,?,?,?,?)')->execute([(int)$_POST['patient_id'],$_POST['bill_type'],$total,$paid,$paid>=$total?'paid':'partial',(int)$user['id']]);
        $billId=(int)$pdo->lastInsertId();
        if($paid>0){
            $pdo->prepare('INSERT INTO payments(bill_id, amount, payment_mode, reference_no, received_by) VALUES (?,?,?,?,?)')->execute([$billId,$paid,$_POST['payment_mode'],trim($_POST['reference_no']),(int)$user['id']]);
        }
        flash('Bill created. Invoice #'.$billId);
    }
    if(isset($_POST['add_payment'])){
        $billId=(int)$_POST['bill_id']; $amt=(float)$_POST['amount'];
        $pdo->prepare('INSERT INTO payments(bill_id, amount, payment_mode, reference_no, received_by) VALUES (?,?,?,?,?)')->execute([$billId,$amt,$_POST['payment_mode'],trim($_POST['reference_no']),(int)$user['id']]);
        $pdo->prepare('UPDATE bills SET paid_amount = paid_amount + ? WHERE id=?')->execute([$amt,$billId]);
        $pdo->prepare('UPDATE bills SET status = IF(paid_amount >= total_amount, "paid", "partial") WHERE id=?')->execute([$billId]);
        flash('Payment added.');
    }
    header('Location:/public/index.php?module=billing'); exit;
}
$patients=$pdo->query('SELECT id,uhid,first_name,last_name FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$bills=$pdo->query("SELECT b.*, CONCAT(p.first_name,' ',p.last_name) patient_name,p.uhid FROM bills b JOIN patients p ON p.id=b.patient_id ORDER BY b.id DESC LIMIT 100")->fetchAll();
?>
<h4>Billing & Payments</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><input type="hidden" name="create_bill" value="1">
<div class="col-md-3"><select name="patient_id" class="form-select" required><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="bill_type" class="form-select"><option>consultation</option><option>diagnostics</option><option>combined</option><option>package</option></select></div>
<div class="col-md-2"><input name="total_amount" type="number" step="0.01" class="form-control" placeholder="Total" required></div>
<div class="col-md-2"><input name="paid_amount" type="number" step="0.01" class="form-control" placeholder="Advance/paid" value="0"></div>
<div class="col-md-2"><select name="payment_mode" class="form-select"><option>Cash</option><option>Card</option><option>UPI</option><option>Net Banking</option><option>Wallet</option></select></div>
<div class="col-md-2"><input name="reference_no" class="form-control" placeholder="Reference"></div>
<div class="col-md-2"><button class="btn btn-primary">Create Bill</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>Invoice</th><th>Patient</th><th>Type</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th><th>Add Payment</th></tr>
<?php foreach($bills as $b): $out=(float)$b['total_amount']-(float)$b['paid_amount']; ?><tr>
<td>#<?= $b['id'] ?></td><td><?= e($b['uhid'].' '.$b['patient_name']) ?></td><td><?= e($b['bill_type']) ?></td><td><?= money((float)$b['total_amount']) ?></td><td><?= money((float)$b['paid_amount']) ?></td><td><?= money($out) ?></td><td><?= e($b['status']) ?></td>
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="add_payment" value="1"><input type="hidden" name="bill_id" value="<?= $b['id'] ?>"><input name="amount" type="number" step="0.01" class="form-control form-control-sm" placeholder="Amount"><select name="payment_mode" class="form-select form-select-sm"><option>Cash</option><option>Card</option><option>UPI</option><option>Net Banking</option><option>Wallet</option></select><input name="reference_no" class="form-control form-control-sm" placeholder="Ref"><button class="btn btn-sm btn-outline-primary">Receive</button></form></td></tr><?php endforeach; ?>
</table>
