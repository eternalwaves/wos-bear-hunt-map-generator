<?php

namespace App\Application\Service;

use App\Application\Exception\MapNotFoundException;
use App\Application\Exception\ValidationException;
use App\Domain\Map\Map;
use App\Domain\Map\MapGenerator;
use App\Domain\Map\SvgGenerator;
use App\Domain\MapObject\Furnace;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Trap;
use App\Domain\Repository\MapRepositoryInterface;
use App\Application\Service\ExcelService;
use App\Application\Service\WeightedCriteriaService;

class MapService
{
    private MapRepositoryInterface $mapRepository;
    private ExcelService $excelService;
    private WeightedCriteriaService $weightedCriteriaService;

    public function __construct(MapRepositoryInterface $mapRepository, ExcelService $excelService, WeightedCriteriaService $weightedCriteriaService)
    {
        $this->mapRepository = $mapRepository;
        $this->excelService = $excelService;
        $this->weightedCriteriaService = $weightedCriteriaService;
    }

    public function createMap(string $name, int $cellSize = 50): Map
    {
        if (empty(trim($name))) {
            throw new ValidationException("Map name is required");
        }
        
        $map = new Map($name, '1.0', null, $cellSize);
        $this->mapRepository->save($map);
        return $map;
    }

    public function getMap(string $id): Map
    {
        $map = $this->mapRepository->findById($id);
        if (!$map) {
            throw new MapNotFoundException("Map with ID {$id} not found");
        }
        return $map;
    }

    public function getMapByName(string $name): Map
    {
        $map = $this->mapRepository->findByName($name);
        if (!$map) {
            throw new MapNotFoundException("Map with name '{$name}' not found");
        }
        return $map;
    }

    public function getAllMaps(): array
    {
        return $this->mapRepository->findAll();
    }

    public function deleteMap(string $id): bool
    {
        return $this->mapRepository->delete($id);
    }

    // Trap operations
    public function addTrap(string $mapId, int $x, int $y): Trap
    {
        $map = $this->getMap($mapId);
        
        if ($x < 0 || $y < 0) {
            throw new ValidationException("Invalid coordinates");
        }
        
        if (!$map->isPositionFree($x, $y, 3)) {
            throw new ValidationException("Position ({$x}, {$y}) is already occupied");
        }

        $trap = new Trap($x, $y);
        $map->addTrap($trap);
        $this->mapRepository->save($map);
        
        return $trap;
    }

    public function removeTrap(string $mapId, string $trapId, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        $removed = $map->removeTrap($trapId);
        
        if ($removed) {
            $this->mapRepository->save($map);
        }
        
        return $removed;
    }

    // Misc object operations
    public function addMiscObject(string $mapId, int $x, int $y, int $size, string $name = ''): MiscObject
    {
        $map = $this->getMap($mapId);
        
        if ($size < 1) {
            throw new ValidationException("Size must be at least 1");
        }
        
        if (!$map->isPositionFree($x, $y, $size)) {
            throw new ValidationException("Position ({$x}, {$y}) is already occupied");
        }

        $object = new MiscObject($x, $y, $size, $name);
        $map->addMiscObject($object);
        $this->mapRepository->save($map);
        
        return $object;
    }

    public function removeMiscObject(string $mapId, string $objectId, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        $removed = $map->removeMiscObject($objectId);
        
        if ($removed) {
            $this->mapRepository->save($map);
        }
        
        return $removed;
    }

    // Furnace operations
    public function addFurnace(
        string $mapId,
        string $name,
        string $level,
        int $power,
        string $rank,
        ?int $participation,
        string $trapPref,
        ?int $x = null,
        ?int $y = null,
        ?string $capLevel = null,
        ?string $watchLevel = null,
        ?string $vestLevel = null,
        ?string $pantsLevel = null,
        ?string $ringLevel = null,
        ?string $caneLevel = null,
        ?string $capCharms = null,
        ?string $watchCharms = null,
        ?string $vestCharms = null,
        ?string $pantsCharms = null,
        ?string $ringCharms = null,
        ?string $caneCharms = null
    ): Furnace {
        $map = $this->getMap($mapId);
        
        // Validate required fields
        if (empty(trim($name)) || empty(trim($level)) || empty(trim($rank))) {
            throw new ValidationException("Name, level, power, and rank are required");
        }
        
        if ($x !== null && $y !== null) {
            if (!$map->isPositionFree($x, $y, 2)) {
                throw new ValidationException("Position ({$x}, {$y}) is already occupied");
            }
        }

        $furnace = new Furnace(
            $name, $level, $power, $rank, $participation, $trapPref, $x, $y, null, '', false,
            $capLevel, $watchLevel, $vestLevel, $pantsLevel, $ringLevel, $caneLevel,
            $capCharms, $watchCharms, $vestCharms, $pantsCharms, $ringCharms, $caneCharms
        );
        $map->addFurnace($furnace);
        $this->mapRepository->save($map);
        
        return $furnace;
    }

    public function updateFurnace(string $mapId, array $furnaceData, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        // Get existing furnace to preserve chief gear data
        $existingFurnace = null;
        foreach ($map->getFurnaces() as $furnace) {
            if ($furnace->getId() === $furnaceData['id']) {
                $existingFurnace = $furnace;
                break;
            }
        }
        
        if (!$existingFurnace) {
            return false;
        }
        
        // Preserve existing chief gear data if not provided in update
        $capLevel = $furnaceData['cap_level'] ?? $existingFurnace->getCapLevel();
        $watchLevel = $furnaceData['watch_level'] ?? $existingFurnace->getWatchLevel();
        $vestLevel = $furnaceData['vest_level'] ?? $existingFurnace->getVestLevel();
        $pantsLevel = $furnaceData['pants_level'] ?? $existingFurnace->getPantsLevel();
        $ringLevel = $furnaceData['ring_level'] ?? $existingFurnace->getRingLevel();
        $caneLevel = $furnaceData['cane_level'] ?? $existingFurnace->getCaneLevel();
        $capCharms = $furnaceData['cap_charms'] ?? $existingFurnace->getCapCharms();
        $watchCharms = $furnaceData['watch_charms'] ?? $existingFurnace->getWatchCharms();
        $vestCharms = $furnaceData['vest_charms'] ?? $existingFurnace->getVestCharms();
        $pantsCharms = $furnaceData['pants_charms'] ?? $existingFurnace->getPantsCharms();
        $ringCharms = $furnaceData['ring_charms'] ?? $existingFurnace->getRingCharms();
        $caneCharms = $furnaceData['cane_charms'] ?? $existingFurnace->getCaneCharms();
        
        $furnace = new Furnace(
            $furnaceData['name'],
            $furnaceData['level'],
            $furnaceData['power'],
            $furnaceData['rank'],
            isset($furnaceData['participation']) ? (int)$furnaceData['participation'] : null,
            $furnaceData['trap_pref'],
            $furnaceData['x'] ?? null,
            $furnaceData['y'] ?? null,
            $furnaceData['id'],
            $furnaceData['status'] ?? '',
            $furnaceData['locked'] ?? false,
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

        $updated = $map->updateFurnace($furnace);
        
        if ($updated) {
            $this->mapRepository->save($map);
        }
        
        return $updated;
    }

    /**
     * Bulk update multiple furnaces with collision detection
     * This method validates all final positions before making any changes
     */
    public function bulkUpdateFurnaces(string $mapId, array $furnaceUpdates, string $version = null): void
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->mapRepository->findById($mapId);
        }
        
        if (!$map) {
            throw new ValidationException("Map not found: {$mapId}");
        }

        // Validate required fields for each furnace update
        foreach ($furnaceUpdates as $update) {
            $requiredFields = ['id', 'name', 'level', 'power', 'rank', 'participation', 'trap_pref'];
            foreach ($requiredFields as $field) {
                if (!isset($update[$field])) {
                    throw new ValidationException("Missing required field '{$field}' in furnace update");
                }
            }
            
            // X and Y coordinates are optional and can be null
            // Chief gear fields are optional and will be preserved from existing data if not provided
        }

        $map->bulkUpdateFurnaces($furnaceUpdates);
        $this->mapRepository->save($map);
    }

    public function generateMap(string $mapId, array $sortPriority = ['power', 'level', 'rank', 'participation'], ?array $criteriaWeights = null, ?string $version = null): void
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        // Convert criteria weights to CriteriaWeight instances if provided
        $convertedCriteriaWeights = null;
        if ($criteriaWeights) {
            $convertedCriteriaWeights = $this->weightedCriteriaService->createCriteriaWeightsFromArray($criteriaWeights);
        }
        
        // Save the weighted criteria to the map
        $map->setWeightedCriteria($convertedCriteriaWeights);
        
        // Inject WeightedCriteriaService if criteriaWeights are provided
        $mapGenerator = new MapGenerator($map, $convertedCriteriaWeights ? $this->weightedCriteriaService : null);
        $mapGenerator->generateMap($sortPriority, $convertedCriteriaWeights);

        $this->mapRepository->save($map);
    }

    public function generateSvg(string $mapId, string $version = null): string
    {
        $map = $this->mapRepository->findById($mapId);
        if (!$map) {
            throw new ValidationException("Map not found: {$mapId}");
        }

        if ($version) {
            $map = $this->mapRepository->findVersion($mapId, $version);
            if (!$map) {
                throw new ValidationException("Version not found: {$version}");
            }
        }

        $svgGenerator = new \App\Domain\Map\SvgGenerator($map);
        return $svgGenerator->generateSvg();
    }

    public function saveSvgToFile(string $mapId, string $version = null, string $filePath = null): bool
    {
        $svg = $this->generateSvg($mapId, $version);
        
        if (!$filePath) {
            $filePath = dirname(__DIR__, 3) . '/public/map.svg';
        }

        return file_put_contents($filePath, $svg) !== false;
    }

    public function getOccupiedPositions(string $mapId, string $version = null): array
    {
        $map = $this->mapRepository->findById($mapId);
        if (!$map) {
            throw new ValidationException("Map not found: {$mapId}");
        }

        if ($version) {
            $map = $this->mapRepository->findVersion($mapId, $version);
            if (!$map) {
                throw new ValidationException("Version not found: {$version}");
            }
        }

        $svgGenerator = new \App\Domain\Map\SvgGenerator($map);
        return $svgGenerator->getOccupiedPositions();
    }

    public function removeFurnace(string $mapId, string $furnaceId, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        $removed = $map->removeFurnace($furnaceId);
        
        if ($removed) {
            $this->mapRepository->save($map);
        }
        
        return $removed;
    }

    public function updateFurnaceStatus(string $mapId, string $furnaceId, string $status, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        foreach ($map->getFurnaces() as $furnace) {
            if ($furnace->getId() === $furnaceId) {
                $furnace->setStatus($status);
                $this->mapRepository->save($map);
                return true;
            }
        }
        
        return false;
    }

    public function setFurnaceLocked(string $mapId, string $furnaceId, bool $locked, string $version = null): bool
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        foreach ($map->getFurnaces() as $furnace) {
            if ($furnace->getId() === $furnaceId) {
                $furnace->setLocked($locked);
                $this->mapRepository->save($map);
                return true;
            }
        }
        
        return false;
    }

    public function resetFurnaces(string $mapId, string $version = null): void
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        $map->resetFurnaces();
        $this->mapRepository->save($map);
    }

    public function resetMap(string $mapId): void
    {
        $map = $this->getMap($mapId);
        $map->reset();
        $this->mapRepository->save($map);
    }

    // Versioning operations
    public function saveVersion(string $mapId, string $version): void
    {
        $map = $this->getMap($mapId);
        $this->mapRepository->saveVersion($map, $version);
    }

    public function getVersion(string $mapId, string $version): Map
    {
        $map = $this->mapRepository->findVersion($mapId, $version);
        if (!$map) {
            throw new MapNotFoundException("Map version {$version} not found for map {$mapId}");
        }
        return $map;
    }

    public function getVersions(string $mapId): array
    {
        return $this->mapRepository->getVersions($mapId);
    }

    public function deleteVersion(string $mapId, string $version): bool
    {
        return $this->mapRepository->deleteVersion($mapId, $version);
    }

    // Data export
    public function exportMapData(string $mapId, string $version = null): array
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        return [
            'traps' => array_map(fn($trap) => $trap->toArray(), $map->getTraps()),
            'misc' => array_map(fn($obj) => $obj->toArray(), $map->getMiscObjects()),
            'furnaces' => array_map(fn($furnace) => $furnace->toArray(), $map->getFurnaces()),
            'occupied' => $map->getOccupiedPositions(),
            'cellSize' => $map->getCellSize(),
            'weightedCriteria' => $map->getWeightedCriteria()
        ];
    }

    public function importExcel(string $mapId, string $filePath, string $fileName, string $version = null): void
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        
        if (!file_exists($filePath)) {
            throw new ValidationException("Excel file not found");
        }

        try {
            $furnaces = $this->excelService->importExcel($filePath, $fileName);
            
            // Phase 1: Identify existing furnaces that will be overwritten and collect new furnaces
            $furnaceIdsToOverwrite = [];
            $newFurnaces = [];
            
            foreach ($furnaces as $furnaceData) {
                if (!empty($furnaceData['id'])) {
                    $furnaceIdsToOverwrite[] = $furnaceData['id'];
                }
                $newFurnaces[] = $furnaceData;
            }
            
            // Phase 2: Create a temporary occupied positions array for validation
            $tempOccupied = $map->getOccupiedPositions();
            
            // Remove positions of furnaces that will be overwritten
            foreach ($furnaceIdsToOverwrite as $furnaceId) {
                foreach ($map->getFurnaces() as $furnace) {
                    if ($furnace->getId() === $furnaceId && $furnace->hasPosition()) {
                        // Mark the position as free in temp array
                        for ($dx = 0; $dx < $furnace->getSize(); $dx++) {
                            for ($dy = 0; $dy < $furnace->getSize(); $dy++) {
                                $key = ($furnace->getX() + $dx) . ',' . ($furnace->getY() + $dy);
                                unset($tempOccupied[$key]);
                            }
                        }
                        break;
                    }
                }
            }
            
            // Phase 3: Validate all new positions against the temporary occupied array
            foreach ($newFurnaces as $furnaceData) {
                if (isset($furnaceData['x']) && isset($furnaceData['y'])) {
                    $x = (int)$furnaceData['x'];
                    $y = (int)$furnaceData['y'];
                    $size = 2; // Furnaces are always 2x2
                    
                    // Check if position is available
                    for ($dx = 0; $dx < $size; $dx++) {
                        for ($dy = 0; $dy < $size; $dy++) {
                            $key = ($x + $dx) . ',' . ($y + $dy);
                            if (isset($tempOccupied[$key])) {
                                throw new ValidationException("Position ({$x}, {$y}) is already occupied");
                            }
                        }
                    }
                    
                    // Mark as occupied in temp array
                    for ($dx = 0; $dx < $size; $dx++) {
                        for ($dy = 0; $dy < $size; $dy++) {
                            $key = ($x + $dx) . ',' . ($y + $dy);
                            $tempOccupied[$key] = $furnaceData['trap_pref'] ?? 'both';
                        }
                    }
                }
            }
            
            // Phase 4: If we get here, no collisions occurred, so apply the changes
            // Remove existing furnaces that will be overwritten
            foreach ($furnaceIdsToOverwrite as $furnaceId) {
                $map->removeFurnace($furnaceId);
            }
            
            // Add all new furnaces
            foreach ($newFurnaces as $furnaceData) {
                // Determine status based on whether coordinates are provided
                $status = (isset($furnaceData['x']) && isset($furnaceData['y'])) ? 'assigned' : '';
                
                $furnace = new Furnace(
                    $furnaceData['name'],
                    $furnaceData['level'],
                    $furnaceData['power'],
                    $furnaceData['rank'],
                    $furnaceData['participation'],
                    $furnaceData['trap_pref'],
                    $furnaceData['x'],
                    $furnaceData['y'],
                    $furnaceData['id'],
                    $status,
                    false,
                    $furnaceData['cap_level'],
                    $furnaceData['watch_level'],
                    $furnaceData['vest_level'],
                    $furnaceData['pants_level'],
                    $furnaceData['ring_level'],
                    $furnaceData['cane_level'],
                    $furnaceData['cap_charms'],
                    $furnaceData['watch_charms'],
                    $furnaceData['vest_charms'],
                    $furnaceData['pants_charms'],
                    $furnaceData['ring_charms'],
                    $furnaceData['cane_charms']
                );
                $map->addFurnace($furnace);
            }
            
            $this->mapRepository->save($map);
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ValidationException("Error processing Excel file: " . $e->getMessage());
        }
    }

    public function exportFurnacesToExcel(string $mapId, string $version = null): string
    {
        if ($version) {
            $map = $this->getVersion($mapId, $version);
        } else {
            $map = $this->getMap($mapId);
        }
        $furnaces = $map->getFurnaces();
        
        $furnaceData = [];
        foreach ($furnaces as $furnace) {
            $furnaceData[] = [
                'id' => $furnace->getId(),
                'name' => $furnace->getName(),
                'level' => $furnace->getLevel(),
                'power' => $furnace->getPower(),
                'rank' => $furnace->getRank(),
                'participation' => $furnace->getParticipation(),
                'trap_pref' => $furnace->getTrapPref(),
                'x' => $furnace->getX(),
                'y' => $furnace->getY(),
                'cap_level' => $furnace->getCapLevel(),
                'watch_level' => $furnace->getWatchLevel(),
                'vest_level' => $furnace->getVestLevel(),
                'pants_level' => $furnace->getPantsLevel(),
                'ring_level' => $furnace->getRingLevel(),
                'cane_level' => $furnace->getCaneLevel(),
                'cap_charms' => $furnace->getCapCharms(),
                'watch_charms' => $furnace->getWatchCharms(),
                'vest_charms' => $furnace->getVestCharms(),
                'pants_charms' => $furnace->getPantsCharms(),
                'ring_charms' => $furnace->getRingCharms(),
                'cane_charms' => $furnace->getCaneCharms()
            ];
        }
        
        return $this->excelService->exportToExcel($furnaceData);
    }

    public function generateTemplateExcel(string $mapId): string
    {
        return $this->excelService->generateTemplateXls();
    }
} 