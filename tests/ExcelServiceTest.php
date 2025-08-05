<?php

use PHPUnit\Framework\TestCase;
use App\Application\Service\ExcelService;
use App\Application\Exception\ValidationException;

class ExcelServiceTest extends TestCase
{
    private TestableExcelService $excelService;

    protected function setUp(): void
    {
        $this->excelService = new TestableExcelService();
    }

    /**
     * Test that null bytes and non-printable characters are sanitized properly
     */
    public function testSanitizeNullBytesAndNonPrintableCharacters()
    {
        // Create a test row with null bytes and non-printable characters
        $row = [
            'furnace_1', // ID
            'Test' . "\x00" . 'Furnace', // Name with null byte
            '10', // Level
            '1000', // Power
            'R3', // Rank
            '2', // Participation
            'both', // Trap Preference
            '5', // X
            '10', // Y
            'Epic', // Cap Level
            'Rare', // Watch Level
            'Mythic', // Vest Level
            'Legendary', // Pants Level
            'Epic T1', // Ring Level
            'Mythic T2', // Cane Level
            '3' . "\x01" . ',4,3', // Cap Charms with control character
            '8,8,11', // Watch Charms
            '5,6,7', // Vest Charms
            '9,10,11', // Pants Charms
            '12,13,14', // Ring Charms
            '15,16,17' // Cane Charms
        ];

        $result = $this->excelService->parseAndValidateRow($row, 1);

        // Verify that null bytes and control characters were removed
        $this->assertEquals('TestFurnace', $result['name']);
        $this->assertEquals('3,4,3', $result['cap_charms']);
        $this->assertEquals('10', $result['level']);
        $this->assertEquals('R3', $result['rank']);
    }

    /**
     * Test that case-insensitive validation works for levels
     */
    public function testCaseInsensitiveLevelValidation()
    {
        $validLevels = ['10', 'FC1', 'FC5', '30'];
        $caseVariations = ['10', '10', 'fc1', 'FC1', 'fc5', 'FC5', '30', '30'];

        foreach ($caseVariations as $level) {
            $row = $this->createValidRow();
            $row[2] = $level; // Level column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertNotEmpty($result['level']);
        }
    }

    /**
     * Test that case-insensitive validation works for ranks
     */
    public function testCaseInsensitiveRankValidation()
    {
        $caseVariations = ['r1', 'R1', 'r2', 'R2', 'r3', 'R3', 'r4', 'R4', 'r5', 'R5'];

        foreach ($caseVariations as $rank) {
            $row = $this->createValidRow();
            $row[4] = $rank; // Rank column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertNotEmpty($result['rank']);
        }
    }

    /**
     * Test that case-insensitive validation works for trap preferences
     */
    public function testCaseInsensitiveTrapPreferenceValidation()
    {
        $caseVariations = ['1', '2', 'both', 'BOTH', 'Both', 'n/a', 'N/A', 'N/a'];

        foreach ($caseVariations as $trapPref) {
            $row = $this->createValidRow();
            $row[6] = $trapPref; // Trap Preference column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertNotEmpty($result['trap_pref']);
        }
    }

    /**
     * Test that case-insensitive validation works for gear levels
     */
    public function testCaseInsensitiveGearLevelValidation()
    {
        $caseVariations = [
            'epic', 'EPIC', 'Epic',
            'mythic t1', 'MYTHIC T1', 'Mythic T1',
            'legendary t2', 'LEGENDARY T2', 'Legendary T2'
        ];

        foreach ($caseVariations as $gearLevel) {
            $row = $this->createValidRow();
            $row[9] = $gearLevel; // Cap Level column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertNotEmpty($result['cap_level']);
        }
    }

    /**
     * Test that whitespace in gear charms is handled properly
     */
    public function testWhitespaceInGearCharms()
    {
        $charmVariations = [
            '3,4,3',
            ' 3,4,3 ',
            '3 , 4 , 3',
            ' 3 , 4 , 3 ',
            '3, 4,3',
            '3,4 ,3'
        ];

        foreach ($charmVariations as $charms) {
            $row = $this->createValidRow();
            $row[15] = $charms; // Cap Charms column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertEquals('3,4,3', $result['cap_charms']);
        }
    }

    /**
     * Test that empty charm values are filtered out
     */
    public function testEmptyCharmValuesAreFiltered()
    {
        $charmVariations = [
            '3,,4,3',
            '3, ,4,3',
            '3,4,,3',
            '3,4,3,',
            ',3,4,3',
            '3,4,3, '
        ];

        foreach ($charmVariations as $charms) {
            $row = $this->createValidRow();
            $row[15] = $charms; // Cap Charms column

            $result = $this->excelService->parseAndValidateRow($row, 1);
            $this->assertEquals('3,4,3', $result['cap_charms']);
        }
    }

    /**
     * Test that whitespace around string values is trimmed
     */
    public function testWhitespaceTrimming()
    {
        $row = $this->createValidRow();
        $row[1] = '  Test Furnace  '; // Name with whitespace
        $row[2] = '  10  '; // Level with whitespace
        $row[4] = '  R3  '; // Rank with whitespace
        $row[6] = '  both  '; // Trap Preference with whitespace
        $row[9] = '  Epic  '; // Cap Level with whitespace

        $result = $this->excelService->parseAndValidateRow($row, 1);

        $this->assertEquals('Test Furnace', $result['name']);
        $this->assertEquals('10', $result['level']);
        $this->assertEquals('R3', $result['rank']);
        $this->assertEquals('both', $result['trap_pref']);
        $this->assertEquals('Epic', $result['cap_level']);
    }

    /**
     * Test that values are normalized to correct case
     */
    public function testValueNormalization()
    {
        $row = $this->createValidRow();
        $row[2] = 'fc1'; // Level in lowercase
        $row[4] = 'r3'; // Rank in lowercase
        $row[6] = 'BOTH'; // Trap Preference in uppercase
        $row[9] = 'epic'; // Cap Level in lowercase
        $row[10] = 'MYTHIC T1'; // Watch Level in uppercase

        $result = $this->excelService->parseAndValidateRow($row, 1);

        $this->assertEquals('FC1', $result['level']);
        $this->assertEquals('R3', $result['rank']);
        $this->assertEquals('both', $result['trap_pref']);
        $this->assertEquals('Epic', $result['cap_level']);
        $this->assertEquals('Mythic T1', $result['watch_level']);
    }

    /**
     * Test that invalid values still throw exceptions
     */
    public function testInvalidValuesStillThrowExceptions()
    {
        $row = $this->createValidRow();
        $row[2] = 'invalid_level'; // Invalid level

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Invalid Level 'invalid_level' at line 1. Must be 1-30 or FC1-FC10");

        $this->excelService->parseAndValidateRow($row, 1);
    }

    /**
     * Test that null values are handled properly
     */
    public function testNullValuesAreHandled()
    {
        $row = $this->createValidRow();
        $row[1] = null; // Name as null
        $row[2] = null; // Level as null
        $row[15] = null; // Cap Charms as null

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Missing Name at line 1");

        $this->excelService->parseAndValidateRow($row, 1);
    }

    /**
     * Test that mixed case and whitespace combinations work
     */
    public function testMixedCaseAndWhitespaceCombinations()
    {
        $row = $this->createValidRow();
        $row[1] = '  Test Furnace  ';
        $row[2] = '  fc1  ';
        $row[4] = '  r3  ';
        $row[6] = '  BOTH  ';
        $row[9] = '  epic  ';
        $row[15] = '  3 , 4 , 3  ';

        $result = $this->excelService->parseAndValidateRow($row, 1);

        $this->assertEquals('Test Furnace', $result['name']);
        $this->assertEquals('FC1', $result['level']);
        $this->assertEquals('R3', $result['rank']);
        $this->assertEquals('both', $result['trap_pref']);
        $this->assertEquals('Epic', $result['cap_level']);
        $this->assertEquals('3,4,3', $result['cap_charms']);
    }

    /**
     * Helper method to create a valid row for testing
     */
    private function createValidRow(): array
    {
        return [
            'furnace_1', // ID
            'Test Furnace', // Name
            '10', // Level
            '1000', // Power
            'R3', // Rank
            '2', // Participation
            'both', // Trap Preference
            '5', // X
            '10', // Y
            'Epic', // Cap Level
            'Rare', // Watch Level
            'Mythic', // Vest Level
            'Legendary', // Pants Level
            'Epic T1', // Ring Level
            'Mythic T2', // Cane Level
            '3,4,3', // Cap Charms
            '8,8,11', // Watch Charms
            '5,6,7', // Vest Charms
            '9,10,11', // Pants Charms
            '12,13,14', // Ring Charms
            '15,16,17' // Cane Charms
        ];
    }
}

/**
 * Testable version of ExcelService that exposes protected methods for testing
 */
class TestableExcelService extends ExcelService
{
    public function parseAndValidateRow(array $row, int $lineNumber): ?array
    {
        return parent::parseAndValidateRow($row, $lineNumber);
    }

    public function processGearCharms(string $charms): string
    {
        return parent::processGearCharms($charms);
    }

    public function isValidLevel(string $level): bool
    {
        return parent::isValidLevel($level);
    }

    public function isValidRank(string $rank): bool
    {
        return parent::isValidRank($rank);
    }

    public function isValidTrapPreference(string $trapPref): bool
    {
        return parent::isValidTrapPreference($trapPref);
    }

    public function isValidGearLevel(string $gearLevel): bool
    {
        return parent::isValidGearLevel($gearLevel);
    }

    public function normalizeLevel(string $level): string
    {
        return parent::normalizeLevel($level);
    }

    public function normalizeRank(string $rank): string
    {
        return parent::normalizeRank($rank);
    }

    public function normalizeTrapPreference(string $trapPref): string
    {
        return parent::normalizeTrapPreference($trapPref);
    }

    public function normalizeGearLevel(string $gearLevel): string
    {
        return parent::normalizeGearLevel($gearLevel);
    }
} 