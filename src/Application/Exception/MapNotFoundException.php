<?php

namespace App\Application\Exception;

class MapNotFoundException extends \Exception
{
    public function __construct(string $message = "Map not found", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 