<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Contracts.php';
require_once __DIR__ . '/../src/PortalAuthService.php';

final class NullLogger implements LoggerInterface
{
    public function info(string $event, array $context = []): void {}
    public function warning(string $event, array $context = []): void {}
    public function error(string $event, array $context = []): void {}
}

final class InMemoryRepo implements UserRepositoryInterface
{
    public array $users = [];
    public array $plans = [];
    public array $used = [];
    public array $activePlans = [
        ['plan_code' => 'FREE_2H_DAILY', 'display_name' => 'Free 2 Hours Daily', 'seconds_per_day' => 7200],
        ['plan_code' => 'FREE_4H_DAILY', 'display_name' => 'Free 4 Hours Daily', 'seconds_per_day' => 14400],
        ['plan_code' => 'FREE_6H_DAILY', 'display_name' => 'Free 6 Hours Daily', 'seconds_per_day' => 21600],
        ['plan_code' => 'FREE_8H_DAILY', 'display_name' => 'Free 8 Hours Daily', 'seconds_per_day' => 28800],
    ];
    public array $profiles = [];

    public function getCleartextPassword(string $username): ?string
    {
        return $this->users[$username] ?? null;
    }

    public function getUserPlan(string $username): ?array
    {
        return $this->plans[$username] ?? null;
    }

    public function getTodayUsedSeconds(string $username): int
    {
        return $this->used[$username] ?? 0;
    }

    public function getActivePlans(): array
    {
        return $this->activePlans;
    }

    public function upsertUserWithPlan(string $username, string $password, string $planCode): void
    {
        $this->users[$username] = $password;
        $this->plans[$username] = ['plan_code' => $planCode, 'seconds_per_day' => 7200];
    }

    public function saveRegistrationProfile(array $profile): void
    {
        $this->profiles[] = $profile;
    }
}

function assertTrue(bool $cond, string $message): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

$repo = new InMemoryRepo();
$repo->users['u1'] = 'p1';
$repo->plans['u1'] = ['plan_code' => 'FREE_2H_DAILY', 'seconds_per_day' => 7200];
$repo->used['u1'] = 1200;

$service = new PortalAuthService($repo, new NullLogger());

$r1 = $service->authenticate('u1', 'p1');
assertTrue(($r1['ok'] ?? false) === true, 'auth succeeds for valid user/password');
assertTrue(($r1['remaining_seconds'] ?? 0) === 6000, 'remaining seconds computed correctly');

$r2 = $service->authenticate('u1', 'bad');
assertTrue(($r2['ok'] ?? true) === false, 'auth fails on wrong password');

$repo->used['u1'] = 7200;
$r3 = $service->authenticate('u1', 'p1');
assertTrue(($r3['ok'] ?? true) === false, 'auth fails when quota exhausted');

$r4 = $service->register('u2', 'pin2', 'FREE_2H_DAILY');
assertTrue(($r4['ok'] ?? false) === true, 'register/upsert succeeds');
assertTrue(($repo->users['u2'] ?? '') === 'pin2', 'register stores password');

$r5 = $service->register('u3', 'pin3', 'INVALID_PLAN');
assertTrue(($r5['ok'] ?? true) === false, 'register fails on unknown plan');

echo "All tests passed.\n";
