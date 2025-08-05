<?php

namespace App\Application\Service;

use App\Domain\User\User;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl;
    private bool $isTestEnvironment;
    private ?string $smtpHost;
    private ?int $smtpPort;
    private ?string $smtpUsername;
    private ?string $smtpPassword;
    private ?string $smtpEncryption;
    private bool $emailEnabled;

    public function __construct()
    {
        $this->fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@example.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Bear Hunt Map Application';
        $this->baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        
        // Email server configuration
        $this->smtpHost = $_ENV['MAIL_HOST'] ?? null;
        $this->smtpPort = (int)($_ENV['MAIL_PORT'] ?? 587);
        $this->smtpUsername = $_ENV['MAIL_USERNAME'] ?? null;
        $this->smtpPassword = $_ENV['MAIL_PASSWORD'] ?? null;
        $this->smtpEncryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->emailEnabled = filter_var($_ENV['MAIL_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        
        // Detect test environment
        $this->isTestEnvironment = $this->detectTestEnvironment();
    }

    private function detectTestEnvironment(): bool
    {
        // Check for PHPUnit
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            return true;
        }
        
        // Check for test environment variable
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
            return true;
        }
        
        // Check if we're running tests
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'phpunit') !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function sendEmailVerification(User $user): bool
    {
        $subject = 'Verify Your Email Address';
        $verificationUrl = $this->baseUrl . '/verify-email.php?token=' . urlencode($user->getEmailVerificationToken());
        
        $message = $this->createEmailTemplate(
            'Email Verification',
            "Hello {$user->getUsername()},",
            "Thank you for registering. Please click the link below to verify your email address:",
            $verificationUrl,
            "This link will expire in 24 hours.",
            "If you didn't create this account, please ignore this email."
        );

        return $this->sendEmail($user->getEmail(), $subject, $message);
    }

    public function sendPasswordReset(User $user): bool
    {
        $subject = 'Password Reset Request';
        $resetUrl = $this->baseUrl . '/reset-password.php?token=' . urlencode($user->getPasswordResetToken());
        
        $message = $this->createEmailTemplate(
            'Password Reset',
            "Hello {$user->getUsername()},",
            "You have requested to reset your password. Click the link below to set a new password:",
            $resetUrl,
            "This link will expire in 1 hour.",
            "If you didn't request this password reset, please ignore this email."
        );

        return $this->sendEmail($user->getEmail(), $subject, $message);
    }

    public function sendApprovalNotification(User $user): bool
    {
        $subject = 'Account Approved';
        
        $message = $this->createEmailTemplate(
            'Account Approved',
            "Hello {$user->getUsername()},",
            "Great news! Your account has been approved by an administrator.",
            $this->baseUrl,
            "You can now log in to the application.",
            "Welcome to the team!"
        );

        return $this->sendEmail($user->getEmail(), $subject, $message);
    }

    public function sendOtpCode(User $user, string $otpCode): bool
    {
        $subject = 'Your OTP Code';
        
        $message = $this->createEmailTemplate(
            'OTP Code',
            "Hello {$user->getUsername()},",
            "Your OTP code is:",
            "<strong style='font-size: 24px; color: #007BFF;'>{$otpCode}</strong>",
            "This code will expire in 5 minutes.",
            "If you didn't request this code, please ignore this email."
        );

        return $this->sendEmail($user->getEmail(), $subject, $message);
    }

    private function createEmailTemplate(string $title, string $greeting, string $intro, string $actionUrl, string $expiry, string $footer): string
    {
        return "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$title}</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                    .button { display: inline-block; background: #007BFF; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
                    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>{$title}</h1>
                    </div>
                    <div class='content'>
                        <p>{$greeting}</p>
                        <p>{$intro}</p>
                        <p style='text-align: center;'>
                            <a href='{$actionUrl}' class='button'>Click Here</a>
                        </p>
                        <p style='text-align: center;'>
                            <small>Or copy and paste this link: <a href='{$actionUrl}'>{$actionUrl}</a></small>
                        </p>
                        <div class='warning'>
                            <strong>Security Notice:</strong> {$expiry}
                        </div>
                        <p><em>{$footer}</em></p>
                    </div>
                    <div class='footer'>
                        <p>This email was sent from the Bear HuntMap Application.</p>
                        <p>If you have any questions, please contact the administrator.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    private function sendEmail(string $to, string $subject, string $message): bool
    {
        // Don't send emails in test environment
        if ($this->isTestEnvironment) {
            $this->logEmail($to, $subject, $message, 'TEST_ENVIRONMENT');
            return true;
        }

        // Don't send emails if disabled
        if (!$this->emailEnabled) {
            $this->logEmail($to, $subject, $message, 'EMAIL_DISABLED');
            return true;
        }

        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logEmail($to, $subject, $message, 'INVALID_EMAIL');
            return false;
        }

        // Sanitize inputs
        $to = filter_var($to, FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        
        // Prepare headers with security best practices
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: Bear HuntMap Application',
            'X-Priority: 3',
            'X-MSMail-Priority: Normal',
            'Message-ID: <' . time() . '.' . uniqid() . '@' . parse_url($this->baseUrl, PHP_URL_HOST) . '>',
            'Date: ' . date('r'),
            'List-Unsubscribe: <mailto:' . $this->fromEmail . '?subject=unsubscribe>'
        ];

        try {
            if ($this->smtpHost && $this->smtpUsername && $this->smtpPassword) {
                // Use SMTP
                $result = $this->sendViaSmtp($to, $subject, $message, $headers);
            } else {
                // Use PHP mail() function
                $result = $this->sendViaMail($to, $subject, $message, $headers);
            }

            if ($result) {
                $this->logEmail($to, $subject, $message, 'SENT');
            } else {
                $this->logEmail($to, $subject, $message, 'FAILED');
            }

            return $result;
        } catch (\Exception $e) {
            $this->logEmail($to, $subject, $message, 'ERROR: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaSmtp(string $to, string $subject, string $message, array $headers): bool
    {
        // For production, you would use a proper SMTP library like PHPMailer
        // This is a simplified implementation
        $smtpConnection = fsockopen(
            $this->smtpHost,
            $this->smtpPort,
            $errno,
            $errstr,
            30
        );

        if (!$smtpConnection) {
            throw new \RuntimeException("SMTP connection failed: $errstr ($errno)");
        }

        // Basic SMTP conversation (simplified)
        fwrite($smtpConnection, "EHLO " . parse_url($this->baseUrl, PHP_URL_HOST) . "\r\n");
        fwrite($smtpConnection, "AUTH LOGIN\r\n");
        fwrite($smtpConnection, base64_encode($this->smtpUsername) . "\r\n");
        fwrite($smtpConnection, base64_encode($this->smtpPassword) . "\r\n");
        fwrite($smtpConnection, "MAIL FROM: <{$this->fromEmail}>\r\n");
        fwrite($smtpConnection, "RCPT TO: <{$to}>\r\n");
        fwrite($smtpConnection, "DATA\r\n");
        fwrite($smtpConnection, "Subject: {$subject}\r\n");
        fwrite($smtpConnection, implode("\r\n", $headers) . "\r\n\r\n");
        fwrite($smtpConnection, $message . "\r\n.\r\n");
        fwrite($smtpConnection, "QUIT\r\n");

        fclose($smtpConnection);
        return true;
    }

    private function sendViaMail(string $to, string $subject, string $message, array $headers): bool
    {
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    private function logEmail(string $to, string $subject, string $message, string $status): void
    {
        $logDir = __DIR__ . '/../../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'status' => $status,
            'environment' => $this->isTestEnvironment ? 'TEST' : 'PRODUCTION',
            'smtp_configured' => !empty($this->smtpHost),
            'email_enabled' => $this->emailEnabled
        ];

        $logMessage = json_encode($logEntry, JSON_PRETTY_PRINT) . "\n---\n";
        
        error_log($logMessage, 3, $logDir . '/email.log');
    }

    public function isTestEnvironment(): bool
    {
        return $this->isTestEnvironment;
    }

    public function isEmailEnabled(): bool
    {
        return $this->emailEnabled;
    }

    public function isSmtpConfigured(): bool
    {
        return !empty($this->smtpHost) && !empty($this->smtpUsername) && !empty($this->smtpPassword);
    }
} 