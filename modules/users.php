<?php
$pdo = db();
$user = current_user();
if (!has_role(['Super Admin'])) {
    echo '<div class="alert alert-warning">Only Super Admin can manage users.</div>';
    return;
}

$roles = ['Super Admin', 'Receptionist', 'Front Desk Executive', 'Doctor', 'Technician', 'Radiologist', 'Lab Admin', 'Cashier', 'Centre Manager'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = trim((string)($_POST['role'] ?? ''));

        if ($name === '' || $email === '' || $username === '' || $password === '') {
            throw new InvalidArgumentException('Name, username, email and password are required.');
        }
        if (!is_valid_email($email)) {
            throw new InvalidArgumentException('Invalid email format.');
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3-30 chars and can include letters, numbers, dot, underscore, hyphen.');
        }
        if (!in_array($role, $roles, true)) {
            throw new InvalidArgumentException('Invalid role selected.');
        }

        $pdo->prepare('INSERT INTO users(name,username,email,password_hash,role,is_active) VALUES (?,?,?,?,?,1)')
            ->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        audit($pdo, (int)$user['id'], 'create', 'users', 'user', (int)$pdo->lastInsertId());
        flash('User created.');
    } catch (Throwable $e) {
        if ($e instanceof PDOException && $e->getCode() === '23000') {
            flash('Username or email already exists.', 'danger');
        } else {
            flash($e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to create user.', 'danger');
        }
    }

    header('Location: ' . module_url('users'));
    exit;
}

$rows = $pdo->query('SELECT id,name,username,email,role,is_active,created_at FROM users ORDER BY id DESC')->fetchAll();
?>
<h4>User Management</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><div class="col-md-2"><input name="name" class="form-control" placeholder="Name" required></div><div class="col-md-2"><input name="username" class="form-control" placeholder="Username" required></div><div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Email" required></div><div class="col-md-2"><input name="password" type="password" class="form-control" placeholder="Password" required></div><div class="col-md-2"><select name="role" class="form-select"><?php foreach($roles as $r): ?><option><?= e($r) ?></option><?php endforeach; ?></select></div><div class="col-md-1"><button class="btn btn-primary">Add User</button></div></form></div></div>
<table class="table table-sm"><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr><?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e((string)$r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= $r['is_active'] ? 'Yes':'No' ?></td></tr><?php endforeach; ?></table>
