<?php

namespace App\Services\Ai;

use App\Data\AiResponse;
use App\Models\EditorialRequest;

class MockAiClient implements AiClientInterface
{
    public function generate(EditorialRequest $request, string $prompt): AiResponse
    {
        $templateType = $request->aiPromptTemplate?->type;

        if ($templateType === 'editorial_research') {
            $payload = [
                'candidate_news_items' => [
                    [
                        'title' => 'City council confirms phased transit upgrades',
                        'summary' => 'The city announced a multi-phase rollout for transit improvements over the coming months.',
                        'source_hint' => 'Local municipal briefing',
                        'source_url' => 'https://example.com/mock/transit-upgrades',
                        'suggested_category' => 'General',
                        'suggested_location' => $request->location?->name ?? 'Global',
                        'relevance_score' => 5,
                        'editorial_angle' => 'How the phased rollout impacts daily commute reliability.',
                        'why_it_matters' => 'Transit reliability is directly tied to productivity and quality of life.',
                    ],
                    [
                        'title' => 'Regional health office launches preventive outreach campaign',
                        'summary' => 'Health authorities are opening a two-week campaign with neighborhood outreach points.',
                        'source_hint' => 'Regional health office statement',
                        'source_url' => 'https://example.com/mock/health-campaign',
                        'suggested_category' => 'Health',
                        'suggested_location' => $request->location?->name ?? 'Global',
                        'relevance_score' => 4,
                        'editorial_angle' => 'Practical actions audiences can take this week.',
                        'why_it_matters' => 'Preventive campaigns reduce seasonal pressure on local clinics.',
                    ],
                ],
            ];

            return new AiResponse(
                content: json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
                parsedJson: $payload,
                inputTokens: 300,
                outputTokens: 220,
                costCents: 0,
                metadata: ['provider' => 'mock', 'deterministic' => true],
            );
        }

        return new AiResponse(
            content: "Good day. This is your concise bulletin script.\n\nTop story: Infrastructure and community updates remain central today.\n\nSecond story: Public service teams are focusing on practical local impact.\n\nClosing: Stay informed and keep following verified updates.",
            parsedJson: null,
            inputTokens: 220,
            outputTokens: 140,
            costCents: 0,
            metadata: ['provider' => 'mock', 'deterministic' => true],
        );
    }
}
