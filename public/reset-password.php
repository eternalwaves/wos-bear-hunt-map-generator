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
$validToken = false;

if (!empty($token)) {
    try {
        $user = $userRepository->findByPasswordResetToken($token);
        if ($user && $user->isPasswordResetTokenValid()) {
            $validToken = true;
        } else {
            $message = 'Invalid or expired reset token.';
            $success = false;
        }
    } catch (\Exception $e) {
        $message = 'Invalid reset token.';
        $success = false;
    }
} else {
    $message = 'No reset token provided.';
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bear Hunt Map Application</title>
    <link rel="stylesheet" href="auth-styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>Reset Password</h1>
            
            <?php if (!empty($message)): ?>
                <div class="auth-form active">
                    <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    
                    <div class="form-links">
                        <a href="login.html">Go to Login</a>
                    </div>
                </div>
            <?php elseif ($validToken): ?>
                <div class="auth-form active">
                    <h2>Set New Password</h2>
                    <form id="resetPasswordForm">
                        <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                        
                        <button type="submit">Reset Password</button>
                    </form>
                    
                    <div class="form-links">
                        <a href="login.html">Back to Login</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const token = document.getElementById('resetToken').value;
            
            if (!newPassword || !confirmPassword) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showMessage('Password must be at least 8 characters long', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            const button = this.querySelector('button');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Resetting...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('token', token);
                formData.append('new_password', newPassword);
                
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.error || 'An error occurred');
                }
                
                showMessage('Password reset successful! You can now log in with your new password.', 'success');
                
                // Clear form
                this.reset();
                
                // Redirect to login after a delay
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 3000);
            } catch (error) {
                showMessage(error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });

        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            
            const container = document.querySelector('.auth-container');
            container.insertBefore(messageDiv, container.firstChild);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html> 