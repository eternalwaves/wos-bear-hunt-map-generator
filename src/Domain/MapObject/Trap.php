<?php

namespace App\Domain\MapObject;

class Trap extends MapObject
{
    public function __construct(int $x, int $y, string $id = null)
    {
        parent::__construct($x, $y, 3, $id);
    }

    public function getType(): string
    {
        return 'trap';
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'id' => $this->id
        ];
    }
} 