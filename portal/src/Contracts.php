<?php

declare(strict_types=1);

interface UserRepositoryInterface
{
    public function getCleartextPassword(string $username): ?string;

    public function isUserRegistered(string $username): bool;

    public function getUserPlan(string $username): ?array;

    public function getTodayUsedSeconds(string $username): int;

    public function getActivePlans(): array;

    public function upsertUserWithPlan(string $username, string $password, string $planCode): void;

    public function saveRegistrationProfile(array $profile): void;

    public function getLatestRegistrationProfile(string $username): ?array;
}

interface LoggerInterface
{
    public function info(string $event, array $context = []): void;

    public function warning(string $event, array $context = []): void;

    public function error(string $event, array $context = []): void;
}
