<?php

use PHPUnit\Framework\TestCase;
use App\Domain\User\User;
use App\Application\Exception\AuthenticationException;
use App\Application\Exception\ValidationException;

class AuthenticationTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT)
        );

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertFalse($user->isMaster());
        $this->assertFalse($user->isApproved());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isEmailVerified());
        $this->assertTrue($user->isOtpEnabled());
    }

    public function testMasterUserCreation()
    {
        $user = new User(
            'admin',
            'admin@example.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            true, // isMaster
            true, // isApproved
            true, // isActive
            true  // emailVerified
        );

        $this->assertTrue($user->isMaster());
        $this->assertTrue($user->isApproved());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isEmailVerified());
        $this->assertFalse($user->needsOtp()); // Master users don't need OTP
    }

    public function testUserCanLogin()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            false, // isMaster
            true,  // isApproved
            true,  // isActive
            true   // emailVerified
        );

        $this->assertTrue($user->canLogin());
    }

    public function testUserCannotLoginWhenNotApproved()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            false, // isMaster
            false, // isApproved
            true,  // isActive
            true   // emailVerified
        );

        $this->assertFalse($user->canLogin());
    }

    public function testUserCannotLoginWhenNotActive()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            false, // isMaster
            true,  // isApproved
            false, // isActive
            true   // emailVerified
        );

        $this->assertFalse($user->canLogin());
    }

    public function testUserCannotLoginWhenEmailNotVerified()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            false, // isMaster
            true,  // isApproved
            true,  // isActive
            false  // emailVerified
        );

        $this->assertFalse($user->canLogin());
    }

    public function testPasswordResetTokenValidation()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT)
        );

        // Set a valid token (expires in 1 hour)
        $user->setPasswordResetToken('valid-token');
        $user->setPasswordResetExpires(new DateTime('+1 hour'));

        $this->assertTrue($user->isPasswordResetTokenValid());

        // Set an expired token
        $user->setPasswordResetToken('expired-token');
        $user->setPasswordResetExpires(new DateTime('-1 hour'));

        $this->assertFalse($user->isPasswordResetTokenValid());
    }

    public function testClearTokens()
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT)
        );

        $user->setPasswordResetToken('test-token');
        $user->setEmailVerificationToken('test-email-token');

        $user->clearPasswordResetToken();
        $user->clearEmailVerificationToken();

        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getEmailVerificationToken());
    }

    public function testAuthenticationException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Custom auth error');

        throw new AuthenticationException('Custom auth error');
    }

    public function testValidationException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Custom validation error');

        throw new ValidationException('Custom validation error');
    }
} 