<?php
$pdo = db();
$user = current_user();
require_roles(['Super Admin', 'Receptionist', 'Front Desk Executive', 'Doctor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $dob = normalize_optional($_POST['dob'] ?? null);
    $gender = trim((string)($_POST['gender'] ?? ''));
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $email = normalize_optional($_POST['email'] ?? null);
    $address = normalize_optional($_POST['address'] ?? null);
    $emergencyContact = normalize_optional($_POST['emergency_contact'] ?? null);

    if ($firstName === '') {
        flash('First name is required.', 'danger');
        header('Location: ' . module_url('patients'));
        exit;
    }
    if ($mobile === '') {
        flash('Mobile number is required.', 'danger');
        header('Location: ' . module_url('patients'));
        exit;
    }
    if (!is_valid_mobile($mobile)) {
        flash('Enter a valid mobile number (10-15 digits).', 'danger');
        header('Location: ' . module_url('patients'));
        exit;
    }
    if ($email !== null && !is_valid_email($email)) {
        flash('Enter a valid email address.', 'danger');
        header('Location: ' . module_url('patients'));
        exit;
    }

    $allowedGender = ['Male', 'Female', 'Other'];
    if (!in_array($gender, $allowedGender, true)) {
        flash('Invalid gender selected.', 'danger');
        header('Location: ' . module_url('patients'));
        exit;
    }

    try {
        $duplicateStmt = $pdo->prepare('SELECT id, uhid, first_name, last_name FROM patients WHERE mobile = ? OR (? IS NOT NULL AND email = ?) LIMIT 1');
        $duplicateStmt->execute([$mobile, $email, $email]);
        $duplicate = $duplicateStmt->fetch();
        if ($duplicate) {
            flash(
                'Potential duplicate found (UHID: ' . $duplicate['uhid'] . ', Name: ' . $duplicate['first_name'] . ' ' . $duplicate['last_name'] . '). Please search before creating a new patient.',
                'warning'
            );
            header('Location: ' . module_url('patients', ['q' => $duplicate['uhid']]));
            exit;
        }

        $pdo->beginTransaction();

        $uhidValue = null;
        for ($i = 0; $i < 5; $i++) {
            $candidate = uhid();
            $existsStmt = $pdo->prepare('SELECT id FROM patients WHERE uhid = ? LIMIT 1');
            $existsStmt->execute([$candidate]);
            if (!$existsStmt->fetch()) {
                $uhidValue = $candidate;
                break;
            }
        }

        if ($uhidValue === null) {
            throw new RuntimeException('Unable to generate unique UHID. Please retry.');
        }

        $stmt = $pdo->prepare('INSERT INTO patients(uhid, first_name, last_name, dob, gender, mobile, email, address, emergency_contact) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $uhidValue,
            $firstName,
            $lastName,
            $dob,
            $gender,
            $mobile,
            $email,
            $address,
            $emergencyContact,
        ]);

        $id = (int)$pdo->lastInsertId();
        audit($pdo, (int)$user['id'], 'create', 'patients', 'patient', $id);
        $pdo->commit();

        flash('Patient registered successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof PDOException && $e->getCode() === '23000') {
            flash('Duplicate patient details detected. Mobile and email must be unique.', 'danger');
        } else {
            flash('Could not save patient. Please verify data and try again.', 'danger');
        }
    }

    header('Location: ' . module_url('patients'));
    exit;
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE uhid LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? ORDER BY id DESC');
    $like = "%{$q}%";
    $stmt->execute([$like, $like, $like, $like]);
    $rows = $stmt->fetchAll();
} else {
    $rows = $pdo->query('SELECT * FROM patients ORDER BY id DESC LIMIT 100')->fetchAll();
}
?>
<h4>Patient Registration</h4>
<form method="get" class="mb-2"><input type="hidden" name="module" value="patients"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by UHID/name/mobile"><button class="btn btn-outline-primary">Search</button></div></form>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2">
<div class="col-md-2"><input class="form-control" name="first_name" placeholder="First Name" required></div>
<div class="col-md-2"><input class="form-control" name="last_name" placeholder="Last Name"></div>
<div class="col-md-2"><input class="form-control" name="dob" type="date"></div>
<div class="col-md-1"><select class="form-select" name="gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
<div class="col-md-2"><input class="form-control" name="mobile" placeholder="Mobile" pattern="[0-9]{10,15}" required></div>
<div class="col-md-2"><input class="form-control" name="email" type="email" placeholder="Email"></div>
<div class="col-md-3"><input class="form-control" name="address" placeholder="Address"></div>
<div class="col-md-2"><input class="form-control" name="emergency_contact" placeholder="Emergency Contact"></div>
<div class="col-md-2"><button class="btn btn-primary">Register</button></div>
</form></div></div>
<table class="table table-sm table-striped"><tr><th>UHID</th><th>Name</th><th>DOB</th><th>Gender</th><th>Mobile</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['uhid']) ?></td><td><?= e($r['first_name'].' '.$r['last_name']) ?></td><td><?= e((string)$r['dob']) ?></td><td><?= e($r['gender']) ?></td><td><?= e($r['mobile']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
</table>
