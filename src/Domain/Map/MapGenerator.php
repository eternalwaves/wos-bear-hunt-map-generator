<?php

namespace App\Domain\Map;

use App\Domain\MapObject\Furnace;
use App\Domain\MapObject\Trap;
use App\Application\Exception\ValidationException;
use App\Application\Service\WeightedCriteriaService;

class MapGenerator
{
    private const FURNACE_SIZE = 2;
    private const MAX_RADIUS = 20;
    
    private Map $map;
    private array $occupiedPositions = [];
    private array $spiralPositions = [];
    private ?WeightedCriteriaService $weightedCriteriaService;
    private ?array $gapZone = null;
    private array $trapZoneBounds = []; // Store zone bounds for each trap

    public function __construct(Map $map, WeightedCriteriaService $weightedCriteriaService = null)
    {
        $this->map = $map;
        $this->weightedCriteriaService = $weightedCriteriaService;
        $this->initializeOccupiedPositions();
        
        // Validate that we have at least 2 traps before generating spiral positions
        $traps = $this->map->getTraps();
        if (count($traps) >= 2) {
            $this->generateSpiralPositions();
        }
    }

    public function generateMap(array $sortPriority = ['power', 'level', 'rank', 'participation'], ?array $criteriaWeights = null): void
    {
        $traps = $this->map->getTraps();
        if (count($traps) < 2) {
            throw new ValidationException("At least 2 traps are required for map generation");
        }
        
        // Generate spiral positions if not already done
        if (empty($this->spiralPositions)) {
            $this->generateSpiralPositions();
        }

        // Sort furnaces by priority
        $furnaces = $criteriaWeights ? $this->sortFurnacesByWeightedCriteria($criteriaWeights) : $this->sortFurnaces($sortPriority);
        $placedFurnaces = [];
        $remainingFurnaces = [];
        $cantDo = [];

        foreach ($furnaces as $furnace) {
            if ($furnace->isLocked()) {
                $placedFurnaces[] = $furnace;
                continue;
            }

            $trapId = $furnace->getTrapPref();
            
            if ($trapId == '1' || $trapId == '2' || strtolower($trapId) === 'both') {
                $spiralPositions = $this->getSpiralPositionsForTrap($trapId);
                $placedFurnace = $this->placeFurnace($furnace, $spiralPositions);
                if ($placedFurnace) {
                    $placedFurnaces[] = $placedFurnace;
                }
            } else {
                if (strtolower($trapId) === 'n/a') {
                    $cantDo[] = $furnace;
                } else {
                    $remainingFurnaces[] = $furnace;
                }
            }
        }
        
        // Place remaining furnaces in center area
        $remainingFurnaces = array_merge($remainingFurnaces, $cantDo);
        foreach ($remainingFurnaces as $furnace) {
            if ($furnace->isLocked()) {
                $placedFurnaces[] = $furnace;
                continue;
            }

            $spiralPositions = $this->spiralPositions['center'];
            $placedFurnace = $this->placeFurnace($furnace, $spiralPositions);
            if ($placedFurnace) {
                $placedFurnaces[] = $placedFurnace;
            }
        }

        // Update map with placed furnaces
        $this->map->setFurnaces($placedFurnaces);
    }

    private function initializeOccupiedPositions(): void
    {
        // Mark traps as occupied and create 4x4 no-occupy zones
        $this->createTrapNoOccupyZones();

        // Mark misc objects as occupied
        foreach ($this->map->getMiscObjects() as $miscObject) {
            $this->markPositionOccupied($miscObject->getX(), $miscObject->getY(), $miscObject->getSize(), 'misc');
        }

        // Mark locked furnaces as occupied
        foreach ($this->map->getFurnaces() as $furnace) {
            if ($furnace->isLocked() && $furnace->getX() && $furnace->getY()) {
                $this->markPositionOccupied($furnace->getX(), $furnace->getY(), self::FURNACE_SIZE, $furnace->getTrapPref());
            }
        }
    }

    /**
     * Create 4x4 no-occupy zones around each trap
     * This ensures furnaces are placed properly around traps without gaps
     */
    private function createTrapNoOccupyZones(): void
    {
        $traps = $this->map->getTraps();
        $miscObjects = $this->map->getMiscObjects();
        
        // First, calculate and store the gap zone (but don't mark it as occupied)
        $this->enforceTrapGap($traps);
        
        // Then calculate and mark trap zones, ensuring they don't overlap with the gap
        foreach ($traps as $index => $trap) {
            $trapX = $trap->getX();
            $trapY = $trap->getY();
            
            // Find banners that are touching this trap
            $touchingBanners = $this->findTouchingBanners($trap, $miscObjects);
            
            // Calculate the 4x4 zone that includes the trap and touching banners
            $zoneBounds = $this->calculateZoneBounds($trap, $touchingBanners);
            
            // Store the zone bounds for this trap (index 0 for trap1, 1 for trap2)
            $this->trapZoneBounds[$index] = $zoneBounds;
            
            // Mark the entire zone as occupied
            for ($x = $zoneBounds['startX']; $x < $zoneBounds['endX']; $x++) {
                for ($y = $zoneBounds['startY']; $y < $zoneBounds['endY']; $y++) {
                    $this->occupiedPositions["$x,$y"] = 'trap_zone';
                }
            }
        }
    }

    /**
     * Find banners that are touching a trap
     */
    private function findTouchingBanners(Trap $trap, array $miscObjects): array
    {
        $touchingBanners = [];
        $trapX = $trap->getX();
        $trapY = $trap->getY();
        $trapSize = $trap->getSize();
        
        foreach ($miscObjects as $object) {
            if ($object->getName() === 'Banner') {
                $objectX = $object->getX();
                $objectY = $object->getY();
                $objectSize = $object->getSize();
                
                // Check if banner is touching the trap (including diagonally)
                if ($this->objectsAreTouching($trapX, $trapY, $trapSize, $objectX, $objectY, $objectSize)) {
                    $touchingBanners[] = $object;
                }
            }
        }
        
        return $touchingBanners;
    }

    /**
     * Check if two objects are touching (including diagonally)
     */
    private function objectsAreTouching(int $x1, int $y1, int $size1, int $x2, int $y2, int $size2): bool
    {
        // Calculate the bounds of each object
        $obj1EndX = $x1 + $size1 - 1;
        $obj1EndY = $y1 + $size1 - 1;
        $obj2EndX = $x2 + $size2 - 1;
        $obj2EndY = $y2 + $size2 - 1;
        
        // Check if objects are adjacent (touching but not overlapping)
        // Objects are touching if they are adjacent (no gap between them)
        return (
            // Right edge
            ($obj1EndX === $x2 - 1 && (
                $y2 - 1 === $obj1EndY || (
                    $obj2EndY >= $y1 - 1 &&
                    $obj2EndY < $obj1EndY
                )
            )) ||
            // Left edge
            ($x1 === $obj2EndX - 1 && (
                $y2 - 1 === $obj1EndY || (
                    $obj2EndY >= $y1 - 1 &&
                    $obj2EndY < $obj1EndY
                )
            )) ||
            // Top edge
            ($obj1EndY === $y2 - 1 && (
                $x2 - 1 === $obj1EndX || (
                    $obj2EndX >= $x1 - 1 &&
                    $obj2EndX < $obj1EndX
                )
            )) ||
            // Bottom edge
            ($y1 === $obj2EndY - 1 && (
                $x2 - 1 === $obj1EndX || (
                    $obj2EndX >= $x1 - 1 &&
                    $obj2EndX < $obj1EndX
                )
            ))
        );
    }

    /**
     * Calculate the bounds of a 4x4 zone that includes the trap and touching banners
     * Ensures the zone doesn't overlap with the gap between traps
     */
    private function calculateZoneBounds(Trap $trap, array $touchingBanners): array
    {
        $trapX = $trap->getX();
        $trapY = $trap->getY();
        $trapSize = $trap->getSize();
        
        // Start with trap bounds
        $minX = $trapX;
        $maxX = $trapX + $trapSize - 1;
        $minY = $trapY;
        $maxY = $trapY + $trapSize - 1;
        
        // Extend bounds to include touching banners
        foreach ($touchingBanners as $banner) {
            $bannerX = $banner->getX();
            $bannerY = $banner->getY();
            $bannerSize = $banner->getSize();
            $bannerEndX = $bannerX + $bannerSize - 1;
            $bannerEndY = $bannerY + $bannerSize - 1;
            
            $minX = min($minX, $bannerX);
            $maxX = max($maxX, $bannerEndX);
            $minY = min($minY, $bannerY);
            $maxY = max($maxY, $bannerEndY);
        }
        
        // Calculate the center of the combined area
        $centerX = round(($minX + $maxX) / 2);
        $centerY = round(($minY + $maxY) / 2);
        
        // Create a 4x4 zone centered on this area
        $zoneSize = 4;
        $startX = $centerX - ($zoneSize / 2);
        $startY = $centerY - ($zoneSize / 2);
        $endX = $startX + $zoneSize;
        $endY = $startY + $zoneSize;
        
        
        // Check if this zone would overlap with the gap zone
        if ($this->gapZone && $this->zonesOverlap(
            $startX, $startY, $endX, $endY,
            $this->gapZone['startX'], $this->gapZone['startY'], 
            $this->gapZone['endX'], $this->gapZone['endY']
        )) {
            // Adjust the zone to avoid overlap
            // Move the zone away from the gap zone
            if ($centerX < $this->gapZone['startX']) {
                // Zone is to the left of gap, move it further left
                $startX = $this->gapZone['startX'] - $zoneSize;
                $endX = $startX + $zoneSize;
            } elseif ($centerX > $this->gapZone['endX']) {
                // Zone is to the right of gap, move it further right
                $startX = $this->gapZone['endX'];
                $endX = $startX + $zoneSize;
            }
            
            if ($centerY < $this->gapZone['startY']) {
                // Zone is above gap, move it further up
                $startY = $this->gapZone['startY'] - $zoneSize;
                $endY = $startY + $zoneSize;
            } elseif ($centerY > $this->gapZone['endY']) {
                // Zone is below gap, move it further down
                $startY = $this->gapZone['endY'];
                $endY = $startY + $zoneSize;
            }
        }
        
        return [
            'startX' => $startX,
            'startY' => $startY,
            'endX' => $endX,
            'endY' => $endY
        ];
    }

    /**
     * Check if two zones overlap
     */
    private function zonesOverlap(int $startX1, int $startY1, int $endX1, int $endY1, 
                                 int $startX2, int $startY2, int $endX2, int $endY2): bool
    {
        return !($endX1 < $startX2 || $endX2 < $startX1 || $endY1 < $startY2 || $endY2 < $startY1);
    }

    /**
     * Ensure there's a 6-space gap between traps
     * This doesn't mark the gap as occupied, but ensures 4x4 zones don't overlap with it
     */
    private function enforceTrapGap(array $traps): void
    {
        if (count($traps) < 2) {
            return; // Need at least 2 traps to enforce gap
        }
        
        $trap1 = $traps[0];
        $trap2 = $traps[1];
        
        $trap1X = $trap1->getX();
        $trap1Y = $trap1->getY();
        $trap2X = $trap2->getX();
        $trap2Y = $trap2->getY();
        
        // Calculate the distance between trap centers
        $trap1CenterX = $trap1X + 1; // Center of 3x3 trap
        $trap1CenterY = $trap1Y + 1;
        $trap2CenterX = $trap2X + 1;
        $trap2CenterY = $trap2Y + 1;
        
        $distanceX = abs($trap2CenterX - $trap1CenterX);
        $distanceY = abs($trap2CenterY - $trap1CenterY);
        
        // The minimum distance should be 6 spaces between trap edges
        // Since each trap is 3x3, the minimum center-to-center distance should be 9 (6 + 3)
        $minDistance = 9;
        
        if ($distanceX < $minDistance || $distanceY < $minDistance) {
            // Calculate the midpoint between traps
            $midX = round(($trap1CenterX + $trap2CenterX) / 2);
            $midY = round(($trap1CenterY + $trap2CenterY) / 2);
            
            // Define the gap zone (6x6 area in the middle)
            $gapSize = 6;
            $gapStartX = $midX - ($gapSize / 2);
            $gapStartY = $midY - ($gapSize / 2);
            $gapEndX = $gapStartX + $gapSize;
            $gapEndY = $gapStartY + $gapSize;
            
            // Store gap information for later use in zone calculation
            $this->gapZone = [
                'startX' => $gapStartX,
                'startY' => $gapStartY,
                'endX' => $gapEndX,
                'endY' => $gapEndY
            ];
        }
    }

    private function generateSpiralPositions(): void
    {
        $traps = $this->map->getTraps();
        
        // Generate positions for each trap (first ring as square around zone, then spiral)
        $this->spiralPositions['trap1'] = $this->generateSquarePositionsForTrap($traps[0]->getX(), $traps[0]->getY(), 1, 0);
        $this->spiralPositions['trap2'] = $this->generateSquarePositionsForTrap($traps[1]->getX(), $traps[1]->getY(), 2, 1);
        
        // Get the first ring positions for each trap to exclude them from center positions
        $trap1FirstRing = $this->getFirstRingPositionsForTrap(0);
        $trap2FirstRing = $this->getFirstRingPositionsForTrap(1);
        $excludedPositions = array_merge($trap1FirstRing, $trap2FirstRing);
        
        // Generate center spiral positions
        $leftX = min($traps[0]->getX(), $traps[1]->getX()) + 3;
        $rightX = max($traps[0]->getX(), $traps[1]->getX());
        $midX = round(($leftX + $rightX) / 2) - 1;
        
        // Calculate centerY that aligns with one of the first ring Y-values of the traps
        // First, get the zone centers for both traps
        $trap1ZoneCenterY = round(($this->trapZoneBounds[0]['startY'] + $this->trapZoneBounds[0]['endY']) / 2);
        $trap2ZoneCenterY = round(($this->trapZoneBounds[1]['startY'] + $this->trapZoneBounds[1]['endY']) / 2);
        
        // Get the first ring Y-values for both traps (Y-4, Y-2, Y, Y+2)
        $trap1FirstRingY = [$trap1ZoneCenterY - 4, $trap1ZoneCenterY - 2, $trap1ZoneCenterY, $trap1ZoneCenterY + 2];
        $trap2FirstRingY = [$trap2ZoneCenterY - 4, $trap2ZoneCenterY - 2, $trap2ZoneCenterY, $trap2ZoneCenterY + 2];
        
        // Find a Y-value that appears in both first rings or is close to both
        $allFirstRingY = array_merge($trap1FirstRingY, $trap2FirstRingY);
        $bottomY = min($traps[0]->getY(), $traps[1]->getY());
        $topY = max($traps[0]->getY(), $traps[1]->getY()) + 3;
        $originalMidY = round(($bottomY + $topY) / 2) - 1; // Original calculation as fallback
        
        // Try to find a Y-value that's close to the midpoint and aligns with first ring patterns
        $bestY = $originalMidY;
        $minDistance = PHP_INT_MAX;
        
        foreach ($allFirstRingY as $y) {
            $distance = abs($y - $originalMidY);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestY = $y;
            }
        }
        
        $midY = $bestY;
        
        $this->spiralPositions['center'] = $this->generateSquarePositionsForTrap($midX, $midY, '', -1, $excludedPositions);
    }

    private function generateSquarePositionsForTrap(int $trapX, int $trapY, $group, int $trapIndex = -1, array $excludedPositions = []): array
    {
        $positions = [];
        
        // For trap-specific positioning, use the zone center if available
        if ($trapIndex >= 0 && isset($this->trapZoneBounds[$trapIndex])) {
            $zoneBounds = $this->trapZoneBounds[$trapIndex];
            // Calculate center of the 4x4 zone
            $centerX = round(($zoneBounds['startX'] + $zoneBounds['endX']) / 2);
            $centerY = round(($zoneBounds['startY'] + $zoneBounds['endY']) / 2);
        } else {
            // For center positioning, use the original trap center
            $centerX = $trapX;
            $centerY = $trapY;
        }

        // For trap-specific positioning, generate the first ring as a perfect square
        if ($trapIndex >= 0) {
            // Define the exact coordinates for the first ring (distance 2 from zone center)
            $firstRingXCoords = [$centerX - 4, $centerX - 2, $centerX, $centerX + 2];
            $firstRingYCoords = [$centerY - 4, $centerY - 2, $centerY, $centerY + 2];
            
            // Determine which side is away from the gap to prioritize
            $prioritizeSide = $this->getPrioritizedSide($trapIndex, $centerX, $centerY);
            
            // Generate positions in the prioritized order
            $firstRingPositions = $this->generatePrioritizedFirstRing(
                $firstRingXCoords, 
                $firstRingYCoords, 
                $prioritizeSide
            );
            
            // Add first ring positions
            foreach ($firstRingPositions as $pos) {
                if (!isset($this->occupiedPositions["{$pos[0]},{$pos[1]}"])) {
                    $positions["{$pos[0]},{$pos[1]}"] = $pos;
                }
            }
            
            // Generate subsequent rings in a spiral pattern from the zone center
            for ($radius = 4; $radius <= self::MAX_RADIUS; $radius++) {
                // Generate positions in a spiral pattern around the zone center
                for ($dx = -$radius; $dx <= $radius; $dx++) {
                    $px = $centerX + $dx;

                    // Top row
                    $py = $centerY - $radius;
                    if (!isset($this->occupiedPositions["$px,$py"])) {
                        $positions["$px,$py"] = [$px, $py];
                    }

                    // Bottom row
                    $py = $centerY + $radius;
                    if (!isset($this->occupiedPositions["$px,$py"])) {
                        $positions["$px,$py"] = [$px, $py];
                    }
                }

                for ($dy = -$radius; $dy <= $radius; $dy++) {
                    $py = $centerY + $dy;

                    // Left column
                    $px = $centerX - $radius;
                    if (!isset($this->occupiedPositions["$px,$py"])) {
                        $positions["$px,$py"] = [$px, $py];
                    }

                    // Right column
                    $px = $centerX + $radius;
                    if (!isset($this->occupiedPositions["$px,$py"])) {
                        $positions["$px,$py"] = [$px, $py];
                    }
                }
            }
        } else {
            // For center positioning, use a regular spiral pattern (no first ring concept)
            // Generate positions in a spiral pattern around the center
            for ($radius = 0; $radius <= self::MAX_RADIUS; $radius++) {
                // Generate positions in a spiral pattern around the center
                for ($dx = -$radius; $dx <= $radius; $dx++) {
                    $px = $centerX + $dx;

                    // Top row
                    $py = $centerY - $radius;
                    if (!isset($this->occupiedPositions["$px,$py"]) && !$this->isPositionExcluded($px, $py, $excludedPositions)) {
                        $positions["$px,$py"] = [$px, $py];
                    }

                    // Bottom row
                    $py = $centerY + $radius;
                    if (!isset($this->occupiedPositions["$px,$py"]) && !$this->isPositionExcluded($px, $py, $excludedPositions)) {
                        $positions["$px,$py"] = [$px, $py];
                    }
                }

                for ($dy = -$radius; $dy <= $radius; $dy++) {
                    $py = $centerY + $dy;

                    // Left column
                    $px = $centerX - $radius;
                    if (!isset($this->occupiedPositions["$px,$py"]) && !$this->isPositionExcluded($px, $py, $excludedPositions)) {
                        $positions["$px,$py"] = [$px, $py];
                    }

                    // Right column
                    $px = $centerX + $radius;
                    if (!isset($this->occupiedPositions["$px,$py"]) && !$this->isPositionExcluded($px, $py, $excludedPositions)) {
                        $positions["$px,$py"] = [$px, $py];
                    }
                }
            }
        }

        // Sort positions by proximity to center for better placement (except first ring which is already prioritized)
        $this->sortPositions($positions, $centerX, $centerY, $group);

        return $positions;
    }

    /**
     * Determine which side of the trap is away from the gap to prioritize placement
     */
    private function getPrioritizedSide(int $trapIndex, int $centerX, int $centerY): string
    {
        if (!$this->gapZone) {
            return 'all'; // No gap, prioritize all sides equally
        }
        
        // Determine which side is furthest from the gap zone
        $gapCenterX = ($this->gapZone['startX'] + $this->gapZone['endX']) / 2;
        $gapCenterY = ($this->gapZone['startY'] + $this->gapZone['endY']) / 2;
        
        $distanceToGap = sqrt(pow($centerX - $gapCenterX, 2) + pow($centerY - $gapCenterY, 2));
        
        // For trap 1 (left), prioritize right side
        // For trap 2 (right), prioritize left side
        if ($trapIndex === 0) {
            return 'right';
        } else {
            return 'left';
        }
    }

    /**
     * Generate first ring positions in prioritized order
     */
    private function generatePrioritizedFirstRing(array $xCoords, array $yCoords, string $prioritizeSide): array
    {
        $positions = [];
        
        // Define the order based on priority
        $order = [];
        
        if ($prioritizeSide === 'right') {
            // Prioritize right side first, then top/bottom, then left
            $order = [
                // Right side (furthest from gap) - X+2 positions
                [$xCoords[3], $yCoords[0]], // top-right (X+2, Y-4)
                [$xCoords[3], $yCoords[1]], // top-right (X+2, Y-2)
                [$xCoords[3], $yCoords[2]], // middle-right (X+2, Y)
                [$xCoords[3], $yCoords[3]], // bottom-right (X+2, Y+2)
                // Top and bottom edges
                [$xCoords[0], $yCoords[0]], // top-left (X-4, Y-4)
                [$xCoords[1], $yCoords[0]], // top-left (X-2, Y-4)
                [$xCoords[2], $yCoords[0]], // top-middle (X, Y-4)
                [$xCoords[0], $yCoords[3]], // bottom-left (X-4, Y+2)
                [$xCoords[1], $yCoords[3]], // bottom-left (X-2, Y+2)
                [$xCoords[2], $yCoords[3]], // bottom-middle (X, Y+2)
                // Left side (closest to gap) - X-4 and X-2 positions
                [$xCoords[0], $yCoords[1]], // middle-left (X-4, Y-2)
                [$xCoords[0], $yCoords[2]], // middle-left (X-4, Y)
                [$xCoords[1], $yCoords[1]], // middle-left (X-2, Y-2)
                [$xCoords[1], $yCoords[2]], // middle-left (X-2, Y)
            ];
        } elseif ($prioritizeSide === 'left') {
            // Prioritize left side first, then top/bottom, then right
            $order = [
                // Left side (furthest from gap) - X-4 positions
                [$xCoords[0], $yCoords[0]], // top-left (X-4, Y-4)
                [$xCoords[0], $yCoords[1]], // top-left (X-4, Y-2)
                [$xCoords[0], $yCoords[2]], // middle-left (X-4, Y)
                [$xCoords[0], $yCoords[3]], // bottom-left (X-4, Y+2)
                // Left side - X-2 positions
                [$xCoords[1], $yCoords[0]], // top-left (X-2, Y-4)
                [$xCoords[1], $yCoords[1]], // top-left (X-2, Y-2)
                [$xCoords[1], $yCoords[2]], // middle-left (X-2, Y)
                [$xCoords[1], $yCoords[3]], // bottom-left (X-2, Y+2)
                // Top and bottom edges
                [$xCoords[2], $yCoords[0]], // top-middle (X, Y-4)
                [$xCoords[3], $yCoords[0]], // top-right (X+2, Y-4)
                [$xCoords[2], $yCoords[3]], // bottom-middle (X, Y+2)
                [$xCoords[3], $yCoords[3]], // bottom-right (X+2, Y+2)
                // Right side (closest to gap) - X+2 positions
                [$xCoords[3], $yCoords[1]], // middle-right (X+2, Y-2)
                [$xCoords[3], $yCoords[2]], // middle-right (X+2, Y)
            ];
        } else {
            // No specific priority, use standard order (clockwise from top-left)
            $order = [
                // Top row
                [$xCoords[0], $yCoords[0]], // top-left (X-4, Y-4)
                [$xCoords[1], $yCoords[0]], // top-left (X-2, Y-4)
                [$xCoords[2], $yCoords[0]], // top-middle (X, Y-4)
                [$xCoords[3], $yCoords[0]], // top-right (X+2, Y-4)
                // Right column
                [$xCoords[3], $yCoords[1]], // top-right (X+2, Y-2)
                [$xCoords[3], $yCoords[2]], // middle-right (X+2, Y)
                [$xCoords[3], $yCoords[3]], // bottom-right (X+2, Y+2)
                // Bottom row
                [$xCoords[2], $yCoords[3]], // bottom-middle (X, Y+2)
                [$xCoords[1], $yCoords[3]], // bottom-left (X-2, Y+2)
                [$xCoords[0], $yCoords[3]], // bottom-left (X-4, Y+2)
                // Left column
                [$xCoords[0], $yCoords[2]], // middle-left (X-4, Y)
                [$xCoords[0], $yCoords[1]], // top-left (X-4, Y-2)
                // Inner positions
                [$xCoords[1], $yCoords[1]], // inner (X-2, Y-2)
                [$xCoords[2], $yCoords[1]], // inner (X, Y-2)
                [$xCoords[1], $yCoords[2]], // inner (X-2, Y)
                [$xCoords[2], $yCoords[2]], // inner (X, Y)
            ];
        }
        
        return $order;
    }

    private function sortPositions(array &$positions, int $centerX, int $centerY, $group): void
    {
        // For trap-specific positioning, preserve first ring order and sort the rest
        if (is_numeric($group) && $group >= 0) {
            // This is trap-specific positioning - preserve first ring order
            $firstRingPositions = [];
            $otherPositions = [];
            
            // Convert group number to trap ID for isFirstRingPosition
            $trapId = ($group == 1) ? '1' : '2';
            
            foreach ($positions as $posKey => $pos) {
                // Use the existing helper method to check if this is a first ring position
                if ($this->isFirstRingPosition($pos[0], $pos[1], $trapId)) {
                    $firstRingPositions[$posKey] = $pos;
                } else {
                    $otherPositions[$posKey] = $pos;
                }
            }
            
            // Sort only the non-first-ring positions by proximity to center
            uasort($otherPositions, function ($a, $b) use ($centerX, $centerY) {
                $distA = sqrt(pow($a[0] - $centerX, 2) + pow($a[1] - $centerY, 2));
                $distB = sqrt(pow($b[0] - $centerX, 2) + pow($b[1] - $centerY, 2));
                return $distA <=> $distB;
            });
            
            // Clear the original array and rebuild it in the correct order
            foreach ($positions as $posKey => $pos) {
                unset($positions[$posKey]);
            }
            foreach ($firstRingPositions as $posKey => $pos) {
                $positions[$posKey] = $pos;
            }
            foreach ($otherPositions as $posKey => $pos) {
                $positions[$posKey] = $pos;
            }
        } else {
            // For center positioning, sort all positions by proximity to center
            uasort($positions, function ($a, $b) use ($centerX, $centerY) {
                $distA = sqrt(pow($a[0] - $centerX, 2) + pow($a[1] - $centerY, 2));
                $distB = sqrt(pow($b[0] - $centerX, 2) + pow($b[1] - $centerY, 2));
                return $distA <=> $distB;
            });
        }
    }

    private function getDistanceToOccupied(int $x, int $y): int
    {
        $minDistance = PHP_INT_MAX;
        
        foreach ($this->occupiedPositions as $posKey => $groupType) {
            [$occupiedX, $occupiedY] = explode(',', $posKey);
            $distance = abs($x - $occupiedX) + abs($y - $occupiedY);
            $minDistance = min($minDistance, $distance);
        }
        
        return $minDistance;
    }

    private function getSpiralPositionsForTrap($trapId): array
    {
        if ($trapId == '1') {
            return $this->spiralPositions['trap1'];
        } elseif ($trapId == '2') {
            return $this->spiralPositions['trap2'];
        } else {
            return $this->spiralPositions['center'];
        }
    }

    private function placeFurnace(Furnace $furnace, array $spiralPositions): ?Furnace
    {
        $bestSpot = $this->findBestPlacement($furnace, $spiralPositions);
        
        if ($bestSpot) {
            [$x, $y] = $bestSpot;
            $furnace->setPosition($x, $y);
            return $furnace;
        }

        return null;
    }

    private function findBestPlacement(Furnace $furnace, array $spiralPositions): ?array
    {
        $trapId = $furnace->getTrapPref();
        
        // Calculate the trap center for distance comparison
        $trapCenterX = 0;
        $trapCenterY = 0;
        
        if ($trapId == '1' || $trapId == '2') {
            $trapIndex = ($trapId == '1') ? 0 : 1;
            $traps = $this->map->getTraps();
            if (isset($traps[$trapIndex])) {
                $trap = $traps[$trapIndex];
                $trapCenterX = $trap->getX() + 1; // Center of 3x3 trap
                $trapCenterY = $trap->getY() + 1;
            }
        } else {
            // For center positioning, use the center of the map
            $traps = $this->map->getTraps();
            if (count($traps) >= 2) {
                $trap1CenterX = $traps[0]->getX() + 1;
                $trap1CenterY = $traps[0]->getY() + 1;
                $trap2CenterX = $traps[1]->getX() + 1;
                $trap2CenterY = $traps[1]->getY() + 1;
                $trapCenterX = round(($trap1CenterX + $trap2CenterX) / 2);
                $trapCenterY = round(($trap1CenterY + $trap2CenterY) / 2);
            }
        }

        foreach ($spiralPositions as [$px, $py]) {
            if ($this->isPositionFree($px, $py, self::FURNACE_SIZE)) {
                $this->markPositionOccupied($px, $py, self::FURNACE_SIZE, $trapId);
                return [$px, $py];
            }
        }

        return null;
    }

    private function isPositionFree(int $x, int $y, int $size): bool
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                if (isset($this->occupiedPositions[($x + $dx) . "," . ($y + $dy)])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function markPositionOccupied(int $x, int $y, int $size, $group = true): void
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $this->occupiedPositions[($x + $dx) . "," . ($y + $dy)] = $group;
                
                // Remove from spiral positions
                foreach ($this->spiralPositions as &$spiralPositions) {
                    if (isset($spiralPositions[($x + $dx) . "," . ($y + $dy)])) {
                        unset($spiralPositions[($x + $dx) . "," . ($y + $dy)]);
                    }
                }
            }
        }
    }

    private function sortFurnaces(array $sortPriority): array
    {
        $furnaces = $this->map->getFurnaces();
        
        usort($furnaces, function ($a, $b) use ($sortPriority) {
            // First, sort by chief gear placement priority (highest priority first)
            $priorityA = $a->getPlacementPriority();
            $priorityB = $b->getPlacementPriority();
            
            if ($priorityA !== $priorityB) {
                return $priorityB <=> $priorityA; // Descending order (highest priority first)
            }
            
            // If placement priorities are equal, fall back to the original sort criteria
            foreach ($sortPriority as $key) {
                $valueA = $this->getFurnaceValue($a, $key);
                $valueB = $this->getFurnaceValue($b, $key);
                
                if ($valueA != $valueB) {
                    return $valueB <=> $valueA; // Descending order
                }
            }
            return 0;
        });

        return $furnaces;
    }

    private function getFurnaceValue(Furnace $furnace, string $key)
    {
        switch ($key) {
            case 'power':
                return $furnace->getPower();
            case 'level':
                return $furnace->getLevel();
            case 'rank':
                return $furnace->getRank();
            case 'participation':
                return $furnace->getParticipation();
            default:
                return 0;
        }
    }

    private function sortFurnacesByWeightedCriteria(array $criteriaWeights): array
    {
        if (!$this->weightedCriteriaService) {
            throw new \RuntimeException('WeightedCriteriaService is required for weighted criteria sorting');
        }
        
        $furnaces = $this->map->getFurnaces();
        return $this->weightedCriteriaService->sortFurnacesByWeightedCriteria($furnaces, $criteriaWeights);
    }

    public function getOccupiedPositions(): array
    {
        return $this->occupiedPositions;
    }

    /**
     * Get the first ring positions for a specific trap
     */
    private function getFirstRingPositionsForTrap(int $trapIndex): array
    {
        if (!isset($this->trapZoneBounds[$trapIndex])) {
            return [];
        }
        
        $zoneBounds = $this->trapZoneBounds[$trapIndex];
        $centerX = round(($zoneBounds['startX'] + $zoneBounds['endX']) / 2);
        $centerY = round(($zoneBounds['startY'] + $zoneBounds['endY']) / 2);
        
        // Define the exact coordinates for the first ring (distance 2 from zone center)
        $firstRingXCoords = [$centerX - 4, $centerX - 2, $centerX, $centerX + 2];
        $firstRingYCoords = [$centerY - 4, $centerY - 2, $centerY, $centerY + 2];
        
        // Determine which side is away from the gap to prioritize
        $prioritizeSide = $this->getPrioritizedSide($trapIndex, $centerX, $centerY);
        
        // Generate positions in the prioritized order
        $firstRingPositions = $this->generatePrioritizedFirstRing(
            $firstRingXCoords, 
            $firstRingYCoords, 
            $prioritizeSide
        );
        
        return $firstRingPositions;
    }

    private function isPositionExcluded(int $x, int $y, array $excludedPositions): bool
    {
        foreach ($excludedPositions as $excludedPos) {
            if ($x === $excludedPos[0] && $y === $excludedPos[1]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a position is in the first ring for a specific trap
     */
    private function isFirstRingPosition(int $x, int $y, string $trapId): bool
    {
        $trapIndex = ($trapId == '1') ? 0 : 1;
        
        if (!isset($this->trapZoneBounds[$trapIndex])) {
            return false;
        }
        
        $zoneBounds = $this->trapZoneBounds[$trapIndex];
        $centerX = round(($zoneBounds['startX'] + $zoneBounds['endX']) / 2);
        $centerY = round(($zoneBounds['startY'] + $zoneBounds['endY']) / 2);
        
        // First ring positions are at distance 2 from zone center (X±4, Y±4, X±2, Y±2)
        $firstRingXCoords = [$centerX - 4, $centerX - 2, $centerX, $centerX + 2];
        $firstRingYCoords = [$centerY - 4, $centerY - 2, $centerY, $centerY + 2];
        
        return in_array($x, $firstRingXCoords) && in_array($y, $firstRingYCoords);
    }
} 