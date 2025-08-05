<?php

use PHPUnit\Framework\TestCase;
use App\Domain\Map\MapGenerator;
use App\Domain\Map\Map;
use App\Domain\Map\CriteriaWeight;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;
use App\Application\Service\WeightedCriteriaService;
use App\Application\Exception\ValidationException;

class MapGeneratorTest extends TestCase
{
    private Map $map;
    private MapGenerator $mapGenerator;
    private WeightedCriteriaService $weightedCriteriaService;

    protected function setUp(): void
    {
        $this->map = new Map("Test Map", "1.0", "test_map_123");
        
        // Add two traps for map generation
        $trap1 = new Trap(10, 20);
        $trap2 = new Trap(30, 40);
        $this->map->addTrap($trap1);
        $this->map->addTrap($trap2);
        
        $this->weightedCriteriaService = new WeightedCriteriaService();
        $this->mapGenerator = new MapGenerator($this->map, $this->weightedCriteriaService);
    }

    public function testFirstRingPositionGeneration()
    {
        // Add furnaces with single trap preferences
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "1", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "2", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);

        $this->mapGenerator->generateMap();

        // Verify that furnaces are placed in first ring positions
        $furnaces = $this->map->getFurnaces();
        $this->assertCount(2, $furnaces);

        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
            
            // Check if position is in first ring (distance 2 from trap center)
            $trapId = $furnace->getTrapPref();
            $trapIndex = ($trapId === '1') ? 0 : 1;
            $traps = $this->map->getTraps();
            $trap = $traps[$trapIndex];
            
            $trapCenterX = $trap->getX() + 1; // Center of 3x3 trap
            $trapCenterY = $trap->getY() + 1;
            
            $distanceX = abs($furnace->getX() - $trapCenterX);
            $distanceY = abs($furnace->getY() - $trapCenterY);
            
            // First ring positions should be at distance 2 (X±4, Y±4, X±2, Y±2)
            $this->assertLessThanOrEqual(4, $distanceX);
            $this->assertLessThanOrEqual(4, $distanceY);
        }
    }

    public function testFirstRingExclusionForNonSingleTrapPreferences()
    {
        // Add furnaces with non-single trap preferences
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "n/a", null, null, null, 'assigned', false);
        $furnace3 = new Furnace("Furnace 3", "FC3", 200, "R3", 3, "1", null, null, null, 'assigned', false); // Single trap preference
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);
        $this->map->addFurnace($furnace3);

        $this->mapGenerator->generateMap();

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(3, $furnaces);

        // Check that furnaces with non-single trap preferences are not in first ring
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
            
            if ($furnace->getTrapPref() === 'both' || $furnace->getTrapPref() === 'n/a') {
                // These should be placed in center area, not first ring
                $traps = $this->map->getTraps();
                $trap1CenterX = $traps[0]->getX() + 1;
                $trap1CenterY = $traps[0]->getY() + 1;
                $trap2CenterX = $traps[1]->getX() + 1;
                $trap2CenterY = $traps[1]->getY() + 1;
                
                // Calculate distance to both traps
                $distanceToTrap1 = sqrt(pow($furnace->getX() - $trap1CenterX, 2) + pow($furnace->getY() - $trap1CenterY, 2));
                $distanceToTrap2 = sqrt(pow($furnace->getX() - $trap2CenterX, 2) + pow($furnace->getY() - $trap2CenterY, 2));
                
                // Should be further than first ring distance (4 units)
                $this->assertGreaterThan(4, $distanceToTrap1);
                $this->assertGreaterThan(4, $distanceToTrap2);
            }
        }
    }

    public function testWeightedCriteriaIntegration()
    {
        // Add furnaces with different characteristics
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "1", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "1", null, null, null, 'assigned', false);
        $furnace3 = new Furnace("Furnace 3", "FC3", 200, "R3", 3, "1", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);
        $this->map->addFurnace($furnace3);

        // Define weighted criteria that prioritizes power
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 3.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 1.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_RANK, 1.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_PARTICIPATION, 1.0)
        ];

        $this->mapGenerator->generateMap(['power', 'level', 'rank', 'participation'], $criteriaWeights);

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(3, $furnaces);

        // Verify all furnaces are positioned
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }

        // Verify that weighted criteria was applied (furnace with highest power should be placed first)
        // Note: We can't directly test the order since positions depend on availability,
        // but we can verify that the weighted criteria service was used
        $this->assertNotNull($this->map->getWeightedCriteria());
    }

    public function testVersionSpecificMapGeneration()
    {
        // Set a specific version on the map
        $this->map->setVersion("2.0");
        
        // Add furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "1", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "2", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);

        $this->mapGenerator->generateMap();

        // Verify that the map retains its version
        $this->assertEquals("2.0", $this->map->getVersion());
        
        // Verify furnaces are positioned
        $furnaces = $this->map->getFurnaces();
        $this->assertCount(2, $furnaces);
        
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }

    public function testFirstRingPositionPrioritization()
    {
        // Add furnaces with single trap preferences
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "1", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "1", null, null, null, 'assigned', false);
        $furnace3 = new Furnace("Furnace 3", "FC3", 200, "R3", 3, "1", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);
        $this->map->addFurnace($furnace3);

        $this->mapGenerator->generateMap();

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(3, $furnaces);

        // Verify that furnaces are placed in prioritized first ring positions
        $trap1 = $this->map->getTraps()[0];
        $trap1CenterX = $trap1->getX() + 1;
        $trap1CenterY = $trap1->getY() + 1;

        foreach ($furnaces as $furnace) {
            if ($furnace->getTrapPref() === '1') {
                $distanceX = abs($furnace->getX() - $trap1CenterX);
                $distanceY = abs($furnace->getY() - $trap1CenterY);
                
                // Should be in first ring area
                $this->assertLessThanOrEqual(4, $distanceX);
                $this->assertLessThanOrEqual(4, $distanceY);
            }
        }
    }

    public function testCenterPositioningForNonSingleTrapPreferences()
    {
        // Add furnaces with different trap preferences
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "n/a", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);

        $this->mapGenerator->generateMap();

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(2, $furnaces);

        // Calculate center between traps
        $traps = $this->map->getTraps();
        $centerX = ($traps[0]->getX() + $traps[1]->getX()) / 2;
        $centerY = ($traps[0]->getY() + $traps[1]->getY()) / 2;

        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
            
            // Should be positioned near the center between traps
            $distanceToCenter = sqrt(pow($furnace->getX() - $centerX, 2) + pow($furnace->getY() - $centerY, 2));
            $this->assertLessThan(20, $distanceToCenter); // Reasonable distance from center
        }
    }

    public function testLockedFurnacePositionPreservation()
    {
        // Add a locked furnace with a position
        $lockedFurnace = new Furnace("Locked Furnace", "FC1", 100, "R1", 2, "1", 15, 25, null, 'assigned', true);
        $this->map->addFurnace($lockedFurnace);
        
        // Add an unlocked furnace
        $unlockedFurnace = new Furnace("Unlocked Furnace", "FC2", 150, "R2", 1, "1", null, null, null, 'assigned', false);
        $this->map->addFurnace($unlockedFurnace);

        $this->mapGenerator->generateMap();

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(2, $furnaces);

        foreach ($furnaces as $furnace) {
            if ($furnace->getName() === "Locked Furnace") {
                // Locked furnace should keep its original position
                $this->assertEquals(15, $furnace->getX());
                $this->assertEquals(25, $furnace->getY());
            } else {
                // Unlocked furnace should get a new position
                $this->assertNotNull($furnace->getX());
                $this->assertNotNull($furnace->getY());
                $this->assertNotEquals(15, $furnace->getX());
                $this->assertNotEquals(25, $furnace->getY());
            }
        }
    }

    public function testInsufficientTrapsThrowsException()
    {
        // Remove one trap to have only one
        $this->map = new Map("Test Map", "1.0", "test_map_123");
        $trap1 = new Trap(10, 20);
        $this->map->addTrap($trap1);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("At least 2 traps are required for map generation");
        
        $mapGenerator = new MapGenerator($this->map, $this->weightedCriteriaService);
        $mapGenerator->generateMap();
    }

    public function testOccupiedPositionHandling()
    {
        // Add a misc object to occupy some positions
        $miscObject = new MiscObject(12, 22, 2, "Test Object");
        $this->map->addMiscObject($miscObject);
        
        // Add furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "1", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "1", null, null, null, 'assigned', false);
        $this->map->addFurnace($furnace1);
        $this->map->addFurnace($furnace2);

        $this->mapGenerator->generateMap();

        $furnaces = $this->map->getFurnaces();
        $this->assertCount(2, $furnaces);

        // Verify that furnaces are not placed on occupied positions
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
            
            // Check that furnace doesn't overlap with misc object (2x2 furnace)
            $furnaceEndX = $furnace->getX() + 1;
            $furnaceEndY = $furnace->getY() + 1;
            $miscEndX = 12 + 1;
            $miscEndY = 22 + 1;
            
            $this->assertTrue(
                $furnace->getX() >= $miscEndX || $furnaceEndX <= 12 ||
                $furnace->getY() >= $miscEndY || $furnaceEndY <= 22,
                "Furnace should not overlap with misc object"
            );
        }
    }
} 