<?php

use PHPUnit\Framework\TestCase;
use App\Domain\Map\CriteriaWeight;
use App\Application\Service\WeightedCriteriaService;
use App\Domain\MapObject\Furnace;

class WeightedCriteriaTest extends TestCase
{
    private WeightedCriteriaService $service;

    protected function setUp(): void
    {
        $this->service = new WeightedCriteriaService();
    }

    public function testCriteriaWeightCreation()
    {
        $criteriaWeight = new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 2.5);
        
        $this->assertEquals(CriteriaWeight::CRITERIA_POWER, $criteriaWeight->getCriteria());
        $this->assertEquals(2.5, $criteriaWeight->getWeight());
    }

    public function testCriteriaWeightValidation()
    {
        // Test invalid criteria
        $this->expectException(\InvalidArgumentException::class);
        new CriteriaWeight('invalid_criteria', 1.0);
    }

    public function testCriteriaWeightNegativeWeight()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, -1.0);
    }

    public function testGetAvailableCriteria()
    {
        $criteria = CriteriaWeight::getAvailableCriteria();
        
        $this->assertArrayHasKey(CriteriaWeight::CRITERIA_POWER, $criteria);
        $this->assertArrayHasKey(CriteriaWeight::CRITERIA_LEVEL, $criteria);
        $this->assertArrayHasKey(CriteriaWeight::CRITERIA_RANK, $criteria);
        $this->assertArrayHasKey(CriteriaWeight::CRITERIA_PARTICIPATION, $criteria);
        $this->assertArrayHasKey(CriteriaWeight::CRITERIA_CHIEF_GEAR_AND_CHARMS, $criteria);
        
        $this->assertEquals('Power', $criteria[CriteriaWeight::CRITERIA_POWER]);
        $this->assertEquals('Level', $criteria[CriteriaWeight::CRITERIA_LEVEL]);
        $this->assertEquals('Rank', $criteria[CriteriaWeight::CRITERIA_RANK]);
        $this->assertEquals('Participation', $criteria[CriteriaWeight::CRITERIA_PARTICIPATION]);
        $this->assertEquals('Chief Gear and Charms', $criteria[CriteriaWeight::CRITERIA_CHIEF_GEAR_AND_CHARMS]);
    }

    public function testGetDefaultWeights()
    {
        $defaultWeights = CriteriaWeight::getDefaultWeights();
        
        $this->assertEquals(1.0, $defaultWeights[CriteriaWeight::CRITERIA_POWER]);
        $this->assertEquals(1.0, $defaultWeights[CriteriaWeight::CRITERIA_LEVEL]);
        $this->assertEquals(1.0, $defaultWeights[CriteriaWeight::CRITERIA_RANK]);
        $this->assertEquals(1.0, $defaultWeights[CriteriaWeight::CRITERIA_PARTICIPATION]);
        $this->assertEquals(1.0, $defaultWeights[CriteriaWeight::CRITERIA_CHIEF_GEAR_AND_CHARMS]);
    }

    public function testCalculateWeightedScore()
    {
        $furnace = new Furnace(
            "Test Furnace",
            "15",
            1000,
            "R3",
            2,
            "both",
            null,
            null,
            null,
            'assigned',
            false,
            "Legendary T1", // cap
            "Legendary T2", // watch
            "Legendary T1", // vest
            "Legendary T2", // pants
            "Legendary T3", // ring
            "Legendary T3", // cane
            "5,7,9", // cap charms
            "8,8,8", // watch charms
            "6,8,10", // vest charms
            "7,9,11", // pants charms
            "8,10,12", // ring charms
            "9,11,13" // cane charms
        );

        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 2.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 1.5),
            new CriteriaWeight(CriteriaWeight::CRITERIA_RANK, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        
        // Power: 1000 * 2.0 = 2000
        // Level: 15 * 1.5 = 22.5
        // Rank: 3 * 1.0 = 3.0
        // Total: 2025.5 / 4.5 = 450.11...
        $this->assertGreaterThan(400, $score);
        $this->assertLessThan(500, $score);
    }

    public function testCalculateWeightedScoreWithZeroWeight()
    {
        $furnace = new Furnace("Test Furnace", "15", 1000, "R3", 2, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 2.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 0.0), // Zero weight
            new CriteriaWeight(CriteriaWeight::CRITERIA_RANK, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        
        // Power: 1000 * 2.0 = 2000
        // Level: 15 * 0.0 = 0 (skipped)
        // Rank: 3 * 1.0 = 3.0
        // Total: 2003.0 / 3.0 = 667.67...
        $this->assertGreaterThan(650, $score);
        $this->assertLessThan(700, $score);
    }

    public function testSortFurnacesByWeightedCriteria()
    {
        $furnace1 = new Furnace("Furnace 1", "10", 500, "R1", 1, "both");
        $furnace2 = new Furnace("Furnace 2", "20", 1000, "R5", 4, "both");
        $furnace3 = new Furnace("Furnace 3", "15", 750, "R3", 2, "both");

        $furnaces = [$furnace1, $furnace2, $furnace3];

        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 1.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 1.0)
        ];

        $sortedFurnaces = $this->service->sortFurnacesByWeightedCriteria($furnaces, $criteriaWeights);
        
        // Should be sorted by combined score (power + level) in descending order
        $this->assertEquals($furnace2, $sortedFurnaces[0]); // Power: 1000, Level: 20 = 1020
        $this->assertEquals($furnace3, $sortedFurnaces[1]); // Power: 750, Level: 15 = 765
        $this->assertEquals($furnace1, $sortedFurnaces[2]); // Power: 500, Level: 10 = 510
    }

    public function testSortFurnacesByChiefGearAndCharms()
    {
        // Create furnaces with different gear levels and charms
        $furnace1 = new Furnace(
            "Furnace 1", "15", 1000, "R3", 2, "both",
            null, null, null, "assigned", false,
            null, null, null, null, "Legendary T1", "Legendary T1",
            null, null, null, null, "5,5,5", "5,5,5"
        );

        $furnace2 = new Furnace(
            "Furnace 2", "15", 1000, "R3", 2, "both",
            null, null, null, "assigned", false,
            null, null, null, null, "Legendary T3", "Legendary T3",
            null, null, null, null, "10,10,10", "10,10,10"
        );

        $furnaces = [$furnace1, $furnace2];

        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_CHIEF_GEAR_AND_CHARMS, 1.0)
        ];

        $sortedFurnaces = $this->service->sortFurnacesByWeightedCriteria($furnaces, $criteriaWeights);
        
        // Furnace 2 should have higher priority due to better gear and charms
        $this->assertEquals($furnace2, $sortedFurnaces[0]);
        $this->assertEquals($furnace1, $sortedFurnaces[1]);
    }

    public function testValidateCriteriaWeights()
    {
        $validWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 1.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 2.0)
        ];

        // Should not throw exception
        $this->service->validateCriteriaWeights($validWeights);
        $this->assertTrue(true); // Test passes if no exception
    }

    public function testValidateCriteriaWeightsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->validateCriteriaWeights([]);
    }

    public function testValidateCriteriaWeightsInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->validateCriteriaWeights(['invalid']);
    }

    public function testValidateCriteriaWeightsDuplicate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $duplicateWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 1.0),
            new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 2.0)
        ];
        $this->service->validateCriteriaWeights($duplicateWeights);
    }

    public function testCreateCriteriaWeightsFromArray()
    {
        $criteriaWeightsData = [
            ['criteria' => CriteriaWeight::CRITERIA_POWER, 'weight' => 2.5],
            ['criteria' => CriteriaWeight::CRITERIA_LEVEL, 'weight' => 1.5]
        ];

        $criteriaWeights = $this->service->createCriteriaWeightsFromArray($criteriaWeightsData);
        
        $this->assertCount(2, $criteriaWeights);
        $this->assertInstanceOf(CriteriaWeight::class, $criteriaWeights[0]);
        $this->assertInstanceOf(CriteriaWeight::class, $criteriaWeights[1]);
        
        $this->assertEquals(CriteriaWeight::CRITERIA_POWER, $criteriaWeights[0]->getCriteria());
        $this->assertEquals(2.5, $criteriaWeights[0]->getWeight());
        $this->assertEquals(CriteriaWeight::CRITERIA_LEVEL, $criteriaWeights[1]->getCriteria());
        $this->assertEquals(1.5, $criteriaWeights[1]->getWeight());
    }

    public function testCreateCriteriaWeightsFromArrayInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidData = [
            ['criteria' => CriteriaWeight::CRITERIA_POWER] // Missing weight
        ];
        $this->service->createCriteriaWeightsFromArray($invalidData);
    }

    public function testLevelScoreCalculation()
    {
        $furnace = new Furnace("Test", "15", 1000, "R3", 2, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        $this->assertEquals(15.0, $score); // Level 15 should score 15.0
    }

    public function testFCLevelScoreCalculation()
    {
        $furnace = new Furnace("Test", "FC5", 1000, "R3", 2, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_LEVEL, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        $this->assertEquals(35.0, $score); // FC5 should score 30 + 5 = 35.0
    }

    public function testRankScoreCalculation()
    {
        $furnace = new Furnace("Test", "15", 1000, "R4", 2, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_RANK, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        $this->assertEquals(4.0, $score); // R4 should score 4.0
    }

    public function testParticipationScoreCalculation()
    {
        $furnace = new Furnace("Test", "15", 1000, "R3", 3, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_PARTICIPATION, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        $this->assertEquals(3.0, $score); // Participation 3 should score 3.0
    }

    public function testParticipationScoreCalculationNull()
    {
        $furnace = new Furnace("Test", "15", 1000, "R3", null, "both");
        
        $criteriaWeights = [
            new CriteriaWeight(CriteriaWeight::CRITERIA_PARTICIPATION, 1.0)
        ];

        $score = $this->service->calculateWeightedScore($furnace, $criteriaWeights);
        $this->assertEquals(0.0, $score); // Null participation should score 0.0
    }

    public function testCriteriaWeightToArray()
    {
        $criteriaWeight = new CriteriaWeight(CriteriaWeight::CRITERIA_POWER, 2.5);
        $array = $criteriaWeight->toArray();
        
        $this->assertEquals([
            'criteria' => CriteriaWeight::CRITERIA_POWER,
            'weight' => 2.5
        ], $array);
    }

    public function testCriteriaWeightFromArray()
    {
        $data = [
            'criteria' => CriteriaWeight::CRITERIA_LEVEL,
            'weight' => 3.0
        ];
        
        $criteriaWeight = CriteriaWeight::fromArray($data);
        
        $this->assertEquals(CriteriaWeight::CRITERIA_LEVEL, $criteriaWeight->getCriteria());
        $this->assertEquals(3.0, $criteriaWeight->getWeight());
    }


} 