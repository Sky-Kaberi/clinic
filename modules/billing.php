<?php
$pdo = db();
$user = current_user();
require_roles(['Super Admin', 'Cashier', 'Receptionist', 'Front Desk Executive']);

$paymentModes = ['Cash', 'Card', 'UPI', 'Net Banking', 'Wallet'];
$billTypes = ['consultation', 'diagnostics', 'combined', 'package'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_bill'])) {
            $patientId = (int)($_POST['patient_id'] ?? 0);
            $billType = trim((string)($_POST['bill_type'] ?? ''));
            $total = (float)($_POST['total_amount'] ?? 0);
            $paid = (float)($_POST['paid_amount'] ?? 0);
            $paymentMode = trim((string)($_POST['payment_mode'] ?? ''));
            $referenceNo = normalize_optional($_POST['reference_no'] ?? null);

            if ($patientId <= 0 || !in_array($billType, $billTypes, true)) {
                throw new InvalidArgumentException('Valid patient and bill type are required.');
            }
            if ($total <= 0) {
                throw new InvalidArgumentException('Total amount must be greater than 0.');
            }
            if ($paid < 0 || $paid > $total) {
                throw new InvalidArgumentException('Advance payment cannot exceed bill amount.');
            }
            if ($paid > 0 && !in_array($paymentMode, $paymentModes, true)) {
                throw new InvalidArgumentException('Invalid payment mode selected.');
            }

            $status = $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partial');

            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO bills(patient_id, bill_type, total_amount, paid_amount, status, created_by) VALUES (?,?,?,?,?,?)')
                ->execute([$patientId, $billType, $total, $paid, $status, (int)$user['id']]);

            $billId = (int)$pdo->lastInsertId();
            audit($pdo, (int)$user['id'], 'create', 'billing', 'bill', $billId);

            if ($paid > 0) {
                $pdo->prepare('INSERT INTO payments(bill_id, amount, payment_mode, reference_no, received_by) VALUES (?,?,?,?,?)')
                    ->execute([$billId, $paid, $paymentMode, $referenceNo, (int)$user['id']]);
                audit($pdo, (int)$user['id'], 'create', 'billing', 'payment', (int)$pdo->lastInsertId());
            }
            $pdo->commit();
            flash('Bill created. Invoice #' . $billId);
        }

        if (isset($_POST['add_payment'])) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $amt = (float)($_POST['amount'] ?? 0);
            $paymentMode = trim((string)($_POST['payment_mode'] ?? ''));
            $referenceNo = normalize_optional($_POST['reference_no'] ?? null);

            if ($billId <= 0 || $amt <= 0) {
                throw new InvalidArgumentException('Bill and valid payment amount are required.');
            }
            if (!in_array($paymentMode, $paymentModes, true)) {
                throw new InvalidArgumentException('Invalid payment mode selected.');
            }

            $billStmt = $pdo->prepare('SELECT id, total_amount, paid_amount, COALESCE(refund_amount,0) refund_amount FROM bills WHERE id = ? LIMIT 1');
            $billStmt->execute([$billId]);
            $bill = $billStmt->fetch();
            if (!$bill) {
                throw new InvalidArgumentException('Bill not found.');
            }

            $due = (float)$bill['total_amount'] - (float)$bill['paid_amount'];
            if ($amt > $due) {
                throw new InvalidArgumentException('Payment cannot exceed pending due amount of ' . money($due) . '.');
            }

            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO payments(bill_id, amount, payment_mode, reference_no, received_by) VALUES (?,?,?,?,?)')
                ->execute([$billId, $amt, $paymentMode, $referenceNo, (int)$user['id']]);
            $paymentId = (int)$pdo->lastInsertId();

            $pdo->prepare('UPDATE bills SET paid_amount = paid_amount + ? WHERE id=?')->execute([$amt, $billId]);
            $pdo->prepare('UPDATE bills SET status = CASE WHEN paid_amount <= 0 THEN "unpaid" WHEN paid_amount >= total_amount THEN "paid" ELSE "partial" END WHERE id=?')
                ->execute([$billId]);

            audit($pdo, (int)$user['id'], 'create', 'billing', 'payment', $paymentId);
            audit($pdo, (int)$user['id'], 'update', 'billing', 'bill', $billId);
            $pdo->commit();
            flash('Payment added.');
        }

        if (isset($_POST['refund_payment'])) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $refundAmount = (float)($_POST['refund_amount'] ?? 0);

            if ($billId <= 0 || $refundAmount <= 0) {
                throw new InvalidArgumentException('Valid bill and refund amount are required.');
            }

            $billStmt = $pdo->prepare('SELECT id, total_amount, paid_amount, COALESCE(refund_amount,0) refund_amount FROM bills WHERE id = ? LIMIT 1');
            $billStmt->execute([$billId]);
            $bill = $billStmt->fetch();
            if (!$bill) {
                throw new InvalidArgumentException('Bill not found.');
            }

            $netPaid = (float)$bill['paid_amount'] - (float)$bill['refund_amount'];
            if ($refundAmount > $netPaid) {
                throw new InvalidArgumentException('Refund cannot exceed net paid amount of ' . money($netPaid) . '.');
            }

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE bills SET refund_amount = COALESCE(refund_amount,0) + ?, status = CASE WHEN (paid_amount - (COALESCE(refund_amount,0) + ?)) <= 0 THEN "refunded" WHEN paid_amount >= total_amount THEN "paid" ELSE "partial" END WHERE id = ?')
                ->execute([$refundAmount, $refundAmount, $billId]);
            audit($pdo, (int)$user['id'], 'update', 'billing', 'bill_refund', $billId);
            $pdo->commit();
            flash('Refund recorded.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash($e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to complete billing action.', 'danger');
    }

    header('Location: ' . module_url('billing'));
    exit;
}

$patients = $pdo->query('SELECT id,uhid,first_name,last_name FROM patients ORDER BY id DESC LIMIT 200')->fetchAll();
$bills = $pdo->query("SELECT b.*, COALESCE(b.refund_amount,0) refund_amount, CONCAT(p.first_name,' ',p.last_name) patient_name,p.uhid FROM bills b JOIN patients p ON p.id=b.patient_id ORDER BY b.id DESC LIMIT 100")->fetchAll();
?>
<h4>Billing & Payments</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><input type="hidden" name="create_bill" value="1">
<div class="col-md-3"><select name="patient_id" class="form-select" required><?php foreach($patients as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['uhid'].' '.$p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="bill_type" class="form-select"><?php foreach($billTypes as $type): ?><option><?= e($type) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input name="total_amount" type="number" step="0.01" min="0.01" class="form-control" placeholder="Total" required></div>
<div class="col-md-2"><input name="paid_amount" type="number" step="0.01" min="0" class="form-control" placeholder="Advance/paid" value="0"></div>
<div class="col-md-2"><select name="payment_mode" class="form-select"><?php foreach($paymentModes as $mode): ?><option><?= e($mode) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input name="reference_no" class="form-control" placeholder="Reference"></div>
<div class="col-md-2"><button class="btn btn-primary">Create Bill</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>Invoice</th><th>Patient</th><th>Type</th><th>Total</th><th>Paid</th><th>Refund</th><th>Outstanding</th><th>Status</th><th>Add Payment</th><th>Refund</th></tr>
<?php foreach($bills as $b): $out=(float)$b['total_amount']-((float)$b['paid_amount']-(float)$b['refund_amount']); ?><tr>
<td>#<?= $b['id'] ?></td><td><?= e($b['uhid'].' '.$b['patient_name']) ?></td><td><?= e($b['bill_type']) ?></td><td><?= money((float)$b['total_amount']) ?></td><td><?= money((float)$b['paid_amount']) ?></td><td><?= money((float)$b['refund_amount']) ?></td><td><?= money($out) ?></td><td><?= e($b['status']) ?></td>
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="add_payment" value="1"><input type="hidden" name="bill_id" value="<?= $b['id'] ?>"><input name="amount" type="number" min="0.01" step="0.01" class="form-control form-control-sm" placeholder="Amount"><select name="payment_mode" class="form-select form-select-sm"><?php foreach($paymentModes as $mode): ?><option><?= e($mode) ?></option><?php endforeach; ?></select><input name="reference_no" class="form-control form-control-sm" placeholder="Ref"><button class="btn btn-sm btn-outline-primary">Receive</button></form></td>
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="refund_payment" value="1"><input type="hidden" name="bill_id" value="<?= $b['id'] ?>"><input name="refund_amount" type="number" min="0.01" step="0.01" class="form-control form-control-sm" placeholder="Refund"><button class="btn btn-sm btn-outline-warning">Refund</button></form></td></tr><?php endforeach; ?>
</table>
