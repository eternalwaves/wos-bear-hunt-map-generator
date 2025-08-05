<?php

namespace App\Domain\MapObject;

class MiscObject extends MapObject
{
    private string $name;

    public function __construct(int $x, int $y, int $size, string $name = '', string $id = null)
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Size must be at least 1');
        }
        
        parent::__construct($x, $y, $size, $id);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return 'misc';
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'size' => $this->size,
            'name' => $this->name,
            'id' => $this->id
        ];
    }
} 