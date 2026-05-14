<?php
declare(strict_types=1);

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Mallupur Adhunik Gram Seva Public Wi-Fi</title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700;800&family=Quicksand:wght@500;600;700&display=swap');
      :root { color-scheme: light; --bg:#f4f8ef; --panel:rgba(255,255,255,.94); --text:#12331f; --muted:#5d7464; --brand:#1f7a43; --brand-deep:#11512b; --accent:#f0b94b; --line:rgba(17,81,43,.12); --shadow:0 20px 60px rgba(17,81,43,.12); }
      * { box-sizing:border-box; }
      body { margin:0; min-height:100vh; font-family:"Quicksand","Noto Sans Devanagari",system-ui,sans-serif; color:var(--text); background:radial-gradient(circle at top left, rgba(240,185,75,.22), transparent 38%), radial-gradient(circle at top right, rgba(31,122,67,.16), transparent 36%), linear-gradient(180deg,#f8fbf5 0%,var(--bg) 100%); }
      h1,h2,h3,.btn,.section-title,.fact strong { font-family:"Lexend","Noto Sans Devanagari",system-ui,sans-serif; }
      .page { width:min(100%,1120px); margin:0 auto; padding:24px 16px 48px; }
      .hero { position:relative; overflow:hidden; border-radius:30px; padding:26px; background:linear-gradient(145deg, rgba(17,81,43,.97), rgba(31,122,67,.92)); color:#fff; box-shadow:var(--shadow); }
      .hero::after { content:""; position:absolute; inset:-34% auto auto 58%; width:260px; height:260px; border-radius:999px; background:rgba(240,185,75,.18); filter:blur(5px); }
      .hero-grid { position:relative; z-index:1; display:grid; gap:22px; align-items:center; }
      .brand { display:inline-flex; align-items:center; gap:10px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.12); font-size:12px; letter-spacing:.08em; text-transform:uppercase; }
      .brand-badge { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:var(--accent); }
      h1 { max-width:12ch; margin:18px 0 12px; font-size:clamp(36px,7vw,64px); line-height:.97; letter-spacing:-.05em; }
      .hero p { max-width:44ch; margin:0; color:rgba(255,255,255,.84); line-height:1.65; font-size:16px; }
      .actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:22px; }
      .btn { display:inline-flex; align-items:center; justify-content:center; min-height:54px; padding:14px 20px; border-radius:16px; text-decoration:none; font-weight:800; }
      .btn.primary { color:var(--brand-deep); background:var(--accent); box-shadow:0 18px 32px rgba(0,0,0,.16); }
      .btn.secondary { color:#fff; border:1px solid rgba(255,255,255,.28); background:rgba(255,255,255,.1); }
      .hero-media { width:min(100%,260px); justify-self:center; }
      .hero-frame { overflow:hidden; border-radius:24px; padding:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); box-shadow:0 22px 44px rgba(9,43,23,.24); }
      .hero-image { display:block; width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:18px; }
      .content { display:grid; gap:18px; margin-top:18px; }
      .card { border-radius:24px; padding:24px; background:var(--panel); border:1px solid var(--line); box-shadow:var(--shadow); }
      .section-title { margin:0 0 8px; font-size:clamp(24px,4vw,34px); line-height:1.06; letter-spacing:-.035em; }
      .section-copy { margin:0; color:var(--muted); line-height:1.7; }
      .facts { display:grid; gap:12px; margin-top:18px; }
      .fact { border-radius:18px; padding:15px; background:rgba(17,81,43,.04); border:1px solid rgba(17,81,43,.08); }
      .fact strong { display:block; color:var(--brand-deep); font-size:13px; letter-spacing:.04em; text-transform:uppercase; margin-bottom:5px; }
      .fact span { display:block; line-height:1.5; }
      @media (min-width:680px){ .hero-grid{grid-template-columns:minmax(0,1.15fr) minmax(220px,.85fr);} .hero-media{justify-self:end;} }
      @media (min-width:920px){ .content{grid-template-columns:1.05fr .95fr;} .card{padding:28px;} }
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
            <h1>Mallupur Adhunik Gram Seva Public Wi-Fi</h1>
            <p>Community Wi-Fi access for Mallupur residents. Register once to create your Radius username and password, then use those credentials on the enterprise Wi-Fi network.</p>
            <div class="actions">
              <a class="btn primary" href="/register">Register</a>
              <a class="btn secondary" href="/login">Login</a>
            </div>
          </div>
          <div class="hero-media" aria-hidden="true"><div class="hero-frame"><img class="hero-image" src="https://kisaanu.com/media/public-wifi-mallupur-hero.gif" alt="Mallupur public Wi-Fi illustration" decoding="async" /></div></div>
        </div>
      </section>

      <section class="content">
        <div class="card">
          <h2 class="section-title">How To Access</h2>
          <p class="section-copy">Use this portal to create or open your Kisaanu Wi-Fi account. The same account is used by the Radius server for Wi-Fi authentication.</p>
          <div class="facts">
            <div class="fact"><strong>Step 1</strong><span>Tap Register and create your Wi-Fi username and password.</span></div>
            <div class="fact"><strong>Step 2</strong><span>Select the Kisaanu SSID on your phone and enter your Radius login details.</span></div>
            <div class="fact"><strong>Step 3</strong><span>Use Login later to view SSID, usage, and profile information.</span></div>
          </div>
        </div>
        <div class="card">
          <h2 class="section-title">Project Guidelines</h2>
          <p class="section-copy">This network is intended for study, public services, farming information, and essential communication. Keep your Radius password private.</p>
          <div class="facts">
            <div class="fact"><strong>SSID</strong><span>MALLUPUR-KISAANU-WIFI</span></div>
            <div class="fact"><strong>Package</strong><span>Daily time allowance is controlled by the assigned Radius package.</span></div>
            <div class="fact"><strong>Support</strong><span>If login fails, open this portal and check your dashboard credentials.</span></div>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>
