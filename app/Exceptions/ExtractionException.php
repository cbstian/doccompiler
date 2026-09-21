<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class ExtractionException extends Exception
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
