<?php

namespace App\Services\Ai;

use App\Models\EditorialRequest;

class PromptRenderer
{
    public function render(?string $template, EditorialRequest $request, array $extra = []): string
    {
        $selectedItems = $request->candidates->where('is_selected', true)->map(fn ($candidate) => sprintf('- %s: %s', $candidate->title, $candidate->summary))->implode("\n");

        $values = [
            '{{location_name}}' => $request->location?->name ?? '',
            '{{category_name}}' => $request->newsCategory?->name ?? '',
            '{{edition_type}}' => $request->edition_type ?? '',
            '{{language_name}}' => $request->language?->name ?? '',
            '{{language_code}}' => $request->language?->code ?? '',
            '{{target_duration_seconds}}' => (string) ($request->target_duration_seconds ?? ''),
            '{{date}}' => now()->toDateString(),
            '{{editorial_instructions}}' => $request->editorial_instructions ?? '',
            '{{edition_title}}' => $request->title,
            '{{selected_news_items}}' => $selectedItems,
            '{{editorial_template}}' => (string) ($extra['editorial_template'] ?? ''),
            '{{tone}}' => (string) ($extra['tone'] ?? 'neutral'),
        ];

        return strtr((string) $template, $values);
    }
}
