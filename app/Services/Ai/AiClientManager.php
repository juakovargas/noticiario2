<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use RuntimeException;

class AiClientManager
{
    public function resolve(AiProvider $provider): AiClientInterface
    {
        if ($provider->provider_type === 'mock') {
            return new MockAiClient();
        }

        throw new RuntimeException(sprintf('Provider not implemented yet: %s', $provider->provider_type));
    }
}
