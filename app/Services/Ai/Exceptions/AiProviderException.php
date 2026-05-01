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
        public readonly bool $requestWasSent = false,
        public readonly ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
