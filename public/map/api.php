<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set up error handling
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Set up dependency injection (same as main API but without authentication)
    $mapRepository = new \App\Infrastructure\Repository\DatabaseMapRepository();
    $excelService = new \App\Application\Service\ExcelService();
    $weightedCriteriaService = new \App\Application\Service\WeightedCriteriaService();
    $mapService = new \App\Application\Service\MapService($mapRepository, $excelService, $weightedCriteriaService);
    $mapController = new \App\Infrastructure\Api\MapController($mapService);

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_maps':
            echo json_encode($mapController->getAllMaps());
            break;
            
        case 'get_versions':
            $mapId = $_GET['map_id'] ?? '';
            
            if (empty($mapId)) {
                throw new Exception("Map ID is required");
            }
            
            echo json_encode($mapController->getVersions($mapId));
            break;
            
        case 'get_public_map_data':
            $mapId = $_GET['map_id'] ?? '';
            $version = $_GET['version'] ?? null;
            
            if (empty($mapId)) {
                throw new Exception("Map ID is required");
            }
            
            echo json_encode($mapController->getPublicMapData($mapId, $version));
            break;
            
        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
