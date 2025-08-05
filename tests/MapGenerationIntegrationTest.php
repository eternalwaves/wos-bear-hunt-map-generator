<?php

use PHPUnit\Framework\TestCase;
use App\Application\Service\MapService;
use App\Infrastructure\Repository\DatabaseMapRepository;
use App\Application\Service\ExcelService;
use App\Application\Service\WeightedCriteriaService;
use App\Domain\Map\Map;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;
use App\Application\Exception\ValidationException;
use App\Application\Exception\MapNotFoundException;

class MapGenerationIntegrationTest extends TestCase
{
    private MapService $mapService;
    private DatabaseMapRepository $mapRepository;
    private WeightedCriteriaService $weightedCriteriaService;

    protected function setUp(): void
    {
        $this->mapRepository = new DatabaseMapRepository();
        $excelService = new ExcelService();
        $this->weightedCriteriaService = new WeightedCriteriaService();
        $this->mapService = new MapService($this->mapRepository, $excelService, $this->weightedCriteriaService);
    }

    public function testEndToEndMapGenerationWithVersion()
    {
        // Create a new map
        $map = $this->mapService->createMap("Integration Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add furnaces with different characteristics
        $furnace1 = $this->mapService->addFurnace($mapId, "High Priority", "FC1", 1000, "R1", 2, "1");
        $furnace2 = $this->mapService->addFurnace($mapId, "Medium Priority", "FC2", 500, "R2", 1, "2");
        $furnace3 = $this->mapService->addFurnace($mapId, "Low Priority", "FC3", 200, "R3", 3, "both");

        // Save a version
        $this->mapService->saveVersion($mapId, "1.0");

        // Generate map with weighted criteria
        $criteriaWeights = [
            ['criteria' => 'power', 'weight' => 3.0],
            ['criteria' => 'level', 'weight' => 1.0],
            ['criteria' => 'rank', 'weight' => 1.0],
            ['criteria' => 'participation', 'weight' => 1.0]
        ];

        $this->mapService->generateMap($mapId, ['power', 'level', 'rank', 'participation'], $criteriaWeights, "1.0");

        // Retrieve the generated map
        $generatedMap = $this->mapService->getVersion($mapId, "1.0");

        // Verify the map has the correct version
        $this->assertEquals("1.0", $generatedMap->getVersion());

        // Verify weighted criteria was saved
        $this->assertNotNull($generatedMap->getWeightedCriteria());
        $this->assertEquals($criteriaWeights, $generatedMap->getWeightedCriteria());

        // Verify all furnaces are positioned
        $furnaces = $generatedMap->getFurnaces();
        $this->assertCount(3, $furnaces);

        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }

        // Verify first ring positioning logic
        $this->verifyFirstRingPositioning($generatedMap);
    }

    public function testFirstRingPositionAssignment()
    {
        // Create a new map
        $map = $this->mapService->createMap("First Ring Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add furnaces with single trap preferences
        $this->mapService->addFurnace($mapId, "Trap1 Furnace", "FC1", 100, "R1", 2, "1");
        $this->mapService->addFurnace($mapId, "Trap2 Furnace", "FC2", 150, "R2", 1, "2");

        // Generate map
        $this->mapService->generateMap($mapId);

        // Retrieve the generated map
        $generatedMap = $this->mapService->getMap($mapId);

        // Verify first ring positioning
        $this->verifyFirstRingPositioning($generatedMap);
    }

    public function testWeightedCriteriaSortingInMapGeneration()
    {
        // Create a new map
        $map = $this->mapService->createMap("Weighted Criteria Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add furnaces with different power levels
        $this->mapService->addFurnace($mapId, "Low Power", "FC1", 100, "R1", 2, "1");
        $this->mapService->addFurnace($mapId, "High Power", "FC2", 1000, "R2", 1, "1");
        $this->mapService->addFurnace($mapId, "Medium Power", "FC3", 500, "R3", 3, "1");

        // Define weighted criteria that heavily prioritizes power
        $criteriaWeights = [
            ['criteria' => 'power', 'weight' => 10.0],
            ['criteria' => 'level', 'weight' => 1.0],
            ['criteria' => 'rank', 'weight' => 1.0],
            ['criteria' => 'participation', 'weight' => 1.0]
        ];

        // Generate map with weighted criteria
        $this->mapService->generateMap($mapId, ['power', 'level', 'rank', 'participation'], $criteriaWeights);

        // Retrieve the generated map
        $generatedMap = $this->mapService->getMap($mapId);

        // Verify weighted criteria was applied
        $this->assertNotNull($generatedMap->getWeightedCriteria());
        $this->assertEquals($criteriaWeights, $generatedMap->getWeightedCriteria());

        // Verify all furnaces are positioned
        $furnaces = $generatedMap->getFurnaces();
        $this->assertCount(3, $furnaces);

        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }

    public function testVersionSpecificMapGenerationAndRetrieval()
    {
        // Create a new map
        $map = $this->mapService->createMap("Version Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add furnaces
        $this->mapService->addFurnace($mapId, "Test Furnace", "FC1", 100, "R1", 2, "1");

        // Save initial version
        $this->mapService->saveVersion($mapId, "1.0");

        // Generate map for version 1.0
        $this->mapService->generateMap($mapId, ['power', 'level', 'rank', 'participation'], null, "1.0");

        // Save another version
        $this->mapService->saveVersion($mapId, "2.0");

        // Generate map for version 2.0 with different criteria
        $criteriaWeights = [
            ['criteria' => 'power', 'weight' => 2.0],
            ['criteria' => 'level', 'weight' => 1.0],
            ['criteria' => 'rank', 'weight' => 1.0],
            ['criteria' => 'participation', 'weight' => 1.0]
        ];
        $this->mapService->generateMap($mapId, ['power', 'level', 'rank', 'participation'], $criteriaWeights, "2.0");

        // Retrieve both versions
        $version1 = $this->mapService->getVersion($mapId, "1.0");
        $version2 = $this->mapService->getVersion($mapId, "2.0");

        // Verify versions are different
        $this->assertEquals("1.0", $version1->getVersion());
        $this->assertEquals("2.0", $version2->getVersion());

        // Verify version 1 has no weighted criteria
        $this->assertNull($version1->getWeightedCriteria());

        // Verify version 2 has weighted criteria
        $this->assertNotNull($version2->getWeightedCriteria());
        $this->assertEquals($criteriaWeights, $version2->getWeightedCriteria());

        // Verify both versions have positioned furnaces
        $furnaces1 = $version1->getFurnaces();
        $furnaces2 = $version2->getFurnaces();

        $this->assertCount(1, $furnaces1);
        $this->assertCount(1, $furnaces2);

        foreach ($furnaces1 as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }

        foreach ($furnaces2 as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }

    public function testNonSingleTrapPreferenceExclusion()
    {
        // Create a new map
        $map = $this->mapService->createMap("Exclusion Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add furnaces with different trap preferences
        $this->mapService->addFurnace($mapId, "Single Trap", "FC1", 100, "R1", 2, "1"); // Should be in first ring
        $this->mapService->addFurnace($mapId, "Both Traps", "FC2", 150, "R2", 1, "both"); // Should be excluded from first ring
        $this->mapService->addFurnace($mapId, "No Preference", "FC3", 200, "R3", 3, "n/a"); // Should be excluded from first ring

        // Generate map
        $this->mapService->generateMap($mapId);

        // Retrieve the generated map
        $generatedMap = $this->mapService->getMap($mapId);

        // Verify exclusion logic
        $this->verifyFirstRingExclusion($generatedMap);
    }

    public function testLockedFurnacePositionPreservationInVersion()
    {
        // Create a new map
        $map = $this->mapService->createMap("Locked Furnace Test Map", 50);
        $mapId = $map->getId();

        // Add traps
        $this->mapService->addTrap($mapId, 10, 20);
        $this->mapService->addTrap($mapId, 30, 40);

        // Add a furnace and lock it at a specific position
        $furnace = $this->mapService->addFurnace($mapId, "Locked Furnace", "FC1", 100, "R1", 2, "1", 15, 25);
        $this->mapService->setFurnaceLocked($mapId, $furnace->getId(), true);

        // Save version
        $this->mapService->saveVersion($mapId, "1.0");

        // Generate map
        $this->mapService->generateMap($mapId, ['power', 'level', 'rank', 'participation'], null, "1.0");

        // Retrieve the generated map
        $generatedMap = $this->mapService->getVersion($mapId, "1.0");

        // Find the locked furnace
        $lockedFurnace = null;
        foreach ($generatedMap->getFurnaces() as $furnace) {
            if ($furnace->getName() === "Locked Furnace") {
                $lockedFurnace = $furnace;
                break;
            }
        }

        // Verify locked furnace kept its position
        $this->assertNotNull($lockedFurnace);
        $this->assertTrue($lockedFurnace->isLocked());
        $this->assertEquals(15, $lockedFurnace->getX());
        $this->assertEquals(25, $lockedFurnace->getY());
    }

    private function verifyFirstRingPositioning(Map $map): void
    {
        $traps = $map->getTraps();
        $this->assertCount(2, $traps);

        foreach ($map->getFurnaces() as $furnace) {
            if ($furnace->getTrapPref() === '1' || $furnace->getTrapPref() === '2') {
                $trapIndex = ($furnace->getTrapPref() === '1') ? 0 : 1;
                $trap = $traps[$trapIndex];
                
                $trapCenterX = $trap->getX() + 1; // Center of 3x3 trap
                $trapCenterY = $trap->getY() + 1;
                
                $distanceX = abs($furnace->getX() - $trapCenterX);
                $distanceY = abs($furnace->getY() - $trapCenterY);
                
                // Should be in first ring area (distance ≤ 4)
                $this->assertLessThanOrEqual(4, $distanceX, "Furnace {$furnace->getName()} should be in first ring X distance");
                $this->assertLessThanOrEqual(4, $distanceY, "Furnace {$furnace->getName()} should be in first ring Y distance");
            }
        }
    }

    private function verifyFirstRingExclusion(Map $map): void
    {
        $traps = $map->getTraps();
        $this->assertCount(2, $traps);

        foreach ($map->getFurnaces() as $furnace) {
            if ($furnace->getTrapPref() === 'both' || $furnace->getTrapPref() === 'n/a') {
                // These should be placed in center area, not first ring
                $trap1CenterX = $traps[0]->getX() + 1;
                $trap1CenterY = $traps[0]->getY() + 1;
                $trap2CenterX = $traps[1]->getX() + 1;
                $trap2CenterY = $traps[1]->getY() + 1;
                
                // Calculate distance to both traps
                $distanceToTrap1 = sqrt(pow($furnace->getX() - $trap1CenterX, 2) + pow($furnace->getY() - $trap1CenterY, 2));
                $distanceToTrap2 = sqrt(pow($furnace->getX() - $trap2CenterX, 2) + pow($furnace->getY() - $trap2CenterY, 2));
                
                // Should be further than first ring distance (4 units)
                $this->assertGreaterThan(4, $distanceToTrap1, "Furnace {$furnace->getName()} should be excluded from first ring of trap 1");
                $this->assertGreaterThan(4, $distanceToTrap2, "Furnace {$furnace->getName()} should be excluded from first ring of trap 2");
            }
        }
    }
} 