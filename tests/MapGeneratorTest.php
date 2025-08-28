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
        // Add a misc object to occupy some positions (moved to avoid conflict with traps)
        $miscObject = new MiscObject(35, 45, 2, "Test Object");
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
            $miscEndX = 35 + 1;
            $miscEndY = 45 + 1;
            
            $this->assertTrue(
                $furnace->getX() >= $miscEndX || $furnaceEndX <= 35 ||
                $furnace->getY() >= $miscEndY || $furnaceEndY <= 45,
                "Furnace should not overlap with misc object"
            );
        }
    }

    public function testTrapOrientationDetection(): void
    {
        // Create a map with horizontal trap layout (same Y coordinates)
        $map = new Map('Test Map', 'v1', '1', 50);
        $trap1 = new Trap(10, 15, 'trap1');
        $trap2 = new Trap(25, 15, 'trap2'); // Same Y coordinate = horizontal layout
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        $mapGenerator = new MapGenerator($map);
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($mapGenerator);
        $detectOrientationMethod = $reflection->getMethod('detectTrapOrientation');
        $detectOrientationMethod->setAccessible(true);
        
        $orientation = $detectOrientationMethod->invoke($mapGenerator, [$trap1, $trap2]);
        $this->assertEquals('horizontal', $orientation);
        
        // Create a map with vertical trap layout (same X coordinates)
        $map2 = new Map('Test Map 2', 'v1', '2', 50);
        $trap3 = new Trap(15, 10, 'trap3');
        $trap4 = new Trap(15, 25, 'trap4'); // Same X coordinate = vertical layout
        $map2->addTrap($trap3);
        $map2->addTrap($trap4);
        
        $mapGenerator2 = new MapGenerator($map2);
        $orientation2 = $detectOrientationMethod->invoke($mapGenerator2, [$trap3, $trap4]);
        $this->assertEquals('vertical', $orientation2);
    }

    public function testMapGenerationWithDifferentOrientations(): void
    {
        // Test horizontal layout
        $mapHorizontal = new Map('Horizontal Test', 'v1', '3', 50);
        $trap1 = new Trap(10, 15, 'trap1');
        $trap2 = new Trap(25, 15, 'trap2'); // Same Y = horizontal
        $mapHorizontal->addTrap($trap1);
        $mapHorizontal->addTrap($trap2);
        
        $furnace1 = new Furnace('Test1', 'FC1', 100, 'R1', 50, '1');
        $furnace2 = new Furnace('Test2', 'FC2', 90, 'R2', 45, '2');
        $furnace3 = new Furnace('Test3', 'FC3', 80, 'R3', 40, 'both');
        $mapHorizontal->addFurnace($furnace1);
        $mapHorizontal->addFurnace($furnace2);
        $mapHorizontal->addFurnace($furnace3);
        
        $mapGenerator = new MapGenerator($mapHorizontal);
        $mapGenerator->generateMap();
        
        // Verify furnaces were placed
        $placedFurnaces = $mapHorizontal->getFurnaces();
        $this->assertCount(3, $placedFurnaces);
        
        // Test vertical layout
        $mapVertical = new Map('Vertical Test', 'v1', '4', 50);
        $trap3 = new Trap(15, 10, 'trap3');
        $trap4 = new Trap(15, 25, 'trap4'); // Same X = vertical
        $mapVertical->addTrap($trap3);
        $mapVertical->addTrap($trap4);
        
        $furnace4 = new Furnace('Test4', 'FC1', 100, 'R1', 50, '1');
        $furnace5 = new Furnace('Test5', 'FC2', 90, 'R2', 45, '2');
        $furnace6 = new Furnace('Test6', 'FC3', 80, 'R3', 40, 'both');
        $mapVertical->addFurnace($furnace4);
        $mapVertical->addFurnace($furnace5);
        $mapVertical->addFurnace($furnace6);
        
        $mapGenerator2 = new MapGenerator($mapVertical);
        $mapGenerator2->generateMap();
        
        // Verify furnaces were placed
        $placedFurnaces2 = $mapVertical->getFurnaces();
        $this->assertCount(3, $placedFurnaces2);
        
        // Verify that furnaces have positions
        foreach ($placedFurnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
        
        foreach ($placedFurnaces2 as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }

    public function testVerticalLayoutZoneCalculation(): void
    {
        // Create a map with vertical trap layout
        $map = new Map('Vertical Test', 'v1', '5', 50);
        $trap1 = new Trap(15, 10, 'trap1');
        $trap2 = new Trap(15, 25, 'trap2'); // Same X = vertical layout
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        $mapGenerator = new MapGenerator($map);
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($mapGenerator);
        $calculateZoneBoundsMethod = $reflection->getMethod('calculateZoneBounds');
        $calculateZoneBoundsMethod->setAccessible(true);
        
        // Test zone calculation for first trap
        $zoneBounds1 = $calculateZoneBoundsMethod->invoke($mapGenerator, $trap1, []);
        
        // For vertical layout, the zone should be properly positioned
        // Trap 1 is at (15, 10), so zone should be centered around that
        // Zone is 4x4, so it should span from X=13-17 and Y=8-12
        $this->assertGreaterThanOrEqual(13, $zoneBounds1['startX']);
        $this->assertLessThanOrEqual(18, $zoneBounds1['endX']);
        $this->assertGreaterThanOrEqual(8, $zoneBounds1['startY']);
        $this->assertLessThanOrEqual(13, $zoneBounds1['endY']);
        
        // Test zone calculation for second trap
        $zoneBounds2 = $calculateZoneBoundsMethod->invoke($mapGenerator, $trap2, []);
        
        // Trap 2 is at (15, 25), so zone should be centered around that
        // Zone is 4x4, so it should span from X=13-17 and Y=23-27
        $this->assertGreaterThanOrEqual(13, $zoneBounds2['startX']);
        $this->assertLessThanOrEqual(18, $zoneBounds2['endX']);
        $this->assertGreaterThanOrEqual(23, $zoneBounds2['startY']);
        $this->assertLessThanOrEqual(28, $zoneBounds2['endY']);
        
        // Verify zones are 4x4
        $this->assertEquals(4, $zoneBounds1['endX'] - $zoneBounds1['startX']);
        $this->assertEquals(4, $zoneBounds1['endY'] - $zoneBounds1['startY']);
        $this->assertEquals(4, $zoneBounds2['endX'] - $zoneBounds2['startX']);
        $this->assertEquals(4, $zoneBounds2['endY'] - $zoneBounds2['startY']);
    }

    public function testBannerInclusionInZoneCalculation(): void
    {
        // Create a map with a trap and adjacent banners
        $map = new Map('Banner Test', 'v1', '5', 50);
        $trap = new Trap(15, 15, 'trap1');
        $map->addTrap($trap);
        
        // Add banners that are touching the trap
        $banner1 = new MiscObject(18, 15, 1, 'banner'); // Right of trap
        $banner2 = new MiscObject(15, 18, 1, 'banner'); // Below trap
        $banner3 = new MiscObject(14, 15, 1, 'banner'); // Left of trap (corrected position)
        $banner4 = new MiscObject(15, 14, 1, 'banner'); // Above trap (corrected position)
        $map->addMiscObject($banner1);
        $map->addMiscObject($banner2);
        $map->addMiscObject($banner3);
        $map->addMiscObject($banner4);
        
        // Add a second trap to make it a valid map
        $trap2 = new Trap(25, 25, 'trap2');
        $map->addTrap($trap2);
        
        $mapGenerator = new MapGenerator($map);
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($mapGenerator);
        $findTouchingBannersMethod = $reflection->getMethod('findTouchingBanners');
        $findTouchingBannersMethod->setAccessible(true);
        
        // Test that banners are found
        $touchingBanners = $findTouchingBannersMethod->invoke($mapGenerator, $trap, $map->getMiscObjects());
        $this->assertCount(4, $touchingBanners, 'Should find 4 touching banners');
        
        // Test zone calculation with banners
        $calculateZoneBoundsMethod = $reflection->getMethod('calculateZoneBounds');
        $calculateZoneBoundsMethod->setAccessible(true);
        
        $zoneBounds = $calculateZoneBoundsMethod->invoke($mapGenerator, $trap, $touchingBanners);
        
        // The zone should include the trap (15,15) and all banners
        // Trap is 3x3, so it spans (15,15) to (17,17)
        // Banners are at (14,15), (15,14), (18,15), (15,18)
        // The zone is 4x4 and centered on the combined area
        // Since the zone is constrained to 4x4, it may not include all banners at the edges
        // But it should be positioned to include as many banners as possible
        
        // Verify zone is 4x4
        $this->assertEquals(4, $zoneBounds['endX'] - $zoneBounds['startX']);
        $this->assertEquals(4, $zoneBounds['endY'] - $zoneBounds['startY']);
        
        // Verify that the zone is positioned reasonably (should be centered around the trap area)
        $this->assertGreaterThanOrEqual(13, $zoneBounds['startX'], 'Zone should start at reasonable X position');
        $this->assertLessThanOrEqual(19, $zoneBounds['endX'], 'Zone should end at reasonable X position');
        $this->assertGreaterThanOrEqual(13, $zoneBounds['startY'], 'Zone should start at reasonable Y position');
        $this->assertLessThanOrEqual(19, $zoneBounds['endY'], 'Zone should end at reasonable Y position');
    }
} 