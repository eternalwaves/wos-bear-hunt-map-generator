<?php

namespace App\Domain\Map;

use App\Domain\MapObject\Furnace;
use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
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

        // Set weighted criteria on the map if provided
        if ($criteriaWeights) {
            $this->map->setWeightedCriteria($criteriaWeights);
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
     * Banners are allowed to be placed adjacent to traps, which will adjust the zones
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
            
            // Mark the entire zone as occupied, but allow banners to be placed adjacent
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
            // Make banner detection case-insensitive
            if (strtolower($object->getName()) === 'banner') {
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
        
        // Check right edge condition
        $rightEdge = $obj1EndX === $x2 - 1;
        $rightEdgeY = $y2 - 1 === $obj1EndY || ($obj2EndY >= $y1 - 1 && $obj2EndY <= $obj1EndY);
        
        // Check left edge condition
        $leftEdge = $obj2EndX === $x1 - 1;
        $leftEdgeY = $y2 - 1 === $obj1EndY || ($obj2EndY >= $y1 - 1 && $obj2EndY <= $obj1EndY);
        
        // Check top edge condition
        $topEdge = $obj1EndY === $y2 - 1;
        $topEdgeX = $x2 - 1 === $obj1EndX || ($obj2EndX >= $x1 - 1 && $obj2EndX <= $obj1EndX);
        
        // Check bottom edge condition
        $bottomEdge = $obj2EndY === $y1 - 1;
        $bottomEdgeX = $x2 - 1 === $obj1EndX || ($obj2EndX >= $x1 - 1 && $obj2EndX <= $obj1EndX);
        
        // Check if objects are adjacent (touching but not overlapping)
        // Objects are touching if they are adjacent (no gap between them)
        $result = (
            // Right edge
            ($rightEdge && $rightEdgeY) ||
            // Left edge
            ($leftEdge && $leftEdgeY) ||
            // Top edge
            ($topEdge && $topEdgeX) ||
            // Bottom edge
            ($bottomEdge && $bottomEdgeX)
        );
        
        return $result;
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
            // Detect trap orientation to adjust zone appropriately
            $traps = $this->map->getTraps();
            $orientation = $this->detectTrapOrientation($traps);
            
            if ($orientation === 'vertical') {
                // For vertical layout, adjust primarily on Y-axis
                if ($centerY < $this->gapZone['startY']) {
                    // Zone is above gap, move it further up
                    $startY = $this->gapZone['startY'] - $zoneSize;
                    $endY = $startY + $zoneSize;
                } elseif ($centerY > $this->gapZone['endY']) {
                    // Zone is below gap, move it further down
                    $startY = $this->gapZone['endY'];
                    $endY = $startY + $zoneSize;
                }
                
                // Also adjust X if needed, but prioritize Y adjustment
                if ($centerX < $this->gapZone['startX']) {
                    // Zone is to the left of gap, move it further left
                    $startX = $this->gapZone['startX'] - $zoneSize;
                    $endX = $startX + $zoneSize;
                } elseif ($centerX > $this->gapZone['endX']) {
                    // Zone is to the right of gap, move it further right
                    $startX = $this->gapZone['endX'];
                    $endX = $startX + $zoneSize;
                }
            } else {
                // For horizontal layout, adjust primarily on X-axis
                if ($centerX < $this->gapZone['startX']) {
                    // Zone is to the left of gap, move it further left
                    $startX = $this->gapZone['startX'] - $zoneSize;
                    $endX = $startX + $zoneSize;
                } elseif ($centerX > $this->gapZone['endX']) {
                    // Zone is to the right of gap, move it further right
                    $startX = $this->gapZone['endX'];
                    $endX = $startX + $zoneSize;
                }
                
                // Also adjust Y if needed, but prioritize X adjustment
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

    /**
     * Detect if traps are laid out horizontally or vertically
     * Returns 'horizontal' if traps have same Y-coordinate, 'vertical' if same X-coordinate
     */
    private function detectTrapOrientation(array $traps): string
    {
        if (count($traps) < 2) {
            return 'horizontal'; // Default fallback
        }
        
        $trap1 = $traps[0];
        $trap2 = $traps[1];
        
        $trap1X = $trap1->getX();
        $trap1Y = $trap1->getY();
        $trap2X = $trap2->getX();
        $trap2Y = $trap2->getY();
        
        // If Y coordinates are the same (within 1 unit), it's horizontal layout
        if (abs($trap1Y - $trap2Y) <= 1) {
            return 'horizontal';
        }
        
        // If X coordinates are the same (within 1 unit), it's vertical layout
        if (abs($trap1X - $trap2X) <= 1) {
            return 'vertical';
        }
        
        // If neither is clearly aligned, determine by which coordinate has smaller difference
        $xDiff = abs($trap1X - $trap2X);
        $yDiff = abs($trap1Y - $trap2Y);
        
        return ($xDiff < $yDiff) ? 'vertical' : 'horizontal';
    }

    private function generateSpiralPositions(): void
    {
        $traps = $this->map->getTraps();
        $orientation = $this->detectTrapOrientation($traps);
        
        // Ensure trap zones are calculated before generating spiral positions
        if (empty($this->trapZoneBounds)) {
            $this->createTrapNoOccupyZones();
        }
        
        // Generate positions for each trap (first ring as square around zone, then spiral)
        $this->spiralPositions['trap1'] = $this->generateSquarePositionsForTrap($traps[0]->getX(), $traps[0]->getY(), 1, 0);
        $this->spiralPositions['trap2'] = $this->generateSquarePositionsForTrap($traps[1]->getX(), $traps[1]->getY(), 2, 1);
        
        // Get the first ring positions for each trap to exclude them from center positions
        $trap1FirstRing = $this->getFirstRingPositionsForTrap(0);
        $trap2FirstRing = $this->getFirstRingPositionsForTrap(1);
        $excludedPositions = array_merge($trap1FirstRing, $trap2FirstRing);
        
        // Generate center spiral positions based on orientation
        if ($orientation === 'horizontal') {
            // Horizontal layout: start at centerY of both 4x4 zones, X = x2-4
            // For horizontal layout, the 4x4 zones are centered on the trap Y coordinates
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
        } else {
            // Vertical layout: start at centerX of both 4x4 zones, Y = y2-2
            // For vertical layout, the 4x4 zones are centered on the trap X coordinates
            $bottomY = min($traps[0]->getY(), $traps[1]->getY()) + 3;
            $topY = max($traps[0]->getY(), $traps[1]->getY());
            $midY = round(($bottomY + $topY) / 2) - 1;
            
            // Calculate centerX that aligns with one of the first ring X-values of the traps
            // First, get the zone centers for both traps
            $trap1ZoneCenterX = round(($this->trapZoneBounds[0]['startX'] + $this->trapZoneBounds[0]['endX']) / 2);
            $trap2ZoneCenterX = round(($this->trapZoneBounds[1]['startX'] + $this->trapZoneBounds[1]['endX']) / 2);
            
            // Get the first ring X-values for both traps (X-4, X-2, X, X+2)
            $trap1FirstRingX = [$trap1ZoneCenterX - 4, $trap1ZoneCenterX - 2, $trap1ZoneCenterX, $trap1ZoneCenterX + 2];
            $trap2FirstRingX = [$trap2ZoneCenterX - 4, $trap2ZoneCenterX - 2, $trap2ZoneCenterX, $trap2ZoneCenterX + 2];
            
            // Find a X-value that appears in both first rings or is close to both
            $allFirstRingX = array_merge($trap1FirstRingX, $trap2FirstRingX);
            $leftX = min($traps[0]->getX(), $traps[1]->getX());
            $rightX = max($traps[0]->getX(), $traps[1]->getX()) + 3;
            $originalMidX = round(($leftX + $rightX) / 2) - 1; // Original calculation as fallback
            
            // Try to find a X-value that's close to the midpoint and aligns with first ring patterns
            $bestX = $originalMidX;
            $minDistance = PHP_INT_MAX;
            
            foreach ($allFirstRingX as $x) {
                $distance = abs($x - $originalMidX);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestX = $x;
                }
            }
            
            $midX = $bestX;
        }
        
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
        
        // Detect trap orientation to determine which sides to prioritize
        $traps = $this->map->getTraps();
        $orientation = $this->detectTrapOrientation($traps);
        
        if ($orientation === 'horizontal') {
            // Horizontal layout: prioritize left/right sides
            // For trap 1 (left), prioritize right side
            // For trap 2 (right), prioritize left side
            if ($trapIndex === 0) {
                return 'right';
            } else {
                return 'left';
            }
        } else {
            // Vertical layout: prioritize top/bottom sides
            // For trap 1 (top), prioritize bottom side
            // For trap 2 (bottom), prioritize top side
            if ($trapIndex === 0) {
                return 'bottom';
            } else {
                return 'top';
            }
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
        } elseif ($prioritizeSide === 'bottom') {
            // Prioritize bottom side first, then left/right, then top
            $order = [
                // Bottom side (furthest from gap) - Y+2 positions
                [$xCoords[0], $yCoords[3]], // bottom-left (X-4, Y+2)
                [$xCoords[1], $yCoords[3]], // bottom-left (X-2, Y+2)
                [$xCoords[2], $yCoords[3]], // bottom-middle (X, Y+2)
                [$xCoords[3], $yCoords[3]], // bottom-right (X+2, Y+2)
                // Left and right edges
                [$xCoords[0], $yCoords[0]], // top-left (X-4, Y-4)
                [$xCoords[0], $yCoords[1]], // top-left (X-4, Y-2)
                [$xCoords[0], $yCoords[2]], // middle-left (X-4, Y)
                [$xCoords[3], $yCoords[0]], // top-right (X+2, Y-4)
                [$xCoords[3], $yCoords[1]], // top-right (X+2, Y-2)
                [$xCoords[3], $yCoords[2]], // middle-right (X+2, Y)
                // Top side (closest to gap) - Y-4 and Y-2 positions
                [$xCoords[1], $yCoords[0]], // top-left (X-2, Y-4)
                [$xCoords[2], $yCoords[0]], // top-middle (X, Y-4)
                [$xCoords[1], $yCoords[1]], // top-left (X-2, Y-2)
                [$xCoords[2], $yCoords[1]], // top-middle (X, Y-2)
            ];
        } elseif ($prioritizeSide === 'top') {
            // Prioritize top side first, then left/right, then bottom
            $order = [
                // Top side (furthest from gap) - Y-4 positions
                [$xCoords[0], $yCoords[0]], // top-left (X-4, Y-4)
                [$xCoords[1], $yCoords[0]], // top-left (X-2, Y-4)
                [$xCoords[2], $yCoords[0]], // top-middle (X, Y-4)
                [$xCoords[3], $yCoords[0]], // top-right (X+2, Y-4)
                // Top side - Y-2 positions
                [$xCoords[0], $yCoords[1]], // top-left (X-4, Y-2)
                [$xCoords[1], $yCoords[1]], // top-left (X-2, Y-2)
                [$xCoords[2], $yCoords[1]], // top-middle (X, Y-2)
                [$xCoords[3], $yCoords[1]], // top-right (X+2, Y-2)
                // Left and right edges
                [$xCoords[0], $yCoords[2]], // middle-left (X-4, Y)
                [$xCoords[3], $yCoords[2]], // middle-right (X+2, Y)
                [$xCoords[0], $yCoords[3]], // bottom-left (X-4, Y+2)
                [$xCoords[3], $yCoords[3]], // bottom-right (X+2, Y+2)
                // Bottom side (closest to gap) - Y+2 positions
                [$xCoords[1], $yCoords[2]], // middle-left (X-2, Y)
                [$xCoords[2], $yCoords[2]], // middle (X, Y)
                [$xCoords[1], $yCoords[3]], // bottom-left (X-2, Y+2)
                [$xCoords[2], $yCoords[3]], // bottom-middle (X, Y+2)
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
        
        // Add positions in the defined order
        foreach ($order as $pos) {
            $positions[] = $pos;
        }
        
        return $positions;
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
            // For center positioning, prioritize positions at centerX first, then by distance
            uasort($positions, function ($a, $b) use ($centerX, $centerY) {
                // First priority: positions at centerX
                $aAtCenterX = ($a[0] === $centerX);
                $bAtCenterX = ($b[0] === $centerX);
                
                if ($aAtCenterX && !$bAtCenterX) return -1; // a comes first
                if (!$aAtCenterX && $bAtCenterX) return 1;  // b comes first
                
                // If both are at centerX or both are not at centerX, sort by distance
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
        
        // First ring positions: range from x-4 to x+2 with step 2, excluding zone overlap
        $xDiff = $x - $centerX;
        $yDiff = $y - $centerY;
        
        // Check if position is in the first ring range
        $inXRange = ($xDiff >= -4 && $xDiff <= 2 && ($xDiff % 2 === 0));
        $inYRange = ($yDiff >= -4 && $yDiff <= 2 && ($yDiff % 2 === 0));
        
        // Must be in both ranges and not overlap with the zone itself
        if ($inXRange && $inYRange) {
            // Check if position overlaps with the zone
            $zoneBounds = $this->trapZoneBounds[$trapIndex];
            if ($x >= $zoneBounds['startX'] && $x < $zoneBounds['endX'] && 
                $y >= $zoneBounds['startY'] && $y < $zoneBounds['endY']) {
                return false; // Position overlaps with zone, not in first ring
            }
            return true; // Position is in first ring
        }
        
        return false;
    }

    /**
     * Automatically place objects when they're added to the map
     * - Traps and misc objects are placed at their specified positions
     * - Banners are automatically placed adjacent to traps
     * - Triggers immediate map generation if no map exists yet
     */
    public function autoPlaceObject($object): void
    {
        if ($object instanceof Trap) {
            $this->autoPlaceTrap($object);
        } elseif ($object instanceof MiscObject) {
            $this->autoPlaceMiscObject($object);
        }
        
        // Generate map immediately if we have at least 2 traps
        $traps = $this->map->getTraps();
        if (count($traps) >= 2) {
            // Only generate if we haven't already generated for this number of traps
            if (empty($this->spiralPositions) || count($this->spiralPositions) < 3) {
                $this->generateMapImmediately();
            }
        }
    }

    /**
     * Generate map immediately when objects are placed
     * This creates the 4x4 zones and places any existing furnaces
     */
    private function generateMapImmediately(): void
    {
        // Generate spiral positions if not already done
        if (empty($this->spiralPositions)) {
            $this->generateSpiralPositions();
        }
        
        // Place any existing furnaces
        $furnaces = $this->map->getFurnaces();
        if (!empty($furnaces)) {
            $this->placeExistingFurnaces($furnaces);
        }
    }

    /**
     * Place existing furnaces on the map
     */
    private function placeExistingFurnaces(array $furnaces): void
    {
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

    /**
     * Automatically place a trap and update the map
     */
    private function autoPlaceTrap(Trap $trap): void
    {
        // Add trap directly to map's traps array to avoid position conflicts
        $traps = $this->map->getTraps();
        $traps[] = $trap;
        
        // Use reflection to set the traps array directly
        $reflection = new \ReflectionClass($this->map);
        $trapsProperty = $reflection->getProperty('traps');
        $trapsProperty->setAccessible(true);
        $trapsProperty->setValue($this->map, $traps);
        
        // Mark trap position as occupied in MapGenerator
        $this->markPositionOccupied($trap->getX(), $trap->getY(), $trap->getSize(), 'trap');
        
        // Only regenerate spiral positions if we now have 2+ traps
        if (count($traps) >= 2) {
            // Ensure zones are calculated first
            $this->createTrapNoOccupyZones();
            $this->generateSpiralPositions();
        }
        
        // Only recalculate trap zones if we already have zones (i.e., not the first time)
        if (!empty($this->trapZoneBounds)) {
            $this->recalculateTrapZones();
        }
    }

    /**
     * Automatically place a misc object
     * - If it's a banner, place it adjacent to a trap
     * - Otherwise, place it at the specified position
     */
    private function autoPlaceMiscObject(MiscObject $object): void
    {
        if (strtolower($object->getName()) === 'banner') {
            $this->autoPlaceBanner($object);
        } else {
            // Place non-banner misc objects at their specified position
            // Check if position is available (non-banners cannot be placed in 4x4 zones)
            if (!$this->isPositionAvailable($object->getX(), $object->getY(), $object->getSize(), 'general')) {
                // Position is not available, find an alternative position
                $alternativePosition = $this->findAlternativePosition($object);
                if ($alternativePosition) {
                    $object->setPosition($alternativePosition['x'], $alternativePosition['y']);
                }
            }
            
            // Add directly to map's miscObjects array to avoid position conflicts
            $miscObjects = $this->map->getMiscObjects();
            $miscObjects[] = $object;
            
            // Use reflection to set the miscObjects array directly
            $reflection = new \ReflectionClass($this->map);
            $miscObjectsProperty = $reflection->getProperty('miscObjects');
            $miscObjectsProperty->setAccessible(true);
            $miscObjectsProperty->setValue($this->map, $miscObjects);
            
            // Mark position as occupied in MapGenerator
            $this->markPositionOccupied($object->getX(), $object->getY(), $object->getSize(), 'misc');
        }
    }

    /**
     * Find an alternative position for a misc object if the original position is occupied
     */
    private function findAlternativePosition(MiscObject $object): ?array
    {
        $size = $object->getSize();
        $originalX = $object->getX();
        $originalY = $object->getY();
        
        // Try positions in a spiral pattern around the original position
        $spiralRadius = 1;
        $maxRadius = 10; // Limit search radius
        
        while ($spiralRadius <= $maxRadius) {
            for ($dx = -$spiralRadius; $dx <= $spiralRadius; $dx++) {
                for ($dy = -$spiralRadius; $dy <= $spiralRadius; $dy++) {
                    // Only check positions at the current radius
                    if (abs($dx) === $spiralRadius || abs($dy) === $spiralRadius) {
                        $newX = $originalX + $dx;
                        $newY = $originalY + $dy;
                        
                        if ($this->isPositionAvailable($newX, $newY, $size, 'general')) {
                            return ['x' => $newX, 'y' => $newY];
                        }
                    }
                }
            }
            $spiralRadius++;
        }
        
        return null; // No alternative position found
    }

    /**
     * Automatically place a banner adjacent to a trap
     */
    private function autoPlaceBanner(MiscObject $banner): void
    {
        // Check if the banner's position is available (not occupied by any object)
        if (!$this->isPositionAvailable($banner->getX(), $banner->getY(), $banner->getSize(), 'banner')) {
            // Position is occupied, find an alternative position
            $alternativePosition = $this->findAlternativePosition($banner);
            if ($alternativePosition) {
                $banner->setPosition($alternativePosition['x'], $alternativePosition['y']);
            }
            // If no alternative position found, the banner will be placed at original position anyway
        }
        
        // Add banner to map's miscObjects array
        $miscObjects = $this->map->getMiscObjects();
        $miscObjects[] = $banner;
        
        // Use reflection to set the miscObjects array directly
        $reflection = new \ReflectionClass($this->map);
        $miscObjectsProperty = $reflection->getProperty('miscObjects');
        $miscObjectsProperty->setAccessible(true);
        $miscObjectsProperty->setValue($this->map, $miscObjects);
        
        // Mark position as occupied in MapGenerator
        $this->markPositionOccupied($banner->getX(), $banner->getY(), $banner->getSize(), 'misc');
        
        // Recalculate trap zones to include the new banner
        $this->recalculateTrapZones();
    }



    /**
     * Check if two positions overlap
     */
    private function positionsOverlap(int $x1, int $y1, int $size1, int $size2, int $x2, int $y2, int $size3, int $size4): bool
    {
        $endX1 = $x1 + $size1 - 1;
        $endY1 = $y1 + $size2 - 1;
        $endX2 = $x2 + $size3 - 1;
        $endY2 = $y2 + $size4 - 1;
        
        return !($endX1 < $x2 || $endX2 < $x1 || $endY1 < $y2 || $endY2 < $y1);
    }

    /**
     * Check if a position is available for placement
     * Banners are allowed to be placed adjacent to traps even if they're in the 4x4 zone
     */
    private function isPositionAvailable(int $x, int $y, int $size, string $objectType = 'general'): bool
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $posX = $x + $dx;
                $posY = $y + $dy;
                if (isset($this->occupiedPositions["$posX,$posY"])) {
                    $occupier = $this->occupiedPositions["$posX,$posY"];
                    
                    // If it's a banner and the position is in a trap zone, check if it's adjacent to a trap
                    if ($objectType === 'banner' && $occupier === 'trap_zone') {
                        if (!$this->isBannerAdjacentToTrap($x, $y, $size)) {
                            return false;
                        }
                    } else {
                        // For non-banners or non-adjacent banners, any occupied position is blocked
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Check if a banner position is adjacent to a trap
     */
    private function isBannerAdjacentToTrap(int $bannerX, int $bannerY, int $bannerSize): bool
    {
        $traps = $this->map->getTraps();
        
        foreach ($traps as $trap) {
            $trapX = $trap->getX();
            $trapY = $trap->getY();
            $trapSize = $trap->getSize();
            
            // Check if banner is adjacent to this trap
            $bannerEndX = $bannerX + $bannerSize - 1;
            $bannerEndY = $bannerY + $bannerSize - 1;
            $trapEndX = $trapX + $trapSize - 1;
            $trapEndY = $trapY + $trapSize - 1;
            
            // Check horizontal adjacency (banner is to the left or right of trap)
            $horizontalAdjacent = (
                ($bannerEndX + 1 === $trapX) || // Banner is to the left of trap
                ($trapEndX + 1 === $bannerX)    // Banner is to the right of trap
            );
            
            // Check vertical adjacency (banner is above or below trap)
            $verticalAdjacent = (
                ($bannerEndY + 1 === $trapY) || // Banner is above trap
                ($trapEndY + 1 === $bannerY)    // Banner is below trap
            );
            
            // Check if banner overlaps horizontally and vertically with trap
            $horizontalOverlap = !($bannerEndX < $trapX || $trapEndX < $bannerX);
            $verticalOverlap = !($bannerEndY < $trapY || $trapEndY < $bannerY);
            
            // Banner is adjacent if it's horizontally adjacent and overlaps vertically,
            // OR it's vertically adjacent and overlaps horizontally
            if (($horizontalAdjacent && $verticalOverlap) || ($verticalAdjacent && $horizontalOverlap)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Recalculate trap zones to include any new banners
     * This adjusts the 4x4 zones dynamically when banners are added adjacent to traps
     */
    private function recalculateTrapZones(): void
    {
        // Clear existing trap zones
        foreach ($this->trapZoneBounds as $index => $zoneBounds) {
            for ($x = $zoneBounds['startX']; $x < $zoneBounds['endX']; $x++) {
                for ($y = $zoneBounds['startY']; $y < $zoneBounds['endY']; $y++) {
                    if (isset($this->occupiedPositions["$x,$y"]) && $this->occupiedPositions["$x,$y"] === 'trap_zone') {
                        unset($this->occupiedPositions["$x,$y"]);
                    }
                }
            }
        }
        
        // Clear trap zone bounds
        $this->trapZoneBounds = [];
        
        // Recalculate zones with updated banner positions
        $this->createTrapNoOccupyZones();
        
        // If we have furnaces, we need to check if any are now in invalid positions
        $furnaces = $this->map->getFurnaces();
        if (!empty($furnaces)) {
            $this->validateAndRepositionFurnaces($furnaces);
        }
    }

    /**
     * Validate and reposition furnaces that may now be in invalid positions
     * after zone recalculation
     */
    private function validateAndRepositionFurnaces(array $furnaces): void
    {
        $repositionedFurnaces = [];
        
        foreach ($furnaces as $furnace) {
            if ($furnace->isLocked()) {
                $repositionedFurnaces[] = $furnace;
                continue;
            }
            
            // Check if furnace is in a valid position
            if ($furnace->hasPosition()) {
                $furnaceX = $furnace->getX();
                $furnaceY = $furnace->getY();
                $furnaceSize = $furnace->getSize();
                
                if (!$this->isPositionAvailable($furnaceX, $furnaceY, $furnaceSize, 'general')) {
                    // Furnace is in an invalid position, need to reposition it
                    $furnace->setPosition(null, null); // Clear position
                }
            }
            
            $repositionedFurnaces[] = $furnace;
        }
        
        // Update map with repositioned furnaces
        $this->map->setFurnaces($repositionedFurnaces);
        
        // Regenerate map to place repositioned furnaces
        $this->generateMapImmediately();
    }
} 