<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts.php';
require_once __DIR__ . '/Config.php';

final class JsonLogger implements LoggerInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? Config::logPath();
    }

    public function info(string $event, array $context = []): void
    {
        $this->write('INFO', $event, $context);
    }

    public function warning(string $event, array $context = []): void
    {
        $this->write('WARN', $event, $context);
    }

    public function error(string $event, array $context = []): void
    {
        $this->write('ERROR', $event, $context);
    }

    private function write(string $level, string $event, array $context): void
    {
        $line = json_encode([
            'ts' => date('c'),
            'level' => $level,
            'event' => $event,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        @file_put_contents($this->filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
