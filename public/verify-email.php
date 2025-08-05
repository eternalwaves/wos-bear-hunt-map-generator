<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database connection is now configured automatically from environment variables

// Set up dependency injection
$userRepository = new \App\Infrastructure\Repository\DatabaseUserRepository();
$emailService = new \App\Application\Service\EmailService();
$authService = new \App\Application\Service\AuthenticationService($userRepository, $emailService);

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if (!empty($token)) {
    try {
        $authService->verifyEmail($token);
        $message = 'Email verified successfully! You can now log in to your account.';
        $success = true;
    } catch (\Exception $e) {
        $message = 'Invalid or expired verification token.';
        $success = false;
    }
} else {
    $message = 'No verification token provided.';
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Bear Hunt Map Application</title>
    <link rel="stylesheet" href="auth-styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>Email Verification</h1>
            
            <div class="auth-form active">
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <div class="form-links">
                    <a href="login.html">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 