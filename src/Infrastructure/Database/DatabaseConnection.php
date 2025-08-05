<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $connection = null;

    public static function setConfig(array $config): void
    {
        // This method is kept for backward compatibility but is no longer needed
        // The connection now reads directly from environment variables
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    '%s:host=%s;port=%d;dbname=%s;charset=%s',
                    $_ENV['DB_DRIVER'] ?? 'mysql',
                    $_ENV['DB_HOST'] ?? 'localhost',
                    (int)($_ENV['DB_PORT'] ?? 3306),
                    $_ENV['DB_NAME'] ?? 'koinoni6_bh_staging',
                    $_ENV['DB_CHARSET'] ?? 'utf8mb4'
                );

                self::$connection = new PDO(
                    $dsn,
                    $_ENV['DB_USER'] ?? 'koinoni6_bh_staging',
                    $_ENV['DB_PASS'] ?? 'RuYiJiXiang0221!',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function createTables(): void
    {
        $pdo = self::getConnection();

        // Create maps table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS maps (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                cell_size INT NOT NULL DEFAULT 50,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Create map_versions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS map_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_id VARCHAR(255) NOT NULL,
                version_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
                UNIQUE KEY unique_map_version (map_id, version_name)
            )
        ");

        // Note: Map objects are now handled by separate tables (map_traps, map_misc_objects, map_furnaces)
        // These are created by the DatabaseMapRepository class

        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_master BOOLEAN DEFAULT FALSE,
                is_approved BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                email_verified BOOLEAN DEFAULT FALSE,
                email_verification_token VARCHAR(255),
                password_reset_token VARCHAR(255),
                password_reset_expires TIMESTAMP NULL,
                otp_secret VARCHAR(255),
                otp_enabled BOOLEAN DEFAULT TRUE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_email (email),
                INDEX idx_users_username (username)
            )
        ");

        // Create user_sessions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_sessions_token (token),
                INDEX idx_user_sessions_user (user_id)
            )
        ");

        // Create master user if it doesn't exist
        self::createMasterUser();
    }

    private static function createMasterUser(): void
    {
        $pdo = self::getConnection();
        
        // Check if master user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE is_master = TRUE LIMIT 1");
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return; // Master user already exists
        }

        // Create master user
        $masterUsername = $_ENV['MASTER_USERNAME'] ?? 'admin';
        $masterEmail = $_ENV['MASTER_EMAIL'] ?? 'admin@example.com';
        $masterPassword = $_ENV['MASTER_PASSWORD'] ?? 'admin123';

        $passwordHash = password_hash($masterPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, is_master, is_approved, is_active, email_verified, otp_enabled)
            VALUES (?, ?, ?, TRUE, TRUE, TRUE, TRUE, FALSE)
        ");
        
        $stmt->execute([$masterUsername, $masterEmail, $passwordHash]);
    }
} 