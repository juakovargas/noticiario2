<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class GeminiClient implements AiClient
{
    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        $key = trim((string) env($provider->api_key_env_name ?? 'GEMINI_API_KEY'));
        if ($key === '') throw new AiProviderException('Environment key is not configured.');
        $model = $provider->default_model ?: 'gemini-2.0-flash';
        $url = rtrim((string)($provider->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/')."/models/{$model}:generateContent";
        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ];
        if ($provider->supports_grounding || $provider->supportsCapability('google_search_grounding')) {
            $payload['tools'] = [['google_search' => (object)[]]];
        }
        $response = Http::timeout($provider->timeoutSecondsForRequest())->withQueryParameters(['key'=>$key])->post($url, $payload);
        if ($response->failed()) throw new AiProviderException('AI request failed with status '.$response->status());
        $json = $response->json();
        $text = data_get($json, 'candidates.0.content.parts.0.text', '');
        return new AiResponseData($text, $json, $provider->provider_type, $model,
            data_get($json,'usageMetadata.promptTokenCount'), data_get($json,'usageMetadata.candidatesTokenCount'), data_get($json,'usageMetadata.totalTokenCount'),
            data_get($json,'candidates.0.finishReason'), null, ['grounding' => data_get($json,'candidates.0.groundingMetadata')]);
    }
}
