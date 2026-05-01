<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use Tests\TestCase;

class AiProviderExecutionDriverTest extends TestCase
{
    public function test_default_execution_driver_is_custom_and_invalid_values_fallback_to_custom(): void
    {
        $provider = new AiProvider(['provider_type' => 'gemini']);
        $this->assertSame(AiProvider::DRIVER_CUSTOM, $provider->executionDriver());
        $this->assertTrue($provider->usesCustomDriver());

        $invalid = new AiProvider(['provider_type' => 'gemini', 'client_driver' => 'invalid']);
        $this->assertSame(AiProvider::DRIVER_CUSTOM, $invalid->executionDriver());

        $sdk = new AiProvider(['provider_type' => 'gemini', 'client_driver' => AiProvider::DRIVER_LARAVEL_AI]);
        $this->assertTrue($sdk->usesLaravelAiDriver());
    }
}
