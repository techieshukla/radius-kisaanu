<?php
declare(strict_types=1);

require __DIR__ . '/_portal_bootstrap.php';
if (!is_logged_in()) {
    $_SESSION['flash_type'] = 'danger';
    $_SESSION['flash_message'] = 'Please login first.';
    portal_redirect('/login');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>Mallupur Adhunik Gram Seva Public Wi-Fi | Dashboard</title>
  <style>
    body{margin:0;font-family:Quicksand,system-ui,sans-serif;background:#f4f8ef;color:#12331f}
    .wrap{max-width:980px;margin:0 auto;padding:24px 16px}
    .card{background:#fff;border:1px solid rgba(17,81,43,.12);border-radius:20px;padding:18px;margin-top:12px}
    .row{display:grid;grid-template-columns:1fr;gap:12px}
    @media(min-width:800px){.row{grid-template-columns:1fr 1fr 1fr}}
    .k{font-size:12px;color:#5d7464}.v{font-weight:700}
    .links a{margin-right:8px}
    .btn{display:inline-block;padding:8px 12px;border:1px solid rgba(17,81,43,.2);border-radius:999px;text-decoration:none;color:#11512b;background:#fff}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="links">
      <a class="btn" href="/index.php">Home</a>
      <a class="btn" href="/wifi.php">wifi.php</a>
      <a class="btn" href="/register.php">Register</a>
      <a class="btn" href="/login.php">Login</a>
      <a class="btn" href="/profile">Profile</a>
      <form method="post" action="/login" style="display:inline"><input type="hidden" name="formMode" value="logout" /><button class="btn" type="submit">Logout</button></form>
    </div>
    <?php if ($statusMessage !== ''): ?><div class="card"><?php echo e($statusMessage); ?></div><?php endif; ?>
    <div class="card"><h2>Dashboard</h2><p>Usage and package summary.</p></div>
    <div class="row">
      <div class="card"><div class="k">Wi-Fi Username</div><div class="v"><?php echo e((string)($dashboard['username'] ?? '')); ?></div></div>
      <div class="card"><div class="k">Plan</div><div class="v"><?php echo e((string)($dashboard['plan_code'] ?? '')); ?></div></div>
      <div class="card"><div class="k">SSID</div><div class="v"><?php echo e((string)($dashboard['ssid_name'] ?? '')); ?></div></div>
      <div class="card"><div class="k">Used Today</div><div class="v"><?php echo e((string)$minutesUsed); ?> min</div></div>
      <div class="card"><div class="k">Remaining</div><div class="v"><?php echo e((string)$minutesRemaining); ?> / <?php echo e((string)$minutesTotal); ?> min</div></div>
    </div>
  </div>
</body>
</html>
