<?php

namespace App\Services\Ai\Support;

class AiRequestOptionsNormalizer
{
    public static function normalize(array $options): array
    {
        $normalized = $options;
        self::castNumeric($normalized, 'temperature', 'float');
        self::castNumeric($normalized, 'max_tokens', 'int');
        self::castNumeric($normalized, 'top_p', 'float');
        self::castNumeric($normalized, 'frequency_penalty', 'float');
        self::castNumeric($normalized, 'presence_penalty', 'float');

        if (array_key_exists('stream', $normalized) && $normalized['stream'] !== null && $normalized['stream'] !== '') {
            $normalized['stream'] = filter_var($normalized['stream'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return array_filter($normalized, fn ($value) => $value !== null && $value !== '');
    }

    private static function castNumeric(array &$payload, string $key, string $type): void
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            unset($payload[$key]);
            return;
        }

        $payload[$key] = $type === 'int' ? (int) $payload[$key] : (float) $payload[$key];
    }
}
