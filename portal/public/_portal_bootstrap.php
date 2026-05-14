<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Contracts.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/MysqlUserRepository.php';
require_once __DIR__ . '/../src/PortalAuthService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function is_logged_in(): bool { return isset($_SESSION['wifi_user']) && $_SESSION['wifi_user'] !== ''; }
function portal_redirect(string $path): never { header('Location: ' . $path, true, 302); exit; }

$repo = new MysqlUserRepository(Database::connect());
$logger = new JsonLogger();
$authService = new PortalAuthService($repo, $logger);

$target = (string)($_GET['target'] ?? ($_POST['target'] ?? ''));
$targetPort = (string)($_GET['targetPort'] ?? ($_POST['targetPort'] ?? ''));
$clientMac = (string)($_GET['clientMac'] ?? ($_POST['clientMac'] ?? ''));
$clientIp = (string)($_GET['clientIp'] ?? ($_POST['clientIp'] ?? ''));
$radiusServerIp = (string)($_GET['radiusServerIp'] ?? ($_POST['radiusServerIp'] ?? ''));
$ap = (string)($_GET['ap'] ?? ($_POST['ap'] ?? ''));
$ssid = (string)($_GET['ssid'] ?? ($_POST['ssid'] ?? ''));
$radioId = (string)($_GET['radioId'] ?? ($_POST['radioId'] ?? ''));
$redirectUrl = (string)($_GET['redirectUrl'] ?? ($_POST['redirectUrl'] ?? ''));
$errorHint = (string)($_GET['errorHint'] ?? ($_POST['errorHint'] ?? ''));

$statusType = '';
$statusMessage = '';

if (isset($_SESSION['flash_type'], $_SESSION['flash_message'])) {
    $statusType = (string)$_SESSION['flash_type'];
    $statusMessage = (string)$_SESSION['flash_message'];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = (string)($_POST['formMode'] ?? 'login');

    if ($formMode === 'logout') {
        session_destroy();
        session_start();
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = 'Logged out successfully.';
        portal_redirect('/login');
    }

    if ($formMode === 'login') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            $statusType = 'danger';
            $statusMessage = 'Wi-Fi username and password are required.';
        } else {
            $result = $authService->authenticate($username, $password);
            if (!($result['ok'] ?? false)) {
                $statusType = 'danger';
                $statusMessage = (string)($result['message'] ?? 'Login failed.');
            } else {
                $_SESSION['wifi_user'] = $username;
                $_SESSION['flash_type'] = 'success';
                $_SESSION['flash_message'] = 'Login successful.';
                portal_redirect('/dashboard');
            }
        }
    }

    if ($formMode === 'register') {
        $fullName = trim((string)($_POST['fullName'] ?? ''));
        $fatherName = trim((string)($_POST['fatherName'] ?? ''));
        $motherName = trim((string)($_POST['motherName'] ?? ''));
        $village = trim((string)($_POST['village'] ?? ''));
        $mobileNumber = preg_replace('/\D+/', '', (string)($_POST['mobileNumber'] ?? ''));
        $wifiPassword = trim((string)($_POST['wifiPassword'] ?? ''));
        $planCode = trim((string)($_POST['planCode'] ?? Config::defaultPlanCode()));
        $aadhaarRaw = preg_replace('/\D+/', '', (string)($_POST['aadhaarNumber'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $ssidName = trim((string)($_POST['ssidName'] ?? ''));

        if ($fullName === '' || $fatherName === '' || $motherName === '' || $village === '' || strlen($mobileNumber) < 10 || $wifiPassword === '' || $address === '' || strlen($aadhaarRaw) < 12 || $ssidName === '') {
            $statusType = 'danger';
            $statusMessage = 'Please fill all required fields.';
        } else {
            $register = $authService->register($mobileNumber, $wifiPassword, $planCode);
            if (!($register['ok'] ?? false)) {
                $statusType = (($register['already_registered'] ?? false) ? 'warning' : 'danger');
                $statusMessage = (string)($register['message'] ?? 'Registration failed.');
            } else {
                $aadhaarMasked = 'XXXXXXXX' . substr($aadhaarRaw, -4);
                try {
                    $authService->storeProfile([
                        'username' => $mobileNumber,
                        'full_name' => $fullName,
                        'father_name' => $fatherName,
                        'mother_name' => $motherName,
                        'village' => $village,
                        'mobile_number' => $mobileNumber,
                        'aadhaar_number_masked' => $aadhaarMasked,
                        'address_text' => $address,
                        'client_mac' => $clientMac,
                        'ap_mac' => $ap,
                        'ssid_name' => $ssidName,
                        'plan_code' => $planCode,
                    ]);
                } catch (Throwable $ex) {
                    $logger->warning('portal.profile_store_failed', ['username' => $mobileNumber, 'message' => $ex->getMessage()]);
                }

                $_SESSION['wifi_user'] = $mobileNumber;
                $_SESSION['flash_type'] = 'success';
                $_SESSION['flash_message'] = 'Registration successful.';
                portal_redirect('/dashboard');
            }
        }
    }
}

$activePlans = $repo->getActivePlans();
$user = is_logged_in() ? (string)$_SESSION['wifi_user'] : '';
$dashboard = $user !== '' ? $authService->getDashboardData($user) : [];
$profile = (array)($dashboard['profile'] ?? []);
$minutesUsed = isset($dashboard['used_seconds']) ? (int)floor(((int)$dashboard['used_seconds']) / 60) : 0;
$minutesRemaining = isset($dashboard['remaining_seconds']) ? (int)floor(((int)$dashboard['remaining_seconds']) / 60) : 0;
$minutesTotal = isset($dashboard['seconds_per_day']) ? (int)floor(((int)$dashboard['seconds_per_day']) / 60) : 0;
