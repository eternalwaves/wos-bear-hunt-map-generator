<?php

namespace App\Domain\Repository;

use App\Domain\User\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(int $id): ?User;
    public function findByUsername(string $username): ?User;
    public function findByEmail(string $email): ?User;
    public function findByEmailVerificationToken(string $token): ?User;
    public function findByPasswordResetToken(string $token): ?User;
    public function findAll(): array;
    public function findPendingApproval(): array;
    public function delete(int $id): void;
} 