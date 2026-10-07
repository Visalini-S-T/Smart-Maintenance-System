<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Smart Maintenance</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<div class="login-shell">
  <div class="brand-panel">
    <div class="brand-mark">SM</div>
    <h1>Smart Maintenance</h1>
    <p>Industrial maintenance, downtime analytics and machine health monitoring.</p>
    <div class="feature-list">
      <span>✓ MTBF & Availability</span>
      <span>✓ Health scoring</span>
      <span>✓ Work orders</span>
      <span>✓ Spare parts tracking</span>
      <span>✓ Role-based access</span>
    </div>
  </div>
  <div class="login-card">
    <h2>Sign in</h2>
    <p class="muted">Use one of the demo accounts from the README.</p>
    <?php if(isset($_GET['error'])): ?><div class="alert danger"><?=htmlspecialchars($_GET['error'])?></div><?php endif; ?>
    <form method="post" action="auth/login.php" data-validate>
      <label>Email<input type="email" name="email" required></label>
      <label>Password<input type="password" name="password" required minlength="8"></label>
      <button class="btn primary full" type="submit">Sign in</button>
    </form>
  </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
