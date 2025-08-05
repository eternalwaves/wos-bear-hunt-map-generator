<?php

namespace App\Infrastructure\Api;

use App\Application\Service\MapService;
use App\Application\Exception\MapNotFoundException;
use App\Application\Exception\ValidationException;

class MapController
{
    private MapService $mapService;

    public function __construct(MapService $mapService)
    {
        $this->mapService = $mapService;
    }

    /**
     * Parse form data from request body (handles both multipart and URL-encoded)
     */
    private function parseFormData(): array
    {
        $rawInput = file_get_contents("php://input");
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Check if it's multipart form data
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $boundary = null;
            if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
                $boundary = $matches[1];
            }
            
            if ($boundary) {
                $data = [];
                $parts = explode('--' . $boundary, $rawInput);
                
                foreach ($parts as $part) {
                    if (empty($part) || $part === "\r\n" || $part === "--\r\n") {
                        continue;
                    }
                    
                    // Parse the part to extract name and value
                    if (preg_match('/name="([^"]+)"/', $part, $nameMatches)) {
                        $name = $nameMatches[1];
                        // Extract the value (everything after the double newline)
                        $valueStart = strpos($part, "\r\n\r\n");
                        if ($valueStart !== false) {
                            $value = substr($part, $valueStart + 4);
                            // Remove trailing \r\n
                            $value = rtrim($value, "\r\n");
                            $data[$name] = $value;
                        }
                    }
                }
                
                return $data;
            }
        }
        
        // Fall back to URL-encoded parsing
        $data = [];
        parse_str($rawInput, $data);
        return $data;
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        // Handle special actions that return different content types
        if ($action === 'export_furnaces') {
            $this->handleExcelExport();
            return;
        }
        
        if ($action === 'download_template') {
            $this->handleTemplateDownload();
            return;
        }

        if ($action === 'generate_svg') {
            $this->handleSvgGeneration();
            return;
        }

        // Default JSON response
        header('Content-Type: application/json');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        try {
            $response = $this->processRequest($method, $action);
            echo json_encode($response);
        } catch (MapNotFoundException $e) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Exception in MapController: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
        }
    }

    private function processRequest(string $method, string $action): array
    {
        // GET requests
        if ($method === 'GET') {
            switch ($action) {
                case 'get_map':
                    return $this->getMap();
                case 'get_all_maps':
                    return $this->getAllMaps();
                case 'get_objects':
                    return $this->getObjects();
                case 'get_version':
                    return $this->getVersion();
                case 'get_versions':
                    return $this->getVersions();
                default:
                    throw new ValidationException("Invalid GET action: {$action}");
            }
        }

        // PUT requests
        if ($method === 'PUT') {
            switch ($action) {
                case 'update_furnace':
                    return $this->updateFurnace();
                case 'update_all_furnaces':
                    return $this->bulkUpdateFurnaces();
                case 'update_furnace_status':
                    return $this->updateFurnaceStatus();
                case 'set_furnace_locked':
                    return $this->setFurnaceLocked();
                case 'reset_furnaces':
                    return $this->resetFurnaces();
                case 'reset':
                    return $this->resetMap();
                case 'save_version':
                    return $this->saveVersion();
                default:
                    throw new ValidationException("Invalid PUT action: {$action}");
            }
        }

        // POST requests
        if ($method === 'POST') {
            switch ($action) {
                case 'create_map':
                    return $this->createMap();
                case 'add_trap':
                    return $this->addTrap();
                case 'add_object':
                    return $this->addMiscObject();
                case 'add_furnace':
                    return $this->addFurnace();
                case 'generate_map':
                    return $this->generateMap();
                case 'regenerate_svg':
                    return $this->regenerateSvg();
                case 'get_occupied_positions':
                    return $this->getOccupiedPositions();
                case 'upload_excel':
                    return $this->uploadExcel();
                default:
                    throw new ValidationException("Invalid POST action: {$action}");
            }
        }

        // DELETE requests
        if ($method === 'DELETE') {
            switch ($action) {
                case 'delete_trap':
                    return $this->deleteTrap();
                case 'delete_object':
                    return $this->deleteMiscObject();
                case 'delete_furnace':
                    return $this->deleteFurnace();
                case 'delete_version':
                    return $this->deleteVersion();
                default:
                    throw new ValidationException("Invalid DELETE action: {$action}");
            }
        }

        throw new ValidationException("Invalid action: {$action}");
    }

    private function createMap(): array
    {
        $name = $_POST['name'] ?? '';
        $cellSize = isset($_POST['cell_size']) ? (int)$_POST['cell_size'] : 50;

        if (empty($name)) {
            throw new ValidationException("Map name is required");
        }

        $map = $this->mapService->createMap($name, $cellSize);
        return ['status' => 'success', 'map_id' => $map->getId()];
    }

    private function getMap(): array
    {
        $mapId = $_GET['map_id'] ?? '';
        if (empty($mapId)) {
            throw new ValidationException("Map ID is required");
        }

        $map = $this->mapService->getMap($mapId);
        return ['status' => 'success', 'data' => $map->toArray()];
    }

    private function getAllMaps(): array
    {
        $maps = $this->mapService->getAllMaps();
        return ['status' => 'success', 'data' => array_map(fn($map) => $map->toArray(), $maps)];
    }

    private function addTrap(): array
    {
        $mapId = $_POST['map_id'] ?? '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : null;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : null;

        if (empty($mapId) || $x === null || $y === null) {
            throw new ValidationException("Map ID, X, and Y coordinates are required");
        }

        $trap = $this->mapService->addTrap($mapId, $x, $y);
        return ['status' => 'success', 'trap_id' => $trap->getId()];
    }

    private function deleteTrap(): array
    {
        // Parse DELETE request body as form data
        $deleteData = $this->parseFormData();
        
        $mapId = $deleteData['map_id'] ?? '';
        $trapId = $deleteData['trap_id'] ?? ''; // Updated to expect trap_id
        $version = $deleteData['version'] ?? null;

        if (empty($mapId) || empty($trapId)) {
            throw new ValidationException("Map ID and trap ID are required");
        }

        $removed = $this->mapService->removeTrap($mapId, $trapId, $version);
        return ['status' => $removed ? 'success' : 'error', 'message' => $removed ? 'Trap removed' : 'Trap not found'];
    }

    private function addMiscObject(): array
    {
        $mapId = $_POST['map_id'] ?? '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : null;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : null;
        $size = isset($_POST['size']) ? (int)$_POST['size'] : null;
        $name = $_POST['name'] ?? '';

        if (empty($mapId) || $x === null || $y === null || $size === null) {
            throw new ValidationException("Map ID, X, Y, and size are required");
        }

        $object = $this->mapService->addMiscObject($mapId, $x, $y, $size, $name);
        return ['status' => 'success', 'object_id' => $object->getId()];
    }

    private function deleteMiscObject(): array
    {
        // Parse DELETE request body as form data
        $deleteData = $this->parseFormData();
        
        $mapId = $deleteData['map_id'] ?? '';
        $objectId = $deleteData['object_id'] ?? ''; // Updated to expect object_id
        $version = $deleteData['version'] ?? null;

        if (empty($mapId) || empty($objectId)) {
            throw new ValidationException("Map ID and object ID are required");
        }

        $removed = $this->mapService->removeMiscObject($mapId, $objectId, $version);
        return ['status' => $removed ? 'success' : 'error', 'message' => $removed ? 'Object removed' : 'Object not found'];
    }

    private function addFurnace(): array
    {
        $mapId = $_POST['map_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $level = $_POST['level'] ?? '';
        $power = isset($_POST['power']) ? (int)$_POST['power'] : null;
        $rank = $_POST['rank'] ?? '';
        $participation = isset($_POST['participation']) ? (int)$_POST['participation'] : null;
        $trapPref = $_POST['trap_pref'] ?? '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : null;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : null;

        // Chief gear properties (optional)
        $capLevel = $_POST['cap_level'] ?? null;
        $watchLevel = $_POST['watch_level'] ?? null;
        $vestLevel = $_POST['vest_level'] ?? null;
        $pantsLevel = $_POST['pants_level'] ?? null;
        $ringLevel = $_POST['ring_level'] ?? null;
        $caneLevel = $_POST['cane_level'] ?? null;
        $capCharms = $_POST['cap_charms'] ?? null;
        $watchCharms = $_POST['watch_charms'] ?? null;
        $vestCharms = $_POST['vest_charms'] ?? null;
        $pantsCharms = $_POST['pants_charms'] ?? null;
        $ringCharms = $_POST['ring_charms'] ?? null;
        $caneCharms = $_POST['cane_charms'] ?? null;

        if (empty($mapId) || empty($name) || empty($level) || $power === null || empty($rank) || empty($trapPref)) {
            throw new ValidationException("Map ID, name, level, power, rank, and trap preference are required");
        }

        $furnace = $this->mapService->addFurnace(
            $mapId, $name, $level, $power, $rank, $participation, $trapPref, $x, $y,
            $capLevel, $watchLevel, $vestLevel, $pantsLevel, $ringLevel, $caneLevel,
            $capCharms, $watchCharms, $vestCharms, $pantsCharms, $ringCharms, $caneCharms
        );
        return ['status' => 'success', 'furnace_id' => $furnace->getId()];
    }

    private function updateFurnace(): array
    {
        $furnaceData = json_decode(file_get_contents("php://input"), true);
        
        if (!$furnaceData || !isset($furnaceData['map_id'], $furnaceData['id'])) {
            throw new ValidationException("Map ID and furnace ID are required");
        }

        $mapId = $furnaceData['map_id'];
        $version = $furnaceData['version'] ?? null;
        unset($furnaceData['map_id']);
        unset($furnaceData['version']);

        $updated = $this->mapService->updateFurnace($mapId, $furnaceData, $version);
        return ['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Furnace updated' : 'Furnace not found'];
    }

    private function bulkUpdateFurnaces(): array
    {
        // Parse PUT request body as form data
        $putData = $this->parseFormData();
        
        $mapId = $putData['map_id'] ?? null;
        $version = $putData['version'] ?? null;
        $furnaceUpdates = $putData['furnace_updates'] ?? null;
        
        if (!$mapId) {
            throw new ValidationException("Map ID is required for bulk furnace updates");
        }

        if (!$furnaceUpdates) {
            throw new ValidationException("Furnace updates data is required");
        }

        $furnaceUpdates = json_decode($furnaceUpdates, true);
        
        if (!is_array($furnaceUpdates) || empty($furnaceUpdates) || !isset($furnaceUpdates[0]['id'])) {
            throw new ValidationException("Invalid furnace updates format. Expected array of furnaces");
        }

        try {
            $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates, $version);
            return ['status' => 'success', 'message' => 'All furnaces updated successfully'];
        } catch (ValidationException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function generateMap(): array
    {
        try {
            $mapId = $_POST['map_id'] ?? null;
            $version = $_POST['version'] ?? null;
            $sortPriority = $_POST['sort_priority'] ?? ['power', 'level', 'rank', 'participation'];
            $criteriaWeights = $_POST['criteria_weights'] ?? null;

            if (!$mapId) {
                throw new ValidationException("Map ID is required");
            }

            if (is_string($sortPriority)) {
                $sortPriority = explode(',', $sortPriority);
            }

            // Parse criteria weights if provided
            $parsedCriteriaWeights = null;
            if ($criteriaWeights) {
                if (is_string($criteriaWeights)) {
                    $criteriaWeights = json_decode($criteriaWeights, true);
                }
                
                if (is_array($criteriaWeights)) {
                    $weightedCriteriaService = new \App\Application\Service\WeightedCriteriaService();
                    $parsedCriteriaWeights = $weightedCriteriaService->createCriteriaWeightsFromArray($criteriaWeights);
                }
            }

            $this->mapService->generateMap($mapId, $sortPriority, $parsedCriteriaWeights, $version);

            return [
                'status' => 'success',
                'message' => 'Map generated successfully'
            ];
        } catch (ValidationException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }

    private function regenerateSvg(): array
    {
        try {
            $mapId = $_POST['map_id'] ?? null;
            $version = $_POST['version'] ?? null;

            if (!$mapId) {
                throw new ValidationException("Map ID is required");
            }

            $svg = $this->mapService->generateSvg($mapId, $version);
            $success = $this->mapService->saveSvgToFile($mapId, $version);

            if (!$success) {
                throw new ValidationException("Failed to save SVG file");
            }

            return [
                'status' => 'success',
                'svg' => $svg,
                'message' => 'SVG regenerated successfully'
            ];
        } catch (ValidationException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }

    private function getOccupiedPositions(): array
    {
        try {
            $mapId = $_POST['map_id'] ?? null;
            $version = $_POST['version'] ?? null;

            if (!$mapId) {
                throw new ValidationException("Map ID is required");
            }

            $occupiedPositions = $this->mapService->getOccupiedPositions($mapId, $version);

            return [
                'status' => 'success',
                'occupied_positions' => $occupiedPositions
            ];
        } catch (ValidationException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }

    private function deleteFurnace(): array
    {
        // Parse DELETE request body as form data
        $deleteData = $this->parseFormData();
        
        $mapId = $deleteData['map_id'] ?? '';
        $furnaceId = $deleteData['furnace_id'] ?? '';
        $version = $deleteData['version'] ?? null;

        if (empty($mapId) || empty($furnaceId)) {
            throw new ValidationException("Map ID and furnace ID are required");
        }

        $removed = $this->mapService->removeFurnace($mapId, $furnaceId, $version);
        return ['status' => $removed ? 'success' : 'error', 'message' => $removed ? 'Furnace removed' : 'Furnace not found'];
    }

    private function updateFurnaceStatus(): array
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['map_id'], $data['furnace_id'], $data['status'])) {
            throw new ValidationException("Map ID, furnace ID, and status are required");
        }

        $version = $data['version'] ?? null;
        $updated = $this->mapService->updateFurnaceStatus($data['map_id'], $data['furnace_id'], $data['status'], $version);
        return ['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Status updated' : 'Furnace not found'];
    }

    private function setFurnaceLocked(): array
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['map_id'], $data['furnace_id'], $data['locked'])) {
            throw new ValidationException("Map ID, furnace ID, and locked status are required");
        }

        $version = $data['version'] ?? null;
        $updated = $this->mapService->setFurnaceLocked($data['map_id'], $data['furnace_id'], (bool)$data['locked'], $version);
        return ['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Lock status updated' : 'Furnace not found'];
    }

    private function resetFurnaces(): array
    {
        // Parse PUT request body as form data
        $putData = $this->parseFormData();
        
        $mapId = $putData['map_id'] ?? '';
        $version = $putData['version'] ?? null;
        
        if (empty($mapId)) {
            throw new ValidationException("Map ID is required");
        }

        $this->mapService->resetFurnaces($mapId, $version);
        return ['status' => 'success', 'message' => 'Furnaces reset successfully'];
    }

    private function resetMap(): array
    {
        // Parse PUT request body as form data
        $putData = $this->parseFormData();
        
        $mapId = $putData['map_id'] ?? '';
        if (empty($mapId)) {
            throw new ValidationException("Map ID is required");
        }

        $this->mapService->resetMap($mapId);
        return ['status' => 'success', 'message' => 'Map reset successfully'];
    }

    private function getObjects(): array
    {
        $mapId = $_GET['map_id'] ?? '';
        $version = $_GET['version'] ?? null;
        
        if (empty($mapId)) {
            throw new ValidationException("Map ID is required");
        }

        if ($version) {
            $data = $this->mapService->exportMapData($mapId, $version);
        } else {
            $data = $this->mapService->exportMapData($mapId);
        }
        
        return ['status' => 'success', 'data' => $data];
    }

    private function saveVersion(): array
    {
        // Parse PUT request body as JSON (consistent with other PUT methods)
        $putData = json_decode(file_get_contents("php://input"), true);
        
        if (!$putData || !isset($putData['map_id'], $putData['version'])) {
            throw new ValidationException("Map ID and version are required");
        }

        $mapId = $putData['map_id'];
        $version = $putData['version'];

        $this->mapService->saveVersion($mapId, $version);
        return ['status' => 'success', 'message' => 'Version saved successfully'];
    }

    private function getVersion(): array
    {
        $mapId = $_GET['map_id'] ?? '';
        $version = $_GET['version'] ?? '';

        if (empty($mapId) || empty($version)) {
            throw new ValidationException("Map ID and version are required");
        }

        $map = $this->mapService->getVersion($mapId, $version);
        return ['status' => 'success', 'data' => $map->toArray()];
    }

    private function getVersions(): array
    {
        $mapId = $_GET['map_id'] ?? '';
        if (empty($mapId)) {
            throw new ValidationException("Map ID is required");
        }

        $versions = $this->mapService->getVersions($mapId);
        return ['status' => 'success', 'data' => $versions];
    }

    private function deleteVersion(): array
    {
        // Parse DELETE request body as form data
        $deleteData = $this->parseFormData();
        
        $mapId = $deleteData['map_id'] ?? '';
        $version = $deleteData['version'] ?? '';

        if (empty($mapId) || empty($version)) {
            throw new ValidationException("Map ID and version are required");
        }

        $deleted = $this->mapService->deleteVersion($mapId, $version);
        return ['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Version deleted' : 'Version not found'];
    }

    private function uploadExcel(): array
    {
        $mapId = $_POST['map_id'] ?? '';
        $file = $_FILES['csv_file'] ?? null;

        if (empty($mapId) || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException("Map ID and Excel file are required");
        }

        $filePath = $file['tmp_name'];
        $fileName = $file['name'];

        try {
            $this->mapService->importExcel($mapId, $filePath, $fileName);
            return ['status' => 'success', 'message' => 'Excel file uploaded and processed successfully'];
        } catch (ValidationException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }

    private function handleExcelExport(): void
    {
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment; filename=furnaces.xlsx");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        try {
            $mapId = $_GET['map_id'] ?? '';
            $version = $_GET['version'] ?? null;
            
            if (empty($mapId)) {
                throw new ValidationException("Map ID is required for furnace export");
            }

            $excelContent = $this->mapService->exportFurnacesToExcel($mapId, $version);
            echo $excelContent;
        } catch (\Exception $e) {
            http_response_code(400);
            echo "Error: " . $e->getMessage();
        }
    }

    private function handleTemplateDownload(): void
    {
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment; filename=furnace_template.xlsx");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        try {
            $mapId = $_GET['map_id'] ?? '';
            if (empty($mapId)) {
                throw new ValidationException("Map ID is required for template download");
            }

            $templateContent = $this->mapService->generateTemplateExcel($mapId);
            echo $templateContent;
        } catch (\Exception $e) {
            http_response_code(400);
            echo "Error: " . $e->getMessage();
        }
    }

    private function handleSvgGeneration(): void
    {
        header("Content-Type: image/svg+xml");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        try {
            $mapId = $_GET['map_id'] ?? '';
            $version = $_GET['version'] ?? null;

            if (empty($mapId)) {
                // If no map_id provided, try to get the latest map
                $maps = $this->mapService->getAllMaps();
                if (empty($maps)) {
                    throw new ValidationException("No maps available");
                }
                $mapId = $maps[0]->getId();
            }

            $svgContent = $this->mapService->generateSvg($mapId, $version);
            echo $svgContent;
        } catch (ValidationException $e) {
            http_response_code(400);
            echo $this->generateErrorSvg("Validation Error: " . $e->getMessage());
        } catch (MapNotFoundException $e) {
            http_response_code(404);
            echo $this->generateErrorSvg("Map Not Found: " . $e->getMessage());
        } catch (\Exception $e) {
            http_response_code(500);
            echo $this->generateErrorSvg("Internal Server Error: " . $e->getMessage());
        }
    }

    private function generateErrorSvg(string $message): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="200" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
    <text x="200" y="100" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#dc3545">
        ' . htmlspecialchars($message) . '
    </text>
</svg>';
    }
} 