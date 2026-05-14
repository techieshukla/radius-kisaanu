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
function redirect_to(string $path): never {
    header('Location: ' . $path, true, 302);
    exit;
}
function is_logged_in(): bool {
    return isset($_SESSION['wifi_user']) && $_SESSION['wifi_user'] !== '';
}
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please login first.'];
        redirect_to('/login');
    }
}

$repo = new MysqlUserRepository(Database::connect());
$logger = new JsonLogger();
$authService = new PortalAuthService($repo, $logger);
$routePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routePath = rtrim($routePath, '/');
$route = $routePath === '' ? '/' : $routePath;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/logout') {
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Logged out successfully.'];
    redirect_to('/login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/login') {
    $username = preg_replace('/\D+/', '', (string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    if ($username === '' || $password === '') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Wi-Fi username and password are required.'];
        redirect_to('/login');
    }

    $result = $authService->authenticate($username, $password);
    if (!($result['ok'] ?? false)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => (string)($result['message'] ?? 'Login failed.')];
        redirect_to('/login');
    }

    $_SESSION['wifi_user'] = $username;
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Login successful.'];
    redirect_to('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/register') {
    $fullName = trim((string)($_POST['fullName'] ?? ''));
    $mobileNumber = preg_replace('/\D+/', '', (string)($_POST['mobileNumber'] ?? ''));
    $wifiPassword = trim((string)($_POST['wifiPassword'] ?? ''));
    $planCode = trim((string)($_POST['planCode'] ?? Config::defaultPlanCode()));
    $aadhaarRaw = preg_replace('/\D+/', '', (string)($_POST['aadhaarNumber'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $ssidName = trim((string)($_POST['ssidName'] ?? ''));

    if ($fullName === '' || strlen($mobileNumber) < 10 || $wifiPassword === '' || $address === '' || strlen($aadhaarRaw) < 12 || $ssidName === '') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please fill full name, mobile, password, Aadhaar, address, and SSID.'];
        redirect_to('/register');
    }

    $register = $authService->register($mobileNumber, $wifiPassword, $planCode);
    if (!($register['ok'] ?? false)) {
        $msg = (string)($register['message'] ?? 'Registration failed.');
        $_SESSION['flash'] = ['type' => (($register['already_registered'] ?? false) ? 'warning' : 'danger'), 'message' => $msg];
        redirect_to(($register['already_registered'] ?? false) ? '/login' : '/register');
    }

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

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful. Please login now.'];
    redirect_to('/login');
}

if ($route === '/wifi.php') {
    redirect_to('/login');
}
if ($route === '/') {
    if (!is_logged_in()) {
        redirect_to('/login');
    }
    $route = '/dashboard';
}

$activePlans = $repo->getActivePlans();
$user = is_logged_in() ? (string)$_SESSION['wifi_user'] : '';
$dashboard = $user !== '' ? $authService->getDashboardData($user) : [];

if (in_array($route, ['/dashboard', '/profile'], true)) {
    require_login();
}
if (!in_array($route, ['/login', '/register', '/dashboard', '/profile'], true)) {
    http_response_code(404);
    $route = '/login';
    $flash = ['type' => 'warning', 'message' => 'Page not found. Redirected to login.'];
}

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
        <a class="btn btn-outline-primary btn-sm" href="/dashboard">Dashboard</a>
        <a class="btn btn-outline-secondary btn-sm" href="/profile">Profile</a>
        <form method="post" action="/logout" class="d-inline"><button class="btn btn-danger btn-sm" type="submit">Logout</button></form>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo e((string)$flash['type']); ?>"><?php echo e((string)$flash['message']); ?></div>
    <?php endif; ?>

    <?php if ($route === '/login'): ?>
      <div class="card card-soft p-4">
        <h5>Login</h5>
        <p class="text-muted mb-3">Use your Wi-Fi username and password.</p>
        <form method="post" action="/login">
          <div class="mb-3"><label class="form-label">Wi-Fi Username (Mobile)</label><input class="form-control" name="username" maxlength="15" required></div>
          <div class="mb-3"><label class="form-label">Wi-Fi Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary" type="submit">Login</button>
          <a class="btn btn-link" href="/register">New user? Register</a>
        </form>
      </div>
    <?php elseif ($route === '/register'): ?>
      <div class="card card-soft p-4">
        <h5>Register</h5>
        <p class="text-muted mb-3">Create your Wi-Fi account. If already registered, use Login.</p>
        <form method="post" action="/register">
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
            <a class="btn btn-link" href="/login">Already registered? Login</a>
          </div>
        </form>
      </div>
    <?php elseif ($route === '/dashboard'): ?>
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
    <?php elseif ($route === '/profile'): ?>
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
