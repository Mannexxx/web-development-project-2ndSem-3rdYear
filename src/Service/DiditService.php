<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class DiditService
{
    private const API_URL = 'https://verification.didit.me/v3/session/';

    public function configured(): bool
    {
        $callback = parse_url(DIDIT_CALLBACK_URL);
        return DIDIT_API_KEY !== '' && DIDIT_WORKFLOW_ID !== '' && DIDIT_WEBHOOK_SECRET !== ''
            && is_array($callback) && strtolower((string) ($callback['scheme'] ?? '')) === 'https'
            && !empty($callback['host']) && extension_loaded('curl');
    }

    /** @return array{session_id: string, url: string, status: string} */
    public function createSession(array $application, array $user): array
    {
        if (!$this->configured()) {
            throw new RuntimeException('Didit is not configured. Please contact the marketplace admin.');
        }
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The server is missing PHP cURL, which is required for identity verification.');
        }
        $callback = parse_url(DIDIT_CALLBACK_URL);
        if (!is_array($callback) || strtolower((string) ($callback['scheme'] ?? '')) !== 'https'
            || empty($callback['host'])) {
            throw new RuntimeException('The Didit callback must be configured as a public HTTPS URL.');
        }

        $payload = [
            'workflow_id' => DIDIT_WORKFLOW_ID,
            'vendor_data' => 'seller-application-' . (int) $application['id'],
            'callback' => DIDIT_CALLBACK_URL,
            'callback_method' => 'both',
            'language' => 'en',
            'metadata' => ['seller_application_id' => (int) $application['id']],
            'expected_details' => [
                'first_name' => (string) $application['first_name'],
                'last_name' => (string) $application['last_name'],
                'date_of_birth' => (string) $application['date_of_birth'],
                'nationality' => 'PHI',
                'id_country' => 'PHI',
            ],
            'biometric_consent' => [
                'given_at' => date(DATE_ATOM, strtotime((string) $application['biometric_consent_at'])),
                'text_version' => (string) $application['agreement_version'],
            ],
        ];
        if (!empty($user['email'])) {
            $payload['contact_details'] = [
                'email' => (string) $user['email'],
                'send_notification_emails' => false,
            ];
        }

        $handle = curl_init(self::API_URL);
        if ($handle === false) {
            throw new RuntimeException('Could not initialize the Didit request.');
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'x-api-key: ' . DIDIT_API_KEY,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($body === false) {
            throw new RuntimeException('Could not reach Didit. Please try again later.' . ($curlError !== '' ? ' (' . $curlError . ')' : ''));
        }
        $result = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($result)) {
            $detail = is_array($result) ? (string) ($result['detail'] ?? '') : '';
            throw new RuntimeException('Didit could not start verification (HTTP ' . $status . '). ' . mb_substr($detail, 0, 300));
        }

        $sessionId = (string) ($result['session_id'] ?? '');
        $verificationUrl = (string) ($result['url'] ?? '');
        $url = parse_url($verificationUrl);
        $host = strtolower((string) ($url['host'] ?? ''));
        if ($sessionId === '' || !is_array($url) || strtolower((string) ($url['scheme'] ?? '')) !== 'https'
            || ($host !== 'didit.me' && !str_ends_with($host, '.didit.me'))) {
            throw new RuntimeException('Didit returned an invalid verification session.');
        }

        return [
            'session_id' => $sessionId,
            'url' => $verificationUrl,
            'status' => (string) ($result['status'] ?? 'Not Started'),
        ];
    }

    public static function canonicalJson(mixed $payload): string
    {
        $sort = static function (mixed $value) use (&$sort): mixed {
            if (is_object($value)) {
                $properties = get_object_vars($value);
                ksort($properties, SORT_STRING);
                foreach ($properties as &$item) {
                    $item = $sort($item);
                }
                unset($item);
                return (object) $properties;
            }
            if (is_array($value)) {
                foreach ($value as &$item) {
                    $item = $sort($item);
                }
                unset($item);
            }
            return $value;
        };

        return json_encode($sort($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    public static function verifyWebhook(string $rawBody, mixed $payload, array $headers): bool
    {
        if (DIDIT_WEBHOOK_SECRET === '') {
            return false;
        }
        $timestamp = (string) ($headers['timestamp'] ?? '');
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signatures = array_filter([
            (string) ($headers['signature_v2'] ?? ''),
            (string) ($headers['signature'] ?? ''),
        ]);
        foreach ($signatures as $signature) {
            $candidate = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
            $rawMatch = hash_equals(hash_hmac('sha256', $rawBody, DIDIT_WEBHOOK_SECRET), $candidate);
            $v2Match = hash_equals(hash_hmac('sha256', self::canonicalJson($payload), DIDIT_WEBHOOK_SECRET), $candidate);
            if ($rawMatch || $v2Match) {
                return true;
            }
        }
        return false;
    }
}
