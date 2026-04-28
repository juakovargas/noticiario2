<?php

namespace App\Services\Ai\Data;

class AiResponseData
{
    public function __construct(
        public readonly string $text,
        public readonly ?array $rawResponse,
        public readonly string $provider,
        public readonly ?string $model,
        public readonly ?int $inputTokens,
        public readonly ?int $outputTokens,
        public readonly ?int $totalTokens,
        public readonly ?string $finishReason,
        public readonly ?int $durationMs,
    ) {
    }
}
