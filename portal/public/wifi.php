<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Contracts.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/MysqlUserRepository.php';
require_once __DIR__ . '/../src/PortalAuthService.php';

session_start();

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function is_logged_in(): bool { return isset($_SESSION['wifi_user']) && $_SESSION['wifi_user'] !== ''; }

$repo = new MysqlUserRepository(Database::connect());
$logger = new JsonLogger();
$authService = new PortalAuthService($repo, $logger);

$statusType = '';
$statusMessage = '';
$view = (string)($_GET['view'] ?? 'login');
if ($view === '') {
    $view = 'login';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = (string)($_POST['formMode'] ?? 'login');

    if ($formMode === 'logout') {
        session_destroy();
        session_start();
        $statusType = 'success';
        $statusMessage = 'Logged out successfully.';
        $view = 'login';
    } elseif ($formMode === 'login') {
        $username = preg_replace('/\D+/', '', (string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            $statusType = 'danger';
            $statusMessage = 'Wi-Fi username and password are required.';
            $view = 'login';
        } else {
            $result = $authService->authenticate($username, $password);
            if (!($result['ok'] ?? false)) {
                $statusType = 'danger';
                $statusMessage = (string)($result['message'] ?? 'Login failed.');
                $view = 'login';
            } else {
                $_SESSION['wifi_user'] = $username;
                $statusType = 'success';
                $statusMessage = 'Login successful.';
                $view = 'dashboard';
            }
        }
    } elseif ($formMode === 'register') {
        $fullName = trim((string)($_POST['fullName'] ?? ''));
        $mobileNumber = preg_replace('/\D+/', '', (string)($_POST['mobileNumber'] ?? ''));
        $wifiPassword = trim((string)($_POST['wifiPassword'] ?? ''));
        $planCode = trim((string)($_POST['planCode'] ?? Config::defaultPlanCode()));
        $aadhaarRaw = preg_replace('/\D+/', '', (string)($_POST['aadhaarNumber'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $ssidName = trim((string)($_POST['ssidName'] ?? ''));

        if ($fullName === '' || strlen($mobileNumber) < 10 || $wifiPassword === '' || $address === '' || strlen($aadhaarRaw) < 12 || $ssidName === '') {
            $statusType = 'danger';
            $statusMessage = 'Please fill full name, mobile, password, Aadhaar, address, and SSID.';
            $view = 'register';
        } else {
            $register = $authService->register($mobileNumber, $wifiPassword, $planCode);
            if (!($register['ok'] ?? false)) {
                $statusType = (($register['already_registered'] ?? false) ? 'warning' : 'danger');
                $statusMessage = (string)($register['message'] ?? 'Registration failed.');
                $view = (($register['already_registered'] ?? false) ? 'login' : 'register');
            } else {
                $aadhaarMasked = 'XXXXXXXX' . substr($aadhaarRaw, -4);
                try {
                    $authService->storeProfile([
                        'username' => $mobileNumber,
                        'full_name' => $fullName,
                        'mobile_number' => $mobileNumber,
                        'aadhaar_number_masked' => $aadhaarMasked,
                        'address_text' => $address,
                        'client_mac' => '',
                        'ap_mac' => '',
                        'ssid_name' => $ssidName,
                        'plan_code' => $planCode,
                    ]);
                } catch (Throwable $ex) {
                    $logger->warning('portal.profile_store_failed', ['username' => $mobileNumber, 'message' => $ex->getMessage()]);
                }
                $statusType = 'success';
                $statusMessage = 'Registration successful. Please login now.';
                $view = 'login';
            }
        }
    }
}

if (!is_logged_in() && in_array($view, ['dashboard', 'profile'], true)) {
    $view = 'login';
    if ($statusMessage === '') {
        $statusType = 'danger';
        $statusMessage = 'Please login first.';
    }
}

$activePlans = $repo->getActivePlans();
$user = is_logged_in() ? (string)$_SESSION['wifi_user'] : '';
$dashboard = $user !== '' ? $authService->getDashboardData($user) : [];
$minutesUsed = isset($dashboard['used_seconds']) ? (int)floor(((int)$dashboard['used_seconds']) / 60) : 0;
$minutesRemaining = isset($dashboard['remaining_seconds']) ? (int)floor(((int)$dashboard['remaining_seconds']) / 60) : 0;
$minutesTotal = isset($dashboard['seconds_per_day']) ? (int)floor(((int)$dashboard['seconds_per_day']) / 60) : 0;
$profile = (array)($dashboard['profile'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kisaanu Wi-Fi Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background:#f5f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.06); }
  </style>
</head>
<body>
  <div class="container py-4" style="max-width:980px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="m-0">Kisaanu Wi-Fi Portal</h4>
      <?php if (is_logged_in()): ?>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="/wifi.php?view=dashboard">Dashboard</a>
        <a class="btn btn-outline-secondary btn-sm" href="/wifi.php?view=profile">Profile</a>
        <form method="post" action="/wifi.php?view=login" class="d-inline">
          <input type="hidden" name="formMode" value="logout" />
          <button class="btn btn-danger btn-sm" type="submit">Logout</button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($statusMessage !== ''): ?>
      <div class="alert alert-<?php echo e($statusType !== '' ? $statusType : 'info'); ?>"><?php echo e($statusMessage); ?></div>
    <?php endif; ?>

    <?php if ($view === 'login'): ?>
      <div class="card card-soft p-4">
        <h5>Login</h5>
        <p class="text-muted mb-3">Use your Wi-Fi username and password.</p>
        <form method="post" action="/wifi.php?view=login">
          <input type="hidden" name="formMode" value="login" />
          <div class="mb-3"><label class="form-label">Wi-Fi Username (Mobile)</label><input class="form-control" name="username" maxlength="15" required></div>
          <div class="mb-3"><label class="form-label">Wi-Fi Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary" type="submit">Login</button>
          <a class="btn btn-link" href="/wifi.php?view=register">New user? Register</a>
        </form>
      </div>
    <?php elseif ($view === 'register'): ?>
      <div class="card card-soft p-4">
        <h5>Register</h5>
        <p class="text-muted mb-3">Create your Wi-Fi account. If already registered, use Login.</p>
        <form method="post" action="/wifi.php?view=register">
          <input type="hidden" name="formMode" value="register" />
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" name="fullName" required></div>
            <div class="col-md-6"><label class="form-label">Mobile Number</label><input class="form-control" name="mobileNumber" maxlength="15" required></div>
            <div class="col-md-6"><label class="form-label">Wi-Fi Password</label><input class="form-control" name="wifiPassword" type="password" required></div>
            <div class="col-md-6"><label class="form-label">SSID</label><input class="form-control" name="ssidName" value="MALLUPUR-KISAANU-WIFI" required></div>
            <div class="col-md-6"><label class="form-label">Aadhaar Number</label><input class="form-control" name="aadhaarNumber" maxlength="14" required></div>
            <div class="col-md-6"><label class="form-label">Package</label>
              <select class="form-select" name="planCode" required>
                <?php foreach ($activePlans as $plan): ?>
                  <option value="<?php echo e((string)$plan['plan_code']); ?>"><?php echo e((string)$plan['display_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2" required></textarea></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-success" type="submit">Create Account</button>
            <a class="btn btn-link" href="/wifi.php?view=login">Already registered? Login</a>
          </div>
        </form>
      </div>
    <?php elseif ($view === 'dashboard'): ?>
      <div class="card card-soft p-4 mb-3">
        <h5>Dashboard</h5>
        <p class="text-muted mb-1">Daily usage and package overview.</p>
      </div>
      <div class="row g-3">
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">Wi-Fi Username</small><div class="fw-bold"><?php echo e((string)($dashboard['username'] ?? '')); ?></div></div></div>
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">Wi-Fi Password</small><div class="fw-bold"><?php echo e((string)($dashboard['password'] ?? '')); ?></div></div></div>
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">SSID</small><div class="fw-bold"><?php echo e((string)($dashboard['ssid_name'] ?? '')); ?></div></div></div>
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">Package</small><div class="fw-bold"><?php echo e((string)($dashboard['plan_code'] ?? '')); ?></div></div></div>
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">Used Today</small><div class="fw-bold"><?php echo e((string)$minutesUsed); ?> min</div></div></div>
        <div class="col-md-4"><div class="card card-soft p-3"><small class="text-muted">Remaining Today</small><div class="fw-bold"><?php echo e((string)$minutesRemaining); ?> min / <?php echo e((string)$minutesTotal); ?> min</div></div></div>
      </div>
    <?php elseif ($view === 'profile'): ?>
      <div class="card card-soft p-4">
        <h5>Profile</h5>
        <div class="row g-3 mt-1">
          <div class="col-md-6"><small class="text-muted d-block">Full Name</small><strong><?php echo e((string)($profile['full_name'] ?? '')); ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Mobile</small><strong><?php echo e((string)($profile['mobile_number'] ?? $user)); ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Aadhaar (masked)</small><strong><?php echo e((string)($profile['aadhaar_number_masked'] ?? '')); ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Plan</small><strong><?php echo e((string)($profile['plan_code'] ?? ($dashboard['plan_code'] ?? ''))); ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">SSID</small><strong><?php echo e((string)($profile['ssid_name'] ?? ($dashboard['ssid_name'] ?? ''))); ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Registered At</small><strong><?php echo e((string)($profile['created_at'] ?? '')); ?></strong></div>
          <div class="col-12"><small class="text-muted d-block">Address</small><strong><?php echo e((string)($profile['address_text'] ?? '')); ?></strong></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
