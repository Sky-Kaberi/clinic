<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (current_user()) {
    header('Location: ' . app_url('/public/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($ok) {
        flash('Login successful.');
        header('Location: ' . app_url('/public/index.php'));
        exit;
    }

    flash('Invalid credentials', 'danger');
}

render_header('Login');
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5>ClinicMS Login</h5>
        <form method="post" class="mt-3">
          <div class="mb-3"><label>Email</label><input class="form-control" name="email" required></div>
          <div class="mb-3"><label>Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php render_footer();
