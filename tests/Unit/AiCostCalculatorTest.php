<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\Ai\AiCostCalculator;
use Tests\TestCase;

class AiCostCalculatorTest extends TestCase
{
    public function test_returns_null_when_tokens_missing(): void
    {
        $provider = AiProvider::factory()->make([
            'cost_input_per_1k_tokens' => 0.50,
            'cost_output_per_1k_tokens' => 1.20,
        ]);

        $service = new AiCostCalculator();

        $this->assertNull($service->estimate($provider, null, 200));
        $this->assertNull($service->estimate($provider, 200, null));
    }

    public function test_returns_null_when_costs_missing(): void
    {
        $provider = AiProvider::factory()->make([
            'cost_input_per_1k_tokens' => null,
            'cost_output_per_1k_tokens' => null,
        ]);

        $service = new AiCostCalculator();

        $this->assertNull($service->estimate($provider, 100, 200));
    }

    public function test_calculates_and_rounds_cost(): void
    {
        $provider = AiProvider::factory()->make([
            'cost_input_per_1k_tokens' => 0.123456,
            'cost_output_per_1k_tokens' => 0.654321,
        ]);

        $service = new AiCostCalculator();

        $cost = $service->estimate($provider, 1500, 500);

        $this->assertSame(0.512345, $cost);
    }
}
