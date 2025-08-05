<?php

namespace App\Application\Exception;

class ValidationException extends \Exception
{
    public function __construct(string $message = "Validation failed", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 