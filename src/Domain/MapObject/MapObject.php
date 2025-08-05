<?php

namespace App\Domain\MapObject;

abstract class MapObject
{
    protected int $x;
    protected int $y;
    protected int $size;
    protected string $id;

    public function __construct(int $x, int $y, int $size, string $id = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->size = $size;
        $this->id = $id ?? $this->generateId();
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setPosition(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getPosition(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }

    public function getOccupiedCoordinates(): array
    {
        $coordinates = [];
        for ($dx = 0; $dx < $this->size; $dx++) {
            for ($dy = 0; $dy < $this->size; $dy++) {
                $coordinates[] = [
                    'x' => $this->x + $dx,
                    'y' => $this->y + $dy
                ];
            }
        }
        return $coordinates;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'x' => $this->x,
            'y' => $this->y,
            'size' => $this->size,
            'type' => $this->getType()
        ];
    }

    abstract public function getType(): string;

    protected function generateId(): string
    {
        return uniqid($this->getType() . '_', true);
    }
} 