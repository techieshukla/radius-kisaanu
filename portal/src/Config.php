<?php

declare(strict_types=1);

final class Config
{
    public static function envFlag(string $name, bool $default = false): bool
    {
        $raw = getenv($name);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $normalized = strtolower(trim((string)$raw));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public static function dbHost(): string
    {
        return getenv('DB_HOST') ?: 'mysql';
    }

    public static function dbPort(): string
    {
        return getenv('DB_PORT') ?: '3306';
    }

    public static function dbName(): string
    {
        return getenv('DB_NAME') ?: 'radius';
    }

    public static function dbUser(): string
    {
        return getenv('DB_USER') ?: 'radius';
    }

    public static function dbPass(): string
    {
        return getenv('DB_PASS') ?: 'change_radius_password';
    }

    public static function timezone(): string
    {
        return getenv('TZ') ?: 'Asia/Kolkata';
    }

    public static function defaultPlanCode(): string
    {
        return 'FREE_2H_DAILY';
    }

    public static function fallbackPlans(): array
    {
        return [
            ['plan_code' => 'FREE_2H_DAILY', 'display_name' => 'Free 2 Hours Daily', 'seconds_per_day' => 7200],
            ['plan_code' => 'FREE_4H_DAILY', 'display_name' => 'Free 4 Hours Daily', 'seconds_per_day' => 14400],
            ['plan_code' => 'FREE_6H_DAILY', 'display_name' => 'Free 6 Hours Daily', 'seconds_per_day' => 21600],
            ['plan_code' => 'FREE_8H_DAILY', 'display_name' => 'Free 8 Hours Daily', 'seconds_per_day' => 28800],
        ];
    }

    public static function logPath(): string
    {
        $path = '/var/www/html/logs/portal.log';
        return is_writable(dirname($path)) ? $path : '/tmp/portal.log';
    }

    public static function omadaTargetCallbackEnabled(): bool
    {
        // Default OFF for cloud-hosted RADIUS-only deployments.
        return self::envFlag('OMADA_TARGET_CALLBACK_ENABLED', false);
    }
}
