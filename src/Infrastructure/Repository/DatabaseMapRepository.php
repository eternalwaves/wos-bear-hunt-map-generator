<?php

namespace App\Infrastructure\Repository;

use App\Domain\Repository\MapRepositoryInterface;
use App\Domain\Map\Map;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;

class DatabaseMapRepository implements MapRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getConnection();
        $this->createTables();
    }

    private function createTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS maps (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                cell_size INT NOT NULL DEFAULT 50,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS map_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_id VARCHAR(255) NOT NULL,
                version_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                weighted_criteria JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
                UNIQUE KEY unique_map_version (map_id, version_name)
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS map_traps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_version_id INT NOT NULL,
                trap_id VARCHAR(255) NOT NULL,
                x INT NOT NULL,
                y INT NOT NULL,
                FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS map_misc_objects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_version_id INT NOT NULL,
                object_id VARCHAR(255) NOT NULL,
                x INT NOT NULL,
                y INT NOT NULL,
                size INT NOT NULL,
                name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS map_furnaces (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_version_id INT NOT NULL,
                furnace_id VARCHAR(255) NOT NULL,
                name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                power BIGINT NOT NULL,
                rank VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                participation INT,
                trap_pref VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                x INT,
                y INT,
                status VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
                locked BOOLEAN DEFAULT FALSE,
                cap_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                watch_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                vest_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                pants_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                ring_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                cane_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                cap_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                watch_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                vest_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                pants_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                ring_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                cane_charms VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS map_occupied_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                map_version_id INT NOT NULL,
                position_key VARCHAR(50) NOT NULL,
                group_type VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                FOREIGN KEY (map_version_id) REFERENCES map_versions(id) ON DELETE CASCADE,
                UNIQUE KEY unique_position (map_version_id, position_key)
            )
        ");
    }

    public function save(Map $map): void
    {
        $this->pdo->beginTransaction();
        
        try {
            // Save the map data
            $this->saveMapData($map);
            
            // Get or create the current version
            $version = $map->getVersion() ?: '1.0';
            
            // Insert or update the version
            $versionStmt = $this->pdo->prepare("
                INSERT INTO map_versions (map_id, version_name, weighted_criteria) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    weighted_criteria = VALUES(weighted_criteria)
            ");
            
            $weightedCriteriaJson = $map->getWeightedCriteria() ? json_encode($map->getWeightedCriteria()) : null;
            
            $versionStmt->execute([$map->getId(), $version, $weightedCriteriaJson]);
            $mapVersionId = $this->pdo->lastInsertId();
            
            // If the version already existed, get its ID
            if ($mapVersionId == 0) {
                $getVersionStmt = $this->pdo->prepare("SELECT id FROM map_versions WHERE map_id = ? AND version_name = ?");
                $getVersionStmt->execute([$map->getId(), $version]);
                $mapVersionId = $getVersionStmt->fetchColumn();
            }
            
            // Save objects using the version ID
            $this->saveMapObjectsWithVersionId($map, $mapVersionId);
            $this->saveOccupiedPositionsWithVersionId($map, $mapVersionId);
            
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Repository save - Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function findById(string $id): ?Map
    {
        // Get the latest version for this map
        $stmt = $this->pdo->prepare("
            SELECT m.*, mv.version_name as version
            FROM maps m
            JOIN map_versions mv ON m.id = mv.map_id
            WHERE m.id = ? 
            ORDER BY mv.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $mapData = $stmt->fetch();

        if (!$mapData) {
            return null;
        }

        return $this->loadMapWithObjects($mapData);
    }

    public function findByName(string $name): ?Map
    {
        // Get the latest version for this map
        $stmt = $this->pdo->prepare("
            SELECT m.*, mv.version_name as version
            FROM maps m
            JOIN map_versions mv ON m.id = mv.map_id
            WHERE m.name = ? 
            ORDER BY mv.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$name]);
        $mapData = $stmt->fetch();

        if (!$mapData) {
            return null;
        }

        return $this->loadMapWithObjects($mapData);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT m.id, m.name, mv.version_name as latest_version
            FROM maps m
            JOIN map_versions mv ON m.id = mv.map_id
            WHERE mv.created_at = (
                SELECT MAX(mv2.created_at)
                FROM map_versions mv2
                WHERE mv2.map_id = m.id
            )
            ORDER BY m.id DESC
        ");
        
        $maps = [];
        while ($row = $stmt->fetch()) {
            $map = $this->findVersion($row['id'], $row['latest_version']);
            if ($map) {
                $maps[] = $map;
            }
        }
        
        return $maps;
    }

    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM maps WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function saveVersion(Map $map, string $version): void
    {
        $this->pdo->beginTransaction();
        
        try {
            // First, ensure the map exists in the maps table
            $stmt = $this->pdo->prepare("
                INSERT INTO maps (id, name, cell_size) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name), 
                    cell_size = VALUES(cell_size),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $map->getId(),
                $map->getName(),
                $map->getCellSize()
            ]);
            
            // Insert the version into map_versions table
            $versionStmt = $this->pdo->prepare("
                INSERT INTO map_versions (map_id, version_name, weighted_criteria) 
                VALUES (?, ?, ?)
            ");
            
            $weightedCriteriaJson = $map->getWeightedCriteria() ? json_encode($map->getWeightedCriteria()) : null;
            $versionStmt->execute([$map->getId(), $version, $weightedCriteriaJson]);
            $mapVersionId = $this->pdo->lastInsertId();
            
            // Set the version and save the current map state as a new version
            $map->setVersion($version);
            $this->saveMapObjectsWithVersionId($map, $mapVersionId);
            $this->saveOccupiedPositionsWithVersionId($map, $mapVersionId);
            
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findVersion(string $mapId, string $version): ?Map
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, mv.version_name as version
            FROM maps m
            JOIN map_versions mv ON m.id = mv.map_id
            WHERE m.id = ? AND mv.version_name = ?
        ");
        $stmt->execute([$mapId, $version]);
        $mapData = $stmt->fetch();

        if (!$mapData) {
            return null;
        }

        return $this->loadMapWithObjects($mapData);
    }

    public function getVersions(string $mapId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT version_name as version, created_at, weighted_criteria
            FROM map_versions 
            WHERE map_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$mapId]);
        $versions = $stmt->fetchAll();
        
        // Decode weighted criteria for each version
        foreach ($versions as &$version) {
            $version['weighted_criteria'] = $version['weighted_criteria'] ? json_decode($version['weighted_criteria'], true) : null;
        }
        
        return $versions;
    }

    public function deleteVersion(string $mapId, string $version): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM map_versions WHERE map_id = ? AND version_name = ?");
        return $stmt->execute([$mapId, $version]);
    }

    private function saveMapData(Map $map): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO maps (id, name, cell_size) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                cell_size = VALUES(cell_size),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $map->getId(),
            $map->getName(),
            $map->getCellSize()
        ]);
    }



    private function loadMapWithObjects(array $mapData): Map
    {
        // Get the map_version_id and weighted_criteria for this version
        $versionStmt = $this->pdo->prepare("SELECT id, weighted_criteria FROM map_versions WHERE map_id = ? AND version_name = ?");
        $versionStmt->execute([$mapData['id'], $mapData['version']]);
        $versionData = $versionStmt->fetch();
        
        if (!$versionData) {
            return new Map($mapData['name'], $mapData['version'], $mapData['id'], $mapData['cell_size']); // No version found, return empty map
        }
        
        $mapVersionId = $versionData['id'];
        $weightedCriteria = $versionData['weighted_criteria'] ? json_decode($versionData['weighted_criteria'], true) : null;

        $map = new Map(
            $mapData['name'],
            $mapData['version'],
            $mapData['id'],
            $mapData['cell_size'],
            $weightedCriteria
        );

        // Load traps
        $trapStmt = $this->pdo->prepare("SELECT * FROM map_traps WHERE map_version_id = ?");
        $trapStmt->execute([$mapVersionId]);
        while ($trapData = $trapStmt->fetch()) {
            $trap = new Trap($trapData['x'], $trapData['y'], $trapData['trap_id']);
            $map->addTrap($trap);
        }

        // Load misc objects
        $miscStmt = $this->pdo->prepare("SELECT * FROM map_misc_objects WHERE map_version_id = ?");
        $miscStmt->execute([$mapVersionId]);
        while ($miscData = $miscStmt->fetch()) {
            $object = new MiscObject($miscData['x'], $miscData['y'], $miscData['size'], $miscData['name'], $miscData['object_id']);
            $map->addMiscObject($object);
        }

        // Load furnaces
        $furnaceStmt = $this->pdo->prepare("SELECT * FROM map_furnaces WHERE map_version_id = ?");
        $furnaceStmt->execute([$mapVersionId]);
        while ($furnaceData = $furnaceStmt->fetch()) {
            $furnace = new Furnace(
                $furnaceData['name'],
                $furnaceData['level'],
                $furnaceData['power'],
                $furnaceData['rank'],
                $furnaceData['participation'],
                $furnaceData['trap_pref'],
                $furnaceData['x'],
                $furnaceData['y'],
                $furnaceData['furnace_id'],
                $furnaceData['status'],
                $furnaceData['locked'],
                $furnaceData['cap_level'] ?? null,
                $furnaceData['watch_level'] ?? null,
                $furnaceData['vest_level'] ?? null,
                $furnaceData['pants_level'] ?? null,
                $furnaceData['ring_level'] ?? null,
                $furnaceData['cane_level'] ?? null,
                $furnaceData['cap_charms'] ?? null,
                $furnaceData['watch_charms'] ?? null,
                $furnaceData['vest_charms'] ?? null,
                $furnaceData['pants_charms'] ?? null,
                $furnaceData['ring_charms'] ?? null,
                $furnaceData['cane_charms'] ?? null
            );
            $map->addFurnace($furnace);
        }

        return $map;
    }

    private function saveMapObjectsWithVersionId(Map $map, int $mapVersionId): void
    {
        // Clear existing objects for this version
        $this->pdo->prepare("DELETE FROM map_traps WHERE map_version_id = ?")->execute([$mapVersionId]);
        $this->pdo->prepare("DELETE FROM map_misc_objects WHERE map_version_id = ?")->execute([$mapVersionId]);
        $this->pdo->prepare("DELETE FROM map_furnaces WHERE map_version_id = ?")->execute([$mapVersionId]);

        // Save traps
        $trapStmt = $this->pdo->prepare("
            INSERT INTO map_traps (map_version_id, trap_id, x, y) 
            VALUES (?, ?, ?, ?)
        ");
        foreach ($map->getTraps() as $trap) {
            $trapStmt->execute([$mapVersionId, $trap->getId(), $trap->getX(), $trap->getY()]);
        }

        // Save misc objects
        $miscStmt = $this->pdo->prepare("
            INSERT INTO map_misc_objects (map_version_id, object_id, x, y, size, name) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($map->getMiscObjects() as $object) {
            $miscStmt->execute([$mapVersionId, $object->getId(), $object->getX(), $object->getY(), $object->getSize(), $object->getName()]);
        }

        // Save furnaces
        $furnaceStmt = $this->pdo->prepare("
            INSERT INTO map_furnaces (map_version_id, furnace_id, name, level, power, rank, participation, trap_pref, x, y, status, locked, cap_level, watch_level, vest_level, pants_level, ring_level, cane_level, cap_charms, watch_charms, vest_charms, pants_charms, ring_charms, cane_charms) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($map->getFurnaces() as $furnace) {
            $furnaceStmt->execute([
                $mapVersionId, $furnace->getId(), $furnace->getName(), 
                $furnace->getLevel(), $furnace->getPower(), $furnace->getRank(), 
                $furnace->getParticipation(), $furnace->getTrapPref(), 
                $furnace->getX(), $furnace->getY(), $furnace->getStatus(), $furnace->isLocked(),
                $furnace->getCapLevel(), $furnace->getWatchLevel(), $furnace->getVestLevel(),
                $furnace->getPantsLevel(), $furnace->getRingLevel(), $furnace->getCaneLevel(),
                $furnace->getCapCharms(), $furnace->getWatchCharms(), $furnace->getVestCharms(),
                $furnace->getPantsCharms(), $furnace->getRingCharms(), $furnace->getCaneCharms()
            ]);
        }
    }

    private function saveOccupiedPositionsWithVersionId(Map $map, int $mapVersionId): void
    {
        // Clear existing positions for this version
        $this->pdo->prepare("DELETE FROM map_occupied_positions WHERE map_version_id = ?")->execute([$mapVersionId]);

        // Save occupied positions
        $stmt = $this->pdo->prepare("
            INSERT INTO map_occupied_positions (map_version_id, position_key, group_type) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($map->getOccupiedPositions() as $position => $group) {
            $stmt->execute([$mapVersionId, $position, $group]);
        }
    }
} 