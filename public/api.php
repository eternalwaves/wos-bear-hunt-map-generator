<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
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

// Database connection is now configured automatically from environment variables

// Set up dependency injection
$mapRepository = new \App\Infrastructure\Repository\DatabaseMapRepository();
$excelService = new \App\Application\Service\ExcelService();
$weightedCriteriaService = new \App\Application\Service\WeightedCriteriaService();
$mapService = new \App\Application\Service\MapService($mapRepository, $excelService, $weightedCriteriaService);
$mapController = new \App\Infrastructure\Api\MapController($mapService);

// Handle the request
$mapController->handleRequest(); 