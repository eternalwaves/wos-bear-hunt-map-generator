<?php

namespace App\Domain\Map;

use App\Domain\MapObject\MapObject;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;
use App\Application\Exception\ValidationException;

class Map
{
    private string $id;
    private string $name;
    private string $version;
    private array $traps = [];
    private array $miscObjects = [];
    private array $furnaces = [];
    private array $occupiedPositions = [];
    private int $cellSize;
    private ?array $weightedCriteria = null;

    public function __construct(
        string $name,
        string $version = '1.0',
        string $id = null,
        int $cellSize = 50,
        ?array $weightedCriteria = null
    ) {
        $this->id = $id ?? uniqid('map_', true);
        $this->name = $name;
        $this->version = $version;
        $this->cellSize = $cellSize;
        $this->weightedCriteria = $weightedCriteria;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getCellSize(): int
    {
        return $this->cellSize;
    }

    public function setCellSize(int $cellSize): void
    {
        $this->cellSize = $cellSize;
    }

    public function getWeightedCriteria(): ?array
    {
        return $this->weightedCriteria;
    }

    public function setWeightedCriteria(?array $weightedCriteria): void
    {
        // Convert CriteriaWeight objects to arrays if needed
        if ($weightedCriteria) {
            $convertedCriteria = [];
            foreach ($weightedCriteria as $criteria) {
                if ($criteria instanceof \App\Domain\Map\CriteriaWeight) {
                    $convertedCriteria[] = $criteria->toArray();
                } else {
                    $convertedCriteria[] = $criteria;
                }
            }
            $this->weightedCriteria = $convertedCriteria;
        } else {
            $this->weightedCriteria = null;
        }
    }

    // Trap management
    public function addTrap(Trap $trap): void
    {
        if (!$this->isPositionFree($trap->getX(), $trap->getY(), $trap->getSize())) {
            error_log("Warning: Trap at ({$trap->getX()}, {$trap->getY()}) conflicts with existing occupied position during map loading");
            // Don't throw exception during loading - just log the conflict and continue
        }
        
        $this->traps[] = $trap;
        $this->markPositionOccupied($trap, 'trap');
    }

    public function removeTrap(string $trapId): bool
    {
        foreach ($this->traps as $index => $trap) {
            if ($trap->getId() === $trapId) {
                $this->markPositionFree($trap);
                array_splice($this->traps, $index, 1);
                return true;
            }
        }
        return false;
    }

    public function getTraps(): array
    {
        return $this->traps;
    }

    public function hasTrap(Trap $trap): bool
    {
        return in_array($trap, $this->traps, true);
    }

    // Misc object management
    public function addMiscObject(MiscObject $object): void
    {
        if (!$this->isPositionFree($object->getX(), $object->getY(), $object->getSize())) {
            error_log("Warning: Misc object at ({$object->getX()}, {$object->getY()}) conflicts with existing occupied position during map loading");
            // Don't throw exception during loading - just log the conflict and continue
        }
        
        $this->miscObjects[] = $object;
        $this->markPositionOccupied($object, 'misc');
    }

    public function removeMiscObject(string $objectId): bool
    {
        foreach ($this->miscObjects as $index => $object) {
            if ($object->getId() === $objectId) {
                $this->markPositionFree($object);
                array_splice($this->miscObjects, $index, 1);
                return true;
            }
        }
        return false;
    }

    public function getMiscObjects(): array
    {
        return $this->miscObjects;
    }

    public function hasMiscObject(MiscObject $object): bool
    {
        return in_array($object, $this->miscObjects, true);
    }

    // Furnace management
    public function addFurnace(Furnace $furnace): void
    {
        if ($furnace->hasPosition()) {
            if (!$this->isPositionFree($furnace->getX(), $furnace->getY(), $furnace->getSize())) {
                error_log("Warning: Furnace at ({$furnace->getX()}, {$furnace->getY()}) conflicts with existing occupied position during map loading");
                // Don't throw exception during loading - just log the conflict and continue
            }
        }
        
        $this->furnaces[] = $furnace;
        if ($furnace->hasPosition()) {
            $this->markPositionOccupied($furnace, $furnace->getTrapPref());
        }
    }

    public function removeFurnace(string $furnaceId): bool
    {
        foreach ($this->furnaces as $index => $furnace) {
            if ($furnace->getId() === $furnaceId) {
                if ($furnace->hasPosition()) {
                    $this->markPositionFree($furnace);
                }
                array_splice($this->furnaces, $index, 1);
                return true;
            }
        }
        return false;
    }

    public function getFurnaces(): array
    {
        return $this->furnaces;
    }

    public function setFurnaces(array $furnaces): void
    {
        // Clear existing occupied positions for furnaces
        foreach ($this->furnaces as $furnace) {
            $this->markPositionFree($furnace);
        }
        
        $this->furnaces = $furnaces;
        
        // Mark new positions as occupied
        foreach ($this->furnaces as $furnace) {
            if ($furnace->getX() && $furnace->getY()) {
                $this->markPositionOccupied($furnace, $furnace->getTrapPref());
            }
        }
    }

    public function hasFurnace(Furnace $furnace): bool
    {
        return in_array($furnace, $this->furnaces, true);
    }

    public function updateFurnace(Furnace $updatedFurnace): bool
    {
        foreach ($this->furnaces as &$furnace) {
            if ($furnace->getId() === $updatedFurnace->getId()) {
                // Free old position if it exists
                if ($furnace->hasPosition()) {
                    $this->markPositionFree($furnace);
                }
                
                // Check if new position is available
                if ($updatedFurnace->hasPosition()) {
                    if (!$this->isPositionFree($updatedFurnace->getX(), $updatedFurnace->getY(), $updatedFurnace->getSize())) {
                        throw new ValidationException("Position ({$updatedFurnace->getX()}, {$updatedFurnace->getY()}) is already occupied");
                    }
                }
                
                // Update furnace data
                $furnace = $updatedFurnace;
                
                // Automatically set status based on whether coordinates are provided
                if ($furnace->hasPosition()) {
                    $furnace->setStatus('assigned');
                } else {
                    $furnace->setStatus('');
                }
                
                // Mark new position as occupied if it exists
                if ($furnace->hasPosition()) {
                    $this->markPositionOccupied($furnace, $furnace->getTrapPref());
                }
                
                return true;
            }
        }
        return false;
    }

    /**
     * Bulk update furnaces with collision detection
     * This method validates all final positions before making any changes
     */
    public function bulkUpdateFurnaces(array $furnaceUpdates): void
    {
        // First, create a temporary map to simulate all changes
        $tempOccupied = $this->occupiedPositions;
        
        // Remove positions of furnaces being updated
        foreach ($furnaceUpdates as $update) {
            $furnaceId = $update['id'];
            foreach ($this->furnaces as $furnace) {
                if ($furnace->getId() === $furnaceId && $furnace->hasPosition()) {
                    $this->markPositionFreeInArray($furnace, $tempOccupied);
                    break;
                }
            }
        }
        
        // Check if new positions are available
        foreach ($furnaceUpdates as $update) {
            if (isset($update['x']) && isset($update['y'])) {
                $x = (int)$update['x'];
                $y = (int)$update['y'];
                $size = 2; // Furnaces are always 2x2
                
                if (!$this->isPositionFreeInArray($x, $y, $size, $tempOccupied)) {
                    throw new ValidationException("Position ({$x}, {$y}) is already occupied");
                }
                
                // Mark as occupied in temp array
                $this->markPositionOccupiedInArray($x, $y, $size, $tempOccupied, $update['trap_pref'] ?? 'both');
            }
        }
        
        // If we get here, all positions are valid, so apply the updates
        foreach ($furnaceUpdates as $update) {
            $furnaceId = $update['id'];
            foreach ($this->furnaces as &$furnace) {
                if ($furnace->getId() === $furnaceId) {
                    // Free old position
                    if ($furnace->hasPosition()) {
                        $this->markPositionFree($furnace);
                    }
                    
                    // Preserve existing chief gear data if not provided in update
                    $capLevel = $update['cap_level'] ?? $furnace->getCapLevel();
                    $watchLevel = $update['watch_level'] ?? $furnace->getWatchLevel();
                    $vestLevel = $update['vest_level'] ?? $furnace->getVestLevel();
                    $pantsLevel = $update['pants_level'] ?? $furnace->getPantsLevel();
                    $ringLevel = $update['ring_level'] ?? $furnace->getRingLevel();
                    $caneLevel = $update['cane_level'] ?? $furnace->getCaneLevel();
                    $capCharms = $update['cap_charms'] ?? $furnace->getCapCharms();
                    $watchCharms = $update['watch_charms'] ?? $furnace->getWatchCharms();
                    $vestCharms = $update['vest_charms'] ?? $furnace->getVestCharms();
                    $pantsCharms = $update['pants_charms'] ?? $furnace->getPantsCharms();
                    $ringCharms = $update['ring_charms'] ?? $furnace->getRingCharms();
                    $caneCharms = $update['cane_charms'] ?? $furnace->getCaneCharms();
                    
                    // Update furnace data
                    $furnace = new Furnace(
                        $update['name'],
                        $update['level'],
                        (int)$update['power'],
                        $update['rank'],
                        isset($update['participation']) ? (int)$update['participation'] : null,
                        $update['trap_pref'],
                        isset($update['x']) ? (int)$update['x'] : null,
                        isset($update['y']) ? (int)$update['y'] : null,
                        $furnaceId,
                        $update['status'] ?? $furnace->getStatus(),
                        $furnace->isLocked(),
                        $capLevel,
                        $watchLevel,
                        $vestLevel,
                        $pantsLevel,
                        $ringLevel,
                        $caneLevel,
                        $capCharms,
                        $watchCharms,
                        $vestCharms,
                        $pantsCharms,
                        $ringCharms,
                        $caneCharms
                    );
                    
                    // Automatically set status based on whether coordinates are provided
                    if ($furnace->hasPosition()) {
                        $furnace->setStatus('assigned');
                    } else {
                        $furnace->setStatus('');
                    }
                    
                    // Mark new position as occupied
                    if ($furnace->hasPosition()) {
                        $this->markPositionOccupied($furnace, $furnace->getTrapPref());
                    }
                    
                    break;
                }
            }
        }
    }

    // Position management
    public function isPositionFree(int $x, int $y, int $size): bool
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $posX = $x + $dx;
                $posY = $y + $dy;
                $key = "$posX,$posY";
                
                if (isset($this->occupiedPositions[$key])) {
                    error_log("Position conflict detected: ($posX, $posY) is occupied by '{$this->occupiedPositions[$key]}'");
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if position is free in a given occupied positions array
     */
    private function isPositionFreeInArray(int $x, int $y, int $size, array $occupiedPositions): bool
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $key = ($x + $dx) . ',' . ($y + $dy);
                if (isset($occupiedPositions[$key])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function markPositionOccupied(MapObject $object, $group = true): void
    {
        foreach ($object->getOccupiedCoordinates() as $coord) {
            $key = $coord['x'] . ',' . $coord['y'];
            $this->occupiedPositions[$key] = $group;
        }
    }

    private function markPositionFree(MapObject $object): void
    {
        foreach ($object->getOccupiedCoordinates() as $coord) {
            unset($this->occupiedPositions[$coord['x'] . ',' . $coord['y']]);
        }
    }

    /**
     * Mark position as free in a given occupied positions array
     */
    private function markPositionFreeInArray(MapObject $object, array &$occupiedPositions): void
    {
        foreach ($object->getOccupiedCoordinates() as $coord) {
            unset($occupiedPositions[$coord['x'] . ',' . $coord['y']]);
        }
    }

    /**
     * Mark position as occupied in a given occupied positions array
     */
    private function markPositionOccupiedInArray(int $x, int $y, int $size, array &$occupiedPositions, $group = true): void
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $occupiedPositions[($x + $dx) . ',' . ($y + $dy)] = $group;
            }
        }
    }

    public function getOccupiedPositions(): array
    {
        return $this->occupiedPositions;
    }

    /**
     * Get collision information for a position
     */
    public function getCollisionInfo(int $x, int $y, int $size): array
    {
        $collisions = [];
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $key = ($x + $dx) . ',' . ($y + $dy);
                if (isset($this->occupiedPositions[$key])) {
                    $collisions[] = [
                        'x' => $x + $dx,
                        'y' => $y + $dy,
                        'occupied_by' => $this->occupiedPositions[$key]
                    ];
                }
            }
        }
        return $collisions;
    }

    // Data export
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'traps' => array_map(fn($trap) => $trap->toArray(), $this->traps),
            'furnaces' => array_map(fn($furnace) => $furnace->toArray(), $this->furnaces),
            'misc' => array_map(fn($object) => $object->toArray(), $this->miscObjects),
            'occupied' => $this->occupiedPositions,
            'cellSize' => $this->cellSize,
            'weightedCriteria' => $this->weightedCriteria
        ];
    }

    public function reset(): void
    {
        $this->traps = [];
        $this->furnaces = [];
        $this->miscObjects = [];
        $this->occupiedPositions = [];
    }

    public function resetFurnaces(): void
    {
        // Remove furnace positions from occupied
        foreach ($this->furnaces as $furnace) {
            if ($furnace->hasPosition()) {
                $this->markPositionFree($furnace);
            }
        }
        
        // Keep only traps and misc objects in occupied positions
        $this->occupiedPositions = array_filter(
            $this->occupiedPositions,
            fn($group) => $group === 'trap' || $group === 'misc'
        );
        
        $this->furnaces = [];
    }
} 