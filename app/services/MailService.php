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

        // Temporary debug: confirm how driver/env arrived into MailService
        $this->logMailError(
            'MailService DEBUG construct — '
            . 'config_path=' . $configPath
            . ' | $_ENV[MAIL_DRIVER]=' . var_export($_ENV['MAIL_DRIVER'] ?? null, true)
            . ' | getenv(MAIL_DRIVER)=' . var_export(getenv('MAIL_DRIVER'), true)
            . ' | config[driver]=' . var_export($this->config['driver'] ?? null, true)
            . ' | config[enabled]=' . var_export($this->config['enabled'] ?? null, true)
            . ' | config[from_email]=' . var_export($this->config['from_email'] ?? null, true)
            . ' | elastic_api_key_set=' . (!empty($this->config['elastic_api_key']) ? 'yes' : 'no')
            . ' | smtp_host=' . var_export($this->config['smtp_host'] ?? null, true)
        );
    }

    public function isEnabled(): bool
    {
        if (empty($this->config['enabled']) || empty($this->config['from_email'])) {
            $this->logMailError(
                'MailService DEBUG isEnabled=false — enabled='
                . var_export($this->config['enabled'] ?? null, true)
                . ' from_email=' . var_export($this->config['from_email'] ?? null, true)
                . ' driver=' . $this->getDriver()
            );
            return false;
        }

        $driver = $this->getDriver();

        if ($driver === 'elastic') {
            $ok = !empty($this->config['elastic_api_key']);
            if (!$ok) {
                $this->logMailError('MailService DEBUG isEnabled=false — elastic driver but API key empty');
            }
            return $ok;
        }

        // Default: smtp
        $ok = !empty($this->config['smtp_host']);
        if (!$ok) {
            $this->logMailError('MailService DEBUG isEnabled=false — smtp driver but smtp_host empty');
        }
        return $ok;
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

    private function getDriver(): string
    {
        $rawConfigDriver = $this->config['driver'] ?? null;
        $rawEnvDriver = $_ENV['MAIL_DRIVER'] ?? null;
        $rawGetenvDriver = getenv('MAIL_DRIVER');

        $driver = strtolower(trim((string) ($rawConfigDriver ?? 'smtp')));
        $resolved = $driver === 'elastic' ? 'elastic' : 'smtp';

        // Temporary debug: raw vs resolved driver
        $this->logMailError(
            'MailService DEBUG getDriver — '
            . 'raw_config[driver]=' . var_export($rawConfigDriver, true)
            . ' | raw_$_ENV[MAIL_DRIVER]=' . var_export($rawEnvDriver, true)
            . ' | raw_getenv(MAIL_DRIVER)=' . var_export($rawGetenvDriver, true)
            . ' | normalized=' . var_export($driver, true)
            . ' | resolved=' . $resolved
            . ($resolved !== 'elastic'
                ? ' | WHY_SMTP=config driver is not exactly "elastic" after normalize (defaulting to smtp)'
                : ' | WHY_ELASTIC=config driver resolved to elastic')
        );

        return $resolved;
    }

    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        $driver = $this->getDriver();

        if ($driver === 'elastic') {
            $this->logMailError(
                'MailService DEBUG send() — choosing sendViaElasticEmail()'
                . ' to=' . $toEmail
                . ' subject=' . $subject
            );
            return $this->sendViaElasticEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);
        }

        $this->logMailError(
            'MailService DEBUG send() — choosing sendViaSmtp() UNEXPECTED if MAIL_DRIVER=elastic was intended'
            . ' | resolved_driver=' . $driver
            . ' | config[driver]=' . var_export($this->config['driver'] ?? null, true)
            . ' | $_ENV[MAIL_DRIVER]=' . var_export($_ENV['MAIL_DRIVER'] ?? null, true)
            . ' | to=' . $toEmail
            . ' | subject=' . $subject
        );

        return $this->sendViaSmtp($toEmail, $toName, $subject, $htmlBody, $plainBody);
    }

    /**
     * Existing PHPMailer SMTP transport (unchanged behaviour).
     */
    private function sendViaSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        $this->logMailError(
            'MailService DEBUG sendViaSmtp() ENTERED — host='
            . var_export($this->config['smtp_host'] ?? null, true)
            . ' from=' . var_export($this->config['from_email'] ?? null, true)
        );

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

    /**
     * Elastic Email REST API transport (v2 /email/send).
     */
    private function sendViaElasticEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        $endpoint = trim((string) ($this->config['elastic_endpoint'] ?? ''));
        if ($endpoint === '') {
            $endpoint = 'https://api.elasticemail.com/v2/email/send';
        }

        $apiKey = (string) ($this->config['elastic_api_key'] ?? '');
        $fromEmail = (string) ($this->config['from_email'] ?? '');
        $fromName = (string) ($this->config['from_name'] ?? APP_NAME);

        $to = trim($toName) !== ''
            ? sprintf('%s <%s>', $toName, $toEmail)
            : $toEmail;

        $payload = [
            'apikey' => $apiKey,
            'from' => $fromEmail,
            'fromName' => $fromName,
            'subject' => $subject,
            'to' => $to,
            'bodyHtml' => $htmlBody,
            'bodyText' => $plainBody,
            'isTransactional' => 'true',
            'trackOpens' => 'false',
            'trackClicks' => 'false',
        ];

        // Temporary debug: log request payload without the API key
        $payloadForLog = $payload;
        unset($payloadForLog['apikey']);
        $payloadForLog['bodyHtml'] = '[html length=' . strlen($htmlBody) . ']';
        $payloadForLog['bodyText'] = '[text length=' . strlen($plainBody) . ']';
        $this->logMailError(
            'Elastic Email DEBUG request — endpoint=' . $endpoint
            . ' | has_from=' . (!empty($payload['from']) ? 'yes' : 'no')
            . ' | has_fromName=' . (isset($payload['fromName']) && $payload['fromName'] !== '' ? 'yes' : 'no')
            . ' | has_to=' . (!empty($payload['to']) ? 'yes' : 'no')
            . ' | has_subject=' . (!empty($payload['subject']) ? 'yes' : 'no')
            . ' | has_bodyHtml=' . (!empty($payload['bodyHtml']) ? 'yes' : 'no')
            . ' | has_apikey=' . (!empty($payload['apikey']) ? 'yes' : 'no')
            . ' | payload=' . json_encode($payloadForLog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $ch = curl_init($endpoint);
        if ($ch === false) {
            $this->logMailError('Elastic Email: failed to initialize cURL');
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $rawResponse = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = null;
        if (is_string($rawResponse) && $rawResponse !== '') {
            $decoded = json_decode($rawResponse, true);
        }

        // Temporary debug: full transport diagnostics (API key never logged)
        $this->logMailError(
            'Elastic Email DEBUG response — http_status=' . $httpCode
            . ' curl_errno=' . $curlErrno
            . ' curl_error=' . ($curlError !== '' ? $curlError : '(none)')
            . ' raw_response=' . ($rawResponse === false ? '(curl_exec failed)' : $rawResponse)
            . ' decoded_json=' . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if ($rawResponse === false) {
            $this->logMailError(
                'Elastic Email cURL exec failed: errno=' . $curlErrno
                . ' error=' . ($curlError !== '' ? $curlError : 'unknown')
            );
            return false;
        }

        if (!is_array($decoded)) {
            $this->logMailError(
                'Elastic Email invalid JSON response (HTTP ' . $httpCode . '): '
                . substr((string) $rawResponse, 0, 1000)
            );
            return false;
        }

        $success = !empty($decoded['success']);
        if (!$success) {
            $errorMessage = $decoded['error']
                ?? $decoded['Error']
                ?? $decoded['message']
                ?? ('HTTP ' . $httpCode . ' — ' . substr((string) $rawResponse, 0, 1000));
            $this->logMailError('Elastic Email API error: ' . $errorMessage);
            return false;
        }

        $this->logMailError('Elastic Email DEBUG send succeeded (HTTP ' . $httpCode . ')');
        return true;
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
