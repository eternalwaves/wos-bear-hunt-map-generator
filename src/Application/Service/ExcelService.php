<?php

namespace App\Application\Service;

use App\Application\Exception\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ExcelService
{
    private const VALID_GEAR_LEVELS = [
        'Uncommon', 'Uncommon *', 'Rare', 'Rare *', 'Rare **', 'Rare ***',
        'Epic', 'Epic *', 'Epic **', 'Epic ***', 'Epic T1', 'Epic T1 *', 'Epic T1 **', 'Epic T1 ***',
        'Mythic', 'Mythic *', 'Mythic **', 'Mythic ***', 'Mythic T1', 'Mythic T1 *', 'Mythic T1 **', 'Mythic T1 ***',
        'Mythic T2', 'Mythic T2 *', 'Mythic T2 **', 'Mythic T2 ***',
        'Legendary', 'Legendary *', 'Legendary **', 'Legendary ***',
        'Legendary T1', 'Legendary T1 *', 'Legendary T1 **', 'Legendary T1 ***',
        'Legendary T2', 'Legendary T2 *', 'Legendary T2 **', 'Legendary T2 ***',
        'Legendary T3', 'Legendary T3 *', 'Legendary T3 **', 'Legendary T3 ***'
    ];

    private const VALID_LEVELS = [
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
        'FC1', 'FC2', 'FC3', 'FC4', 'FC5', 'FC6', 'FC7', 'FC8', 'FC9', 'FC10'
    ];

    private const VALID_RANKS = ['R1', 'R2', 'R3', 'R4', 'R5'];

    private const VALID_TRAP_PREFERENCES = ['1', '2', 'both', 'n/a'];

    public function generateTemplateXls(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Furnace Data');

        // Set headers
        $headers = [
            'ID', 'Name', 'Level', 'Power', 'Rank', 'Participation', 'Trap Preference', 'X', 'Y',
            'Cap Level', 'Watch Level', 'Vest Level', 'Pants Level', 'Ring Level', 'Cane Level',
            'Cap Charms', 'Watch Charms', 'Vest Charms', 'Pants Charms', 'Ring Charms', 'Cane Charms'
        ];

        foreach ($headers as $colIndex => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        // Add data validation
        $this->addDataValidation($sheet);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15); // ID
        $sheet->getColumnDimension('B')->setWidth(20); // Name
        $sheet->getColumnDimension('C')->setWidth(10); // Level
        $sheet->getColumnDimension('D')->setWidth(10); // Power
        $sheet->getColumnDimension('E')->setWidth(8);  // Rank
        $sheet->getColumnDimension('F')->setWidth(12); // Participation
        $sheet->getColumnDimension('G')->setWidth(15); // Trap Preference
        $sheet->getColumnDimension('H')->setWidth(8);  // X
        $sheet->getColumnDimension('I')->setWidth(8);  // Y
        $sheet->getColumnDimension('J')->setWidth(15); // Cap Level
        $sheet->getColumnDimension('K')->setWidth(15); // Watch Level
        $sheet->getColumnDimension('L')->setWidth(15); // Vest Level
        $sheet->getColumnDimension('M')->setWidth(15); // Pants Level
        $sheet->getColumnDimension('N')->setWidth(15); // Ring Level
        $sheet->getColumnDimension('O')->setWidth(15); // Cane Level
        $sheet->getColumnDimension('P')->setWidth(12); // Cap Charms
        $sheet->getColumnDimension('Q')->setWidth(12); // Watch Charms
        $sheet->getColumnDimension('R')->setWidth(12); // Vest Charms
        $sheet->getColumnDimension('S')->setWidth(12); // Pants Charms
        $sheet->getColumnDimension('T')->setWidth(12); // Ring Charms
        $sheet->getColumnDimension('U')->setWidth(12); // Cane Charms

        // Freeze the header row
        $sheet->freezePane('A2');

        // Add some sample data for better user experience
        $sheet->setCellValue('A2', 'FURNACE001 - auto-assigned, leave blank unless overwriting existing map');
        $sheet->setCellValue('B2', 'Sample Furnace');
        $sheet->setCellValue('C2', 'FC6');
        $sheet->setCellValue('D2', 1501237234);
        $sheet->setCellValue('E2', 'R3');
        $sheet->setCellValue('F2', 4);
        $sheet->setCellValue('G2', 'both');
        $sheet->setCellValue('H2', 10);
        $sheet->setCellValue('I2', 20);
        $sheet->setCellValue('J2', 'Legendary T2 *');
        $sheet->setCellValue('K2', 'Legendary T2 ***');
        $sheet->setCellValue('L2', 'Legendary T3 *');
        $sheet->setCellValue('M2', 'Legendary T3 *');
        $sheet->setCellValue('N2', 'Legendary T2 *');
        $sheet->setCellValue('O2', 'Legendary T2 **');
        $sheet->setCellValue('P2', '8,8,8');
        $sheet->setCellValue('Q2', '8,8,8');
        $sheet->setCellValue('R2', '8,11,8');
        $sheet->setCellValue('S2', '11,10,10');
        $sheet->setCellValue('T2', '10,8,10');
        $sheet->setCellValue('U2', '11,11,11');

        // Create temporary file with proper error handling
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'furnace_template_');
        
        try {
            $writer->save($tempFile);
            $content = file_get_contents($tempFile);
            
            if ($content === false) {
                throw new \RuntimeException('Failed to read generated template file');
            }
            
            return $content;
        } finally {
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function importExcel(string $filePath, string $fileName): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['xls', 'xlsx', 'csv'])) {
            throw new ValidationException("Unsupported file format. Please use .xls, .xlsx, or .csv files.");
        }

        try {
            // Set up PhpSpreadsheet with proper character encoding
            $reader = IOFactory::createReaderForFile($filePath);
            
            // For CSV files, ensure UTF-8 encoding
            if ($extension === 'csv') {
                $reader->setInputEncoding('UTF-8');
            }
            
            // Load the spreadsheet
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                throw new ValidationException("Excel file is empty or missing data rows.");
            }

            $headers = array_map('trim', $rows[0]);
            $expectedHeaders = [
                'ID', 'Name', 'Level', 'Power', 'Rank', 'Participation', 'Trap Preference', 'X', 'Y',
                'Cap Level', 'Watch Level', 'Vest Level', 'Pants Level', 'Ring Level', 'Cane Level',
                'Cap Charms', 'Watch Charms', 'Vest Charms', 'Pants Charms', 'Ring Charms', 'Cane Charms'
            ];

            if ($headers !== $expectedHeaders) {
                throw new ValidationException("Excel format incorrect. Expected headers: " . implode(', ', $expectedHeaders));
            }

            $furnaces = [];
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $furnace = $this->parseAndValidateRow($row, $i + 1);
                if ($furnace) {
                    $furnaces[] = $furnace;
                }
            }

            return $furnaces;

        } catch (\Exception $e) {
            throw new ValidationException("Error reading Excel file: " . $e->getMessage());
        }
    }

    public function exportToExcel(array $furnaces): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Furnace Data');

        // Set headers
        $headers = [
            'ID', 'Name', 'Level', 'Power', 'Rank', 'Participation', 'Trap Preference', 'X', 'Y',
            'Cap Level', 'Watch Level', 'Vest Level', 'Pants Level', 'Ring Level', 'Cane Level',
            'Cap Charms', 'Watch Charms', 'Vest Charms', 'Pants Charms', 'Ring Charms', 'Cane Charms'
        ];

        foreach ($headers as $colIndex => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        // Add data with proper Unicode handling
        $rowIndex = 2;
        foreach ($furnaces as $furnace) {
            // Ensure all string values are properly UTF-8 encoded
            $sheet->setCellValue('A' . $rowIndex, $this->ensureUtf8($furnace['id']));
            $sheet->setCellValue('B' . $rowIndex, $this->ensureUtf8($furnace['name']));
            $sheet->setCellValue('C' . $rowIndex, $this->ensureUtf8($furnace['level']));
            $sheet->setCellValue('D' . $rowIndex, $furnace['power']);
            $sheet->setCellValue('E' . $rowIndex, $this->ensureUtf8($furnace['rank']));
            $sheet->setCellValue('F' . $rowIndex, $furnace['participation']);
            $sheet->setCellValue('G' . $rowIndex, $this->ensureUtf8($furnace['trap_pref']));
            $sheet->setCellValue('H' . $rowIndex, $furnace['x']);
            $sheet->setCellValue('I' . $rowIndex, $furnace['y']);
            $sheet->setCellValue('J' . $rowIndex, $this->ensureUtf8($furnace['cap_level']));
            $sheet->setCellValue('K' . $rowIndex, $this->ensureUtf8($furnace['watch_level']));
            $sheet->setCellValue('L' . $rowIndex, $this->ensureUtf8($furnace['vest_level']));
            $sheet->setCellValue('M' . $rowIndex, $this->ensureUtf8($furnace['pants_level']));
            $sheet->setCellValue('N' . $rowIndex, $this->ensureUtf8($furnace['ring_level']));
            $sheet->setCellValue('O' . $rowIndex, $this->ensureUtf8($furnace['cane_level']));
            $sheet->setCellValue('P' . $rowIndex, $this->ensureUtf8($furnace['cap_charms']));
            $sheet->setCellValue('Q' . $rowIndex, $this->ensureUtf8($furnace['watch_charms']));
            $sheet->setCellValue('R' . $rowIndex, $this->ensureUtf8($furnace['vest_charms']));
            $sheet->setCellValue('S' . $rowIndex, $this->ensureUtf8($furnace['pants_charms']));
            $sheet->setCellValue('T' . $rowIndex, $this->ensureUtf8($furnace['ring_charms']));
            $sheet->setCellValue('U' . $rowIndex, $this->ensureUtf8($furnace['cane_charms']));
            $rowIndex++;
        }

        // Add data validation
        $this->addDataValidation($sheet);

        // Freeze the header row
        $sheet->freezePane('A2');

        // Create temporary file with proper error handling
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'furnace_export_');
        
        try {
            $writer->save($tempFile);
            $content = file_get_contents($tempFile);
            
            if ($content === false) {
                throw new \RuntimeException('Failed to read generated export file');
            }
            
            return $content;
        } finally {
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Ensure a value is properly UTF-8 encoded for Excel export
     */
    private function ensureUtf8($value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = (string)$value;
        
        // Ensure the string is properly UTF-8 encoded
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        
        // Normalize Unicode characters
        return \Normalizer::normalize($value, \Normalizer::FORM_C);
    }

    private function addDataValidation($sheet): void
    {
        // Set worksheet properties to prevent Excel repair issues
        $sheet->getParent()->getProperties()
            ->setCreator('Bear Hunt Map Application')
            ->setLastModifiedBy('Bear Hunt Map Application')
            ->setTitle('Furnace Data Template')
            ->setSubject('Furnace placement data template')
            ->setDescription('Template for importing furnace data with validation')
            ->setKeywords('furnace, data, template')
            ->setCategory('Data Template');

        // Create a hidden sheet for validation lists
        $workbook = $sheet->getParent();
        $validationSheet = $workbook->createSheet();
        $validationSheet->setTitle('ValidationLists');
        $validationSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // Populate validation sheet with data
        $this->populateValidationSheet($validationSheet);

        // Apply validation to each column using named ranges
        for ($row = 2; $row <= 1000; $row++) {
            // Level column (C) - dropdown with specific values
            $validation = $sheet->getCell('C' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setFormula1('ValidationLists!$A$2:$A$41');
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setPromptTitle('Level Selection');
            $validation->setPrompt('Please select a level from 1-30 or FC1-FC10');
            $validation->setErrorTitle('Invalid Level');
            $validation->setError('Please select a valid level from the dropdown');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Power column (D) - whole number greater than 0
            $validation = $sheet->getCell('D' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_WHOLE);
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('0');
            $validation->setAllowBlank(false);
            $validation->setPromptTitle('Power Input');
            $validation->setPrompt('Enter a positive integer for power');
            $validation->setErrorTitle('Invalid Power');
            $validation->setError('Power must be a positive integer');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Rank column (E) - dropdown with specific values
            $validation = $sheet->getCell('E' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setFormula1('ValidationLists!$B$2:$B$6');
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setPromptTitle('Rank Selection');
            $validation->setPrompt('Please select a rank from R1-R5');
            $validation->setErrorTitle('Invalid Rank');
            $validation->setError('Please select a valid rank from the dropdown');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Participation column (F) - whole number between 0-4
            $validation = $sheet->getCell('F' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_WHOLE);
            $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
            $validation->setFormula1('0');
            $validation->setFormula2('4');
            $validation->setAllowBlank(true);
            $validation->setPromptTitle('Participation Input');
            $validation->setPrompt('Enter a number between 0 and 4 (or leave blank)');
            $validation->setErrorTitle('Invalid Participation');
            $validation->setError('Participation must be between 0 and 4');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Trap Preference column (G) - dropdown with specific values
            $validation = $sheet->getCell('G' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setFormula1('ValidationLists!$C$2:$C$5');
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setPromptTitle('Trap Preference Selection');
            $validation->setPrompt('Please select trap preference (or leave blank)');
            $validation->setErrorTitle('Invalid Trap Preference');
            $validation->setError('Please select a valid trap preference from the dropdown');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // X column (H) - whole number greater than or equal to 0
            $validation = $sheet->getCell('H' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_WHOLE);
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
            $validation->setFormula1('0');
            $validation->setAllowBlank(true);
            $validation->setPromptTitle('X Coordinate Input');
            $validation->setPrompt('Enter a non-negative integer for X coordinate (or leave blank)');
            $validation->setErrorTitle('Invalid X Coordinate');
            $validation->setError('X coordinate must be a non-negative integer');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Y column (I) - whole number greater than or equal to 0
            $validation = $sheet->getCell('I' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_WHOLE);
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
            $validation->setFormula1('0');
            $validation->setAllowBlank(true);
            $validation->setPromptTitle('Y Coordinate Input');
            $validation->setPrompt('Enter a non-negative integer for Y coordinate (or leave blank)');
            $validation->setErrorTitle('Invalid Y Coordinate');
            $validation->setError('Y coordinate must be a non-negative integer');
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            
            // Chief Gear Level columns (J-O) - dropdown with gear levels
            $gearColumns = ['J', 'K', 'L', 'M', 'N', 'O'];
            $gearNames = ['Cap', 'Watch', 'Vest', 'Pants', 'Ring', 'Cane'];
            $gearLevelsRange = 'ValidationLists!$D$2:$D$' . (count(self::VALID_GEAR_LEVELS) + 1);
            foreach ($gearColumns as $index => $col) {
                $validation = $sheet->getCell($col . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setFormula1($gearLevelsRange);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setPromptTitle($gearNames[$index] . ' Level Selection');
                $validation->setPrompt('Please select a gear level (or leave blank)');
                $validation->setErrorTitle('Invalid ' . $gearNames[$index] . ' Level');
                $validation->setError('Please select a valid gear level from the dropdown');
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
            }
            
            // Chief Gear Charm columns (P-U) - comma-separated values
            $gearCharmColumns = ['P', 'Q', 'R', 'S', 'T', 'U'];
            $gearCharmNames = ['Cap Charms', 'Watch Charms', 'Vest Charms', 'Pants Charms', 'Ring Charms', 'Cane Charms'];
            foreach ($gearCharmColumns as $index => $col) {
                $validation = $sheet->getCell($col . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_CUSTOM);
                $validation->setAllowBlank(true);
                $validation->setPromptTitle($gearCharmNames[$index]);
                $validation->setPrompt('Please enter your comma-separated charm values (or leave blank)');
                $validation->setShowInputMessage(true);
            }
        }

        // Set the main sheet as active
        $workbook->setActiveSheetIndex(0);
    }

    private function populateValidationSheet($sheet): void
    {
        // Column A: Levels
        $sheet->setCellValue('A1', 'Levels');
        foreach (self::VALID_LEVELS as $index => $level) {
            $sheet->setCellValue('A' . ($index + 2), $level);
        }

        // Column B: Ranks
        $sheet->setCellValue('B1', 'Ranks');
        foreach (self::VALID_RANKS as $index => $rank) {
            $sheet->setCellValue('B' . ($index + 2), $rank);
        }

        // Column C: Trap Preferences
        $sheet->setCellValue('C1', 'TrapPreferences');
        foreach (self::VALID_TRAP_PREFERENCES as $index => $pref) {
            $sheet->setCellValue('C' . ($index + 2), $pref);
        }

        // Column D: Gear Levels
        $sheet->setCellValue('D1', 'GearLevels');
        foreach (self::VALID_GEAR_LEVELS as $index => $level) {
            $sheet->setCellValue('D' . ($index + 2), $level);
        }
    }



    protected function parseAndValidateRow(array $row, int $lineNumber): ?array
    {
        // Sanitize input - preserve Unicode characters while removing problematic control characters
        $row = array_map(function($value) {
            if ($value === null) return '';
            $value = (string)$value;
            
            // Ensure the string is properly UTF-8 encoded
            if (!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
            
            // Remove only truly problematic control characters (0x00-0x08, 0x0B-0x0C, 0x0E-0x1F, 0x7F)
            // Be more conservative to avoid removing valid UTF-8 sequences
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            
            // Normalize Unicode characters
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C);
            
            // Use Unicode-aware trimming
            $value = $this->unicodeTrim($value);
            
            return $value;
        }, $row);

        $id = $row[0] ?? '';
        $name = $row[1] ?? '';
        $level = $row[2] ?? '';
        $power = $row[3] ?? '';
        $rank = $row[4] ?? '';
        $participation = $row[5] ?? '';
        $trapPref = $row[6] ?? '';
        $x = $row[7] ?? '';
        $y = $row[8] ?? '';

        // Chief gear properties
        $capLevel = $row[9] ?? '';
        $watchLevel = $row[10] ?? '';
        $vestLevel = $row[11] ?? '';
        $pantsLevel = $row[12] ?? '';
        $ringLevel = $row[13] ?? '';
        $caneLevel = $row[14] ?? '';
        $capCharms = $row[15] ?? '';
        $watchCharms = $row[16] ?? '';
        $vestCharms = $row[17] ?? '';
        $pantsCharms = $row[18] ?? '';
        $ringCharms = $row[19] ?? '';
        $caneCharms = $row[20] ?? '';

        // Process gear charms - trim whitespace from each charm value
        $capCharms = $this->processGearCharms($capCharms);
        $watchCharms = $this->processGearCharms($watchCharms);
        $vestCharms = $this->processGearCharms($vestCharms);
        $pantsCharms = $this->processGearCharms($pantsCharms);
        $ringCharms = $this->processGearCharms($ringCharms);
        $caneCharms = $this->processGearCharms($caneCharms);

        // Validate required fields using mb_strlen for proper Unicode length checking
        if (mb_strlen($name) === 0) {
            throw new ValidationException("Missing Name at line {$lineNumber}");
        }

        if (mb_strlen($level) === 0) {
            throw new ValidationException("Missing Level at line {$lineNumber}");
        }

        // Case-insensitive validation for level
        if (!$this->isValidLevel($level)) {
            throw new ValidationException("Invalid Level '{$level}' at line {$lineNumber}. Must be 1-30 or FC1-FC10");
        }

        if (empty($power) || !is_numeric($power) || (int)$power <= 0) {
            throw new ValidationException("Invalid Power '{$power}' at line {$lineNumber}. Must be a positive integer");
        }

        if (mb_strlen($rank) === 0) {
            throw new ValidationException("Missing Rank at line {$lineNumber}");
        }

        // Case-insensitive validation for rank
        if (!$this->isValidRank($rank)) {
            throw new ValidationException("Invalid Rank '{$rank}' at line {$lineNumber}. Must be R1-R5");
        }

        // Validate participation
        if (!empty($participation)) {
            if (!is_numeric($participation) || (int)$participation < 0 || (int)$participation > 4) {
                throw new ValidationException("Invalid Participation '{$participation}' at line {$lineNumber}. Must be 0-4");
            }
        }

        // Case-insensitive validation for trap preference
        if (!empty($trapPref) && !$this->isValidTrapPreference($trapPref)) {
            throw new ValidationException("Invalid Trap Preference '{$trapPref}' at line {$lineNumber}. Must be 1, 2, both, or n/a");
        }

        // Validate coordinates
        if (!empty($x)) {
            if (!is_numeric($x) || (int)$x < 0) {
                throw new ValidationException("Invalid X coordinate '{$x}' at line {$lineNumber}. Must be a positive integer");
            }
        }

        if (!empty($y)) {
            if (!is_numeric($y) || (int)$y < 0) {
                throw new ValidationException("Invalid Y coordinate '{$y}' at line {$lineNumber}. Must be a positive integer");
            }
        }

        // Validate gear levels - case-insensitive
        $gearLevels = [$capLevel, $watchLevel, $vestLevel, $pantsLevel, $ringLevel, $caneLevel];
        $gearNames = ['Cap', 'Watch', 'Vest', 'Pants', 'Ring', 'Cane'];
        
        foreach ($gearLevels as $index => $gearLevel) {
            if (!empty($gearLevel) && !$this->isValidGearLevel($gearLevel)) {
                throw new ValidationException("Invalid {$gearNames[$index]} Level '{$gearLevel}' at line {$lineNumber}");
            }
        }

        return [
            'id' => !empty($id) ? $id : null,
            'name' => $name,
            'level' => $this->normalizeLevel($level),
            'power' => (int)$power,
            'rank' => $this->normalizeRank($rank),
            'participation' => $participation !== '' ? (int)$participation : null,
            'trap_pref' => $this->normalizeTrapPreference($trapPref),
            'x' => !empty($x) ? (int)$x : null,
            'y' => !empty($y) ? (int)$y : null,
            'cap_level' => $this->normalizeGearLevel($capLevel),
            'watch_level' => $this->normalizeGearLevel($watchLevel),
            'vest_level' => $this->normalizeGearLevel($vestLevel),
            'pants_level' => $this->normalizeGearLevel($pantsLevel),
            'ring_level' => $this->normalizeGearLevel($ringLevel),
            'cane_level' => $this->normalizeGearLevel($caneLevel),
            'cap_charms' => $capCharms,
            'watch_charms' => $watchCharms,
            'vest_charms' => $vestCharms,
            'pants_charms' => $pantsCharms,
            'ring_charms' => $ringCharms,
            'cane_charms' => $caneCharms
        ];
    }

    protected function processGearCharms(string $charms): string
    {
        if (empty($charms)) {
            return '';
        }
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($charms, 'UTF-8')) {
            $charms = mb_convert_encoding($charms, 'UTF-8', 'auto');
        }
        
        // Split by comma and trim each charm value
        $charmArray = array_map(function($charm) {
            $charm = $this->unicodeTrim($charm);
            // Normalize Unicode characters in each charm
            return \Normalizer::normalize($charm, \Normalizer::FORM_C);
        }, explode(',', $charms));
        
        // Filter out empty values and rejoin
        $charmArray = array_filter($charmArray, function($charm) {
            return mb_strlen($charm) > 0;
        });
        
        return implode(',', $charmArray);
    }

    protected function isValidLevel(string $level): bool
    {
        $level = mb_strtolower($this->unicodeTrim($level));
        $validLevels = array_map('mb_strtolower', self::VALID_LEVELS);
        return in_array($level, $validLevels);
    }

    protected function isValidRank(string $rank): bool
    {
        $rank = mb_strtolower($this->unicodeTrim($rank));
        $validRanks = array_map('mb_strtolower', self::VALID_RANKS);
        return in_array($rank, $validRanks);
    }

    protected function isValidTrapPreference(string $trapPref): bool
    {
        $trapPref = mb_strtolower($this->unicodeTrim($trapPref));
        $validTrapPrefs = array_map('mb_strtolower', self::VALID_TRAP_PREFERENCES);
        return in_array($trapPref, $validTrapPrefs);
    }

    protected function isValidGearLevel(string $gearLevel): bool
    {
        $gearLevel = mb_strtolower($this->unicodeTrim($gearLevel));
        $validGearLevels = array_map('mb_strtolower', self::VALID_GEAR_LEVELS);
        return in_array($gearLevel, $validGearLevels);
    }

    protected function normalizeLevel(string $level): string
    {
        $level = mb_strtolower($this->unicodeTrim($level));
        $validLevels = array_map('mb_strtolower', self::VALID_LEVELS);
        $index = array_search($level, $validLevels);
        return $index !== false ? self::VALID_LEVELS[$index] : $level;
    }

    protected function normalizeRank(string $rank): string
    {
        $rank = mb_strtolower($this->unicodeTrim($rank));
        $validRanks = array_map('mb_strtolower', self::VALID_RANKS);
        $index = array_search($rank, $validRanks);
        return $index !== false ? self::VALID_RANKS[$index] : $rank;
    }

    protected function normalizeTrapPreference(string $trapPref): string
    {
        if (mb_strlen($trapPref) === 0) {
            return '';
        }
        $trapPref = mb_strtolower($this->unicodeTrim($trapPref));
        $validTrapPrefs = array_map('mb_strtolower', self::VALID_TRAP_PREFERENCES);
        $index = array_search($trapPref, $validTrapPrefs);
        return $index !== false ? self::VALID_TRAP_PREFERENCES[$index] : $trapPref;
    }

    protected function normalizeGearLevel(string $gearLevel): string
    {
        if (mb_strlen($gearLevel) === 0) {
            return '';
        }
        $gearLevel = mb_strtolower($this->unicodeTrim($gearLevel));
        $validGearLevels = array_map('mb_strtolower', self::VALID_GEAR_LEVELS);
        $index = array_search($gearLevel, $validGearLevels);
        return $index !== false ? self::VALID_GEAR_LEVELS[$index] : $gearLevel;
    }

    /**
     * Unicode-aware trim function
     */
    private function unicodeTrim(string $string): string
    {
        // Remove Unicode whitespace characters from start and end
        // This includes: spaces, tabs, newlines, zero-width spaces, non-breaking spaces, etc.
        $string = preg_replace('/^[\p{Z}\s\x{FEFF}\x{200B}\x{200C}\x{200D}\x{2060}]+|[\p{Z}\s\x{FEFF}\x{200B}\x{200C}\x{200D}\x{2060}]+$/u', '', $string);
        
        // Additional cleanup for any remaining problematic characters
        $string = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{2060}]/u', '', $string);
        
        return $string;
    }
} 