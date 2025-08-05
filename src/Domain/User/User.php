<?php

namespace App\Domain\User;

class User
{
    private int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private bool $isMaster;
    private bool $isApproved;
    private bool $isActive;
    private bool $emailVerified;
    private ?string $emailVerificationToken;
    private ?string $passwordResetToken;
    private ?\DateTime $passwordResetExpires;
    private ?string $otpSecret;
    private bool $otpEnabled;
    private ?\DateTime $lastLogin;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $username,
        string $email,
        string $passwordHash,
        bool $isMaster = false,
        bool $isApproved = false,
        bool $isActive = true,
        bool $emailVerified = false,
        ?string $emailVerificationToken = null,
        ?string $passwordResetToken = null,
        ?\DateTime $passwordResetExpires = null,
        ?string $otpSecret = null,
        bool $otpEnabled = true,
        ?\DateTime $lastLogin = null,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->isMaster = $isMaster;
        $this->isApproved = $isApproved;
        $this->isActive = $isActive;
        $this->emailVerified = $emailVerified;
        $this->emailVerificationToken = $emailVerificationToken;
        $this->passwordResetToken = $passwordResetToken;
        $this->passwordResetExpires = $passwordResetExpires;
        $this->otpSecret = $otpSecret;
        $this->otpEnabled = $otpEnabled;
        $this->lastLogin = $lastLogin;
        $this->id = $id ?? 0;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function isMaster(): bool
    {
        return $this->isMaster;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpires(): ?\DateTime
    {
        return $this->passwordResetExpires;
    }

    public function getOtpSecret(): ?string
    {
        return $this->otpSecret;
    }

    public function isOtpEnabled(): bool
    {
        return $this->otpEnabled;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->updatedAt = new \DateTime();
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new \DateTime();
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->updatedAt = new \DateTime();
    }

    public function setApproved(bool $isApproved): void
    {
        $this->isApproved = $isApproved;
        $this->updatedAt = new \DateTime();
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTime();
    }

    public function setEmailVerified(bool $emailVerified): void
    {
        $this->emailVerified = $emailVerified;
        $this->updatedAt = new \DateTime();
    }

    public function setEmailVerificationToken(?string $token): void
    {
        $this->emailVerificationToken = $token;
        $this->updatedAt = new \DateTime();
    }

    public function setPasswordResetToken(?string $token): void
    {
        $this->passwordResetToken = $token;
        $this->updatedAt = new \DateTime();
    }

    public function setPasswordResetExpires(?\DateTime $expires): void
    {
        $this->passwordResetExpires = $expires;
        $this->updatedAt = new \DateTime();
    }

    public function setOtpSecret(?string $secret): void
    {
        $this->otpSecret = $secret;
        $this->updatedAt = new \DateTime();
    }

    public function setOtpEnabled(bool $enabled): void
    {
        $this->otpEnabled = $enabled;
        $this->updatedAt = new \DateTime();
    }

    public function setLastLogin(?\DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
        $this->updatedAt = new \DateTime();
    }

    // Business logic methods
    public function canLogin(): bool
    {
        return $this->isActive && $this->isApproved && $this->emailVerified;
    }

    public function needsOtp(): bool
    {
        return $this->otpEnabled && !$this->isMaster;
    }

    public function isPasswordResetTokenValid(): bool
    {
        if (!$this->passwordResetToken || !$this->passwordResetExpires) {
            return false;
        }

        return $this->passwordResetExpires > new \DateTime();
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetExpires = null;
        $this->updatedAt = new \DateTime();
    }

    public function clearEmailVerificationToken(): void
    {
        $this->emailVerificationToken = null;
        $this->updatedAt = new \DateTime();
    }
} 