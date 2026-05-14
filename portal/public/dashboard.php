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
$isAdmin = is_admin_user($radiusUsername);

function lucide_icon(string $name): string
{
    $icons = [
        'wifi' => '<path d="M5 13a10 10 0 0 1 14 0"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M12 20h.01"/>',
        'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'shield' => '<path d="M20 13c0 5-3.5 7.5-7.3 8.8a2 2 0 0 1-1.4 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.4a1.5 1.5 0 0 1 1.6 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1Z"/><path d="m9 12 2 2 4-4"/>',
        'route' => '<circle cx="6" cy="19" r="3"/><path d="M9 19h8a3 3 0 0 0 0-6H7a3 3 0 0 1 0-6h8"/><circle cx="18" cy="5" r="3"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'gauge' => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/>',
    ];
    $body = $icons[$name] ?? $icons['wifi'];
    return '<svg class="lucide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}
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
      :root { color-scheme: light; --bg:#f4f8ef; --panel:rgba(255, 255, 255, 0.94); --text:#12331f; --muted:#5d7464; --brand:#1f7a43; --brand-deep:#11512b; --accent:#f0b94b; --line:rgba(17,81,43,.12); --danger:#b42318; --success:#0f7a3f; --shadow:0 20px 60px rgba(17,81,43,.12); --soft:#edf6e8; --ink-soft:#2d5c3e; }
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
      h1 { margin:18px 0 10px; font-size:clamp(32px, 6vw, 54px); line-height:0.98; letter-spacing:-.045em; max-width:11ch; }
      .hero p { margin:0; max-width:38ch; color:rgba(255,255,255,.82); line-height:1.55; }
      .chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
      .chip { display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:16px; background:rgba(255,255,255,.1); font-size:13px; font-weight:700; }
      .hero-media { width:min(100%,240px); justify-self:center; }
      .hero-frame { overflow:hidden; border-radius:24px; padding:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); box-shadow:0 22px 44px rgba(9,43,23,.24); }
      .hero-image { display:block; width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:18px; }
      .lucide { width:20px; height:20px; flex:0 0 auto; }
      .layout { display:grid; gap:18px; }
      .card { margin-top:18px; border-radius:28px; padding:24px; background:var(--panel); box-shadow:var(--shadow); border:1px solid var(--line); backdrop-filter:blur(12px); }
      .nav { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
      .nav-link,.logout-btn { border:1px solid rgba(17,81,43,.16); border-radius:999px; min-height:40px; padding:9px 14px; background:#fff; color:var(--brand-deep); text-decoration:none; font-size:12px; font-weight:800; cursor:pointer; }
      .nav-link.active { background:rgba(31,122,67,.1); border-color:rgba(31,122,67,.28); }
      .section-title { margin:0 0 8px; font-size:clamp(24px, 4vw, 34px); line-height:1.05; letter-spacing:-.035em; color:#0f3320; }
      .section-copy { margin:0 0 20px; color:var(--muted); line-height:1.7; font-size:15px; }
      .banner { display:flex; align-items:center; gap:10px; margin-bottom:16px; border-radius:18px; padding:14px 16px; font-size:14px; line-height:1.5; background:rgba(31,122,67,.08); color:var(--success); border:1px solid rgba(31,122,67,.18); font-weight:700; }
      .dashboard-head { display:grid; gap:12px; margin-bottom:18px; }
      .dashboard-kicker { display:inline-flex; align-items:center; gap:8px; color:var(--brand-deep); font:800 12px/1 "Lexend", system-ui, sans-serif; letter-spacing:.08em; text-transform:uppercase; }
      .metrics { display:grid; gap:12px; grid-template-columns:1fr; }
      .metric { position:relative; overflow:hidden; display:grid; gap:10px; border-radius:22px; padding:18px; background:linear-gradient(180deg, #fff, rgba(246,250,242,.92)); border:1px solid rgba(17,81,43,.1); box-shadow:0 14px 34px rgba(17,81,43,.07); }
      .metric::after { content:""; position:absolute; inset:auto -24px -34px auto; width:86px; height:86px; border-radius:999px; background:rgba(31,122,67,.06); }
      .metric-icon { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; color:var(--brand-deep); background:rgba(31,122,67,.1); border:1px solid rgba(31,122,67,.12); }
      .metric strong { font-size:11px; color:var(--ink-soft); letter-spacing:.08em; text-transform:uppercase; }
      .metric span { overflow-wrap:anywhere; font-family:"Lexend", system-ui, sans-serif; font-size:clamp(20px, 4vw, 28px); line-height:1.08; font-weight:800; letter-spacing:-.035em; color:#0e2d1d; }
      .metric small { color:var(--muted); line-height:1.45; }
      .info-section { margin-top:18px; }
      .info-section:first-of-type { margin-top:0; }
      .info-title { display:flex; align-items:center; gap:10px; margin:0 0 12px; font-family:"Lexend", system-ui, sans-serif; font-size:20px; line-height:1.1; letter-spacing:-.025em; color:#0f3320; }
      .plain-grid { display:grid; gap:12px; grid-template-columns:1fr; }
      .plain-item { border-radius:20px; padding:16px; background:linear-gradient(180deg,#fff,rgba(246,250,242,.92)); border:1px solid rgba(17,81,43,.1); }
      .plain-item strong { display:block; margin-bottom:8px; color:var(--ink-soft); font:800 11px/1 "Lexend", system-ui, sans-serif; letter-spacing:.08em; text-transform:uppercase; }
      .plain-item span { display:block; overflow-wrap:anywhere; font-family:"Lexend", system-ui, sans-serif; color:#0e2d1d; font-size:20px; line-height:1.18; font-weight:800; letter-spacing:-.025em; }
      .admin-actions { display:grid; gap:10px; margin-top:18px; }
      .admin-link { display:flex; align-items:center; gap:10px; border-radius:18px; padding:14px 16px; background:#fff; border:1px solid rgba(17,81,43,.12); color:var(--brand-deep); text-decoration:none; font-family:"Lexend", system-ui, sans-serif; font-weight:800; }
      .usage-grid { display:grid; gap:14px; margin-top:18px; }
      .usage { border-radius:24px; padding:18px; background:linear-gradient(135deg, rgba(31,122,67,.09), rgba(240,185,75,.14)); border:1px solid rgba(17,81,43,.1); }
      .usage-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px; font-weight:800; }
      .usage-title { display:flex; align-items:center; gap:10px; font-family:"Lexend", system-ui, sans-serif; color:#0f3320; }
      .usage-title .metric-icon { width:38px; height:38px; border-radius:14px; background:#fff; }
      .usage-value { font-family:"Lexend", system-ui, sans-serif; color:var(--brand-deep); }
      .bar { overflow:hidden; height:16px; border-radius:999px; background:rgba(17,81,43,.1); box-shadow:inset 0 1px 2px rgba(17,81,43,.12); }
      .bar span { display:block; height:100%; width:<?php echo e((string)$usagePercent); ?>%; border-radius:999px; background:linear-gradient(135deg, var(--brand), var(--accent)); }
      .password-row { display:flex; gap:8px; align-items:stretch; }
      .secret { flex:1; display:flex; align-items:center; min-height:52px; border:1px solid rgba(17,81,43,.16); border-radius:16px; padding:12px 14px; background:#fff; overflow-wrap:anywhere; font-family:"Lexend", system-ui, sans-serif; font-weight:800; letter-spacing:.04em; }
      .toggle { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:16px; padding:10px 14px; background:var(--brand-deep); color:#fff; font-weight:800; cursor:pointer; }
      .facts { display:grid; gap:10px; margin-top:18px; }
      .fact { display:grid; grid-template-columns:42px 1fr; gap:12px; align-items:start; border-radius:18px; padding:14px; background:linear-gradient(180deg, #fff, rgba(246,250,242,.92)); border:1px solid rgba(17,81,43,.08); }
      .fact-icon { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:15px; color:var(--brand-deep); background:rgba(31,122,67,.08); }
      .fact strong { font-size:13px; letter-spacing:.04em; text-transform:uppercase; color:var(--brand-deep); }
      .fact span { font-size:14px; line-height:1.45; overflow-wrap:anywhere; }
      .mini-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:14px; }
      .mini-stat { border-radius:18px; padding:14px; background:#fff; border:1px solid rgba(17,81,43,.1); }
      .mini-stat strong { display:block; font-family:"Lexend", system-ui, sans-serif; font-size:24px; line-height:1; letter-spacing:-.04em; color:#0f3320; }
      .mini-stat span { display:block; margin-top:6px; color:var(--muted); font-size:12px; font-weight:700; }
      .aside-banner { margin:0 0 14px; }
      .aside-banner img { width:100%; display:block; height:auto; object-fit:contain; border-radius:14px; border:1px solid rgba(17,81,43,.14); background:#fff; }
      form { margin:0; }
      @media (min-width:680px){ .hero-grid{grid-template-columns:minmax(0,1.2fr) minmax(220px,.8fr);} .hero-media{justify-self:end;} .metrics{grid-template-columns:repeat(2, minmax(0,1fr));} .usage-grid{grid-template-columns:1.15fr .85fr;} .plain-grid{grid-template-columns:repeat(2,minmax(0,1fr));} .admin-actions{grid-template-columns:1fr 1fr;} }
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
            <div class="chips">
              <span class="chip"><?php echo lucide_icon('wifi'); ?> SSID: <?php echo e($radiusSsid); ?></span>
              <span class="chip"><?php echo lucide_icon('clock'); ?> <?php echo e($minutesRemaining . ' min remaining'); ?></span>
              <span class="chip"><?php echo lucide_icon('package'); ?> <?php echo e($planCode !== '' ? $planCode : 'Plan pending'); ?></span>
            </div>
          </div>
          <div class="hero-media" aria-hidden="true"><div class="hero-frame"><img class="hero-image" src="https://kisaanu.com/media/public-wifi-mallupur-hero.gif" alt="Mallupur Wi-Fi illustration" decoding="async" /></div></div>
        </div>
      </section>

      <section class="layout">
        <div class="card">
          <nav class="nav" aria-label="Portal">
            <a class="nav-link" href="/profile">Profile</a>
            <form method="post" action="/login"><input type="hidden" name="formMode" value="logout" /><button class="logout-btn" type="submit">Logout</button></form>
          </nav>

          <?php if ($statusMessage !== ''): ?><div class="banner"><?php echo lucide_icon('shield'); ?> <?php echo e($statusMessage); ?></div><?php endif; ?>

          <div class="dashboard-head">
            <div class="dashboard-kicker"><?php echo lucide_icon('login'); ?> Enterprise Radius Access</div>
            <h2 class="section-title">Wi-Fi Account Dashboard</h2>
            <p class="section-copy">Your SSID details, usage status, and Radius login information are shown below in plain text.</p>
          </div>

          <div class="info-section">
            <h3 class="info-title"><span class="metric-icon"><?php echo lucide_icon('wifi'); ?></span> SSID Information</h3>
            <div class="plain-grid">
              <div class="plain-item"><strong>SSID Name</strong><span><?php echo e($radiusSsid); ?></span></div>
              <div class="plain-item"><strong>Connection Type</strong><span>Enterprise Radius Login</span></div>
            </div>
          </div>

          <div class="info-section">
            <h3 class="info-title"><span class="metric-icon"><?php echo lucide_icon('gauge'); ?></span> Usage Information</h3>
            <div class="usage">
              <div class="usage-head"><span class="usage-title">Today&apos;s Usage</span><span class="usage-value"><?php echo e($usagePercent . '% used'); ?></span></div>
              <div class="bar" aria-label="Usage progress"><span></span></div>
            </div>
            <div class="plain-grid" style="margin-top:12px;">
              <div class="plain-item"><strong>Minutes Used</strong><span><?php echo e((string)$minutesUsed); ?></span></div>
              <div class="plain-item"><strong>Minutes Remaining</strong><span><?php echo e((string)$minutesRemaining); ?></span></div>
              <div class="plain-item"><strong>Total Daily Minutes</strong><span><?php echo e((string)$minutesTotal); ?></span></div>
              <div class="plain-item"><strong>Package</strong><span><?php echo e($planCode !== '' ? $planCode : 'Not assigned'); ?></span></div>
            </div>
          </div>

          <div class="info-section">
            <h3 class="info-title"><span class="metric-icon"><?php echo lucide_icon('key'); ?></span> Login Information</h3>
            <div class="plain-grid">
              <div class="plain-item"><strong>Radius Username</strong><span><?php echo e($radiusUsername); ?></span></div>
              <div class="plain-item"><strong>Radius Password</strong><span><?php echo e($radiusPassword !== '' ? $radiusPassword : 'Not available'); ?></span></div>
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <div class="info-section">
              <h3 class="info-title"><span class="metric-icon"><?php echo lucide_icon('settings'); ?></span> Admin Tools</h3>
              <div class="admin-actions">
                <a class="admin-link" href="/daloradius/"><?php echo lucide_icon('settings'); ?> daloRADIUS</a>
                <a class="admin-link" href="/phpmyadmin/"><?php echo lucide_icon('database'); ?> phpMyAdmin</a>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <aside class="card aside">
          <figure class="aside-banner">
            <picture>
              <source srcset="/media/JayKisaanu-Village-Banner-Final-1600.webp" type="image/webp" />
              <img src="/media/JayKisaanu-Village-Banner-Final-1600.png" alt="Mallupur network advisory banner" loading="lazy" width="1600" height="800" />
            </picture>
          </figure>
          <h3 class="section-title">Connection Steps</h3>
          <div class="facts" style="margin-top:0;">
            <div class="fact"><span class="fact-icon"><?php echo lucide_icon('wifi'); ?></span><span><strong>Step 1</strong><span>Open Wi-Fi settings and select <?php echo e($radiusSsid); ?>.</span></span></div>
            <div class="fact"><span class="fact-icon"><?php echo lucide_icon('user'); ?></span><span><strong>Step 2</strong><span>Use Radius username <?php echo e($radiusUsername); ?> and your Wi-Fi password.</span></span></div>
            <div class="fact"><span class="fact-icon"><?php echo lucide_icon('route'); ?></span><span><strong>Step 3</strong><span>After connection, return here to check usage and profile details.</span></span></div>
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
            var label = button.querySelector('.toggle-label');
            if (label) label.textContent = showing ? 'Show' : 'Hide';
            button.setAttribute('data-showing', showing ? '0' : '1');
          });
        });
      })();
    </script>
  </body>
</html>
