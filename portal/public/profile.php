<?php
declare(strict_types=1);

require __DIR__ . '/_portal_bootstrap.php';
if (!is_logged_in()) {
    $_SESSION['flash_type'] = 'danger';
    $_SESSION['flash_message'] = 'Please login first.';
    portal_redirect('/login');
}

$radiusUsername = (string)($dashboard['username'] ?? $user);
$radiusPassword = (string)($dashboard['password'] ?? '');
$radiusSsid = (string)($dashboard['ssid_name'] ?? '');
if ($radiusSsid === '') {
    $radiusSsid = (string)($profile['ssid_name'] ?? 'MALLUPUR-KISAANU-WIFI');
}
$planCode = (string)($dashboard['plan_code'] ?? ($profile['plan_code'] ?? ''));
$profileName = (string)($profile['full_name'] ?? $radiusUsername);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Mallupur Adhunik Gram Seva Public Wi-Fi | Profile</title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap');
      :root { color-scheme: light; --bg:#f4f8ef; --panel:rgba(255, 255, 255, 0.94); --text:#12331f; --muted:#5d7464; --brand:#1f7a43; --brand-deep:#11512b; --accent:#f0b94b; --line:rgba(17,81,43,.12); --danger:#b42318; --success:#0f7a3f; --shadow:0 20px 60px rgba(17,81,43,.12); }
      * { box-sizing: border-box; }
      body { margin:0; min-height:100vh; font-family:"Quicksand","Noto Sans Devanagari",system-ui,sans-serif; color:var(--text); background:radial-gradient(circle at top left, rgba(240,185,75,.22), transparent 38%), radial-gradient(circle at top right, rgba(31,122,67,.16), transparent 36%), linear-gradient(180deg, #f8fbf5 0%, var(--bg) 100%); }
      h1,h2,h3,.section-title,.btn,.nav-link,.fact strong,label { font-family:"Lexend","Noto Sans Devanagari",system-ui,sans-serif; }
      a { color: var(--brand-deep); }
      .page { width:min(100%,1120px); margin:0 auto; padding:24px 16px 48px; }
      .hero { position:relative; overflow:hidden; border-radius:28px; padding:24px; background:linear-gradient(145deg, rgba(17,81,43,.96), rgba(31,122,67,.92)), var(--brand); color:#fff; box-shadow:var(--shadow); }
      .hero-grid { position:relative; z-index:1; display:grid; gap:18px; align-items:center; }
      .hero::after { content:""; position:absolute; inset:-40% auto auto 54%; width:220px; height:220px; border-radius:999px; background:rgba(240,185,75,.18); filter:blur(4px); }
      .brand { display:inline-flex; align-items:center; gap:10px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.12); font-size:12px; letter-spacing:.08em; text-transform:uppercase; }
      .brand-badge { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--accent); color:var(--brand-deep); font-weight:800; }
      h1 { margin:18px 0 10px; font-size:28px; line-height:1.12; }
      .hero p { margin:0; max-width:38ch; color:rgba(255,255,255,.82); line-height:1.55; }
      .chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
      .chip { display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:16px; background:rgba(255,255,255,.1); font-size:13px; }
      .hero-media { width:min(100%,240px); justify-self:center; }
      .hero-frame { overflow:hidden; border-radius:24px; padding:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); box-shadow:0 22px 44px rgba(9,43,23,.24); }
      .hero-image { display:block; width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:18px; }
      .layout { display:grid; gap:18px; }
      .card { margin-top:18px; border-radius:24px; padding:24px; background:var(--panel); box-shadow:var(--shadow); border:1px solid var(--line); backdrop-filter:blur(12px); }
      .nav { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
      .nav-link,.logout-btn { border:1px solid rgba(17,81,43,.16); border-radius:999px; min-height:40px; padding:9px 14px; background:#fff; color:var(--brand-deep); text-decoration:none; font-size:12px; font-weight:800; cursor:pointer; }
      .nav-link.active { background:rgba(31,122,67,.1); border-color:rgba(31,122,67,.28); }
      .section-title { margin:0 0 8px; font-size:26px; line-height:1.1; letter-spacing:-.01em; }
      .section-copy { margin:0 0 20px; color:var(--muted); line-height:1.7; font-size:15px; }
      .facts { display:grid; gap:10px; margin-top:18px; }
      .fact { display:grid; gap:4px; border-radius:14px; padding:12px; background:rgba(17,81,43,.04); }
      .fact strong { font-size:13px; letter-spacing:.04em; text-transform:uppercase; color:var(--brand-deep); }
      .fact span { font-size:14px; line-height:1.45; overflow-wrap:anywhere; }
      .profile-grid { display:grid; gap:12px; grid-template-columns:1fr; }
      .profile-item { display:grid; gap:6px; border-radius:18px; padding:16px; background:rgba(17,81,43,.04); border:1px solid rgba(17,81,43,.08); }
      .profile-item strong { font-size:12px; color:var(--brand-deep); letter-spacing:.05em; text-transform:uppercase; }
      .profile-item span { overflow-wrap:anywhere; font-weight:800; line-height:1.45; }
      .password-row { display:flex; gap:8px; align-items:stretch; }
      .secret { flex:1; display:flex; align-items:center; min-height:46px; border:1px solid rgba(17,81,43,.16); border-radius:14px; padding:10px 12px; background:#fff; overflow-wrap:anywhere; font-weight:800; }
      .toggle { border:0; border-radius:14px; padding:10px 14px; background:var(--brand-deep); color:#fff; font-weight:800; cursor:pointer; }
      .aside-banner { margin:0 0 14px; }
      .aside-banner img { width:100%; display:block; height:auto; object-fit:contain; border-radius:14px; border:1px solid rgba(17,81,43,.14); background:#fff; }
      form { margin:0; }
      @media (min-width:680px){ .hero-grid{grid-template-columns:minmax(0,1.2fr) minmax(220px,.8fr);} .hero-media{justify-self:end;} .profile-grid{grid-template-columns:repeat(2, minmax(0,1fr));} }
      @media (min-width:980px){ .layout{grid-template-columns:minmax(0,7fr) minmax(300px,5fr); align-items:start;} .card{padding:28px;} .aside{position:sticky; top:14px;} }
    </style>
  </head>
  <body>
    <main class="page">
      <section class="hero">
        <div class="hero-grid">
          <div>
            <div class="brand">
              <span class="brand-badge"><img src="https://kisaanu.com/kisaanu-transparent-logo.png" alt="Kisaanu" style="width:20px;height:20px;object-fit:contain;" /></span>
              <span>Kisaanu Public Wi-Fi Portal</span>
            </div>
            <h1>Kisaanu Wi-Fi profile</h1>
            <p>Your registered Mallupur details and Radius access information for enterprise Wi-Fi login.</p>
            <div class="chips"><span class="chip"><?php echo e($profileName); ?></span><span class="chip">SSID: <?php echo e($radiusSsid); ?></span><span class="chip"><?php echo e($planCode !== '' ? $planCode : 'Plan pending'); ?></span></div>
          </div>
          <div class="hero-media" aria-hidden="true"><div class="hero-frame"><img class="hero-image" src="https://kisaanu.com/media/public-wifi-mallupur-hero.gif" alt="Mallupur Wi-Fi illustration" decoding="async" /></div></div>
        </div>
      </section>

      <section class="layout">
        <div class="card">
          <nav class="nav" aria-label="Portal">
            <a class="nav-link" href="/dashboard">Dashboard</a>
            <form method="post" action="/login"><input type="hidden" name="formMode" value="logout" /><button class="logout-btn" type="submit">Logout</button></form>
          </nav>

          <h2 class="section-title">Kisaanu Wi-Fi Profile</h2>
          <p class="section-copy">These details are stored in the portal database and linked to your Radius account.</p>

          <div class="profile-grid">
            <div class="profile-item"><strong>Full Name</strong><span><?php echo e((string)($profile['full_name'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Mobile / Username</strong><span><?php echo e((string)($profile['mobile_number'] ?? $radiusUsername)); ?></span></div>
            <div class="profile-item"><strong>Father Name</strong><span><?php echo e((string)($profile['father_name'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Mother Name</strong><span><?php echo e((string)($profile['mother_name'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Village</strong><span><?php echo e((string)($profile['village'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Aadhaar</strong><span><?php echo e((string)($profile['aadhaar_number_masked'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Device MAC</strong><span><?php echo e((string)($profile['client_mac'] ?? 'Captured when provided by controller')); ?></span></div>
            <div class="profile-item"><strong>AP MAC</strong><span><?php echo e((string)($profile['ap_mac'] ?? 'Captured when provided by controller')); ?></span></div>
            <div class="profile-item"><strong>Address</strong><span><?php echo e((string)($profile['address_text'] ?? 'Not available')); ?></span></div>
            <div class="profile-item"><strong>Registered At</strong><span><?php echo e((string)($profile['created_at'] ?? 'Not available')); ?></span></div>
          </div>
        </div>

        <aside class="card aside">
          <figure class="aside-banner"><img src="https://kisaanu.com/media/JayKisaanu-Village-Banner-Final.png?v=20260513-2" alt="Mallupur network advisory banner" loading="lazy" /></figure>
          <h3 class="section-title">Radius Info</h3>
          <div class="facts" style="margin-top:0;">
            <div class="fact"><strong>Username</strong><span><?php echo e($radiusUsername); ?></span></div>
            <div class="fact"><strong>Password</strong><span><span class="password-row"><span class="secret" id="radiusPassword" data-secret="<?php echo e($radiusPassword); ?>"><?php echo e($radiusPassword !== '' ? str_repeat('*', min(12, max(8, strlen($radiusPassword)))) : 'Not available'); ?></span><button class="toggle" type="button" data-toggle-secret="radiusPassword">Show</button></span></span></div>
            <div class="fact"><strong>SSID</strong><span><?php echo e($radiusSsid); ?></span></div>
            <div class="fact"><strong>Package</strong><span><?php echo e($planCode !== '' ? $planCode : 'Not assigned'); ?></span></div>
            <div class="fact"><strong>Daily Allowance</strong><span><?php echo e((string)$minutesTotal); ?> minutes, <?php echo e((string)$minutesRemaining); ?> minutes remaining today.</span></div>
          </div>
        </aside>
      </section>
    </main>
    <script>
      (function () {
        document.querySelectorAll('[data-toggle-secret]').forEach(function (button) {
          button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-toggle-secret'));
            if (!target) return;
            var secret = target.getAttribute('data-secret') || '';
            var showing = button.getAttribute('data-showing') === '1';
            target.textContent = showing ? (secret ? '************' : 'Not available') : (secret || 'Not available');
            button.textContent = showing ? 'Show' : 'Hide';
            button.setAttribute('data-showing', showing ? '0' : '1');
          });
        });
      })();
    </script>
  </body>
</html>
