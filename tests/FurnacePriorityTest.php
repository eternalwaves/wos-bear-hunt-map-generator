<?php

use PHPUnit\Framework\TestCase;
use App\Domain\MapObject\Furnace;

class FurnacePriorityTest extends TestCase
{
    public function testGetPlacementPriorityUsesMeanValues()
    {
        // Create a furnace with ring level 30 and cane level 38
        // Ring charms: 5,7,9 (mean = 7.0)
        // Cane charms: 3,6,12 (mean = 7.0)
        $furnace1 = new Furnace(
            "Test Furnace 1",
            "FC1",
            100,
            "R1",
            2,
            "both",
            null,
            null,
            null,
            'assigned',
            false,
            null, // cap
            null, // watch
            null, // vest
            null, // pants
            "Legendary T1", // ring (level 30)
            "Legendary T3", // cane (level 38)
            null, // cap charms
            null, // watch charms
            null, // vest charms
            null, // pants charms
            "5,7,9", // ring charms
            "3,6,12" // cane charms
        );

        // Create a furnace with ring level 34 and cane level 34
        // Ring charms: 8,8,8 (mean = 8.0)
        // Cane charms: 8,8,8 (mean = 8.0)
        $furnace2 = new Furnace(
            "Test Furnace 2",
            "FC1",
            100,
            "R1",
            2,
            "both",
            null,
            null,
            null,
            'assigned',
            false,
            null, // cap
            null, // watch
            null, // vest
            null, // pants
            "Legendary T2", // ring (level 34)
            "Legendary T2", // cane (level 34)
            null, // cap charms
            null, // watch charms
            null, // vest charms
            null, // pants charms
            "8,8,8", // ring charms
            "8,8,8" // cane charms
        );

        $priority1 = $furnace1->getPlacementPriority();
        $priority2 = $furnace2->getPlacementPriority();

        // Furnace 1: mean gear level = (30 + 38) / 2 = 34.0, mean charms = (7.0 + 7.0) / 2 = 7.0
        // Furnace 2: mean gear level = (34 + 34) / 2 = 34.0, mean charms = (8.0 + 8.0) / 2 = 8.0
        
        // Furnace 2 should have higher priority because it has higher mean values
        $this->assertGreaterThan($priority1, $priority2, 
            "Furnace with higher mean gear level and charms should have higher priority");
    }

    public function testGetMeanCharmLevel()
    {
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both");
        
        // Test with valid charm values
        $this->assertEquals(7.0, $furnace->getMeanCharmLevel("5,7,9"));
        $this->assertEquals(8.0, $furnace->getMeanCharmLevel("8,8,8"));
        $this->assertEquals(5.5, $furnace->getMeanCharmLevel("3,8"));
        
        // Test with null/empty values
        $this->assertEquals(0.0, $furnace->getMeanCharmLevel(null));
        $this->assertEquals(0.0, $furnace->getMeanCharmLevel(""));
    }

    public function testGetMeanGearLevelIndex()
    {
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both");
        
        // Test with valid gear levels
        $this->assertEquals(34.0, $furnace->getMeanGearLevelIndex("Legendary T1", "Legendary T3")); // (30 + 38) / 2
        $this->assertEquals(34.0, $furnace->getMeanGearLevelIndex("Legendary T2", "Legendary T2")); // (34 + 34) / 2
        
        // Test with one null value
        $this->assertEquals(30.0, $furnace->getMeanGearLevelIndex("Legendary T1", null));
        $this->assertEquals(38.0, $furnace->getMeanGearLevelIndex(null, "Legendary T3"));
        
        // Test with both null values
        $this->assertEquals(-1.0, $furnace->getMeanGearLevelIndex(null, null));
    }
} 