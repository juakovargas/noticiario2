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
        $outputMode = $type->output_mode ?: 'final_plain_script';

        if ($outputMode === 'structured_script') {
            return $promptLanguage === 'en'
                ? $this->generateStructuredPromptEn($type, $context)
                : $this->generateStructuredPromptEs($type, $context);
        }

        return $promptLanguage === 'en'
            ? $this->generateFinalPlainPromptEn($type, $profile, $context)
            : $this->generateFinalPlainPromptEs($type, $profile, $context);
    }

    private function generateFinalPlainPromptEs($type, $profile, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);
        [$objective, $rule] = $this->editionGuidanceEs((string) $type->edition_type, (string) ($type->newsCategory?->name ?? 'general'));
        $tone = $this->toneSummaryEs($profile);

        $lines = [
            'Eres redactor y guionista de informativos breves en vídeo.',
            'Escribe un guion periodístico claro, natural y listo para locución.',
            '',
            'Fecha de emisión: '.$context['scheduled_for']->timezone($context['timezone'])->format('d/m/Y H:i').'.',
            'Informativo: '.$type->name.'.',
            'Localización: '.($type->location?->name ?? 'Global').'.',
            'Categoría: '.($type->newsCategory?->name ?? 'General').'.',
            'Idioma: '.($type->language?->name ?? 'Español').'.',
            'Duración objetivo: '.($type->target_duration_seconds ?? 75).' segundos.',
            'Propósito: '.$objective,
            'Ventana informativa: de '.$this->formatWindow($context['coverage_from'], $context['timezone']).' a '.$this->formatWindow($context['coverage_to'], $context['timezone']).'.',
            'Tono: '.$tone.'.',
            '',
            'Reglas:',
            '- No inventes datos.',
            '- Usa frases cortas y naturales.',
            '- Incluye '.$minItems.' a '.$maxItems.' noticias o eventos.',
            '- '.$rule,
            '- Si algo no está confirmado, dilo con cautela.',
            '- Prioriza información verificable.',
            '',
            'Devuelve SOLO el guion final en texto plano continuo.',
            'No uses títulos, encabezados, listas, markdown, notas ni bloques separados.',
        ];

        return trim(implode("\n", $lines));
    }

    private function generateFinalPlainPromptEn($type, $profile, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);
        $tone = 'serious, neutral and professional';

        return trim(implode("\n", [
            'You are writing a short video news bulletin for '.($type->location?->name ?? 'a general audience').'.',
            'Write a clear journalistic narration script ready for voice-over.',
            '',
            'DURATION: '.($type->target_duration_seconds ?? 75).' seconds.',
            'TOPIC: '.($type->newsCategory?->name ?? 'General').'.',
            'WINDOW: '.$this->formatWindow($context['coverage_from'], $context['timezone']).' to '.$this->formatWindow($context['coverage_to'], $context['timezone']).'.',
            'OBJECTIVE: Summarize the most relevant developments in the configured window.',
            '',
            'STRUCTURE:',
            '- Include '.$minItems.' to '.$maxItems.' relevant items or events.',
            '- Do not invent facts.',
            '- Use short natural spoken sentences.',
            '- If something is not confirmed, use cautious wording.',
            '- Sources: prioritize reliable sources; if unclear, state it requires verification.',
            '',
            'Return ONLY the final narration script in continuous plain text.',
            'Do not use headings, markdown, sections, notes, or separated blocks.',
        ]));
    }

    private function generateStructuredPromptEs($type, array $context): string
    {
        return "MODO AVANZADO structured_script\nTITLE:\nINTRO:\nNEWS ITEMS:\n1. HEADLINE:\nSUMMARY:\nSCRIPT:\nEDITORIAL ANGLE:\nSOURCE HINTS:\nOUTRO:\nNOTES:";
    }

    private function generateStructuredPromptEn($type, array $context): string
    {
        return "ADVANCED MODE structured_script\nTITLE:\nINTRO:\nNEWS ITEMS:\n1. HEADLINE:\nSUMMARY:\nSCRIPT:\nEDITORIAL ANGLE:\nSOURCE HINTS:\nOUTRO:\nNOTES:";
    }

    private function resolveNewsItemRange(?int $min, ?int $max, ?int $duration): array
    {
        if ($min && $max) return [$min, $max];
        $inferred = $duration !== null && $duration <= 60 ? [3, 4] : ($duration !== null && $duration <= 90 ? [4, 5] : [4, 6]);
        return [$min ?? $inferred[0], $max ?? $inferred[1]];
    }

    private function compactCoverageWindowEs(array $context): string
    {
        $from = $this->formatWindow($context['coverage_from'], $context['timezone']);
        $to = $this->formatWindow($context['coverage_to'], $context['timezone']);
        return strtolower((string) $context['coverage_mode']).", de {$from} a {$to}";
    }

    private function toneSummaryEs($profile): string
    {
        $serious = (int) ($profile->seriousness_level ?? 5);
        $formal = (int) ($profile->formality_level ?? 5);
        $humor = (int) ($profile->humor_level ?? 0);
        $optimism = (int) ($profile->optimism_level ?? 5);

        $parts = [];
        $parts[] = ($serious >= 7 || $formal >= 7) ? 'serio, formal y profesional' : 'claro, cercano y profesional';
        if ($optimism >= 7) $parts[] = 'positivo y constructivo';
        if ($humor >= 6) $parts[] = 'con humor suave';

        return implode(', ', $parts);
    }

    private function isHighStrictness($profile): bool
    {
        return (int) ($profile->source_strictness_level ?? 0) >= 7;
    }

    private function editionGuidanceEs(string $editionType, string $category): array
    {
        return match ($editionType) {
            'morning' => ['Resumir las noticias más relevantes cerradas del periodo anterior.', '1 frase de apertura, 3-5 noticias breves y 1 cierre corto'],
            'afternoon' => ['Actualizar los hechos relevantes del día y lo que sigue abierto.', 'Apertura breve, 4-6 noticias y cierre útil'],
            'night' => ['Recapitular el día y adelantar con cautela lo importante de mañana.', 'Apertura, 4-6 noticias, cierre de resumen'],
            'special' => [str_contains(strtolower($category), 'sport') || str_contains(strtolower($category), 'deport') ? 'Adelantar los eventos deportivos relevantes de las próximas 24 horas.' : 'Crear un boletín breve con las noticias más relevantes de la ventana configurada.', 'Marca como agenda los eventos futuros.'],
            default => ['Crear un boletín breve con las noticias más relevantes de la ventana configurada.', 'Apertura breve, 4-6 noticias y cierre corto'],
        };
    }

    private function formatWindow(?Carbon $date, string $timezone): string
    {
        return $date ? $date->copy()->timezone($timezone)->format('d/m/Y H:i').' '.$timezone : 'N/A';
    }
}
