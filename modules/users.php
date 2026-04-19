<?php
$pdo=db(); $user=current_user();
if(!has_role(['Super Admin'])){
    echo '<div class="alert alert-warning">Only Super Admin can manage users.</div>';
    return;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $pdo->prepare('INSERT INTO users(name,email,password_hash,role,is_active) VALUES (?,?,?,?,1)')->execute([trim($_POST['name']),trim($_POST['email']),password_hash($_POST['password'], PASSWORD_DEFAULT),$_POST['role']]);
    flash('User created.');
    header('Location: ' . module_url('users'));
    exit;
}
$rows=$pdo->query('SELECT id,name,email,role,is_active,created_at FROM users ORDER BY id DESC')->fetchAll();
$roles=['Super Admin','Receptionist','Front Desk Executive','Doctor','Technician','Radiologist','Lab Admin','Cashier','Centre Manager'];
?>
<h4>User Management</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><div class="col-md-2"><input name="name" class="form-control" placeholder="Name" required></div><div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Email" required></div><div class="col-md-2"><input name="password" type="password" class="form-control" placeholder="Password" required></div><div class="col-md-3"><select name="role" class="form-select"><?php foreach($roles as $r): ?><option><?= e($r) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary">Add User</button></div></form></div></div>
<table class="table table-sm"><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr><?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= $r['is_active'] ? 'Yes':'No' ?></td></tr><?php endforeach; ?></table>
