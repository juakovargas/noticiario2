<?php

namespace App\Services\Ai\Contracts;

use App\Models\AiProvider;
use App\Services\Ai\Data\AiResponseData;

interface AiClient
{
    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData;
}
