<?php
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO patients(uhid, first_name, last_name, dob, gender, mobile, email, address, emergency_contact) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        uhid(), trim($_POST['first_name']), trim($_POST['last_name']), $_POST['dob'] ?: null,
        $_POST['gender'], trim($_POST['mobile']), trim($_POST['email']), trim($_POST['address']), trim($_POST['emergency_contact'])
    ]);
    $id = (int)$pdo->lastInsertId();
    audit($pdo, (int)$user['id'], 'create', 'patients', 'patient', $id);
    flash('Patient registered successfully.');
    header('Location: /public/index.php?module=patients');
    exit;
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE uhid LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? ORDER BY id DESC");
    $like = "%{$q}%";
    $stmt->execute([$like,$like,$like,$like]);
    $rows = $stmt->fetchAll();
} else {
    $rows = $pdo->query('SELECT * FROM patients ORDER BY id DESC LIMIT 100')->fetchAll();
}
?>
<h4>Patient Registration</h4>
<form method="get" class="mb-2"><input type="hidden" name="module" value="patients"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by UHID/name/mobile"><button class="btn btn-outline-primary">Search</button></div></form>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<div class="col-md-2"><input class="form-control" name="first_name" placeholder="First Name" required></div>
<div class="col-md-2"><input class="form-control" name="last_name" placeholder="Last Name" required></div>
<div class="col-md-2"><input class="form-control" name="dob" type="date"></div>
<div class="col-md-1"><select class="form-select" name="gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
<div class="col-md-2"><input class="form-control" name="mobile" placeholder="Mobile" required></div>
<div class="col-md-2"><input class="form-control" name="email" placeholder="Email"></div>
<div class="col-md-3"><input class="form-control" name="address" placeholder="Address"></div>
<div class="col-md-2"><input class="form-control" name="emergency_contact" placeholder="Emergency Contact"></div>
<div class="col-md-2"><button class="btn btn-primary">Register</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>UHID</th><th>Name</th><th>DOB</th><th>Gender</th><th>Mobile</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['uhid']) ?></td><td><?= e($r['first_name'].' '.$r['last_name']) ?></td><td><?= e((string)$r['dob']) ?></td><td><?= e($r['gender']) ?></td><td><?= e($r['mobile']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
</table>
