<?php

namespace App\Domain\Map;

use App\Domain\MapObject\Trap;
use App\Domain\MapObject\MiscObject;
use App\Domain\MapObject\Furnace;

class SvgGenerator
{
    private const CELL_SIZE = 50;
    private const TRAP_SIZE = 3;
    private const FURNACE_SIZE = 2;

    private Map $map;
    private array $occupiedPositions = [];
    private array $bounds = [
        'minX' => PHP_INT_MAX,
        'minY' => PHP_INT_MAX,
        'maxX' => PHP_INT_MIN,
        'maxY' => PHP_INT_MIN
    ];

    public function __construct(Map $map)
    {
        $this->map = $map;
        $this->calculateOccupiedPositions();
        $this->calculateBounds();
    }

    public function generateSvg(): string
    {
        $svgWidth = $this->bounds['maxX'] - $this->bounds['minX'];
        $svgHeight = $this->bounds['maxY'] - $this->bounds['minY'];

        // Add some padding around the content
        $padding = 20;
        $svgWidth += $padding * 2;
        $svgHeight += $padding * 2;

        $svg = sprintf(
            '<svg width="%d" height="%d" viewBox="%d %d %d %d" xmlns="http://www.w3.org/2000/svg">',
            $svgWidth,
            $svgHeight,
            $this->bounds['minX'] - $padding,
            $this->bounds['minY'] - $padding,
            $svgWidth,
            $svgHeight
        );

        $svg .= $this->generateTrapsSvg();
        $svg .= $this->generateMiscObjectsSvg();
        $svg .= $this->generateFurnacesSvg();

        $svg .= '</svg>';

        return $svg;
    }

    private function calculateOccupiedPositions(): void
    {
        // Mark traps as occupied
        foreach ($this->map->getTraps() as $trap) {
            $this->markPositionOccupied($trap->getX(), $trap->getY(), self::TRAP_SIZE, 'trap');
        }

        // Mark misc objects as occupied
        foreach ($this->map->getMiscObjects() as $miscObject) {
            $this->markPositionOccupied($miscObject->getX(), $miscObject->getY(), $miscObject->getSize(), 'misc');
        }

        // Mark furnaces as occupied
        foreach ($this->map->getFurnaces() as $furnace) {
            if ($furnace->getX() && $furnace->getY()) {
                $this->markPositionOccupied($furnace->getX(), $furnace->getY(), self::FURNACE_SIZE, $furnace->getTrapPref());
            }
        }
    }

    private function calculateBounds(): void
    {
        // Calculate bounds for traps
        foreach ($this->map->getTraps() as $trap) {
            $this->updateBounds($trap->getX(), $trap->getY(), self::TRAP_SIZE, self::TRAP_SIZE);
        }

        // Calculate bounds for misc objects
        foreach ($this->map->getMiscObjects() as $miscObject) {
            $this->updateBounds($miscObject->getX(), $miscObject->getY(), $miscObject->getSize(), $miscObject->getSize());
        }

        // Calculate bounds for furnaces
        foreach ($this->map->getFurnaces() as $furnace) {
            if ($furnace->getX() && $furnace->getY()) {
                $this->updateBounds($furnace->getX(), $furnace->getY(), self::FURNACE_SIZE, self::FURNACE_SIZE);
            }
        }

        // Adjust maxY for correct height calculation
        if ($this->bounds['maxY'] < $this->bounds['minY']) {
            [$this->bounds['maxY'], $this->bounds['minY']] = [$this->bounds['minY'], $this->bounds['maxY']];
        }
    }

    private function generateTrapsSvg(): string
    {
        $svg = '';
        $index = 1;
        
        foreach ($this->map->getTraps() as $trap) {
            $svg .= $this->drawObject(
                $trap->getX(),
                $trap->getY(),
                self::TRAP_SIZE * self::CELL_SIZE,
                'brown',
                "Trap " . $index
            );
            $index++;
        }

        return $svg;
    }

    private function generateMiscObjectsSvg(): string
    {
        $svg = '';
        
        foreach ($this->map->getMiscObjects() as $miscObject) {
            $svg .= $this->drawObject(
                $miscObject->getX(),
                $miscObject->getY(),
                $miscObject->getSize() * self::CELL_SIZE,
                'darkgrey',
                $miscObject->getName()
            );
        }

        return $svg;
    }

    private function generateFurnacesSvg(): string
    {
        $svg = '';
        
        foreach ($this->map->getFurnaces() as $furnace) {
            if ($furnace->getX() && $furnace->getY()) {
                $svg .= $this->drawObject(
                    $furnace->getX(),
                    $furnace->getY(),
                    self::FURNACE_SIZE * self::CELL_SIZE,
                    '#2DCCFF',
                    $furnace->getName(),
                    $furnace->getId(),
                    $furnace->getStatus()
                );
            }
        }

        return $svg;
    }

    private function drawObject(int $x, int $y, int $size, string $color, string $label, ?string $id = null, ?string $status = null): string
    {
        $pixelX = $x * self::CELL_SIZE;
        $pixelY = -($y * self::CELL_SIZE + $size); // Flip Y-axis
        
        $idString = isset($id) ? 'data-obj-id="' . $id . '"' : '';
        $statusString = '';
        $finalColor = $color;

        if (!empty($id)) {
            switch ($status) {
                case 'messaged': 
                    $statusString = ' messaged';
                    $finalColor = '#FFAF3D';
                    break;
                case 'moved':
                    $statusString = ' moved';
                    $finalColor = '#00E200';
                    break;
                case 'wrong':
                    $statusString = ' wrong';
                    $finalColor = '#FF2A04';
                    break;
                default:
                    $statusString = ' assigned';
            }
        }
        
        $svg = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" style="stroke: black; stroke-width: 1" class="object%s" %s/>',
            $pixelX,
            $pixelY,
            $size,
            $size,
            $finalColor,
            $statusString,
            $idString
        );
        
        $svg .= sprintf(
            '<text x="%d" y="%d" font-size="10" fill="black" class="coords%s" %s>(%d,%d)</text>',
            $pixelX + 5,
            $pixelY + $size - 5,
            $statusString,
            $idString,
            $x,
            $y
        );
        
        $svg .= sprintf(
            '<text x="%d" y="%d" font-size="12" fill="black" text-anchor="middle" alignment-baseline="middle" class="label%s" %s>%s</text>',
            $pixelX + $size / 2,
            $pixelY + $size / 2,
            $statusString,
            $idString,
            htmlspecialchars($label)
        );
        
        return $svg;
    }

    private function markPositionOccupied(int $x, int $y, int $size, $group = true): void
    {
        for ($dx = 0; $dx < $size; $dx++) {
            for ($dy = 0; $dy < $size; $dy++) {
                $this->occupiedPositions[($x + $dx) . "," . ($y + $dy)] = $group;
            }
        }
    }

    private function updateBounds(int $x, int $y, int $width, int $height): void
    {
        // Convert grid coordinates to pixel coordinates (same as drawObject)
        $pixelX = $x * self::CELL_SIZE;
        $pixelY = -($y * self::CELL_SIZE + ($width * self::CELL_SIZE)); // Flip Y-axis and adjust for object height
        
        $pixelWidth = $width * self::CELL_SIZE;
        $pixelHeight = $height * self::CELL_SIZE;

        $this->bounds['minX'] = min($this->bounds['minX'], $pixelX);
        $this->bounds['maxX'] = max($this->bounds['maxX'], $pixelX + $pixelWidth);

        // Ensure correct min/max Y values
        if ($this->bounds['minY'] === PHP_INT_MAX || $pixelY < $this->bounds['minY']) {
            $this->bounds['minY'] = $pixelY;
        }
        if ($pixelY + $pixelHeight > $this->bounds['maxY']) {
            $this->bounds['maxY'] = $pixelY + $pixelHeight;
        }
    }

    public function getOccupiedPositions(): array
    {
        return $this->occupiedPositions;
    }
} 