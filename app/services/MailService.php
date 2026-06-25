<?php

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private array $config;

    public function __construct()
    {
        $configPath = __DIR__ . '/../../config/mail.php';
        if (!is_file($configPath)) {
            $configPath = __DIR__ . '/../../config/mail.example.php';
        }
        $this->config = require $configPath;
    }

    public function isEnabled(): bool
    {
        return !empty($this->config['enabled'])
            && !empty($this->config['smtp_host'])
            && !empty($this->config['from_email']);
    }

    /**
     * Send welcome email with temporary login credentials.
     */
    public function sendWelcomeEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $loginUrl = route('login');
        $htmlBody = render_partial(__DIR__ . '/../views/emails/welcome_user.php', [
            'fullName' => $fullName,
            'email' => $email,
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $loginUrl,
        ]);

        $plainBody = $this->buildWelcomePlainText($fullName, $email, $temporaryPassword, $loginUrl);

        return $this->send(
            $email,
            $fullName,
            'Welcome to AES Project Tracker',
            $htmlBody,
            $plainBody
        );
    }

    /**
     * Send forgot-password email with a new temporary login password.
     */
    public function sendForgotPasswordEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $loginUrl = route('login');
        $htmlBody = render_partial(__DIR__ . '/../views/emails/forgot_password.php', [
            'fullName' => $fullName,
            'email' => $email,
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $loginUrl,
        ]);

        $plainBody = $this->buildForgotPasswordPlainText($fullName, $email, $temporaryPassword, $loginUrl);

        return $this->send(
            $email,
            $fullName,
            'AES Project Tracker - Temporary Password',
            $htmlBody,
            $plainBody
        );
    }

    private function buildForgotPasswordPlainText(
        string $fullName,
        string $email,
        string $temporaryPassword,
        string $loginUrl
    ): string {
        return "Hello {$fullName},\n\n"
            . "A temporary password has been generated for your account.\n\n"
            . "Login URL\n{$loginUrl}\n\n"
            . "Email\n{$email}\n\n"
            . "Temporary Password\n{$temporaryPassword}\n\n"
            . "For security reasons, you will be required to change this password immediately after logging in.\n\n"
            . "Regards,\nAES Project Tracker";
    }

    /**
     * Send password reset email with new temporary login credentials.
     */
    public function sendPasswordResetEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $loginUrl = route('login');
        $htmlBody = render_partial(__DIR__ . '/../views/emails/password_reset.php', [
            'fullName' => $fullName,
            'email' => $email,
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $loginUrl,
        ]);

        $plainBody = $this->buildPasswordResetPlainText($fullName, $email, $temporaryPassword, $loginUrl);

        return $this->send(
            $email,
            $fullName,
            'AES Project Tracker - Password Reset',
            $htmlBody,
            $plainBody
        );
    }

    private function buildPasswordResetPlainText(
        string $fullName,
        string $email,
        string $temporaryPassword,
        string $loginUrl
    ): string {
        return "Hello {$fullName},\n\n"
            . "Your password has been reset by the system administrator.\n\n"
            . "You can now log in using the following credentials.\n\n"
            . "Login URL\n{$loginUrl}\n\n"
            . "Email\n{$email}\n\n"
            . "Temporary Password\n{$temporaryPassword}\n\n"
            . "Please change your password after logging in.\n\n"
            . "Regards,\nAES Project Tracker";
    }

    private function buildWelcomePlainText(
        string $fullName,
        string $email,
        string $temporaryPassword,
        string $loginUrl
    ): string {
        return "Hello {$fullName},\n\n"
            . "Your AES Project Tracker account has been created successfully.\n\n"
            . "You can now sign in using the following credentials.\n\n"
            . "Login URL\n{$loginUrl}\n\n"
            . "Email\n{$email}\n\n"
            . "Temporary Password\n{$temporaryPassword}\n\n"
            . "For security reasons, please change your password after your first login.\n\n"
            . "If you did not expect this email, please contact your administrator.\n\n"
            . "Regards,\nAES Project Tracker";
    }

    /**
     * Send a rendered HTML notification email.
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($plainBody === '') {
            $plainBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody))));
        }

        return $this->send($toEmail, $toName, $subject, $htmlBody, $plainBody);
    }

    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($vendorAutoload)) {
            return false;
        }

        require_once $vendorAutoload;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) ($this->config['smtp_host'] ?? '');
            $mail->Port = (int) ($this->config['smtp_port'] ?? 587);
            $mail->SMTPAuth = !empty($this->config['smtp_auth']);

            if (!empty($this->config['smtp_username'])) {
                $mail->Username = (string) $this->config['smtp_username'];
            }
            if (!empty($this->config['smtp_password'])) {
                $mail->Password = (string) $this->config['smtp_password'];
            }

            $secure = strtolower((string) ($this->config['smtp_secure'] ?? 'tls'));
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(
                (string) ($this->config['from_email'] ?? ''),
                (string) ($this->config['from_name'] ?? APP_NAME)
            );
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody;

            return $mail->send();
        } catch (MailerException $e) {
            $this->logMailError($e->getMessage());
            return false;
        }
    }

    private function logMailError(string $message): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($logDir . '/mail.log', $line, FILE_APPEND | LOCK_EX);
    }
}
