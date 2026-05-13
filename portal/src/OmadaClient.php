<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts.php';

final class OmadaClient
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function sendAuth(string $target, string $username, string $password, string $clientMac, int $sessionTimeout): array
    {
        if ($target === '' || $clientMac === '') {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Captive parameters missing, so gateway enable step was skipped.',
            ];
        }

        $authUrl = 'http://' . $target . '/portal/auth';
        $fields = [
            'username' => $username,
            'password' => $password,
            'clientMac' => $clientMac,
            'sessionTimeout' => $sessionTimeout,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            $this->logger->error('omada.forward.failed', [
                'target' => $target,
                'http_code' => $code,
                'error' => $error,
            ]);
            return [
                'ok' => false,
                'message' => 'Local auth passed but Omada captive auth failed: ' . ($error !== '' ? $error : ('HTTP ' . $code)),
            ];
        }

        $this->logger->info('omada.forward.success', [
            'target' => $target,
            'http_code' => $code,
        ]);

        return [
            'ok' => true,
            'message' => 'Internet access request sent to captive portal.',
        ];
    }
}
