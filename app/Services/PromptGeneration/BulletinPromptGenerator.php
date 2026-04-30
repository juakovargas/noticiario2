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
        [$objective, $structure] = $this->editionGuidanceEs((string) $type->edition_type, (string) ($type->newsCategory?->name ?? 'general'));
        $tone = $this->toneSummaryEs($profile);

        $lines = [
            'Eres redactor de un informativo breve en vídeo para '.($type->location?->name ?? 'audiencia general').'.',
            'Escribe un guion periodístico claro, natural y listo para locución.',
            '',
            'DURACIÓN: '.($type->target_duration_seconds ?? 75).' segundos.',
            'TEMA: '.($type->newsCategory?->name ?? 'General').'.',
            'VENTANA: '.$this->compactCoverageWindowEs($context).'.',
            'OBJETIVO: '.$objective,
            '',
            'ESTRUCTURA:',
            '- 1 frase breve de apertura.',
            '- '.$minItems.'-'.$maxItems.' noticias relevantes en frases cortas (guía: '.$structure.').',
            '- 1 frase final neutra de cierre.',
            '',
            'CRITERIOS:',
            '- No inventes datos.',
            '- Usa frases cortas y naturales.',
            '- Respeta la ventana temporal.',
            '- Si algo no está confirmado, dilo con cautela.',
            '- Tono: '.$tone.'.',
            '- Fuentes: '.($this->isHighStrictness($profile) ? "usa fuentes oficiales o medios reconocidos. Si no hay fuente fiable, escribe 'requiere verificación'." : 'prioriza fuentes fiables; si una información no está clara, indica que requiere verificación.'),
        ];

        if ($this->supportsGrounding($runProvider = null)) {
            $lines[] = '- Si el proveedor aporta fuentes o citas, tenlas en cuenta y evita afirmaciones sin respaldo.';
        }

        $lines = array_merge($lines, [
            '',
            'TÍTULO: '.$type->name.' - '.$context['scheduled_for']->format('Y-m-d'),
            'FECHA: '.$context['scheduled_for']->format('Y-m-d'),
            'BLOQUE: '.($type->edition_type ?? 'general'),
            'SECCIÓN: '.($type->newsCategory?->name ?? 'general'),
            '',
            'Salida: SOLO texto plano continuo, sin listas, sin markdown y sin encabezados.',
        ]);

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
            '- One short opening sentence.',
            '- '.$minItems.'-'.$maxItems.' relevant items in short spoken sentences.',
            '- One short neutral closing sentence.',
            '',
            'CRITERIA:',
            '- Do not invent facts.',
            '- Use short natural spoken sentences.',
            '- Respect the time window.',
            '- If something is not confirmed, use cautious wording.',
            '- Tone: '.$tone.'.',
            '- Sources: prioritize reliable sources; if unclear, state it requires verification.',
            '',
            'TITLE: '.$type->name.' - '.$context['scheduled_for']->format('Y-m-d'),
            'DATE: '.$context['scheduled_for']->format('Y-m-d'),
            'BLOCK: '.($type->edition_type ?? 'general'),
            'SECTION: '.($type->newsCategory?->name ?? 'general'),
            '',
            'Output: ONLY continuous plain text, no lists, no markdown, no headings.',
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
            'special' => [str_contains(strtolower($category), 'sport') || str_contains(strtolower($category), 'deport') ? 'Adelantar los eventos deportivos relevantes de las próximas 24 horas.' : 'Crear un boletín breve con las noticias más relevantes de la ventana configurada.', 'Apertura breve, 4-6 eventos o noticias, cierre ligero'],
            default => ['Crear un boletín breve con las noticias más relevantes de la ventana configurada.', 'Apertura breve, 4-6 noticias y cierre corto'],
        };
    }

    private function supportsGrounding($provider): bool { return false; }

    private function formatWindow(?Carbon $date, string $timezone): string
    {
        return $date ? $date->copy()->timezone($timezone)->format('d/m/Y H:i').' '.$timezone : 'N/A';
    }
}
