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
$usagePercent = $minutesTotal > 0 ? min(100, (int)round(($minutesUsed / $minutesTotal) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Mallupur Adhunik Gram Seva Public Wi-Fi | Dashboard</title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap');
      :root { color-scheme: light; --bg:#f4f8ef; --panel:rgba(255, 255, 255, 0.94); --text:#12331f; --muted:#5d7464; --brand:#1f7a43; --brand-deep:#11512b; --accent:#f0b94b; --line:rgba(17,81,43,.12); --danger:#b42318; --success:#0f7a3f; --shadow:0 20px 60px rgba(17,81,43,.12); }
      * { box-sizing: border-box; }
      body { margin:0; min-height:100vh; font-family:"Quicksand","Noto Sans Devanagari",system-ui,sans-serif; color:var(--text); background:radial-gradient(circle at top left, rgba(240,185,75,.22), transparent 38%), radial-gradient(circle at top right, rgba(31,122,67,.16), transparent 36%), linear-gradient(180deg, #f8fbf5 0%, var(--bg) 100%); }
      h1,h2,h3,.section-title,.btn,.nav-link,.metric strong,.fact strong,label { font-family:"Lexend","Noto Sans Devanagari",system-ui,sans-serif; }
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
      .banner { margin-bottom:16px; border-radius:18px; padding:14px 16px; font-size:14px; line-height:1.5; background:rgba(31,122,67,.08); color:var(--success); border:1px solid rgba(31,122,67,.18); }
      .metrics { display:grid; gap:12px; grid-template-columns:1fr; }
      .metric { display:grid; gap:6px; border-radius:18px; padding:16px; background:rgba(17,81,43,.04); border:1px solid rgba(17,81,43,.08); }
      .metric strong { font-size:12px; color:var(--brand-deep); letter-spacing:.05em; text-transform:uppercase; }
      .metric span { overflow-wrap:anywhere; font-size:20px; font-weight:800; }
      .metric small { color:var(--muted); line-height:1.45; }
      .usage { margin-top:18px; border-radius:18px; padding:16px; background:linear-gradient(135deg, rgba(31,122,67,.08), rgba(240,185,75,.12)); border:1px solid rgba(17,81,43,.1); }
      .usage-head { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:10px; font-weight:800; }
      .bar { overflow:hidden; height:14px; border-radius:999px; background:rgba(17,81,43,.1); }
      .bar span { display:block; height:100%; width:<?php echo e((string)$usagePercent); ?>%; border-radius:999px; background:linear-gradient(135deg, var(--brand), var(--accent)); }
      .password-row { display:flex; gap:8px; align-items:stretch; }
      .secret { flex:1; display:flex; align-items:center; min-height:46px; border:1px solid rgba(17,81,43,.16); border-radius:14px; padding:10px 12px; background:#fff; overflow-wrap:anywhere; font-weight:800; }
      .toggle { border:0; border-radius:14px; padding:10px 14px; background:var(--brand-deep); color:#fff; font-weight:800; cursor:pointer; }
      .facts { display:grid; gap:10px; margin-top:18px; }
      .fact { display:grid; gap:4px; border-radius:14px; padding:12px; background:rgba(17,81,43,.04); }
      .fact strong { font-size:13px; letter-spacing:.04em; text-transform:uppercase; color:var(--brand-deep); }
      .fact span { font-size:14px; line-height:1.45; overflow-wrap:anywhere; }
      .aside-banner { margin:0 0 14px; }
      .aside-banner img { width:100%; display:block; height:auto; object-fit:contain; border-radius:14px; border:1px solid rgba(17,81,43,.14); background:#fff; }
      form { margin:0; }
      @media (min-width:680px){ .hero-grid{grid-template-columns:minmax(0,1.2fr) minmax(220px,.8fr);} .hero-media{justify-self:end;} .metrics{grid-template-columns:repeat(2, minmax(0,1fr));} }
      @media (min-width:980px){ .layout{grid-template-columns:minmax(0,7fr) minmax(300px,5fr); align-items:start;} .card{padding:28px;} .aside{position:sticky; top:14px;} .metrics{grid-template-columns:repeat(3, minmax(0,1fr));} }
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
            <h1>Your Wi-Fi dashboard</h1>
            <p>Use these Radius credentials on the enterprise Wi-Fi login screen and track today&apos;s access allowance.</p>
            <div class="chips"><span class="chip">SSID: <?php echo e($radiusSsid); ?></span><span class="chip"><?php echo e($minutesRemaining . ' min remaining'); ?></span><span class="chip"><?php echo e($planCode !== '' ? $planCode : 'Plan pending'); ?></span></div>
          </div>
          <div class="hero-media" aria-hidden="true"><div class="hero-frame"><img class="hero-image" src="https://kisaanu.com/media/public-wifi-mallupur-hero.gif" alt="Mallupur Wi-Fi illustration" decoding="async" /></div></div>
        </div>
      </section>

      <section class="layout">
        <div class="card">
          <nav class="nav" aria-label="Portal">
            <a class="nav-link" href="/index.php">Home</a>
            <a class="nav-link" href="/wifi.php">wifi.php</a>
            <a class="nav-link" href="/register.php">Register</a>
            <a class="nav-link" href="/login.php">Login</a>
            <a class="nav-link active" href="/dashboard">Dashboard</a>
            <a class="nav-link" href="/profile">Profile</a>
            <form method="post" action="/login"><input type="hidden" name="formMode" value="logout" /><button class="logout-btn" type="submit">Logout</button></form>
          </nav>

          <?php if ($statusMessage !== ''): ?><div class="banner"><?php echo e($statusMessage); ?></div><?php endif; ?>

          <h2 class="section-title">Radius Login</h2>
          <p class="section-copy">After registration, use this username and password for the Kisaanu enterprise Wi-Fi login. Keep the password private.</p>

          <div class="metrics">
            <div class="metric"><strong>Wi-Fi Username</strong><span><?php echo e($radiusUsername); ?></span><small>Usually your registered mobile number.</small></div>
            <div class="metric"><strong>Package</strong><span><?php echo e($planCode !== '' ? $planCode : 'Not assigned'); ?></span><small>Synced through radusergroup.</small></div>
            <div class="metric"><strong>SSID</strong><span><?php echo e($radiusSsid); ?></span><small>Select this network on your phone.</small></div>
          </div>

          <div class="usage">
            <div class="usage-head"><span>Today&apos;s Data Usage</span><span><?php echo e($usagePercent . '% used'); ?></span></div>
            <div class="bar" aria-label="Usage progress"><span></span></div>
            <p class="section-copy" style="margin:12px 0 0;">Used <?php echo e((string)$minutesUsed); ?> minutes. Remaining <?php echo e((string)$minutesRemaining); ?> of <?php echo e((string)$minutesTotal); ?> minutes.</p>
          </div>

          <div class="usage">
            <h3 class="section-title" style="font-size:22px;">Wi-Fi Password</h3>
            <div class="password-row">
              <div class="secret" id="radiusPassword" data-secret="<?php echo e($radiusPassword); ?>"><?php echo e($radiusPassword !== '' ? str_repeat('*', min(12, max(8, strlen($radiusPassword)))) : 'Not available'); ?></div>
              <button class="toggle" type="button" data-toggle-secret="radiusPassword">Show</button>
            </div>
          </div>
        </div>

        <aside class="card aside">
          <figure class="aside-banner"><img src="https://kisaanu.com/media/JayKisaanu-Village-Banner-Final.png?v=20260513-2" alt="Mallupur network advisory banner" loading="lazy" /></figure>
          <h3 class="section-title">Connection Steps</h3>
          <div class="facts" style="margin-top:0;">
            <div class="fact"><strong>Step 1</strong><span>Open Wi-Fi settings and select <?php echo e($radiusSsid); ?>.</span></div>
            <div class="fact"><strong>Step 2</strong><span>Use Radius username <?php echo e($radiusUsername); ?> and your Wi-Fi password.</span></div>
            <div class="fact"><strong>Step 3</strong><span>After connection, return here to check usage and profile details.</span></div>
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
