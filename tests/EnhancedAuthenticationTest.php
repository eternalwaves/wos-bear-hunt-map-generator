<?php

use PHPUnit\Framework\TestCase;
use App\Domain\User\User;
use App\Application\Service\AuthenticationService;
use App\Application\Service\EmailService;
use App\Application\Exception\AuthenticationException;
use App\Application\Exception\ValidationException;

class EnhancedAuthenticationTest extends TestCase
{
    private AuthenticationService $authService;
    private EmailService $emailService;
    private $mockUserRepository;

    protected function setUp(): void
    {
        $this->mockUserRepository = $this->createMock(\App\Domain\Repository\UserRepositoryInterface::class);
        $this->emailService = new EmailService();
        $this->authService = new AuthenticationService($this->mockUserRepository, $this->emailService);
    }

    public function testPasswordValidationRequirements()
    {
        // Test minimum length
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');
        $this->invokeMethod($this->authService, 'validatePassword', ['short']);

        // Test uppercase requirement
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter');
        $this->invokeMethod($this->authService, 'validatePassword', ['password123!']);

        // Test lowercase requirement
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');
        $this->invokeMethod($this->authService, 'validatePassword', ['PASSWORD123!']);

        // Test number requirement
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must contain at least one number');
        $this->invokeMethod($this->authService, 'validatePassword', ['Password!']);

        // Test special character requirement
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must contain at least one special character');
        $this->invokeMethod($this->authService, 'validatePassword', ['Password123']);

        // Test common password rejection
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password is too common');
        $this->invokeMethod($this->authService, 'validatePassword', ['password123!']);

        // Test valid password
        $this->invokeMethod($this->authService, 'validatePassword', ['SecurePass123!']);
        $this->assertTrue(true); // No exception thrown
    }

    public function testUsernameValidation()
    {
        // Test valid username
        $this->invokeMethod($this->authService, 'validateRegistrationInput', ['validuser', 'test@example.com', 'SecurePass123!']);
        $this->assertTrue(true); // No exception thrown

        // Test username too short
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Username must be at least 3 characters long');
        $this->invokeMethod($this->authService, 'validateRegistrationInput', ['ab', 'test@example.com', 'SecurePass123!']);

        // Test invalid characters in username
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Username can only contain letters, numbers, underscores, and hyphens');
        $this->invokeMethod($this->authService, 'validateRegistrationInput', ['user@name', 'test@example.com', 'SecurePass123!']);
    }

    public function testSecureTokenGeneration()
    {
        $token1 = $this->invokeMethod($this->authService, 'generateSecureToken', []);
        $token2 = $this->invokeMethod($this->authService, 'generateSecureToken', []);

        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex characters
        $this->assertEquals(64, strlen($token2));
        $this->assertNotEquals($token1, $token2); // Tokens should be unique
    }

    public function testTOTPGeneration()
    {
        $secret = $this->invokeMethod($this->authService, 'generateSecureOtpSecret', []);
        $otp1 = $this->invokeMethod($this->authService, 'generateTOTP', [$secret]);
        $otp2 = $this->invokeMethod($this->authService, 'generateTOTP', [$secret]);

        $this->assertEquals(6, strlen($otp1));
        $this->assertEquals(6, strlen($otp2));
        $this->assertTrue(is_numeric($otp1));
        $this->assertTrue(is_numeric($otp2));
        
        // OTPs should be the same within the same time window
        $this->assertEquals($otp1, $otp2);
    }

    public function testRateLimiting()
    {
        $username = 'testuser';
        
        // Should allow first few attempts
        for ($i = 0; $i < 5; $i++) {
            $this->invokeMethod($this->authService, 'checkRateLimit', [$username]);
            $this->invokeMethod($this->authService, 'recordFailedAttempt', [$username]);
        }

        // Should block after 5 attempts
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Too many login attempts');
        $this->invokeMethod($this->authService, 'checkRateLimit', [$username]);
    }

    public function testEmailServiceTestEnvironmentDetection()
    {
        // Test that email service detects test environment
        $this->assertTrue($this->emailService->isTestEnvironment());
        
        // Test email configuration methods
        $this->assertTrue($this->emailService->isEmailEnabled());
        $this->assertFalse($this->emailService->isSmtpConfigured());
    }

    public function testSecurePasswordHashing()
    {
        $password = 'SecurePass123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrongpassword', $hash));
        
        // Verify it's using Argon2id
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testEmailTemplateGeneration()
    {
        $template = $this->invokeMethod($this->emailService, 'createEmailTemplate', [
            'Test Title',
            'Hello User,',
            'This is a test message.',
            'https://example.com/test',
            'This expires in 1 hour.',
            'Thank you!'
        ]);

        $this->assertStringContainsString('Test Title', $template);
        $this->assertStringContainsString('Hello User,', $template);
        $this->assertStringContainsString('https://example.com/test', $template);
        $this->assertStringContainsString('This expires in 1 hour.', $template);
        $this->assertStringContainsString('<!DOCTYPE html>', $template);
        $this->assertStringContainsString('<style>', $template);
    }

    public function testBase32EncodingDecoding()
    {
        $originalData = 'test data for encoding';
        
        $encoded = $this->invokeMethod($this->authService, 'base32_encode', [$originalData]);
        $decoded = $this->invokeMethod($this->authService, 'base32_decode', [$encoded]);
        
        $this->assertEquals($originalData, $decoded);
        $this->assertNotEquals($originalData, $encoded); // Encoded should be different
    }

    public function testEmailLogging()
    {
        $logDir = __DIR__ . '/../logs';
        $logFile = $logDir . '/email.log';
        
        // Ensure logs directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Clean up any existing log file
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        // Test email logging
        $this->invokeMethod($this->emailService, 'logEmail', [
            'test@example.com',
            'Test Subject',
            'Test message content',
            'TEST_STATUS'
        ]);
        
        $this->assertFileExists($logFile);
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('test@example.com', $logContent);
        $this->assertStringContainsString('Test Subject', $logContent);
        $this->assertStringContainsString('TEST_STATUS', $logContent);
    }

    // Helper method to invoke private methods for testing
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
} 