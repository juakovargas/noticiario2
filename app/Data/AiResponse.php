<?php

namespace App\Data;

class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly ?array $parsedJson = null,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly ?int $costCents = null,
        public readonly array $metadata = [],
    ) {
    }
}
