<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts.php';

final class MysqlUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getCleartextPassword(string $username): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT value FROM radcheck WHERE username = :username AND attribute = 'Cleartext-Password' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ? (string)$row['value'] : null;
    }

    public function isUserRegistered(string $username): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM radcheck WHERE username = :username AND attribute = 'Cleartext-Password' LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        return (bool)$stmt->fetchColumn();
    }

    public function getUserPlan(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pp.plan_code, pp.seconds_per_day
             FROM radusergroup rug
             JOIN plan_profiles pp ON pp.plan_code = rug.groupname AND pp.is_active = 1
             WHERE rug.username = :username
             ORDER BY rug.priority ASC
             LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getTodayUsedSeconds(string $username): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(acctsessiontime), 0) AS used_seconds
             FROM radacct
             WHERE username = :username
               AND DATE(COALESCE(acctstarttime, UTC_TIMESTAMP()) + INTERVAL 330 MINUTE) = DATE(UTC_TIMESTAMP() + INTERVAL 330 MINUTE)"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return (int)($row['used_seconds'] ?? 0);
    }

    public function getActivePlans(): array
    {
        $stmt = $this->pdo->query(
            "SELECT plan_code, display_name, seconds_per_day
             FROM plan_profiles
             WHERE is_active = 1
             ORDER BY seconds_per_day ASC"
        );
        return $stmt->fetchAll() ?: [];
    }

    public function upsertUserWithPlan(string $username, string $password, string $planCode): void
    {
        $this->pdo->beginTransaction();
        try {
            $delCheck = $this->pdo->prepare(
                "DELETE FROM radcheck WHERE username = :username AND attribute = 'Cleartext-Password'"
            );
            $delCheck->execute(['username' => $username]);

            $insCheck = $this->pdo->prepare(
                "INSERT INTO radcheck (username, attribute, op, value) VALUES (:username, 'Cleartext-Password', ':=', :password)"
            );
            $insCheck->execute(['username' => $username, 'password' => $password]);

            $delGroup = $this->pdo->prepare("DELETE FROM radusergroup WHERE username = :username");
            $delGroup->execute(['username' => $username]);

            $insGroup = $this->pdo->prepare(
                "INSERT INTO radusergroup (username, groupname, priority) VALUES (:username, :planCode, 1)"
            );
            $insGroup->execute(['username' => $username, 'planCode' => $planCode]);

            $this->ensureDaloUserInfo($username);

            $this->pdo->commit();
        } catch (Throwable $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $ex;
        }
    }

    public function saveRegistrationProfile(array $profile): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO portal_registrations
                (username, full_name, father_name, mother_name, village, mobile_number, aadhaar_number_masked, address_text, client_mac, ap_mac, ssid_name, plan_code)
             VALUES
                (:username, :full_name, :father_name, :mother_name, :village, :mobile_number, :aadhaar_number_masked, :address_text, :client_mac, :ap_mac, :ssid_name, :plan_code)"
        );
        $stmt->execute([
            'username' => (string)($profile['username'] ?? ''),
            'full_name' => (string)($profile['full_name'] ?? ''),
            'father_name' => (string)($profile['father_name'] ?? ''),
            'mother_name' => (string)($profile['mother_name'] ?? ''),
            'village' => (string)($profile['village'] ?? ''),
            'mobile_number' => (string)($profile['mobile_number'] ?? ''),
            'aadhaar_number_masked' => (string)($profile['aadhaar_number_masked'] ?? ''),
            'address_text' => (string)($profile['address_text'] ?? ''),
            'client_mac' => (string)($profile['client_mac'] ?? ''),
            'ap_mac' => (string)($profile['ap_mac'] ?? ''),
            'ssid_name' => (string)($profile['ssid_name'] ?? ''),
            'plan_code' => (string)($profile['plan_code'] ?? ''),
        ]);

        $this->ensureDaloUserInfo(
            (string)($profile['username'] ?? ''),
            (string)($profile['full_name'] ?? ''),
            (string)($profile['mobile_number'] ?? ''),
            (string)($profile['address_text'] ?? ''),
            (string)($profile['village'] ?? '')
        );
    }

    public function getLatestRegistrationProfile(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT username, full_name, father_name, mother_name, village, mobile_number, aadhaar_number_masked, address_text, client_mac, ap_mac, ssid_name, plan_code, created_at
             FROM portal_registrations
             WHERE username = :username
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function ensureDaloUserInfo(
        string $username,
        string $fullName = '',
        string $mobileNumber = '',
        string $address = '',
        string $village = ''
    ): void {
        if ($username === '') {
            return;
        }

        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName, 2) ?: [];
        $firstName = (string)($parts[0] ?? '');
        $lastName = (string)($parts[1] ?? '');
        if ($firstName === '') {
            $firstName = $username;
        }

        $email = str_contains($username, '@') ? $username : '';

        $update = $this->pdo->prepare(
            "UPDATE userinfo
             SET firstname = CASE WHEN :firstname <> '' THEN :firstname ELSE firstname END,
                 lastname = CASE WHEN :lastname <> '' THEN :lastname ELSE lastname END,
                 email = CASE WHEN :email <> '' THEN :email ELSE email END,
                 mobilephone = CASE WHEN :mobilephone <> '' THEN :mobilephone ELSE mobilephone END,
                 address = CASE WHEN :address <> '' THEN :address ELSE address END,
                 city = CASE WHEN :city <> '' THEN :city ELSE city END,
                 updatedate = NOW(),
                 updateby = 'portal'
             WHERE username = :username"
        );
        $update->execute([
            'username' => $username,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'mobilephone' => $mobileNumber,
            'address' => $address,
            'city' => $village,
        ]);

        if ($update->rowCount() > 0) {
            return;
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO userinfo
                (username, firstname, lastname, email, mobilephone, address, city, state, country, notes, creationdate, creationby, updatedate, updateby, enableportallogin)
             VALUES
                (:username, :firstname, :lastname, :email, :mobilephone, :address, :city, 'Uttar Pradesh', 'India', 'Created by Kisaanu Wi-Fi portal', NOW(), 'portal', NOW(), 'portal', 1)"
        );
        $insert->execute([
            'username' => $username,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'mobilephone' => $mobileNumber,
            'address' => $address,
            'city' => $village,
        ]);
    }
}
