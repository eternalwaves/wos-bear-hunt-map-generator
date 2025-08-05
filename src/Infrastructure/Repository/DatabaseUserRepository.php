<?php

namespace App\Infrastructure\Repository;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Database\DatabaseConnection;

class DatabaseUserRepository implements UserRepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getConnection();
    }

    public function save(User $user): void
    {
        if ($user->getId() === 0) {
            $this->insert($user);
        } else {
            $this->update($user);
        }
    }

    private function insert(User $user): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (
                username, email, password_hash, is_master, is_approved, is_active,
                email_verified, email_verification_token, password_reset_token,
                password_reset_expires, otp_secret, otp_enabled, last_login,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->isMaster(),
            $user->isApproved(),
            $user->isActive(),
            $user->isEmailVerified(),
            $user->getEmailVerificationToken(),
            $user->getPasswordResetToken(),
            $user->getPasswordResetExpires()?->format('Y-m-d H:i:s'),
            $user->getOtpSecret(),
            $user->isOtpEnabled(),
            $user->getLastLogin()?->format('Y-m-d H:i:s'),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $user->setId((int)$this->pdo->lastInsertId());
    }

    private function update(User $user): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET
                username = ?, email = ?, password_hash = ?, is_master = ?,
                is_approved = ?, is_active = ?, email_verified = ?,
                email_verification_token = ?, password_reset_token = ?,
                password_reset_expires = ?, otp_secret = ?, otp_enabled = ?,
                last_login = ?, updated_at = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->isMaster(),
            $user->isApproved(),
            $user->isActive(),
            $user->isEmailVerified(),
            $user->getEmailVerificationToken(),
            $user->getPasswordResetToken(),
            $user->getPasswordResetExpires()?->format('Y-m-d H:i:s'),
            $user->getOtpSecret(),
            $user->isOtpEnabled(),
            $user->getLastLogin()?->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            $user->getId()
        ]);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? $this->createUserFromData($data) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch();

        return $data ? $this->createUserFromData($data) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch();

        return $data ? $this->createUserFromData($data) : null;
    }

    public function findByEmailVerificationToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        $data = $stmt->fetch();

        return $data ? $this->createUserFromData($data) : null;
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE password_reset_token = ?");
        $stmt->execute([$token]);
        $data = $stmt->fetch();

        return $data ? $this->createUserFromData($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $data = $stmt->fetchAll();

        return array_map([$this, 'createUserFromData'], $data);
    }

    public function findPendingApproval(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE is_approved = FALSE ORDER BY created_at ASC");
        $stmt->execute();
        $data = $stmt->fetchAll();

        return array_map([$this, 'createUserFromData'], $data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function createUserFromData(array $data): User
    {
        return new User(
            $data['username'],
            $data['email'],
            $data['password_hash'],
            (bool)$data['is_master'],
            (bool)$data['is_approved'],
            (bool)$data['is_active'],
            (bool)$data['email_verified'],
            $data['email_verification_token'],
            $data['password_reset_token'],
            $data['password_reset_expires'] ? new \DateTime($data['password_reset_expires']) : null,
            $data['otp_secret'],
            (bool)$data['otp_enabled'],
            $data['last_login'] ? new \DateTime($data['last_login']) : null,
            (int)$data['id'],
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at'])
        );
    }
} 