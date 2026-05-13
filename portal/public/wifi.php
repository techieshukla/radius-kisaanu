<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Contracts.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/MysqlUserRepository.php';
require_once __DIR__ . '/../src/PortalAuthService.php';
require_once __DIR__ . '/../src/OmadaClient.php';

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function safe_continue_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value) === 1) {
        return $value;
    }
    if (strpos($value, '/') === 0 && strpos($value, '//') !== 0) {
        return $value;
    }
    return '';
}

$target = $_GET['target'] ?? ($_POST['target'] ?? '');
$clientMac = $_GET['clientMac'] ?? ($_POST['clientMac'] ?? '');
$apMac = $_GET['apMac'] ?? ($_POST['apMac'] ?? '');
$ssidName = $_GET['ssidName'] ?? ($_POST['ssidName'] ?? '');
$radioId = $_GET['radioId'] ?? ($_POST['radioId'] ?? '');
$continueUrl = safe_continue_url((string)($_GET['continueUrl'] ?? ($_POST['continueUrl'] ?? ($_GET['redirect'] ?? ($_POST['redirect'] ?? '')))));

$statusType = '';
$statusMessage = '';
$activePlans = Config::fallbackPlans();

try {
    $repoBootstrap = new MysqlUserRepository(Database::connect());
    $plansFromDb = $repoBootstrap->getActivePlans();
    if (!empty($plansFromDb)) {
        $activePlans = $plansFromDb;
    }
} catch (Throwable $ex) {
    (new JsonLogger())->warning('portal.plans.bootstrap_failed', [
        'message' => $ex->getMessage(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['formMode'] ?? 'login';

    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    if ($formMode === 'register') {
        $mobileNumber = preg_replace('/\D+/', '', (string)($_POST['mobileNumber'] ?? ''));
        $accessPin = trim((string)($_POST['accessPin'] ?? ''));
        $selectedPlanCode = trim((string)($_POST['planCode'] ?? Config::defaultPlanCode()));
        $fullName = trim((string)($_POST['fullName'] ?? ''));
        $aadhaarRaw = preg_replace('/\D+/', '', (string)($_POST['aadhaarNumber'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));

        if ($mobileNumber !== '' && $accessPin !== '') {
            $username = $mobileNumber;
            $password = $accessPin;
        }

        if ($fullName === '' || strlen((string)$mobileNumber) < 10 || strlen((string)$aadhaarRaw) < 12 || $address === '') {
            $statusType = 'danger';
            $statusMessage = 'Please provide full name, 10-digit mobile, 12-digit Aadhaar, and full address.';
        }
    }

    if ($statusMessage === '' && ($username === '' || $password === '')) {
        $statusType = 'danger';
        $statusMessage = 'Please fill all required credentials before continuing.';
    } elseif ($statusMessage === '') {
        try {
            $logger = new JsonLogger();
            $repo = new MysqlUserRepository(Database::connect());
            $authService = new PortalAuthService($repo, $logger);
            $omadaClient = new OmadaClient($logger);

            if ($formMode === 'register') {
                $registerResult = $authService->register($username, $password, $selectedPlanCode ?? Config::defaultPlanCode());
                if (!($registerResult['ok'] ?? false)) {
                    $statusType = 'danger';
                    $statusMessage = $registerResult['message'] ?? 'Registration failed.';
                    $authResult = ['ok' => false];
                } else {
                    $aadhaarMasked = '';
                    if (($aadhaarRaw ?? '') !== '') {
                        $last4 = substr((string)$aadhaarRaw, -4);
                        $aadhaarMasked = 'XXXXXXXX' . $last4;
                    }
                    try {
                        $authService->storeProfile([
                            'username' => $username,
                            'full_name' => $fullName ?? '',
                            'mobile_number' => $mobileNumber ?? '',
                            'aadhaar_number_masked' => $aadhaarMasked,
                            'address_text' => $address ?? '',
                            'client_mac' => $clientMac,
                            'ap_mac' => $apMac,
                            'ssid_name' => $ssidName,
                            'plan_code' => $selectedPlanCode ?? Config::defaultPlanCode(),
                        ]);
                    } catch (Throwable $profileEx) {
                        $logger->warning('auth.profile_store_failed', [
                            'username' => $username,
                            'message' => $profileEx->getMessage(),
                        ]);
                    }
                }
            }

            $authResult = $authService->authenticate($username, $password);
        } catch (Throwable $ex) {
            $statusType = 'danger';
            $statusMessage = 'Local auth service error. Please try again.';
            (new JsonLogger())->error('portal.request.exception', [
                'message' => $ex->getMessage(),
            ]);
            $authResult = ['ok' => false];
        }

        if (!($authResult['ok'] ?? false)) {
            $statusType = 'danger';
            $statusMessage = $authResult['message'] ?? 'Unable to validate user credentials.';
        } else {
            $remainingMinutes = (int)floor(((int)$authResult['remaining_seconds']) / 60);

            $omadaResult = $omadaClient->sendAuth(
                $target,
                $username,
                $password,
                $clientMac,
                (int)$authResult['remaining_seconds']
            );

            if (!($omadaResult['ok'] ?? false)) {
                if ($omadaResult['skipped'] ?? false) {
                    $statusType = 'success';
                    $statusMessage = sprintf(
                        'Authenticated locally (%s). Remaining daily quota: ~%d minutes. %s',
                        $authResult['plan_code'],
                        $remainingMinutes,
                        $omadaResult['message']
                    );
                } else {
                    $statusType = 'danger';
                    $statusMessage = $omadaResult['message'] ?? 'Omada authentication failed.';
                }
            } else {
                $statusType = 'success';
                $statusMessage = sprintf(
                    'Authenticated (%s). Remaining daily quota: ~%d minutes. %s',
                    $authResult['plan_code'],
                    $remainingMinutes,
                    $omadaResult['message']
                );
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Kisaanu Public Wi-Fi | Access Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@600;700&display=swap');

    :root {
      --bg-1: #f5f8ff;
      --bg-2: #eef7f1;
      --ink: #13263b;
      --muted: #5c6d80;
      --brand: #0f8a4b;
      --brand-2: #0a6a39;
      --accent: #ffbf47;
      --line: rgba(19, 38, 59, 0.12);
      --panel: rgba(255, 255, 255, 0.92);
      --shadow: 0 20px 60px rgba(15, 42, 83, 0.14);
    }

    body {
      font-family: "Manrope", system-ui, sans-serif;
      color: var(--ink);
      background:
        radial-gradient(circle at 12% 8%, rgba(15, 138, 75, 0.13), transparent 38%),
        radial-gradient(circle at 88% 5%, rgba(255, 191, 71, 0.22), transparent 34%),
        linear-gradient(170deg, var(--bg-1), var(--bg-2));
      min-height: 100vh;
    }

    .wifi-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 24px 14px 42px;
    }

    .hero {
      border-radius: 28px;
      background: linear-gradient(140deg, #0f2f4f, #125d3b 60%, #0b8f4d);
      color: #fff;
      box-shadow: var(--shadow);
      overflow: hidden;
      position: relative;
    }

    .hero::after {
      content: "";
      position: absolute;
      width: 280px;
      height: 280px;
      border-radius: 50%;
      right: -70px;
      top: -70px;
      background: rgba(255, 255, 255, 0.12);
      filter: blur(2px);
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 999px;
      padding: 7px 12px;
      font-size: 12px;
      letter-spacing: .06em;
      text-transform: uppercase;
      font-weight: 700;
    }

    .hero h1 {
      font-family: "Sora", system-ui, sans-serif;
      font-size: clamp(1.6rem, 3vw, 2.25rem);
      margin: 14px 0 10px;
      line-height: 1.1;
    }

    .hero p { color: rgba(255,255,255,.82); max-width: 42ch; }

    .chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }

    .chips span {
      background: rgba(255, 255, 255, 0.16);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 999px;
      padding: 6px 10px;
      font-size: 12px;
      font-weight: 700;
    }

    .portal-card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 22px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(10px);
    }

    .nav-pills .nav-link {
      border-radius: 12px;
      font-weight: 700;
      color: #20425f;
      padding: 10px 14px;
    }

    .nav-pills .nav-link.active {
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      color: #fff;
      box-shadow: 0 10px 22px rgba(15, 138, 75, 0.26);
    }

    .form-control, .form-select {
      border-radius: 12px;
      border-color: rgba(19, 38, 59, 0.16);
      min-height: 46px;
    }

    .form-control:focus, .form-select:focus {
      border-color: rgba(15, 138, 75, 0.66);
      box-shadow: 0 0 0 0.2rem rgba(15, 138, 75, 0.14);
    }

    .btn-primary {
      border: 0;
      border-radius: 12px;
      min-height: 50px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      box-shadow: 0 14px 30px rgba(15, 138, 75, 0.24);
    }

    .btn-outline-secondary {
      border-radius: 12px;
      min-height: 50px;
      font-weight: 700;
    }

    .facts .item {
      background: rgba(15, 138, 75, 0.06);
      border: 1px solid rgba(15, 138, 75, 0.12);
      border-radius: 14px;
      padding: 10px 12px;
      margin-bottom: 10px;
    }

    .facts .item strong {
      display: block;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 2px;
      color: #0f5e35;
    }

    .meta-badge {
      background: #fff;
      border: 1px dashed rgba(19, 38, 59, 0.22);
      border-radius: 12px;
      padding: 8px 10px;
      font-size: 12px;
      color: var(--muted);
    }

    .password-wrap { position: relative; }

    .password-toggle {
      position: absolute;
      right: 8px;
      top: 7px;
      border: 0;
      background: transparent;
      color: #5e7387;
      font-size: 13px;
      font-weight: 700;
      padding: 8px;
    }
  </style>
</head>
<body>
  <main class="wifi-wrap">
    <section class="hero p-4 p-lg-5 mb-3 mb-lg-4">
      <div class="row g-4 align-items-center position-relative" style="z-index:2;">
        <div class="col-lg-8">
          <span class="hero-badge">
            <img src="/kisaanu-transparent-logo.png" alt="Kisaanu" width="18" height="18" style="object-fit:contain;" />
            Kisaanu Public Wi-Fi
          </span>
          <h1>Mallupur Adhunik Gram Seva Public Wi-Fi</h1>
          <p class="mb-0">Secure access portal for daily guest connectivity. Device mapping and captive portal authentication are enforced.</p>
          <div class="chips">
            <span>2 hours daily quota</span>
            <span>Device mapped session</span>
            <span>Quick captive login</span>
          </div>
        </div>
        <div class="col-lg-4 text-lg-end">
          <img src="/media/public-wifi-mallupur-hero.gif" alt="Public Wi-Fi" class="img-fluid rounded-4 border border-light-subtle" style="max-height:180px; object-fit:cover;" />
        </div>
      </div>
    </section>

    <div class="row g-3 g-lg-4">
      <div class="col-lg-7">
        <section class="portal-card p-3 p-md-4">
          <?php if ($statusMessage !== ''): ?>
            <div class="alert alert-<?php echo e($statusType); ?> mb-3" role="alert"><?php echo e($statusMessage); ?></div>
          <?php endif; ?>
          <?php if ($statusType === 'success' && $continueUrl !== ''): ?>
            <div class="alert alert-info mb-3" role="alert">
              Login complete. Redirecting to continue page in <strong>3 seconds</strong>.
              <a class="btn btn-sm btn-primary ms-2" href="<?php echo e($continueUrl); ?>">Continue Now</a>
            </div>
          <?php endif; ?>

          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h2 class="h4 mb-1" style="font-family:'Sora',system-ui,sans-serif;">Wi-Fi Access Forms</h2>
              <p class="text-secondary mb-0 small">Origin Omada auth flow is preserved for both forms.</p>
            </div>
          </div>

          <ul class="nav nav-pills mb-3" id="wifiTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-panel" type="button" role="tab" aria-controls="login-panel" aria-selected="true">Login</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register-panel" type="button" role="tab" aria-controls="register-panel" aria-selected="false">Register & Connect</button>
            </li>
          </ul>

          <div class="tab-content" id="wifiTabsContent">
            <div class="tab-pane fade show active" id="login-panel" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
              <form method="post" action="wifi.php" class="row g-3" id="loginForm">
                <input type="hidden" name="formMode" value="login" />
                <input type="hidden" name="target" value="<?php echo e($target); ?>" />
                <input type="hidden" name="clientMac" value="<?php echo e($clientMac); ?>" />
                <input type="hidden" name="apMac" value="<?php echo e($apMac); ?>" />
                <input type="hidden" name="ssidName" value="<?php echo e($ssidName); ?>" />
                <input type="hidden" name="radioId" value="<?php echo e($radioId); ?>" />
                <input type="hidden" name="continueUrl" value="<?php echo e($continueUrl); ?>" />

                <div class="col-12">
                  <label class="form-label fw-semibold" for="username">Username</label>
                  <input class="form-control" id="username" name="username" type="text" required maxlength="100" placeholder="Enter username" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" for="password">Password</label>
                  <div class="password-wrap">
                    <input class="form-control" id="password" name="password" type="password" required maxlength="100" placeholder="Enter password" />
                    <button class="password-toggle" type="button" data-toggle-password="#password">Show</button>
                  </div>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-primary" type="submit">Login & Connect Wi-Fi</button>
                </div>
              </form>
            </div>

            <div class="tab-pane fade" id="register-panel" role="tabpanel" aria-labelledby="register-tab" tabindex="0">
              <form method="post" action="wifi.php" class="row g-3" id="registerForm">
                <input type="hidden" name="formMode" value="register" />
                <input type="hidden" name="target" value="<?php echo e($target); ?>" />
                <input type="hidden" name="clientMac" value="<?php echo e($clientMac); ?>" />
                <input type="hidden" name="apMac" value="<?php echo e($apMac); ?>" />
                <input type="hidden" name="ssidName" value="<?php echo e($ssidName); ?>" />
                <input type="hidden" name="radioId" value="<?php echo e($radioId); ?>" />
                <input type="hidden" name="continueUrl" value="<?php echo e($continueUrl); ?>" />

                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="fullName">Full Name</label>
                  <input class="form-control" id="fullName" name="fullName" type="text" maxlength="150" placeholder="Enter full name" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="mobileNumber">Mobile Number</label>
                  <input class="form-control" id="mobileNumber" name="mobileNumber" type="tel" maxlength="15" inputmode="numeric" placeholder="9876543210" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="aadhaarNumber">Aadhaar Number</label>
                  <input class="form-control" id="aadhaarNumber" name="aadhaarNumber" type="tel" maxlength="14" inputmode="numeric" placeholder="1234 5678 9012" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="accessPin">Portal PIN / Password</label>
                  <div class="password-wrap">
                    <input class="form-control" id="accessPin" name="accessPin" type="password" maxlength="100" placeholder="Set or enter PIN" required />
                    <button class="password-toggle" type="button" data-toggle-password="#accessPin">Show</button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="planCode">Daily Plan</label>
                  <select class="form-select" id="planCode" name="planCode" required>
                    <?php foreach ($activePlans as $plan): ?>
                      <?php
                        $pCode = (string)($plan['plan_code'] ?? '');
                        $pName = (string)($plan['display_name'] ?? $pCode);
                        $pSec = (int)($plan['seconds_per_day'] ?? 0);
                        $pHours = $pSec > 0 ? (int)floor($pSec / 3600) : 0;
                      ?>
                      <option value="<?php echo e($pCode); ?>" <?php echo $pCode === Config::defaultPlanCode() ? 'selected' : ''; ?>>
                        <?php echo e($pName . ($pHours > 0 ? " ({$pHours}h/day)" : '')); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" for="address">Mallupur Address</label>
                  <textarea class="form-control" id="address" name="address" rows="3" maxlength="500" placeholder="House / Street / Mallupur / District" required></textarea>
                </div>

                <div class="col-12 small text-secondary">
                  For captive auth compatibility, this form maps <strong>Mobile Number as Username</strong> and <strong>Portal PIN as Password</strong>.
                </div>

                <input type="hidden" name="username" id="mappedUsername" value="" />
                <input type="hidden" name="password" id="mappedPassword" value="" />

                <div class="col-12 d-grid d-md-flex gap-2">
                  <button class="btn btn-primary flex-fill" type="submit">Register & Connect Wi-Fi</button>
                  <button class="btn btn-outline-secondary" type="button" id="copyToLogin">Use in Login Tab</button>
                </div>
              </form>
            </div>
          </div>
        </section>
      </div>

      <div class="col-lg-5">
        <aside class="portal-card p-3 p-md-4 h-100">
          <img src="/media/JayKisaanu-Village-Banner-Final.png?v=20260513-2" alt="Network advisory" class="img-fluid rounded-4 border mb-3" />
          <h3 class="h5 mb-3" style="font-family:'Sora',system-ui,sans-serif;">Network Guidelines</h3>
          <div class="facts">
            <div class="item"><strong>Security</strong><span class="small text-secondary">Session is tied to your device MAC address.</span></div>
            <div class="item"><strong>Responsible Use</strong><span class="small text-secondary">Use for study, official services, and productivity.</span></div>
            <div class="item"><strong>Restricted Content</strong><span class="small text-secondary">Unsafe or illegal content may be blocked automatically.</span></div>
            <div class="item"><strong>Credential Safety</strong><span class="small text-secondary">Do not share your portal login/PIN with others.</span></div>
          </div>

          <div class="meta-badge mt-3">
            <div><strong>Target:</strong> <?php echo e($target !== '' ? $target : 'Not provided'); ?></div>
            <div><strong>Client MAC:</strong> <?php echo e($clientMac !== '' ? $clientMac : 'Not provided'); ?></div>
            <div><strong>SSID:</strong> <?php echo e($ssidName !== '' ? $ssidName : 'Not provided'); ?></div>
          </div>
        </aside>
      </div>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function ($) {
      function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
      }

      $(document).on('click', '[data-toggle-password]', function () {
        var selector = $(this).attr('data-toggle-password');
        var $input = $(selector);
        if (!$input.length) return;
        var nextType = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', nextType);
        $(this).text(nextType === 'password' ? 'Show' : 'Hide');
      });

      $('#mobileNumber, #aadhaarNumber').on('input', function () {
        $(this).val(digitsOnly($(this).val()));
      });

      $('#registerForm').on('submit', function () {
        var mobile = digitsOnly($('#mobileNumber').val());
        var pin = String($('#accessPin').val() || '').trim();
        $('#mappedUsername').val(mobile);
        $('#mappedPassword').val(pin);
      });

      $('#copyToLogin').on('click', function () {
        $('#username').val(digitsOnly($('#mobileNumber').val()));
        $('#password').val(String($('#accessPin').val() || ''));
        var loginTab = new bootstrap.Tab(document.querySelector('#login-tab'));
        loginTab.show();
      });
      <?php if ($statusType === 'success' && $continueUrl !== ''): ?>
      setTimeout(function () {
        window.location.href = <?php echo json_encode($continueUrl, JSON_UNESCAPED_SLASHES); ?>;
      }, 3000);
      <?php endif; ?>
    })(jQuery);
  </script>
</body>
</html>
