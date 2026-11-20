<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

final class VerificationService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function send(int $userId, string $channel, string $destination, ?string $carrier = null): array
    {
        if (!in_array($channel, ['email', 'sms'], true)) {
            return ['ok' => false, 'error' => 'Invalid verification channel.'];
        }
        if ($channel === 'sms' && !in_array($carrier, ['globe', 'smart'], true)) {
            return ['ok' => false, 'error' => 'Select Globe/TM or Smart/TNT as your mobile carrier.'];
        }
        $recipient = $channel === 'sms' ? $this->gatewayAddress($destination, (string) $carrier) : $destination;
        if ($recipient === null) {
            return ['ok' => false, 'error' => 'Enter a valid Philippine mobile number for the selected carrier.'];
        }
        if ($this->users->hasRecentVerificationCode($userId, $channel, date('Y-m-d H:i:s', time() - 60))) {
            return ['ok' => false, 'error' => 'Please wait one minute before requesting another verification code.'];
        }
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->users->invalidateVerificationCodes($userId, $channel);
        $this->users->createVerificationCode(
            $userId,
            $channel,
            $destination,
            password_hash($code, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s', time() + 10 * 60)
        );

        try {
            $subject = $channel === 'sms' ? 'Bantayan Marketplace code' : 'Verify your Bantayan Marketplace account';
            $body = $channel === 'sms'
                ? 'Bantayan Marketplace verification code: ' . $code
                : $this->emailBody($code);
            $message = $this->sendEmail($recipient, $subject, $body);
            if (!$message['ok']) {
                return $message;
            }
        } catch (MailException $e) {
            error_log('Verification message delivery failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Verification delivery is temporarily unavailable.'];
        }

        return ['ok' => true, 'error' => null];
    }

    public function verify(int $userId, string $channel, string $code): array
    {
        if (!in_array($channel, ['email', 'sms'], true)) {
            return ['ok' => false, 'error' => 'Invalid verification channel.'];
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'error' => 'Enter the 6-digit verification code.'];
        }
        $row = $this->users->latestVerificationCode($userId, $channel);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Request a new verification code first.'];
        }
        if (strtotime((string) $row['expires_at']) !== false && strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'That code has expired. Request a new one.'];
        }
        if (!password_verify($code, (string) $row['code_hash'])) {
            $this->users->incrementVerificationAttempts((int) $row['id']);
            if ((int) $row['attempts'] + 1 >= 5) {
                $this->users->invalidateVerificationCode((int) $row['id']);
            }
            return ['ok' => false, 'error' => 'That verification code is incorrect.'];
        }

        $this->users->invalidateVerificationCode((int) $row['id']);
        if ($channel === 'email') {
            $this->users->markEmailVerified($userId);
        } else {
            $this->users->markPhoneVerified($userId);
        }
        return ['ok' => true, 'error' => null];
    }

    private function sendEmail(string $to, string $subject, string $body): array
    {
        $host = $this->setting('SMTP_HOST', SMTP_HOST);
        $username = $this->setting('SMTP_USERNAME', SMTP_USERNAME);
        $password = $this->setting('SMTP_PASSWORD', SMTP_PASSWORD);
        $from = $this->setting('SMTP_FROM_EMAIL', SMTP_FROM_EMAIL) ?: $username;
        $port = (int) ($this->setting('SMTP_PORT', (string) SMTP_PORT) ?: SMTP_PORT);
        $encryption = strtolower($this->setting('SMTP_ENCRYPTION', SMTP_ENCRYPTION));
        if ($host === '' || $from === '') {
            return ['ok' => false, 'error' => 'Email delivery is not configured yet.'];
        }

        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            return ['ok' => false, 'error' => 'Email encryption setting must be tls, ssl, or none.'];
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = $username !== '';
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = $port;
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($from, $this->setting('SMTP_FROM_NAME', SMTP_FROM_NAME));
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return ['ok' => true, 'error' => null];
    }

    private function gatewayAddress(string $phone, string $carrier): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (preg_match('/^639\d{9}$/', $digits)) {
            $digits = '0' . substr($digits, 2);
        }
        if (!preg_match('/^09\d{9}$/', $digits)) {
            return null;
        }

        return $digits . ($carrier === 'globe' ? '@txt.globe.com.ph' : '@smartemail.com.ph');
    }

    private function setting(string $name, mixed $fallback): string
    {
        $value = getenv($name);
        if ($value !== false && trim((string) $value) !== '') {
            return trim((string) $value);
        }
        return trim((string) $fallback);
    }

    private function emailBody(string $code): string
    {
        return "Your Bantayan Marketplace verification code is:\r\n\r\n" . $code . "\r\n\r\nThis code expires in 10 minutes.";
    }
}
