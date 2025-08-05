<?php

use PHPUnit\Framework\TestCase;
use App\Application\Service\MapService;
use App\Domain\Map\Map;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;
use App\Application\Exception\MapNotFoundException;
use App\Application\Exception\ValidationException;

class MapServiceTest extends TestCase
{
    private ?MapService $mapService = null;
    private $mockRepository = null;

    protected function setUp(): void
    {
        // Create fresh mocks and service for each test
        // This ensures each test starts with a clean state
        $this->mockRepository = $this->createMock(\App\Domain\Repository\MapRepositoryInterface::class);
        $excelService = $this->createMock(\App\Application\Service\ExcelService::class);
        $this->mapService = new MapService($this->mockRepository, $excelService);
    }





    /**
     * Create a test map with predictable properties
     */
    private function createTestMap(string $name = "Test Map", int $cellSize = 50): Map
    {
        return new Map($name, $cellSize);
    }

    /**
     * Create a test map with objects at predictable, non-conflicting positions
     */
    private function createTestMapWithObjects(): Map
    {
        $map = $this->createTestMap();
        
        // Add objects at positions that won't conflict with typical test positions
        $map->addTrap(new Trap(100, 100));  // Far from typical test positions (10,20), (15,25), etc.
        $map->addMiscObject(new MiscObject(200, 200, 2, "Test Rock"));
        $map->addFurnace(new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 300, 300, null, 'assigned', false));
        
        return $map;
    }

    /**
     * Create a test map with objects at specific positions for collision testing
     */
    private function createTestMapWithObjectsAtPositions(array $positions): Map
    {
        $map = $this->createTestMap();
        
        foreach ($positions as $position) {
            $x = $position['x'];
            $y = $position['y'];
            $type = $position['type'];
            
            switch ($type) {
                case 'trap':
                    $map->addTrap(new Trap($x, $y));
                    break;
                case 'misc':
                    $size = $position['size'] ?? 1;
                    $name = $position['name'] ?? "Test Object";
                    $map->addMiscObject(new MiscObject($x, $y, $size, $name));
                    break;
                case 'furnace':
                    $map->addFurnace(new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", $x, $y, null, 'assigned', false));
                    break;
            }
        }
        
        return $map;
    }

    public function testCreateMap()
    {
        $name = "Test Map";
        $cellSize = 50;
        $expectedMap = new Map($name, $cellSize);

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Map $map) use ($name, $cellSize) {
                return $map->getName() === $name && $map->getCellSize() === $cellSize;
            }));

        $result = $this->mapService->createMap($name, $cellSize);

        $this->assertInstanceOf(Map::class, $result);
        $this->assertEquals($name, $result->getName());
        $this->assertEquals($cellSize, $result->getCellSize());
    }

    public function testCreateMapWithDefaultCellSize()
    {
        $name = "Test Map";
        $expectedMap = new Map($name, 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->createMap($name);

        $this->assertEquals(50, $result->getCellSize());
    }

    public function testCreateMapWithEmptyNameThrowsException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Map name is required");

        $this->mapService->createMap("");
    }

    public function testGetMap()
    {
        $mapId = "test_map_get";
        $expectedMap = $this->createTestMap();

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($expectedMap);

        $result = $this->mapService->getMap($mapId);

        $this->assertSame($expectedMap, $result);
    }

    public function testGetMapNotFoundThrowsException()
    {
        $mapId = "nonexistent";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn(null);

        $this->expectException(MapNotFoundException::class);
        $this->expectExceptionMessage("Map with ID nonexistent not found");

        $this->mapService->getMap($mapId);
    }

    public function testGetAllMaps()
    {
        $maps = [
            new Map("Map 1", 50),
            new Map("Map 2", 60)
        ];

        $this->mockRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($maps);

        $result = $this->mapService->getAllMaps();

        $this->assertSame($maps, $result);
    }

    public function testAddTrap()
    {
        $mapId = "map_123";
        $x = 10;
        $y = 20;
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->addTrap($mapId, $x, $y);

        $this->assertInstanceOf(Trap::class, $result);
        $this->assertEquals($x, $result->getX());
        $this->assertEquals($y, $result->getY());
        $this->assertTrue($map->hasTrap($result));
    }

    public function testAddTrapToNonexistentMapThrowsException()
    {
        $mapId = "nonexistent";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn(null);

        $this->expectException(MapNotFoundException::class);

        $this->mapService->addTrap($mapId, 10, 20);
    }

    public function testAddTrapWithInvalidCoordinatesThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Invalid coordinates");

        $this->mapService->addTrap($mapId, -1, 20);
    }

    public function testRemoveTrap()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $trap = new Trap(10, 20);
        $map->addTrap($trap);
        $trapId = $trap->getId();

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->removeTrap($mapId, $trapId);

        $this->assertTrue($result);
        $this->assertFalse($map->hasTrap($trap));
    }

    public function testRemoveTrapNotFound()
    {
        $mapId = "map_123";
        $trapId = "nonexistent";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $result = $this->mapService->removeTrap($mapId, $trapId);

        $this->assertFalse($result);
    }

    public function testAddMiscObject()
    {
        $mapId = "map_123";
        $x = 10;
        $y = 20;
        $size = 2;
        $name = "Rock";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->addMiscObject($mapId, $x, $y, $size, $name);

        $this->assertInstanceOf(MiscObject::class, $result);
        $this->assertEquals($x, $result->getX());
        $this->assertEquals($y, $result->getY());
        $this->assertEquals($size, $result->getSize());
        $this->assertEquals($name, $result->getName());
        $this->assertTrue($map->hasMiscObject($result));
    }

    public function testAddMiscObjectWithLargeSize()
    {
        $mapId = "map_123";
        $x = 10;
        $y = 20;
        $size = 10; // Large size that was previously not allowed
        $name = "Large Rock";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->addMiscObject($mapId, $x, $y, $size, $name);

        $this->assertInstanceOf(MiscObject::class, $result);
        $this->assertEquals($x, $result->getX());
        $this->assertEquals($y, $result->getY());
        $this->assertEquals($size, $result->getSize());
        $this->assertEquals($name, $result->getName());
        $this->assertTrue($map->hasMiscObject($result));
    }

    public function testAddMiscObjectWithInvalidSizeThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Size must be at least 1");

        $this->mapService->addMiscObject($mapId, 10, 20, 0, "Rock");
    }

    public function testRemoveMiscObject()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $object = new MiscObject(10, 20, 2, "Rock");
        $map->addMiscObject($object);
        $objectId = $object->getId();

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->removeMiscObject($mapId, $objectId);

        $this->assertTrue($result);
        $this->assertFalse($map->hasMiscObject($object));
    }

    public function testAddFurnace()
    {
        $mapId = "map_123";
        $name = "Furnace 1";
        $level = "FC1";
        $power = 100;
        $rank = "R1";
        $participation = 2;
        $trapPref = "both";
        $x = 10;
        $y = 20;
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->addFurnace($mapId, $name, $level, $power, $rank, $participation, $trapPref, $x, $y);

        $this->assertInstanceOf(Furnace::class, $result);
        $this->assertEquals($name, $result->getName());
        $this->assertEquals($level, $result->getLevel());
        $this->assertEquals($power, $result->getPower());
        $this->assertEquals($rank, $result->getRank());
        $this->assertEquals($participation, $result->getParticipation());
        $this->assertEquals($trapPref, $result->getTrapPref());
        $this->assertEquals($x, $result->getX());
        $this->assertEquals($y, $result->getY());
        $this->assertTrue($map->hasFurnace($result));
    }

    public function testAddFurnaceWithMissingRequiredFieldsThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Name, level, power, and rank are required");

        $this->mapService->addFurnace($mapId, "", "FC1", 100, "R1", 2, "both", 10, 20);
    }

    public function testUpdateFurnace()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $furnace = new Furnace("Old Name", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);
        $furnaceId = $furnace->getId();

        $updateData = [
            'id' => $furnaceId,
            'name' => 'New Name',
            'level' => 'FC2',
            'power' => 150,
            'rank' => 'R2',
            'participation' => 3,
            'trap_pref' => 'neither',
            'x' => 15,
            'y' => 25
        ];

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->updateFurnace($mapId, $updateData);

        $this->assertTrue($result);
        
        // Get the updated furnace from the map
        $updatedFurnace = null;
        foreach ($map->getFurnaces() as $f) {
            if ($f->getId() === $furnaceId) {
                $updatedFurnace = $f;
                break;
            }
        }
        
        $this->assertNotNull($updatedFurnace);
        $this->assertEquals('New Name', $updatedFurnace->getName());
        $this->assertEquals('FC2', $updatedFurnace->getLevel());
        $this->assertEquals(150, $updatedFurnace->getPower());
    }

    public function testUpdateFurnaceNotFound()
    {
        $mapId = "map_123";
        $furnaceId = "nonexistent";
        $map = new Map("Test Map", 50);

        $updateData = [
            'id' => $furnaceId,
            'name' => 'New Name'
        ];

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $result = $this->mapService->updateFurnace($mapId, $updateData);

        $this->assertFalse($result);
    }

    public function testRemoveFurnace()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);
        $furnaceId = $furnace->getId();

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->removeFurnace($mapId, $furnaceId);

        $this->assertTrue($result);
        $this->assertFalse($map->hasFurnace($furnace));
    }

    public function testUpdateFurnaceStatus()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);
        $furnaceId = $furnace->getId();
        $status = "assigned";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->updateFurnaceStatus($mapId, $furnaceId, $status);

        $this->assertTrue($result);
        $this->assertEquals($status, $furnace->getStatus());
    }

    public function testSetFurnaceLocked()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);
        $furnaceId = $furnace->getId();
        $locked = true;

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->mapService->setFurnaceLocked($mapId, $furnaceId, $locked);

        $this->assertTrue($result);
        $this->assertEquals($locked, $furnace->isLocked());
    }

    public function testResetFurnaces()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $this->mapService->resetFurnaces($mapId);

        $this->assertEmpty($map->getFurnaces());
    }

    public function testResetMap()
    {
        $mapId = "test_map_reset";
        $map = $this->createTestMapWithObjectsAtPositions([
            ['x' => 10, 'y' => 20, 'type' => 'trap'],
            ['x' => 15, 'y' => 25, 'type' => 'misc', 'size' => 2, 'name' => 'Rock'],
            ['x' => 20, 'y' => 30, 'type' => 'furnace']
        ]);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $this->mapService->resetMap($mapId);

        $this->assertEmpty($map->getTraps());
        $this->assertEmpty($map->getMiscObjects());
        $this->assertEmpty($map->getFurnaces());
    }

    public function testExportMapData()
    {
        $mapId = "test_map_export";
        $map = $this->createTestMapWithObjectsAtPositions([
            ['x' => 10, 'y' => 20, 'type' => 'trap'],
            ['x' => 15, 'y' => 25, 'type' => 'misc', 'size' => 2, 'name' => 'Rock'],
            ['x' => 20, 'y' => 30, 'type' => 'furnace']
        ]);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $result = $this->mapService->exportMapData($mapId);

        $this->assertArrayHasKey('traps', $result);
        $this->assertArrayHasKey('misc', $result);
        $this->assertArrayHasKey('furnaces', $result);
        $this->assertArrayHasKey('occupied', $result);
        $this->assertArrayHasKey('cellSize', $result);
        $this->assertEquals(50, $result['cellSize']);
    }

    public function testSaveVersion()
    {
        $mapId = "map_123";
        $version = "v1.0";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('saveVersion')
            ->with($map, $version);

        $this->mapService->saveVersion($mapId, $version);
    }

    public function testGetVersion()
    {
        $mapId = "map_123";
        $version = "v1.0";
        $expectedMap = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findVersion')
            ->with($mapId, $version)
            ->willReturn($expectedMap);

        $result = $this->mapService->getVersion($mapId, $version);

        $this->assertSame($expectedMap, $result);
    }

    public function testGetVersionNotFoundThrowsException()
    {
        $mapId = "map_123";
        $version = "nonexistent";

        $this->mockRepository
            ->expects($this->once())
            ->method('findVersion')
            ->with($mapId, $version)
            ->willReturn(null);

        $this->expectException(MapNotFoundException::class);
        $this->expectExceptionMessage("Map version nonexistent not found for map {$mapId}");

        $this->mapService->getVersion($mapId, $version);
    }

    public function testGetVersions()
    {
        $mapId = "map_123";
        $versions = [
            ['version' => 'v1.0', 'updated_at' => '2023-01-01'],
            ['version' => 'v1.1', 'updated_at' => '2023-01-02']
        ];

        $this->mockRepository
            ->expects($this->once())
            ->method('getVersions')
            ->with($mapId)
            ->willReturn($versions);

        $result = $this->mapService->getVersions($mapId);

        $this->assertSame($versions, $result);
    }

    public function testDeleteVersion()
    {
        $mapId = "map_123";
        $version = "v1.0";

        $this->mockRepository
            ->expects($this->once())
            ->method('deleteVersion')
            ->with($mapId, $version)
            ->willReturn(true);

        $result = $this->mapService->deleteVersion($mapId, $version);

        $this->assertTrue($result);
    }

    public function testDeleteVersionNotFound()
    {
        $mapId = "map_123";
        $version = "nonexistent";

        $this->mockRepository
            ->expects($this->once())
            ->method('deleteVersion')
            ->with($mapId, $version)
            ->willReturn(false);

        $result = $this->mapService->deleteVersion($mapId, $version);

        $this->assertFalse($result);
    }

    // Collision Detection Tests
    public function testAddTrapWithCollisionThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add a trap at position (10, 20)
        $existingTrap = new Trap(10, 20);
        $map->addTrap($existingTrap);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Position (10, 20) is already occupied");

        $this->mapService->addTrap($mapId, 10, 20);
    }

    public function testAddMiscObjectWithCollisionThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add a misc object at position (10, 20) with size 2
        $existingObject = new MiscObject(10, 20, 2, "Rock");
        $map->addMiscObject($existingObject);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Position (11, 21) is already occupied");

        // Try to add another object that would overlap
        $this->mapService->addMiscObject($mapId, 11, 21, 2, "Another Rock");
    }

    public function testAddFurnaceWithCollisionThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add a furnace at position (10, 20)
        $existingFurnace = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($existingFurnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Position (10, 20) is already occupied");

        $this->mapService->addFurnace($mapId, "Furnace 2", "FC2", 150, "R2", 3, "neither", 10, 20);
    }

    public function testUpdateFurnaceWithCollisionThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add two furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 3, "neither", 15, 25, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Position (10, 20) is already occupied");

        // Try to move furnace2 to furnace1's position
        $updateData = [
            'id' => $furnace2->getId(),
            'name' => 'Furnace 2',
            'level' => 'FC2',
            'power' => 150,
            'rank' => 'R2',
            'participation' => 3,
            'trap_pref' => 'neither',
            'x' => 10,
            'y' => 20
        ];

        $this->mapService->updateFurnace($mapId, $updateData);
    }

    public function testBulkUpdateFurnaces()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add two furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 3, "neither", 15, 25, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        $furnaceUpdates = [
            [
                'id' => $furnace1->getId(),
                'name' => 'Updated Furnace 1',
                'level' => 'FC1',
                'power' => 120,
                'rank' => 'R1',
                'participation' => 2,
                'trap_pref' => 'both',
                'x' => 30,
                'y' => 40
            ],
            [
                'id' => $furnace2->getId(),
                'name' => 'Updated Furnace 2',
                'level' => 'FC2',
                'power' => 180,
                'rank' => 'R2',
                'participation' => 4,
                'trap_pref' => 'neither',
                'x' => 35,
                'y' => 45
            ]
        ];

        $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates);

        // Verify the furnaces were updated
        $updatedFurnaces = $map->getFurnaces();
        $this->assertCount(2, $updatedFurnaces);
        
        $updatedFurnace1 = $updatedFurnaces[0];
        $this->assertEquals('Updated Furnace 1', $updatedFurnace1->getName());
        $this->assertEquals(120, $updatedFurnace1->getPower());
        $this->assertEquals(30, $updatedFurnace1->getX());
        $this->assertEquals(40, $updatedFurnace1->getY());
        
        $updatedFurnace2 = $updatedFurnaces[1];
        $this->assertEquals('Updated Furnace 2', $updatedFurnace2->getName());
        $this->assertEquals(180, $updatedFurnace2->getPower());
        $this->assertEquals(35, $updatedFurnace2->getX());
        $this->assertEquals(45, $updatedFurnace2->getY());
    }

    public function testBulkUpdateFurnacesWithCollisionThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add two furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 3, "neither", 15, 25, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $furnaceUpdates = [
            [
                'id' => $furnace1->getId(),
                'name' => 'Updated Furnace 1',
                'level' => 'FC1',
                'power' => 120,
                'rank' => 'R1',
                'participation' => 2,
                'trap_pref' => 'both',
                'x' => 30,
                'y' => 40
            ],
            [
                'id' => $furnace2->getId(),
                'name' => 'Updated Furnace 2',
                'level' => 'FC2',
                'power' => 180,
                'rank' => 'R2',
                'participation' => 4,
                'trap_pref' => 'neither',
                'x' => 30, // This will collide with furnace1's new position
                'y' => 40
            ]
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Position (30, 40) is already occupied");

        $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates);
    }

    public function testBulkUpdateFurnacesWithMissingFieldsThrowsException()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $furnaceUpdates = [
            [
                'id' => 'furnace_123',
                'name' => 'Updated Furnace 1',
                // Missing required fields: level, power, rank
                'x' => 30,
                'y' => 40
            ]
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Missing required field 'level' in furnace update");

        $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates);
    }

    public function testBulkUpdateFurnacesSwappingPositions()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add two furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 3, "neither", 15, 25, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        // Swap their positions
        $furnaceUpdates = [
            [
                'id' => $furnace1->getId(),
                'name' => 'Furnace 1',
                'level' => 'FC1',
                'power' => 100,
                'rank' => 'R1',
                'participation' => 2,
                'trap_pref' => 'both',
                'x' => 15,
                'y' => 25
            ],
            [
                'id' => $furnace2->getId(),
                'name' => 'Furnace 2',
                'level' => 'FC2',
                'power' => 150,
                'rank' => 'R2',
                'participation' => 3,
                'trap_pref' => 'neither',
                'x' => 10,
                'y' => 20
            ]
        ];

        $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates);

        // Verify the positions were swapped
        $updatedFurnaces = $map->getFurnaces();
        $this->assertCount(2, $updatedFurnaces);
        
        $updatedFurnace1 = $updatedFurnaces[0];
        $this->assertEquals(15, $updatedFurnace1->getX());
        $this->assertEquals(25, $updatedFurnace1->getY());
        
        $updatedFurnace2 = $updatedFurnaces[1];
        $this->assertEquals(10, $updatedFurnace2->getX());
        $this->assertEquals(20, $updatedFurnace2->getY());
    }

    public function testGetCollisionInfo()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add a trap at position (10, 20)
        $trap = new Trap(10, 20);
        $map->addTrap($trap);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $result = $this->mapService->exportMapData($mapId);
        
        // Check that the trap is in the occupied positions
        $this->assertArrayHasKey('10,20', $result['occupied']);
        $this->assertEquals('trap', $result['occupied']['10,20']);
    }

    public function testBulkUpdateFurnacesWithOldFormat()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", 50);
        
        // Add a furnace
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 10, 20, null, 'assigned', false);
        $map->addFurnace($furnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save');

        // Test with existing update_all_furnaces format
        $furnaceUpdates = [
            [
                'id' => $furnace->getId(),
                'name' => 'Updated Furnace',
                'level' => 'FC1',
                'power' => 120,
                'rank' => 'R1',
                'participation' => 2,
                'trap_pref' => 'both',
                'x' => 30,
                'y' => 40
            ]
        ];

        $this->mapService->bulkUpdateFurnaces($mapId, $furnaceUpdates);

        // Verify the furnace was updated
        $updatedFurnaces = $map->getFurnaces();
        $this->assertCount(1, $updatedFurnaces);
        
        $updatedFurnace = $updatedFurnaces[0];
        $this->assertEquals('Updated Furnace', $updatedFurnace->getName());
        $this->assertEquals(120, $updatedFurnace->getPower());
        $this->assertEquals(30, $updatedFurnace->getX());
        $this->assertEquals(40, $updatedFurnace->getY());
    }

    // SVG Generation Tests
    public function testGenerateSvgWithValidMap()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add some test objects
        $trap = new Trap(10, 20);
        $map->addTrap($trap);
        
        $miscObject = new MiscObject(15, 25, 2, "Test Object");
        $map->addMiscObject($miscObject);
        
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 20, 30, null, 'assigned', false);
        $map->addFurnace($furnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $svg = $this->mapService->generateSvg($mapId);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function testGenerateSvgWithVersion()
    {
        $mapId = "map_123";
        $version = "2.0";
        $map = new Map("Test Map", $version, $mapId);
        
        $trap = new Trap(10, 20);
        $map->addTrap($trap);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('findVersion')
            ->with($mapId, $version)
            ->willReturn($map);

        $svg = $this->mapService->generateSvg($mapId, $version);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function testGenerateSvgWithNonExistentMap()
    {
        $mapId = "nonexistent_map";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn(null);

        $this->expectException(\App\Application\Exception\ValidationException::class);
        $this->expectExceptionMessage("Map not found: {$mapId}");

        $this->mapService->generateSvg($mapId);
    }

    public function testGenerateSvgWithNonExistentVersion()
    {
        $mapId = "map_123";
        $version = "nonexistent_version";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn(new Map("Test Map", "1.0", $mapId));

        $this->mockRepository
            ->expects($this->once())
            ->method('findVersion')
            ->with($mapId, $version)
            ->willReturn(null);

        $this->expectException(\App\Application\Exception\ValidationException::class);
        $this->expectExceptionMessage("Version not found: {$version}");

        $this->mapService->generateSvg($mapId, $version);
    }

    public function testSaveSvgToFile()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        $trap = new Trap(10, 20);
        $map->addTrap($trap);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        // Test with custom file path
        $tempFile = tempnam(sys_get_temp_dir(), 'test_svg_');
        
        $result = $this->mapService->saveSvgToFile($mapId, null, $tempFile);
        
        $this->assertTrue($result);
        $this->assertFileExists($tempFile);
        
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('<svg', $content);
        
        unlink($tempFile);
    }

    public function testGetOccupiedPositions()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        $trap = new Trap(10, 20);
        $map->addTrap($trap);
        
        $miscObject = new MiscObject(15, 25, 2, "Test Object");
        $map->addMiscObject($miscObject);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $occupiedPositions = $this->mapService->getOccupiedPositions($mapId);

        $this->assertIsArray($occupiedPositions);
        $this->assertNotEmpty($occupiedPositions);
        
        // Check that trap positions are marked as occupied
        $this->assertArrayHasKey('10,20', $occupiedPositions);
        $this->assertArrayHasKey('11,20', $occupiedPositions);
        $this->assertArrayHasKey('12,20', $occupiedPositions);
        $this->assertArrayHasKey('10,21', $occupiedPositions);
        $this->assertArrayHasKey('11,21', $occupiedPositions);
        $this->assertArrayHasKey('12,21', $occupiedPositions);
        $this->assertArrayHasKey('10,22', $occupiedPositions);
        $this->assertArrayHasKey('11,22', $occupiedPositions);
        $this->assertArrayHasKey('12,22', $occupiedPositions);
        
        // Check that misc object positions are marked as occupied
        $this->assertArrayHasKey('15,25', $occupiedPositions);
        $this->assertArrayHasKey('16,25', $occupiedPositions);
        $this->assertArrayHasKey('15,26', $occupiedPositions);
        $this->assertArrayHasKey('16,26', $occupiedPositions);
    }

    public function testSvgContainsCorrectElements()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        $trap = new Trap(10, 20);
        $map->addTrap($trap);
        
        $miscObject = new MiscObject(15, 25, 2, "Test Object");
        $map->addMiscObject($miscObject);
        
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 20, 30, null, 'assigned', false);
        $map->addFurnace($furnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $svg = $this->mapService->generateSvg($mapId);

        // Check for trap elements
        $this->assertStringContainsString('brown', $svg);
        $this->assertStringContainsString('Trap 1', $svg);
        $this->assertStringContainsString('(10,20)', $svg);
        
        // Check for misc object elements
        $this->assertStringContainsString('darkgrey', $svg);
        $this->assertStringContainsString('Test Object', $svg);
        $this->assertStringContainsString('(15,25)', $svg);
        
        // Check for furnace elements
        $this->assertStringContainsString('#2DCCFF', $svg);
        $this->assertStringContainsString('Test Furnace', $svg);
        $this->assertStringContainsString('(20,30)', $svg);
        $this->assertStringContainsString('data-obj-id="', $svg);
    }

    public function testSvgWithFurnaceStatus()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        $furnace = new Furnace("Test Furnace", "FC1", 100, "R1", 2, "both", 20, 30, null, 'messaged', false);
        $map->addFurnace($furnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $svg = $this->mapService->generateSvg($mapId);

        // Check for status-specific styling
        $this->assertStringContainsString('#FFAF3D', $svg); // messaged color
        $this->assertStringContainsString(' messaged', $svg); // messaged class
    }

    // Map Generation Tests
    public function testGenerateMapWithValidData()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add traps
        $trap1 = new Trap(10, 20);
        $trap2 = new Trap(30, 40);
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        // Add misc objects
        $miscObject = new MiscObject(15, 25, 2, "Test Object");
        $map->addMiscObject($miscObject);
        
        // Add furnaces
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "both", null, null, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($map);

        $this->mapService->generateMap($mapId);

        // Verify that furnaces have been positioned
        $furnaces = $map->getFurnaces();
        $this->assertCount(2, $furnaces);
        
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }

    public function testGenerateMapWithCustomSortPriority()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add traps
        $trap1 = new Trap(10, 20);
        $trap2 = new Trap(30, 40);
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        // Add furnaces with different priorities
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "both", null, null, null, 'assigned', false);
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($map);

        $sortPriority = ['rank', 'power', 'level'];
        $this->mapService->generateMap($mapId, $sortPriority);

        // Verify that furnaces have been positioned
        $furnaces = $map->getFurnaces();
        $this->assertCount(2, $furnaces);
    }

    public function testGenerateMapWithInsufficientTraps()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add only one trap
        $trap = new Trap(10, 20);
        $map->addTrap($trap);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->expectException(\App\Application\Exception\ValidationException::class);
        $this->expectExceptionMessage("At least 2 traps are required for map generation");

        $this->mapService->generateMap($mapId);
    }

    public function testGenerateMapWithNonExistentMap()
    {
        $mapId = "nonexistent_map";

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn(null);

        $this->expectException(\App\Application\Exception\ValidationException::class);
        $this->expectExceptionMessage("Map not found: {$mapId}");

        $this->mapService->generateMap($mapId);
    }

    public function testGenerateMapWithLockedFurnaces()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add traps
        $trap1 = new Trap(10, 20);
        $trap2 = new Trap(30, 40);
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        // Add locked furnace with position
        $lockedFurnace = new Furnace("Locked Furnace", "FC1", 100, "R1", 2, "both", 25, 35, null, "assigned", true);
        $map->addFurnace($lockedFurnace);
        
        // Add unlocked furnace
        $unlockedFurnace = new Furnace("Unlocked Furnace", "FC2", 150, "R2", 1, "both", null, null, null, 'assigned', false);
        $map->addFurnace($unlockedFurnace);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($map);

        $this->mapService->generateMap($mapId);

        // Verify that locked furnace keeps its position
        $furnaces = $map->getFurnaces();
        $this->assertCount(2, $furnaces);
        
        $lockedFurnace = null;
        $unlockedFurnace = null;
        
        foreach ($furnaces as $furnace) {
            if ($furnace->getName() === "Locked Furnace") {
                $lockedFurnace = $furnace;
            } elseif ($furnace->getName() === "Unlocked Furnace") {
                $unlockedFurnace = $furnace;
            }
        }
        
        $this->assertNotNull($lockedFurnace);
        $this->assertEquals(25, $lockedFurnace->getX());
        $this->assertEquals(35, $lockedFurnace->getY());
        
        $this->assertNotNull($unlockedFurnace);
        $this->assertNotNull($unlockedFurnace->getX());
        $this->assertNotNull($unlockedFurnace->getY());
    }

    public function testGenerateMapWithDifferentTrapPreferences()
    {
        $mapId = "map_123";
        $map = new Map("Test Map", "1.0", $mapId);
        
        // Add traps
        $trap1 = new Trap(10, 20);
        $trap2 = new Trap(30, 40);
        $map->addTrap($trap1);
        $map->addTrap($trap2);
        
        // Add furnaces with different trap preferences
        $furnace1 = new Furnace("Furnace 1", "FC1", 100, "R1", 2, "both", null, null, null, 'assigned', false);
        $furnace2 = new Furnace("Furnace 2", "FC2", 150, "R2", 1, "both", null, null, null, 'assigned', false);
        $furnace3 = new Furnace("Furnace 3", "FC3", 200, "R3", 3, "both", null, null, null, 'assigned', false);
        $furnace4 = new Furnace("Furnace 4", "FC4", 250, "R4", 4, "both", null, null, null, 'assigned', false);
        
        $map->addFurnace($furnace1);
        $map->addFurnace($furnace2);
        $map->addFurnace($furnace3);
        $map->addFurnace($furnace4);

        $this->mockRepository
            ->expects($this->once())
            ->method('findById')
            ->with($mapId)
            ->willReturn($map);

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($map);

        $this->mapService->generateMap($mapId);

        // Verify that all furnaces have been positioned
        $furnaces = $map->getFurnaces();
        $this->assertCount(4, $furnaces);
        
        foreach ($furnaces as $furnace) {
            $this->assertNotNull($furnace->getX());
            $this->assertNotNull($furnace->getY());
        }
    }
} 