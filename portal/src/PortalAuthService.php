<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts.php';

final class PortalAuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $repo,
        private readonly LoggerInterface $logger
    ) {
    }

    public function authenticate(string $username, string $password): array
    {
        $storedPassword = $this->repo->getCleartextPassword($username);
        if ($storedPassword === null || !hash_equals($storedPassword, $password)) {
            $this->logger->warning('auth.invalid_credentials', ['username' => $username]);
            return ['ok' => false, 'message' => 'Invalid username or password.'];
        }

        $plan = $this->repo->getUserPlan($username);
        if (!$plan) {
            $this->logger->warning('auth.plan_missing', ['username' => $username]);
            return ['ok' => false, 'message' => 'No active Wi-Fi plan assigned to this user.'];
        }

        $used = $this->repo->getTodayUsedSeconds($username);
        $limit = (int)$plan['seconds_per_day'];
        $remaining = max(0, $limit - $used);

        if ($remaining <= 0) {
            $this->logger->info('auth.quota_exhausted', [
                'username' => $username,
                'plan_code' => $plan['plan_code'],
                'limit' => $limit,
                'used' => $used,
            ]);
            return [
                'ok' => false,
                'message' => sprintf('Daily quota exhausted for %s. Please try again tomorrow.', $plan['plan_code']),
            ];
        }

        $this->logger->info('auth.local_success', [
            'username' => $username,
            'plan_code' => $plan['plan_code'],
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
        ]);

        return [
            'ok' => true,
            'plan_code' => (string)$plan['plan_code'],
            'seconds_per_day' => $limit,
            'used_seconds' => $used,
            'remaining_seconds' => $remaining,
            'remaining_minutes' => (int)floor($remaining / 60),
        ];
    }

    public function register(string $username, string $password, string $planCode): array
    {
        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'Mobile and PIN are required for registration.'];
        }

        $availablePlans = $this->repo->getActivePlans();
        $allowedPlanCodes = array_column($availablePlans, 'plan_code');
        if (!in_array($planCode, $allowedPlanCodes, true)) {
            return ['ok' => false, 'message' => 'Selected plan is not available.'];
        }

        $this->repo->upsertUserWithPlan($username, $password, $planCode);
        $this->logger->info('auth.registered', [
            'username' => $username,
            'plan_code' => $planCode,
        ]);
        return ['ok' => true];
    }

    public function storeProfile(array $profile): void
    {
        $this->repo->saveRegistrationProfile($profile);
    }
}
