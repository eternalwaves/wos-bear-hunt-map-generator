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

try {
    // Database connection is now configured automatically from environment variables
    $pdo = \App\Infrastructure\Database\DatabaseConnection::getConnection();
    
    echo "Starting initial database migration...\n";
    echo "Database: " . ($_ENV['DB_NAME'] ?? 'koinoni6_bh_staging') . "\n";
    echo "Host: " . ($_ENV['DB_HOST'] ?? 'localhost') . ":" . ($_ENV['DB_PORT'] ?? 3306) . "\n\n";
    
    // Create users table
    echo "Creating users table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
            email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_master BOOLEAN DEFAULT FALSE,
            is_approved BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            email_verified BOOLEAN DEFAULT FALSE,
            email_verification_token VARCHAR(255) NULL,
            password_reset_token VARCHAR(255) NULL,
            password_reset_expires TIMESTAMP NULL,
            otp_secret VARCHAR(255) NULL,
            otp_enabled BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_username (username),
            INDEX idx_approved (is_approved),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Users table created successfully\n\n";
    
    // Create maps table
    echo "Creating maps table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS maps (
            id VARCHAR(255) PRIMARY KEY,
            name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            cell_size INT NOT NULL DEFAULT 50,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Maps table created successfully\n\n";
    
    // Create map_versions table
    echo "Creating map_versions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS map_versions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            map_id VARCHAR(255) NOT NULL,
            version_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            weighted_criteria JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
            UNIQUE KEY unique_map_version (map_id, version_name),
            INDEX idx_map_id (map_id),
            INDEX idx_version_name (version_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Map versions table created successfully\n\n";
    
    // Create map_traps table
    echo "Creating map_traps table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS map_traps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            map_version_id INT NOT NULL,
            trap_id VARCHAR(255) NOT NULL,
            x INT NOT NULL,
            y INT NOT NULL,
            FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE,
            INDEX idx_map_version_id (map_version_id),
            INDEX idx_trap_id (trap_id),
            INDEX idx_position (x, y)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Map traps table created successfully\n\n";
    
    // Create map_misc_objects table
    echo "Creating map_misc_objects table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS map_misc_objects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            map_version_id INT NOT NULL,
            object_id VARCHAR(255) NOT NULL,
            x INT NOT NULL,
            y INT NOT NULL,
            size INT NOT NULL,
            name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE,
            INDEX idx_map_version_id (map_version_id),
            INDEX idx_object_id (object_id),
            INDEX idx_position (x, y)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Map misc objects table created successfully\n\n";
    
    // Create map_furnaces table with all gear and charm columns
    echo "Creating map_furnaces table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS map_furnaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            map_version_id INT NOT NULL,
            furnace_id VARCHAR(255) NOT NULL,
            name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            power BIGINT NOT NULL,
            rank VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            participation INT NULL,
            trap_pref VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            x INT NULL,
            y INT NULL,
            status VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
            locked BOOLEAN DEFAULT FALSE,
            -- Chief Gear Level columns
            cap_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            watch_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            vest_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            pants_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            ring_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            cane_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            -- Chief Gear Charm columns
            cap_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            watch_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            vest_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            pants_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            ring_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            cane_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE,
            INDEX idx_map_version_id (map_version_id),
            INDEX idx_furnace_id (furnace_id),
            INDEX idx_name (name),
            INDEX idx_level (level),
            INDEX idx_rank (rank),
            INDEX idx_position (x, y),
            INDEX idx_status (status),
            INDEX idx_locked (locked)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Map furnaces table created successfully\n\n";
    
    // Create indexes for better performance
    echo "Creating additional indexes...\n";
    
    // Add composite indexes for common queries (check if they exist first)
    $indexes = [
        'idx_furnaces_map_status' => 'map_furnaces(map_version_id, status)',
        'idx_furnaces_map_position' => 'map_furnaces(map_version_id, x, y)',
        'idx_traps_map_position' => 'map_traps(map_version_id, x, y)',
        'idx_misc_map_position' => 'map_misc_objects(map_version_id, x, y)'
    ];
    
    foreach ($indexes as $indexName => $indexDefinition) {
        try {
            // Check if index already exists
            $stmt = $pdo->query("SHOW INDEX FROM " . explode('(', $indexDefinition)[0] . " WHERE Key_name = '$indexName'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("CREATE INDEX $indexName ON $indexDefinition");
                echo "✓ Created index: $indexName\n";
            } else {
                echo "✓ Index $indexName already exists, skipping...\n";
            }
        } catch (Exception $e) {
            echo "Warning: Could not create index $indexName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Additional indexes processed successfully\n\n";
    
    // Verify all tables were created
    echo "Verifying table creation...\n";
    $tables = ['users', 'maps', 'map_versions', 'map_traps', 'map_misc_objects', 'map_furnaces'];
    $createdTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $createdTables[] = $table;
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' was not created\n";
        }
    }
    
    echo "\n";
    echo "Migration Summary:\n";
    echo "==================\n";
    echo "Total tables to create: " . count($tables) . "\n";
    echo "Tables successfully created: " . count($createdTables) . "\n";
    
    if (count($createdTables) === count($tables)) {
        echo "✓ All tables created successfully!\n";
        echo "\nDatabase schema is ready for use.\n";
    } else {
        echo "✗ Some tables failed to create. Please check the error messages above.\n";
        exit(1);
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 