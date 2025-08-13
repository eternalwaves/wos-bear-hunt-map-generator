<?php

use PHPUnit\Framework\TestCase;
use App\Domain\MapObject\Furnace;

class FurnaceValidationTest extends TestCase
{
    public function testValidLevels()
    {
        // Test all valid levels
        foreach (Furnace::VALID_LEVELS as $level) {
            $furnace = new Furnace("Test", $level, 100, "R1", 2, "both");
            $this->assertEquals($level, $furnace->getLevel());
        }
    }

    public function testInvalidLevelThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid level: invalid_level. Must be one of: " . implode(', ', Furnace::VALID_LEVELS));
        
        new Furnace("Test", "invalid_level", 100, "R1", 2, "both");
    }

    public function testLevelNormalization()
    {
        // Test case normalization
        $furnace = new Furnace("Test", "fc1", 100, "R1", 2, "both");
        $this->assertEquals("fc1", $furnace->getLevel());
        
        $furnace = new Furnace("Test", "  FC1  ", 100, "R1", 2, "both");
        $this->assertEquals("fc1", $furnace->getLevel());
    }

    public function testValidRanks()
    {
        // Test all valid ranks
        foreach (Furnace::VALID_RANKS as $rank) {
            $furnace = new Furnace("Test", "FC1", 100, $rank, 2, "both");
            $this->assertEquals($rank, $furnace->getRank());
        }
    }

    public function testInvalidRankThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid rank: R6. Must be one of: " . implode(', ', Furnace::VALID_RANKS));
        
        new Furnace("Test", "FC1", 100, "R6", 2, "both");
    }

    public function testRankNormalization()
    {
        // Test case normalization
        $furnace = new Furnace("Test", "FC1", 100, "r1", 2, "both");
        $this->assertEquals("R1", $furnace->getRank());
        
        $furnace = new Furnace("Test", "FC1", 100, "  R2  ", 2, "both");
        $this->assertEquals("R2", $furnace->getRank());
    }

    public function testValidTrapPreferences()
    {
        // Test all valid trap preferences
        foreach (Furnace::VALID_TRAP_PREFERENCES as $pref) {
            $furnace = new Furnace("Test", "FC1", 100, "R1", 2, $pref);
            $this->assertEquals($pref, $furnace->getTrapPref());
        }
    }

    public function testInvalidTrapPreferenceThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid trap preference: invalid. Must be one of: " . implode(', ', Furnace::VALID_TRAP_PREFERENCES));
        
        new Furnace("Test", "FC1", 100, "R1", 2, "invalid");
    }

    public function testTrapPreferenceNormalization()
    {
        // Test case normalization
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "BOTH");
        $this->assertEquals("both", $furnace->getTrapPref());
        
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "  N/A  ");
        $this->assertEquals("n/a", $furnace->getTrapPref());
    }

    public function testValidStatuses()
    {
        // Test all valid statuses
        foreach (Furnace::VALID_STATUSES as $status) {
            $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both", null, null, null, $status);
            $this->assertEquals($status, $furnace->getStatus());
        }
    }

    public function testInvalidStatusThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid status: invalid_status. Must be one of: " . implode(', ', Furnace::VALID_STATUSES));
        
        new Furnace("Test", "FC1", 100, "R1", 2, "both", null, null, null, "invalid_status");
    }

    public function testValidPower()
    {
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both");
        $this->assertEquals(100, $furnace->getPower());
        
        $furnace = new Furnace("Test", "FC1", 1, "R1", 2, "both");
        $this->assertEquals(1, $furnace->getPower());
    }

    public function testInvalidPowerThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Power must be a positive integer, got: 0");
        
        new Furnace("Test", "FC1", 0, "R1", 2, "both");
    }

    public function testNegativePowerThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Power must be a positive integer, got: -10");
        
        new Furnace("Test", "FC1", -10, "R1", 2, "both");
    }

    public function testValidParticipation()
    {
        // Test valid participation values
        for ($i = 0; $i <= 4; $i++) {
            $furnace = new Furnace("Test", "FC1", 100, "R1", $i, "both");
            $this->assertEquals($i, $furnace->getParticipation());
        }
        
        // Test null participation
        $furnace = new Furnace("Test", "FC1", 100, "R1", null, "both");
        $this->assertNull($furnace->getParticipation());
    }

    public function testInvalidParticipationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Participation must be between 0 and 4, got: 5");
        
        new Furnace("Test", "FC1", 100, "R1", 5, "both");
    }

    public function testNegativeParticipationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Participation must be between 0 and 4, got: -1");
        
        new Furnace("Test", "FC1", 100, "R1", -1, "both");
    }

    public function testValidGearLevels()
    {
        // Test all valid gear levels
        foreach (Furnace::VALID_GEAR_LEVELS as $level) {
            $furnace = new Furnace(
                "Test", "FC1", 100, "R1", 2, "both",
                null, null, null, '', false,
                $level, null, null, null, null, null
            );
            $this->assertEquals($level, $furnace->getCapLevel());
        }
    }

    public function testInvalidGearLevelThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid gear level: Invalid Level. Must be one of: " . implode(', ', Furnace::VALID_GEAR_LEVELS));
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            "Invalid Level", null, null, null, null, null
        );
    }

    public function testNullGearLevelIsAllowed()
    {
        $furnace = new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null
        );
        $this->assertNull($furnace->getCapLevel());
    }

    public function testEmptyGearLevelIsAllowed()
    {
        $furnace = new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            "", null, null, null, null, null
        );
        $this->assertNull($furnace->getCapLevel());
    }

    public function testValidCharms()
    {
        // Test valid charm combinations
        $validCharms = [
            "1,2,3",
            "5,10,15",
            "16,16,16",
            "1,1,1"
        ];
        
        foreach ($validCharms as $charms) {
            $furnace = new Furnace(
                "Test", "FC1", 100, "R1", 2, "both",
                null, null, null, '', false,
                null, null, null, null, null, null,
                $charms, null, null, null, null, null
            );
            $this->assertEquals($charms, $furnace->getCapCharms());
        }
    }

    public function testInvalidCharmsThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Charms must have exactly 3 comma-separated values: 1,2");
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "1,2", null, null, null, null, null
        );
    }

    public function testTooManyCharmsThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Charms must have exactly 3 comma-separated values: 1,2,3,4");
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "1,2,3,4", null, null, null, null, null
        );
    }

    public function testInvalidCharmLevelThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid charm level: 17. Must be between 1 and 16.");
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "1,2,17", null, null, null, null, null
        );
    }

    public function testZeroCharmLevelThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid charm level: 0. Must be between 1 and 16.");
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "1,0,3", null, null, null, null, null
        );
    }

    public function testNonNumericCharmThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid charm level: abc. Must be between 1 and 16.");
        
        new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "1,abc,3", null, null, null, null, null
        );
    }

    public function testNullCharmsIsAllowed()
    {
        $furnace = new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            null, null, null, null, null, null
        );
        $this->assertNull($furnace->getCapCharms());
    }

    public function testEmptyCharmsIsAllowed()
    {
        $furnace = new Furnace(
            "Test", "FC1", 100, "R1", 2, "both",
            null, null, null, '', false,
            null, null, null, null, null, null,
            "", null, null, null, null, null
        );
        $this->assertNull($furnace->getCapCharms());
    }

    public function testSetterValidation()
    {
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both");
        
        // Test valid setter calls
        $furnace->setLevel("FC2");
        $this->assertEquals("fc2", $furnace->getLevel());
        
        $furnace->setRank("R3");
        $this->assertEquals("R3", $furnace->getRank());
        
        $furnace->setTrapPref("n/a");
        $this->assertEquals("n/a", $furnace->getTrapPref());
        
        $furnace->setPower(150);
        $this->assertEquals(150, $furnace->getPower());
        
        $furnace->setParticipation(3);
        $this->assertEquals(3, $furnace->getParticipation());
        
        $furnace->setStatus("moved");
        $this->assertEquals("moved", $furnace->getStatus());
        
        $furnace->setCapLevel("Epic");
        $this->assertEquals("Epic", $furnace->getCapLevel());
        
        $furnace->setCapCharms("5,6,7");
        $this->assertEquals("5,6,7", $furnace->getCapCharms());
    }

    public function testSetterValidationThrowsExceptions()
    {
        $furnace = new Furnace("Test", "FC1", 100, "R1", 2, "both");
        
        // Test invalid setter calls
        $this->expectException(\InvalidArgumentException::class);
        $furnace->setLevel("invalid");
    }

    public function testValidationConstantsArePublic()
    {
        // Test that validation constants are accessible
        $this->assertIsArray(Furnace::VALID_LEVELS);
        $this->assertIsArray(Furnace::VALID_RANKS);
        $this->assertIsArray(Furnace::VALID_TRAP_PREFERENCES);
        $this->assertIsArray(Furnace::VALID_STATUSES);
        $this->assertIsArray(Furnace::VALID_GEAR_LEVELS);
        $this->assertIsInt(Furnace::MAX_CHARM_LEVEL);
        
        // Test that constants contain expected values
        $this->assertContains("FC1", Furnace::VALID_LEVELS);
        $this->assertContains("R1", Furnace::VALID_RANKS);
        $this->assertContains("both", Furnace::VALID_TRAP_PREFERENCES);
        $this->assertContains("assigned", Furnace::VALID_STATUSES);
        $this->assertContains("Epic", Furnace::VALID_GEAR_LEVELS);
        $this->assertEquals(16, Furnace::MAX_CHARM_LEVEL);
    }
}
