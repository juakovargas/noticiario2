<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly bool $retryable = false,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }
}
