<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Repository\DatabaseMapRepository;
use App\Application\Service\MapService;
use App\Application\Service\ExcelService;
use App\Application\Service\WeightedCriteriaService;

try {
    // Database connection is now configured automatically from environment variables
    
    // Set up services with proper dependency injection
    $mapRepository = new DatabaseMapRepository();
    $excelService = new ExcelService();
    $weightedCriteriaService = new WeightedCriteriaService();
    $mapService = new MapService($mapRepository, $excelService, $weightedCriteriaService);
    
    // JSON file path
    $jsonFile = __DIR__ . '/../storage/objects.json';
    
    if (!file_exists($jsonFile)) {
        echo "Error: objects.json file not found at {$jsonFile}\n";
        exit(1);
    }
    
    // Load JSON data
    $jsonData = json_decode(file_get_contents($jsonFile), true);
    
    if (!$jsonData) {
        echo "Error: Invalid JSON data in objects.json\n";
        exit(1);
    }
    
    // Create a new map
    $map = $mapService->createMap('Migrated Map', $jsonData['cellSize'] ?? 50);
    echo "Created map with ID: {$map->getId()}\n";
    
    // Migrate traps
    if (isset($jsonData['traps']) && is_array($jsonData['traps'])) {
        foreach ($jsonData['traps'] as $trapData) {
            try {
                $trap = $mapService->addTrap($map->getId(), $trapData['x'], $trapData['y']);
                echo "Migrated trap: {$trap->getId()}\n";
            } catch (\Exception $e) {
                echo "Warning: Failed to migrate trap at ({$trapData['x']}, {$trapData['y']}): {$e->getMessage()}\n";
            }
        }
    }
    
    // Migrate misc objects
    if (isset($jsonData['misc']) && is_array($jsonData['misc'])) {
        foreach ($jsonData['misc'] as $miscData) {
            try {
                $object = $mapService->addMiscObject(
                    $map->getId(),
                    $miscData['x'],
                    $miscData['y'],
                    $miscData['size'],
                    $miscData['name'] ?? ''
                );
                echo "Migrated misc object: {$object->getId()}\n";
            } catch (\Exception $e) {
                echo "Warning: Failed to migrate misc object at ({$miscData['x']}, {$miscData['y']}): {$e->getMessage()}\n";
            }
        }
    }
    
    // Migrate furnaces with gear/charm data
    if (isset($jsonData['furnaces']) && is_array($jsonData['furnaces'])) {
        foreach ($jsonData['furnaces'] as $furnaceData) {
            try {
                // Determine status based on whether coordinates are provided
                $status = (isset($furnaceData['x']) && isset($furnaceData['y'])) ? 'assigned' : '';
                
                $furnace = $mapService->addFurnace(
                    $map->getId(),
                    $furnaceData['name'],
                    $furnaceData['level'],
                    $furnaceData['power'],
                    $furnaceData['rank'],
                    $furnaceData['participation'] ?? null,
                    $furnaceData['trap_pref'],
                    $furnaceData['x'] ?? null,
                    $furnaceData['y'] ?? null,
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
                
                // Update status if it was not set correctly during creation
                if (isset($furnaceData['status']) && $furnaceData['status'] !== $status) {
                    $mapService->updateFurnaceStatus($map->getId(), $furnace->getId(), $furnaceData['status']);
                }
                
                // Update locked state if it exists
                if (isset($furnaceData['locked'])) {
                    $mapService->setFurnaceLocked($map->getId(), $furnace->getId(), $furnaceData['locked']);
                }
                
                echo "Migrated furnace: {$furnace->getName()}\n";
            } catch (\Exception $e) {
                echo "Warning: Failed to migrate furnace '{$furnaceData['name']}': {$e->getMessage()}\n";
            }
        }
    }
    
    echo "\nMigration completed successfully!\n";
    echo "Map ID: {$map->getId()}\n";
    echo "You can now use this map ID in your application.\n";
    
} catch (\Exception $e) {
    echo "Error during migration: {$e->getMessage()}\n";
    exit(1);
} 