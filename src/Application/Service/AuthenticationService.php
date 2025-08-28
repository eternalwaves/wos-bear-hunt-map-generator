<?php

namespace App\Application\Service;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Application\Exception\AuthenticationException;
use App\Application\Exception\ValidationException;

class AuthenticationService
{
    private UserRepositoryInterface $userRepository;
    private EmailService $emailService;
    private array $rateLimitAttempts = [];
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 900; // 15 minutes
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_REQUIRE_UPPERCASE = true;
    private const PASSWORD_REQUIRE_LOWERCASE = true;
    private const PASSWORD_REQUIRE_NUMBERS = true;
    private const PASSWORD_REQUIRE_SPECIAL = true;

    public function __construct(UserRepositoryInterface $userRepository, EmailService $emailService)
    {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    public function register(string $username, string $email, string $password): User
    {
        // Validate input
        $this->validateRegistrationInput($username, $email, $password);

        // Check if username or email already exists
        if ($this->userRepository->findByUsername($username)) {
            throw new ValidationException('Username already exists');
        }

        if ($this->userRepository->findByEmail($email)) {
            throw new ValidationException('Email already exists');
        }

        // Create user with secure defaults
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $emailVerificationToken = $this->generateSecureToken();
        $otpSecret = $this->generateSecureOtpSecret();

        $user = new User(
            $username,
            $email,
            $passwordHash,
            false, // isMaster
            false, // isApproved
            true,  // isActive
            false, // emailVerified
            $emailVerificationToken,
            null,  // passwordResetToken
            null,  // passwordResetExpires
            $otpSecret,
            true   // otpEnabled
        );

        $this->userRepository->save($user);

        // Send email verification
        $this->emailService->sendEmailVerification($user);

        return $user;
    }

    public function login(string $username, string $password): array
    {
        // Rate limiting
        $this->checkRateLimit($username);

        $user = $this->userRepository->findByUsername($username);
        if (!$user) {
            $this->recordFailedAttempt($username);
            throw new AuthenticationException('Invalid credentials');
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            $this->recordFailedAttempt($username);
            throw new AuthenticationException('Invalid credentials');
        }

        if (!$user->canLogin()) {
            $this->recordFailedAttempt($username);
            throw new AuthenticationException('Account is not active, approved, or email not verified');
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($username);

        // Update last login
        $user->setLastLogin(new \DateTime());
        $this->userRepository->save($user);

        // Create session for the user
        $sessionData = $this->createSession($user);

        return [
            'user' => $user,
            'session_token' => $sessionData['session_token'],
            'expires_at' => $sessionData['expires_at'],
            'needs_otp' => $user->needsOtp()
        ];
    }

    public function verifyOtp(int $userId, string $otpCode): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if (!$user->needsOtp()) {
            // Master user doesn't need OTP, create session directly
            $sessionData = $this->createSession($user);
            return [
                'success' => true,
                'user' => $user,
                'session_token' => $sessionData['session_token'],
                'expires_at' => $sessionData['expires_at']
            ];
        }

        // Verify OTP code using TOTP (Time-based One-Time Password)
        $expectedOtp = $this->generateTOTP($user->getOtpSecret());
        
        // Allow for time drift (30-second windows)
        $currentTime = time();
        $validOtp = false;
        
        for ($i = -1; $i <= 1; $i++) {
            $timeWindow = $currentTime + ($i * 30);
            $expectedOtpWindow = $this->generateTOTP($user->getOtpSecret(), $timeWindow);
            if (hash_equals($otpCode, $expectedOtpWindow)) {
                $validOtp = true;
                break;
            }
        }
        
        if ($validOtp) {
            // OTP verified, create session
            $sessionData = $this->createSession($user);
            return [
                'success' => true,
                'user' => $user,
                'session_token' => $sessionData['session_token'],
                'expires_at' => $sessionData['expires_at']
            ];
        }
        
        throw new AuthenticationException('Invalid OTP code');
    }

    public function verifyEmail(string $token): bool
    {
        if (empty($token)) {
            throw new AuthenticationException('Invalid verification token');
        }

        $user = $this->userRepository->findByEmailVerificationToken($token);
        if (!$user) {
            throw new AuthenticationException('Invalid verification token');
        }

        $user->setEmailVerified(true);
        $user->clearEmailVerificationToken();
        $this->userRepository->save($user);

        return true;
    }

    public function requestPasswordReset(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Don't reveal if email exists or not
            return true;
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            // Don't reveal if email exists or not
            return true;
        }

        $token = $this->generateSecureToken();
        $expiresAt = (new \DateTime())->modify('+1 hour');

        $user->setPasswordResetToken($token);
        $user->setPasswordResetExpires($expiresAt);
        $this->userRepository->save($user);

        $this->emailService->sendPasswordReset($user);

        return true;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        if (empty($token)) {
            throw new AuthenticationException('Invalid reset token');
        }

        $user = $this->userRepository->findByPasswordResetToken($token);
        if (!$user) {
            throw new AuthenticationException('Invalid reset token');
        }

        if (!$user->isPasswordResetTokenValid()) {
            throw new AuthenticationException('Reset token has expired');
        }

        // Validate new password
        $this->validatePassword($newPassword);

        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $user->setPasswordHash($passwordHash);
        $user->clearPasswordResetToken();
        $this->userRepository->save($user);

        return true;
    }

    public function approveUser(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        $user->setApproved(true);
        $this->userRepository->save($user);

        $this->emailService->sendApprovalNotification($user);

        return true;
    }

    public function deactivateUser(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if ($user->isMaster()) {
            throw new AuthenticationException('Cannot deactivate master user');
        }

        $user->setActive(false);
        $this->userRepository->save($user);

        return true;
    }

    public function deleteUser(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if ($user->isMaster()) {
            throw new AuthenticationException('Cannot delete master user');
        }

        $this->userRepository->delete($userId);

        return true;
    }

    public function getPendingApprovals(): array
    {
        return $this->userRepository->findPendingApproval();
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getUserInfo(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    public function updateUsername(int $userId, string $newUsername, string $currentPassword): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            throw new AuthenticationException('Current password is incorrect');
        }

        // Validate new username
        if (empty($newUsername) || strlen($newUsername) < 3) {
            throw new ValidationException('Username must be at least 3 characters long');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $newUsername)) {
            throw new ValidationException('Username can only contain letters, numbers, underscores, and hyphens');
        }

        // Check if username already exists (excluding current user)
        $existingUser = $this->userRepository->findByUsername($newUsername);
        if ($existingUser && $existingUser->getId() !== $userId) {
            throw new ValidationException('Username already exists');
        }

        // Update username
        $user->setUsername($newUsername);
        $this->userRepository->save($user);

        return true;
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            throw new AuthenticationException('Current password is incorrect');
        }

        // Validate new password
        $this->validatePassword($newPassword);

        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        // Update password
        $user->setPasswordHash($passwordHash);
        $this->userRepository->save($user);

        return true;
    }

    public function createSession(User $user): array
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set session data
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['is_master'] = $user->isMaster();

        // Generate session token for API calls (if needed)
        $sessionToken = $this->generateSecureToken();
        $expiresAt = (new \DateTime())->modify('+24 hours');

        return [
            'user' => $user,
            'session_token' => $sessionToken,
            'expires_at' => $expiresAt
        ];
    }

    private function validateRegistrationInput(string $username, string $email, string $password): void
    {
        // Username validation
        if (empty($username) || strlen($username) < 3) {
            throw new ValidationException('Username must be at least 3 characters long');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new ValidationException('Username can only contain letters, numbers, underscores, and hyphens');
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address');
        }

        // Password validation
        $this->validatePassword($password);
    }

    private function validatePassword(string $password): void
    {
        if (empty($password) || strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new ValidationException('Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters long');
        }

        if (self::PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            throw new ValidationException('Password must contain at least one uppercase letter');
        }

        if (self::PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            throw new ValidationException('Password must contain at least one lowercase letter');
        }

        if (self::PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            throw new ValidationException('Password must contain at least one number');
        }

        if (self::PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new ValidationException('Password must contain at least one special character');
        }

        // Check for common weak passwords
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein'];
        if (in_array(strtolower($password), $commonPasswords)) {
            throw new ValidationException('Password is too common. Please choose a stronger password');
        }
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function generateSecureOtpSecret(): string
    {
        return $this->base32_encode(random_bytes(20));
    }

    private function generateTOTP(string $secret, int $time = null): string
    {
        $time = $time ?? time();
        $timeWindow = floor($time / 30);
        
        $hash = hash_hmac('sha1', pack('J', $timeWindow), $this->base32_decode($secret), true);
        $offset = ord($hash[19]) & 0xf;
        
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    private function checkRateLimit(string $username): void
    {
        $key = 'login_' . $username;
        $now = time();
        
        if (isset($this->rateLimitAttempts[$key])) {
            $attempts = $this->rateLimitAttempts[$key];
            
            // Remove old attempts outside the window
            $attempts = array_filter($attempts, function($timestamp) use ($now) {
                return ($now - $timestamp) < self::RATE_LIMIT_WINDOW;
            });
            
            if (count($attempts) >= self::MAX_LOGIN_ATTEMPTS) {
                throw new AuthenticationException('Too many login attempts. Please try again later.');
            }
            
            $this->rateLimitAttempts[$key] = $attempts;
        }
    }

    private function recordFailedAttempt(string $username): void
    {
        $key = 'login_' . $username;
        if (!isset($this->rateLimitAttempts[$key])) {
            $this->rateLimitAttempts[$key] = [];
        }
        
        $this->rateLimitAttempts[$key][] = time();
    }

    private function clearFailedAttempts(string $username): void
    {
        $key = 'login_' . $username;
        unset($this->rateLimitAttempts[$key]);
    }

    // Helper functions for base32 encoding/decoding
    private function base32_encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $encoded = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        for ($i = 0; $i < strlen($bits); $i += 5) {
            $chunk = substr($bits, $i, 5);
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= $alphabet[bindec($chunk)];
        }
        
        return $encoded;
    }

    private function base32_decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $decoded = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $pos = strpos($alphabet, strtoupper($data[$i]));
            if ($pos === false) continue;
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $chunk = substr($bits, $i, 8);
            if (strlen($chunk) == 8) {
                $decoded .= chr(bindec($chunk));
            }
        }
        
        return $decoded;
    }
} 