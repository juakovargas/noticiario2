<?php

namespace App\Services\Ai\Contracts;

interface LaravelAiSdkGateway
{
    /**
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array;
}
