<?php

namespace App\Services\PromptGeneration;

use App\Models\BulletinPromptRun;
use Carbon\Carbon;

class BulletinPromptGenerator
{
    public function __construct(private readonly BulletinCoverageWindowResolver $coverageWindowResolver)
    {
    }

    public function generate(BulletinPromptRun $run): string
    {
        $run->loadMissing(['bulletinType.location', 'bulletinType.newsCategory', 'bulletinType.language', 'promptProfile']);

        $type = $run->bulletinType;
        $profile = $run->promptProfile;
        $context = $this->coverageWindowResolver->resolve($run);

        $promptLanguage = $type->prompt_language ?: 'es';

        return $promptLanguage === 'en'
            ? $this->generateEnglishPrompt($run, $type, $profile, $context)
            : $this->generateSpanishPrompt($run, $type, $profile, $context);
    }

    private function generateSpanishPrompt(BulletinPromptRun $run, $type, $profile, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);

        $lines = [
            'Eres un redactor y guionista de informativos en vídeo para España.',
            'Tu tarea es escribir un guion periodístico claro, coherente y fácil de escuchar.',
            'El texto será leído en voz alta en un informativo de actualidad.',
            'No inventes datos ni hechos.',
            '',
            'CONFIGURACIÓN DEL NOTICIARIO:',
            '- Tipo de noticiario: '.$type->name,
            '- Ubicación: '.($type->location?->name ?? 'Global'),
            '- Categoría: '.($type->newsCategory?->name ?? 'General'),
            '- Idioma final del guion: '.($type->language?->name ?? 'No especificado'),
            '- Duración objetivo (segundos): '.($type->target_duration_seconds ?? 'N/A'),
            '',
            'BROADCAST TIMING:',
            '- Broadcast date: '.$context['scheduled_for']->format('Y-m-d'),
            '- Broadcast time: '.$context['scheduled_for']->format('H:i'),
            '- Timezone: '.$context['timezone'],
            '',
            'VENTANA DE COBERTURA:',
            '- Modo de cobertura: '.$context['coverage_mode'],
            '- Desde: '.$this->formatWindow($context['coverage_from'], $context['timezone']),
            '- Hasta: '.$this->formatWindow($context['coverage_to'], $context['timezone']),
            '- Significado editorial: '.($context['description'] ?: $this->defaultCoverageMeaningEs($context['coverage_mode'])),
            '- Agenda futura: '.($type->include_future_agenda ? 'Incluir próximos eventos y marcarlos como agenda.' : 'No incluir agenda futura salvo necesidad editorial crítica.'),
            '- Contexto histórico: '.($type->include_historical_context ? 'Incluir contexto breve cuando ayude a entender la noticia.' : 'Usar contexto histórico mínimo.'),
            '',
            'NEWS SELECTION RULES:',
            '- Select the most relevant news for the configured location and category.',
            '- Respect the coverage window.',
            '- Prioritize recent confirmed information.',
            '- Avoid outdated stories unless still developing.',
            '- Do not include future agenda if include_future_agenda is false.',
            '- If include_future_agenda is true, clearly identify future events as upcoming.',
            '',
            'REQUISITOS DE FUENTES:',
            '- Usa información actual si tu herramienta permite navegación web.',
            '- No inventes hechos.',
            '- Si una información no está confirmada, indícalo con cautela.',
            '- Añade pistas de fuentes/URLs cuando sea posible.',
            '',
            'NEWS COUNT:',
            '- Minimum news items: '.$minItems,
            '- Maximum news items: '.$maxItems,
            '',
            'PERSONALIDAD EDITORIAL (0-10):',
            '- Happiness level: '.($profile->happiness_level ?? 'N/A'),
            '- Optimism level: '.($profile->optimism_level ?? 'N/A'),
            '- Seriousness level: '.($profile->seriousness_level ?? 'N/A'),
            '- Humor level: '.($profile->humor_level ?? 'N/A'),
            '- Irony level: '.($profile->irony_level ?? 'N/A'),
            '- Formality level: '.($profile->formality_level ?? 'N/A'),
            '- Source strictness level: '.($profile->source_strictness_level ?? 'N/A'),
            '',
            'OBJETIVO EDITORIAL:',
            'Resumir las noticias más relevantes cerradas durante la ventana de cobertura.',
            '',
            'OUTPUT MODE: '.$type->output_mode,
        ];

        if ($type->output_mode === 'plain_script') {
            $lines = array_merge($lines, [
                '- Entrega únicamente el guion final listo para presentador.',
                '- Sin markdown, sin etiquetas, sin lista de fuentes salvo petición explícita.',
            ]);
        } else {
            $lines = array_merge($lines, [
                '- Responde con estructura explícita:',
                'TITLE:',
                'INTRO:',
                'NEWS ITEMS:',
                'HEADLINE:',
                'SUMMARY:',
                'SCRIPT:',
                'EDITORIAL ANGLE:',
                'SOURCE HINTS:',
                'OUTRO:',
                'NOTES:',
            ]);
        }

        return trim(implode("\n", $lines));
    }

    private function generateEnglishPrompt(BulletinPromptRun $run, $type, $profile, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);

        $lines = [
            'You are an editor and scriptwriter for a digital news bulletin.',
            'Write a clear presenter-ready script and do not invent facts.',
            '',
            'BULLETIN CONFIGURATION:',
            '- Bulletin type: '.$type->name,
            '- Location: '.($type->location?->name ?? 'Global'),
            '- Category: '.($type->newsCategory?->name ?? 'General'),
            '- Final script language: '.($type->language?->name ?? 'Unspecified'),
            '- Target duration (seconds): '.($type->target_duration_seconds ?? 'N/A'),
            '',
            'BROADCAST TIMING:',
            '- Broadcast date: '.$context['scheduled_for']->format('Y-m-d'),
            '- Broadcast time: '.$context['scheduled_for']->format('H:i'),
            '- Timezone: '.$context['timezone'],
            '',
            'COVERAGE WINDOW:',
            '- Coverage mode: '.$context['coverage_mode'],
            '- From: '.$this->formatWindow($context['coverage_from'], $context['timezone']),
            '- To: '.$this->formatWindow($context['coverage_to'], $context['timezone']),
            '- Editorial meaning: '.($context['description'] ?: $this->defaultCoverageMeaningEn($context['coverage_mode'])),
            '- Future agenda: '.($type->include_future_agenda ? 'Include upcoming events and mark them as upcoming.' : 'Do not include future agenda unless strictly essential.'),
            '- Historical context: '.($type->include_historical_context ? 'Include brief context when needed.' : 'Only include minimal historical context.'),
            '',
            'NEWS SELECTION RULES:',
            '- Select the most relevant news for the configured location and category.',
            '- Respect the coverage window.',
            '- Prioritize recent confirmed information.',
            '- Avoid outdated stories unless still developing.',
            '- Do not include future agenda if include_future_agenda is false.',
            '- If include_future_agenda is true, clearly identify future events as upcoming.',
            '',
            'NEWS COUNT:',
            '- Minimum news items: '.$minItems,
            '- Maximum news items: '.$maxItems,
            '',
            'FACT REQUIREMENTS:',
            '- Use current information if your tool supports web browsing.',
            '- Do not invent facts.',
            '- If something is uncertain, mark it clearly.',
            '',
            'OUTPUT MODE: '.$type->output_mode,
        ];

        if ($type->output_mode === 'plain_script') {
            $lines[] = '- Return only clean presenter-ready script text. No markdown.';
        } else {
            $lines = array_merge($lines, [
                '- Return a structured response with:',
                'TITLE, INTRO, NEWS ITEMS, HEADLINE, SUMMARY, SCRIPT, EDITORIAL ANGLE, SOURCE HINTS, OUTRO, NOTES.',
            ]);
        }

        return trim(implode("\n", $lines));
    }

    private function resolveNewsItemRange(?int $min, ?int $max, ?int $duration): array
    {
        if ($min && $max) {
            return [$min, $max];
        }

        $inferred = match (true) {
            $duration !== null && $duration <= 60 => [3, 4],
            $duration !== null && $duration <= 90 => [4, 5],
            $duration !== null && $duration <= 180 => [5, 8],
            default => [7, 10],
        };

        return [$min ?? $inferred[0], $max ?? $inferred[1]];
    }

    private function formatWindow(?Carbon $date, string $timezone): string
    {
        return $date ? $date->copy()->timezone($timezone)->format('Y-m-d H:i').' '.$timezone : 'N/A';
    }

    private function defaultCoverageMeaningEs(string $mode): string
    {
        return match ($mode) {
            'today_so_far' => 'Cubrir noticias confirmadas de hoy hasta la hora de emisión.',
            'yesterday' => 'Cubrir noticias del día anterior completo.',
            'last_24_hours' => 'Cubrir noticias confirmadas de las últimas 24 horas.',
            'next_24_hours' => 'Centrar el boletín en agenda y previsiones de las próximas 24 horas.',
            'custom', 'previous_period' => 'Cubrir noticias cerradas dentro de la ventana definida por offsets.',
            default => 'Aplicar criterio editorial y priorizar actualidad confirmada.',
        };
    }

    private function defaultCoverageMeaningEn(string $mode): string
    {
        return match ($mode) {
            'today_so_far' => 'Cover confirmed news from local start of day to broadcast time.',
            'yesterday' => 'Cover the full previous local day.',
            'last_24_hours' => 'Cover confirmed developments from the last 24 hours.',
            'next_24_hours' => 'Focus on upcoming agenda and previews for the next 24 hours.',
            'custom', 'previous_period' => 'Cover confirmed news inside the configured offset window.',
            default => 'Apply editorial judgement and prioritize confirmed recent developments.',
        };
    }
}
