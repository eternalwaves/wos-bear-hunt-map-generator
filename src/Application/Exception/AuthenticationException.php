<?php

namespace App\Application\Exception;

class AuthenticationException extends \Exception
{
    public function __construct(string $message = "Authentication failed", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 