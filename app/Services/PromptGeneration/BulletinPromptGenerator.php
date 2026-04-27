<?php

namespace App\Services\PromptGeneration;

use App\Models\BulletinPromptRun;

class BulletinPromptGenerator
{
    public function generate(BulletinPromptRun $run): string
    {
        $run->loadMissing(['bulletinType.location', 'bulletinType.newsCategory', 'bulletinType.language', 'promptProfile']);

        $type = $run->bulletinType;
        $profile = $run->promptProfile;
        $languageName = $type->language?->name ?? 'Unspecified';
        $languageCode = $type->language?->code ? ' ('.$type->language->code.')' : '';

        return trim(implode("\n", [
            'You are creating a short digital news bulletin script for presenter narration.',
            'The script will later be used for audio/video generation workflows, so it must be presenter-ready and clearly structured.',
            '',
            'BULLETIN CONFIGURATION:',
            '- Bulletin type: '.$type->name,
            '- Location: '.($type->location?->name ?? 'Global'),
            '- Category: '.($type->newsCategory?->name ?? 'General'),
            '- Language: '.$languageName.$languageCode,
            '- Edition type: '.($type->edition_type ?? 'Unspecified'),
            '- Scheduled date/time: '.($run->scheduled_for?->toDateTimeString() ?? 'N/A'),
            '- Target duration (seconds): '.($type->target_duration_seconds ?? 'N/A'),
            '- Timezone: '.($type->default_timezone ?? config('app.timezone')),
            '',
            'EDITORIAL PERSONALITY (0-10 scale):',
            '- Happiness level: '.($profile->happiness_level ?? 'N/A'),
            '- Optimism level: '.($profile->optimism_level ?? 'N/A'),
            '- Seriousness level: '.($profile->seriousness_level ?? 'N/A'),
            '- Humor level: '.($profile->humor_level ?? 'N/A'),
            '- Irony level: '.($profile->irony_level ?? 'N/A'),
            '- Formality level: '.($profile->formality_level ?? 'N/A'),
            '- Negativity tolerance: '.($profile->negativity_tolerance ?? 'N/A'),
            '- Controversy tolerance: '.($profile->controversy_tolerance ?? 'N/A'),
            '- Source strictness level: '.($profile->source_strictness_level ?? 'N/A'),
            '- Target audience: '.($profile->target_audience ?? 'General audience'),
            '- Presenter style: '.($profile->presenter_style ?? 'Professional and clear'),
            '- Forbidden topics: '.implode(', ', $profile->forbidden_topics ?? ['None explicitly configured']),
            '- Preferred topics: '.implode(', ', $profile->preferred_topics ?? ['No specific preference']),
            '',
            'Style instructions:',
            $profile->style_instructions ?: 'No additional style instructions.',
            '',
            'Fact-checking instructions:',
            $profile->fact_checking_instructions ?: 'Every claim must be verifiable and transparent.',
            '',
            'Output instructions:',
            $profile->output_instructions ?: 'Keep concise and presenter-ready.',
            '',
            'FACT REQUIREMENTS (MANDATORY):',
            '- Use current information if your tool supports web browsing.',
            '- Do not invent facts.',
            '- If something is uncertain, explicitly state that it requires verification.',
            '- Prefer verifiable and relevant news.',
            '- Include source hints/URLs whenever possible, especially when source strictness is high.',
            '',
            'HAPPINESS AND OPTIMISM RULES:',
            '- If happiness level is high, prioritize positive, constructive, or useful news.',
            '- Do not hide major critical news if it is editorially essential; frame it responsibly.',
            '- Avoid sensationalism.',
            '- If negativity tolerance is low, reduce crime/conflict-heavy stories unless essential.',
            '- If humor or irony are enabled, keep it light, respectful, and appropriate for news.',
            '',
            'Write the final script in this bulletin language: '.$languageName.$languageCode.'.',
            'Return plain text using this exact structure and headings:',
            '',
            'TITLE:',
            '...',
            '',
            'INTRO:',
            '...',
            '',
            'NEWS ITEMS:',
            '1. HEADLINE:',
            '...',
            '',
            'SUMMARY:',
            '...',
            '',
            'SCRIPT:',
            '...',
            '',
            'EDITORIAL ANGLE:',
            '...',
            '',
            'SOURCE HINTS:',
            '- ...',
            '',
            '2. HEADLINE:',
            '...',
            '',
            'OUTRO:',
            '...',
            '',
            'NOTES:',
            '...',
        ]));
    }
}
