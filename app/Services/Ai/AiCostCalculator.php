<?php

namespace App\Services\Ai;

use App\Models\AiProvider;

class AiCostCalculator
{
    public function estimate(AiProvider $provider, ?int $inputTokens, ?int $outputTokens): ?float
    {
        if ($inputTokens === null || $outputTokens === null) {
            return null;
        }

        if ($provider->cost_input_per_1k_tokens === null || $provider->cost_output_per_1k_tokens === null) {
            return null;
        }

        $inputCost = ($inputTokens / 1000) * (float) $provider->cost_input_per_1k_tokens;
        $outputCost = ($outputTokens / 1000) * (float) $provider->cost_output_per_1k_tokens;

        return round($inputCost + $outputCost, 6);
    }
}
