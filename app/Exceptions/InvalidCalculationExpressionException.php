<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCalculationExpressionException extends RuntimeException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
