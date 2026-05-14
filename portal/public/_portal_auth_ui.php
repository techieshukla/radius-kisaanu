<?php
/** @var string $pageTitle */
/** @var string $activeTab */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex,nofollow" />
    <title><?php echo e($pageTitle); ?></title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap');
      :root { color-scheme: light; --bg:#f4f8ef; --panel:rgba(255, 255, 255, 0.94); --text:#12331f; --muted:#5d7464; --brand:#1f7a43; --brand-deep:#11512b; --accent:#f0b94b; --line:rgba(17,81,43,.12); --danger:#b42318; --success:#0f7a3f; --shadow:0 20px 60px rgba(17,81,43,.12); }
      * { box-sizing: border-box; }
      body { margin:0; min-height:100vh; font-family:"Quicksand","Noto Sans Devanagari",system-ui,sans-serif; color:var(--text); background:radial-gradient(circle at top left, rgba(240,185,75,.22), transparent 38%), radial-gradient(circle at top right, rgba(31,122,67,.16), transparent 36%), linear-gradient(180deg, #f8fbf5 0%, var(--bg) 100%); }
      h1,h2,h3,.section-title,.btn,.auth-tab,label,.fact strong { font-family:"Lexend","Noto Sans Devanagari",system-ui,sans-serif; }
      a { color: var(--brand-deep); }
      .page { width:min(100%,1120px); margin:0 auto; padding:24px 16px 48px; }
      .hero { position:relative; overflow:hidden; border-radius:28px; padding:24px; background:linear-gradient(145deg, rgba(17,81,43,.96), rgba(31,122,67,.92)), var(--brand); color:#fff; box-shadow:var(--shadow); }
      .hero-grid { position:relative; z-index:1; display:grid; gap:18px; align-items:center; }
      .hero::after { content:""; position:absolute; inset:-40% auto auto 54%; width:220px; height:220px; border-radius:999px; background:rgba(240,185,75,.18); filter:blur(4px); }
      .brand { display:inline-flex; align-items:center; gap:10px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.12); font-size:12px; letter-spacing:.08em; text-transform:uppercase; }
      .brand-badge { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--accent); color:var(--brand-deep); font-weight:800; }
      h1 { margin:18px 0 10px; font-size:28px; line-height:1.12; }
      .hero p { margin:0; max-width:34ch; color:rgba(255,255,255,.82); line-height:1.55; }
      .hero-media { width:min(100%,240px); justify-self:center; }
      .hero-frame { overflow:hidden; border-radius:24px; padding:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); box-shadow:0 22px 44px rgba(9,43,23,.24); }
      .hero-image { display:block; width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:18px; }
      .chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
      .chip { display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:16px; background:rgba(255,255,255,.1); font-size:13px; }
      .card { margin-top:18px; border-radius:24px; padding:24px; background:var(--panel); box-shadow:var(--shadow); border:1px solid var(--line); backdrop-filter:blur(12px); }
      .register-layout { display:grid; gap:18px; }
      .banner { margin-bottom:16px; border-radius:18px; padding:14px 16px; font-size:14px; line-height:1.5; }
      .banner.error { background:rgba(180,35,24,.08); color:var(--danger); border:1px solid rgba(180,35,24,.18); }
      .banner.info { background:rgba(31,122,67,.08); color:var(--success); border:1px solid rgba(31,122,67,.18); }
      .section-title { margin:0 0 8px; font-size:26px; line-height:1.1; letter-spacing:-.01em; }
      .section-copy { margin:0 0 20px; color:var(--muted); line-height:1.7; font-size:15px; }
      .grid { display:grid; gap:16px; }
      .auth-tabs { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:16px; background:rgba(17,81,43,.06); padding:6px; border-radius:14px; }
      .auth-tab { border:0; border-radius:10px; min-height:42px; background:transparent; color:var(--brand-deep); font-weight:700; cursor:pointer; }
      .auth-tab.active { background:#fff; box-shadow:0 8px 18px rgba(17,81,43,.14); }
      .auth-panel[hidden] { display:none; }
      .field { display:grid; gap:8px; }
      label { font-size:13px; font-weight:700; line-height:1.35; color:#0f3a25; }
      .helper { font-size:12px; color:var(--muted); line-height:1.45; }
      input,textarea,button,select { font:inherit; }
      input[type="text"],input[type="tel"],input[type="password"],textarea,select { width:100%; border:1px solid rgba(17,81,43,.16); border-radius:14px; padding:12px 14px; background:rgba(255,255,255,.95); color:var(--text); min-height:48px; }
      textarea { min-height:112px; resize:vertical; }
      .two-up { display:grid; gap:14px; grid-template-columns:1fr; }
      .form-note { border-radius:12px; padding:10px 12px; background:rgba(17,81,43,.04); border:1px solid rgba(17,81,43,.1); color:var(--muted); font-size:12px; line-height:1.5; }
      .action { margin-top:18px; }
      .login-note { margin-top:2px; font-size:13px; color:var(--muted); background:rgba(17,81,43,.05); border:1px solid rgba(17,81,43,.12); border-radius:12px; padding:9px 10px; line-height:1.45; }
      .btn { display:inline-flex; justify-content:center; align-items:center; gap:10px; width:100%; min-height:54px; border:0; border-radius:14px; padding:14px 20px; background:linear-gradient(135deg, var(--brand), var(--brand-deep)); color:#fff; font-weight:800; box-shadow:0 16px 32px rgba(17,81,43,.22); cursor:pointer; }
      .facts { display:grid; gap:10px; margin-top:18px; }
      .aside-banner { margin:0 0 14px; }
      .aside-banner img { width:100%; display:block; height:auto; object-fit:contain; border-radius:14px; border:1px solid rgba(17,81,43,.14); background:#fff; }
      .fact { display:grid; gap:4px; border-radius:14px; padding:12px; background:rgba(17,81,43,.04); }
      .fact strong { font-size:13px; letter-spacing:.04em; text-transform:uppercase; color:var(--brand-deep); }
      .fact span { font-size:14px; line-height:1.45; }
      .quick-links { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 14px; }
      .quick-links a { text-decoration:none; border:1px solid rgba(17,81,43,.18); padding:8px 12px; border-radius:999px; font-size:12px; font-weight:700; background:#fff; }
      @media (min-width:680px){ .hero-grid{grid-template-columns:minmax(0,1.2fr) minmax(220px,.8fr);} .hero-media{justify-self:end;} }
      @media (min-width:1200px){ .two-up{grid-template-columns:1fr 1fr;} }
      @media (min-width:980px){ .register-layout{grid-template-columns:minmax(0,5fr) minmax(0,7fr); align-items:start;} .register-aside{position:sticky; top:14px;} .card{padding:28px;} }
    </style>
  </head>
  <body>
    <main class="page">
      <section class="hero">
        <div class="hero-grid">
          <div class="hero-copy">
            <div class="brand">
              <span class="brand-badge"><img src="https://kisaanu.com/kisaanu-transparent-logo.png" alt="Kisaanu" style="width:20px;height:20px;object-fit:contain;" /></span>
              <span>Kisaanu Public Wi-Fi Portal</span>
            </div>
            <h1>Mallupur Adhunik Gram Seva Public Wi-Fi</h1>
            <p>Register once each day to activate up to 2 hours of Wi-Fi on this device.</p>
            <div class="chips"><span class="chip">2 hours daily</span><span class="chip">This device only</span><span class="chip">Mallupur address required</span></div>
          </div>
          <div class="hero-media" aria-hidden="true"><div class="hero-frame"><img class="hero-image" src="https://kisaanu.com/media/public-wifi-mallupur-hero.gif" alt="Animated Mallupur public Wi-Fi help illustration" decoding="async" /></div></div>
        </div>
      </section>

      <section class="register-layout">
        <div class="card">
          <?php if ($statusMessage !== ''): ?>
            <div class="banner <?php echo e($statusType === 'danger' ? 'error' : 'info'); ?>"><?php echo e($statusMessage); ?></div>
          <?php endif; ?>

          <h2 class="section-title">Wi-Fi Access</h2>
          <p class="section-copy">Mallupur address is mandatory. Your registration is mapped to this device for security.</p>

          <div class="auth-tabs" role="tablist" aria-label="Choose action">
            <button class="auth-tab <?php echo e($activeTab === 'register' ? 'active' : ''); ?>" type="button" data-auth-tab="register" role="tab" aria-selected="<?php echo e($activeTab === 'register' ? 'true' : 'false'); ?>">Register</button>
            <button class="auth-tab <?php echo e($activeTab === 'login' ? 'active' : ''); ?>" type="button" data-auth-tab="login" role="tab" aria-selected="<?php echo e($activeTab === 'login' ? 'true' : 'false'); ?>">Login</button>
          </div>

          <div class="auth-panel" data-auth-panel="register" <?php echo e($activeTab === 'register' ? '' : 'hidden'); ?>>
            <form method="post" action="/register.php" class="grid register-form">
              <input type="hidden" name="formMode" value="register" />
              <div class="two-up">
                <div class="field"><label for="fullName">Full name / पूरा नाम</label><input id="fullName" name="fullName" type="text" autocomplete="name" maxlength="150" required /></div>
                <div class="field"><label for="mobileNumber">Mobile number / मोबाइल नंबर</label><input id="mobileNumber" name="mobileNumber" type="tel" inputmode="numeric" autocomplete="tel" maxlength="15" required /></div>
              </div>
              <div class="field"><label for="aadhaarNumber">Aadhaar number / आधार संख्या</label><input id="aadhaarNumber" name="aadhaarNumber" type="tel" inputmode="numeric" maxlength="14" required /><span class="helper">Used only for identity validation. We store a secure hash and the last 4 digits.</span></div>
              <div class="two-up">
                <div class="field"><label for="fatherName">Father name / पिता का नाम</label><input id="fatherName" name="fatherName" type="text" maxlength="150" required /></div>
                <div class="field"><label for="motherName">Mother name / माता का नाम</label><input id="motherName" name="motherName" type="text" maxlength="150" required /></div>
              </div>
              <div class="two-up">
                <div class="field"><label for="village">Village / गाँव</label><input id="village" name="village" type="text" maxlength="150" required value="Mallupur" /></div>
                <div class="field"><label for="deviceMac">Device / डिवाइस</label><input type="text" value="<?php echo e($clientMac !== '' ? $clientMac : 'Will be captured from Wi-Fi controller'); ?>" readonly id="deviceMac" /></div>
              </div>
              <div class="two-up">
                <div class="field"><label for="wifiPassword">Wi-Fi Password</label><input id="wifiPassword" name="wifiPassword" type="password" required /></div>
                <div class="field"><label for="ssidName">SSID</label><input id="ssidName" name="ssidName" type="text" value="<?php echo e($ssid !== '' ? $ssid : 'MALLUPUR-KISAANU-WIFI'); ?>" required /></div>
              </div>
              <div class="field"><label for="planCode">Package</label><select id="planCode" name="planCode" required><?php foreach ($activePlans as $plan): ?><option value="<?php echo e((string)$plan['plan_code']); ?>"><?php echo e((string)$plan['display_name']); ?></option><?php endforeach; ?></select></div>
              <div class="field"><label for="address">Full Mallupur address / पूरा मल्लूपुर पता</label><textarea id="address" name="address" required maxlength="500"></textarea><span class="helper">The address must clearly mention Mallupur so eligibility can be verified.</span></div>
              <div class="form-note">By continuing, you accept portal terms and device-based verification rules.</div>

              <input type="hidden" name="target" value="<?php echo e($target); ?>" />
              <input type="hidden" name="targetPort" value="<?php echo e($targetPort); ?>" />
              <input type="hidden" name="clientMac" value="<?php echo e($clientMac); ?>" />
              <input type="hidden" name="clientIp" value="<?php echo e($clientIp); ?>" />
              <input type="hidden" name="radiusServerIp" value="<?php echo e($radiusServerIp); ?>" />
              <input type="hidden" name="ap" value="<?php echo e($ap); ?>" />
              <input type="hidden" name="ssid" value="<?php echo e($ssid); ?>" />
              <input type="hidden" name="radioId" value="<?php echo e($radioId); ?>" />
              <input type="hidden" name="redirectUrl" value="<?php echo e($redirectUrl); ?>" />
              <input type="hidden" name="errorHint" value="<?php echo e($errorHint); ?>" />

              <div class="action"><button class="btn" type="submit">Register &amp; Connect Wi-Fi</button></div>
            </form>
          </div>

          <div class="auth-panel" data-auth-panel="login" <?php echo e($activeTab === 'login' ? '' : 'hidden'); ?>>
            <form id="wifiLoginForm" method="post" action="/login.php" class="grid login-form">
              <input type="hidden" name="formMode" value="login" />
              <div class="field"><label for="loginUsername">Username</label><input id="loginUsername" name="username" type="text" autocomplete="username" placeholder="Enter mobile or username" required /></div>
              <div class="field"><label for="loginPassword">Password</label><input id="loginPassword" name="password" type="password" autocomplete="current-password" placeholder="Enter password" required /></div>
              <div class="login-note">Use credentials issued by portal admin. Login is allowed only for mapped device and valid daily quota.</div>
              <div class="action"><button class="btn" type="submit">Login &amp; Connect Wi-Fi</button></div>
            </form>
          </div>
        </div>

        <aside class="card register-aside">
          <figure class="aside-banner"><img src="https://kisaanu.com/media/JayKisaanu-Village-Banner-Final.png?v=20260513-2" alt="Mallupur network advisory banner" loading="lazy" /></figure>
          <h3 class="section-title">Network Use Guidelines</h3>
          <div class="facts" style="margin-top:0;">
            <div class="fact"><strong>Security &amp; Privacy</strong><span>This connection is mapped to your registered device MAC. Keep your login details private and do not share OTP/password.</span></div>
            <div class="fact"><strong>Study &amp; Productive Use</strong><span>Use Wi-Fi for study, official services, farming information, and essential communication.</span></div>
            <div class="fact"><strong>Blocked &amp; Restricted Content</strong><span>Unsafe, illegal, adult, gambling, piracy, and high-risk sites may be blocked by network policy.</span></div>
            <div class="fact"><strong>No File Downloads</strong><span>Do not download unknown files or apps. They may contain viruses or malware and can compromise your device.</span></div>
            <div class="fact"><strong>Usage Credit</strong><span>Daily allowance is up to 2 hours on this approved device. Subscribe to notifications for credit added/exhausted updates.</span></div>
          </div>
        </aside>
      </section>

      <script>
        (function () {
          var tabs = document.querySelectorAll('[data-auth-tab]');
          var panels = document.querySelectorAll('[data-auth-panel]');
          if (!tabs.length || !panels.length) return;
          tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
              var targetTab = tab.getAttribute('data-auth-tab');
              tabs.forEach(function (item) {
                var active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
              });
              panels.forEach(function (panel) {
                var show = panel.getAttribute('data-auth-panel') === targetTab;
                panel.hidden = !show;
              });
            });
          });
        })();
      </script>
    </main>
  </body>
</html>
