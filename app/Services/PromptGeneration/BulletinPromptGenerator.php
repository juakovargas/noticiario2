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
        $outputMode = $type->output_mode ?: 'plain_final_script';
        if ($outputMode === 'final_plain_script') {
            $outputMode = 'plain_final_script';
        }

        if ($outputMode === 'structured_script') {
            return $promptLanguage === 'en'
                ? $this->generateStructuredPromptEn($type, $context)
                : $this->generateStructuredPromptEs($type, $context);
        }

        $prompt = $promptLanguage === 'en'
            ? $this->generateFinalPlainPromptEn($type, $profile, $context)
            : $this->generateFinalPlainPromptEs($type, $profile, $context);

        return $this->sanitizePlainPrompt($prompt);
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
            'Mínimo de noticias: '.$minItems.'.',
            'Máximo de noticias: '.$maxItems.'.',
            'Modo de salida: '.($type->output_mode ?: 'plain_final_script').'.',
            'PERSONALIDAD EDITORIAL (0-10):',
            '- Felicidad: '.((int) ($profile->happiness_level ?? 7)),
            '- Optimismo: '.((int) ($profile->optimism_level ?? 7)),
            '- Seriedad: '.((int) ($profile->seriousness_level ?? 5)),
            '- Humor: '.((int) ($profile->humor_level ?? 4)),
            '- Ironía: '.((int) ($profile->irony_level ?? 2)),
            '- Formalidad: '.((int) ($profile->formality_level ?? 4)),
            '- Exigencia de fuentes: '.((int) ($profile->source_strictness_level ?? 7)),
            'Tono breve: '.$tone.'.',
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
            'Minimum news items: '.$minItems.'.',
            'Maximum news items: '.$maxItems.'.',
            'OUTPUT MODE: '.($type->output_mode ?: 'plain_final_script').'.',
            'EDITORIAL PERSONALITY (0-10):',
            '- Happiness: '.((int) ($profile->happiness_level ?? 7)),
            '- Optimism: '.((int) ($profile->optimism_level ?? 7)),
            '- Seriousness: '.((int) ($profile->seriousness_level ?? 5)),
            '- Humor: '.((int) ($profile->humor_level ?? 4)),
            '- Irony: '.((int) ($profile->irony_level ?? 2)),
            '- Formality: '.((int) ($profile->formality_level ?? 4)),
            '- Source strictness: '.((int) ($profile->source_strictness_level ?? 7)),
            'Tone: '.$tone.'.',
            '',
            'STRUCTURE:',
            '- Include '.$minItems.' to '.$maxItems.' relevant items or events.',
            '- Include at least one source hint per news item when source material is available.',
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
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);
        $scheduled = $context['scheduled_for']->copy()->timezone($context['timezone']);

        return trim(implode("\n", [
            'MODO AVANZADO structured_script',
            '',
            'FECHA Y HORA DE EMISIÓN',
            'Fecha de emisión: '.$scheduled->format('Y-m-d').'.',
            'Hora de emisión: '.$scheduled->format('H:i').'.',
            'Zona horaria: '.$context['timezone'].'.',
            '',
            'CONTEXTO EDITORIAL',
            'Informativo: '.$type->name.'.',
            'Localización: '.($type->location?->name ?? 'Global').'.',
            'Categoría: '.($type->newsCategory?->name ?? 'General').'.',
            'Duración objetivo: '.($type->target_duration_seconds ?? 75).' segundos.',
            'Ventana informativa: de '.$this->formatWindow($context['coverage_from'], $context['timezone']).' a '.$this->formatWindow($context['coverage_to'], $context['timezone']).'.',
            '',
            'NÚMERO DE NOTICIAS',
            'Mínimo de noticias: '.$minItems.'.',
            'Máximo de noticias: '.$maxItems.'.',
            '',
            'REGLAS DE SELECCIÓN DE NOTICIAS',
            '- Prioriza hechos recientes, relevantes y verificables.',
            '- No inventes datos, cifras, citas ni fuentes.',
            '- Incluye al menos una pista de fuente por noticia.',
            '- Si algo no está confirmado, dilo con cautela.',
            '',
            'MODO DE SALIDA: structured_script',
            'Los bloques SCRIPT son la narración principal que se usará para crear el guion.',
            'Devuelve exactamente esta estructura:',
            'TITLE:',
            'INTRO:',
            'NEWS ITEMS:',
            '1. HEADLINE:',
            'SUMMARY:',
            'SCRIPT:',
            'EDITORIAL ANGLE:',
            'SOURCE HINTS:',
            'OUTRO:',
            'NOTES:',
        ]));
    }

    private function generateStructuredPromptEn($type, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);
        $scheduled = $context['scheduled_for']->copy()->timezone($context['timezone']);

        return trim(implode("\n", [
            'ADVANCED MODE structured_script',
            '',
            'BROADCAST TIMING',
            'Broadcast date: '.$scheduled->format('Y-m-d').'.',
            'Broadcast time: '.$scheduled->format('H:i').'.',
            'Timezone: '.$context['timezone'].'.',
            '',
            'EDITORIAL CONTEXT',
            'Bulletin: '.$type->name.'.',
            'Location: '.($type->location?->name ?? 'Global').'.',
            'Category: '.($type->newsCategory?->name ?? 'General').'.',
            'Target duration: '.($type->target_duration_seconds ?? 75).' seconds.',
            'Coverage window: '.$this->formatWindow($context['coverage_from'], $context['timezone']).' to '.$this->formatWindow($context['coverage_to'], $context['timezone']).'.',
            '',
            'NEWS ITEM COUNT',
            'Minimum news items: '.$minItems.'.',
            'Maximum news items: '.$maxItems.'.',
            '',
            'NEWS SELECTION RULES',
            '- Prioritize recent, relevant, verifiable facts.',
            '- Do not invent facts, figures, quotes, or sources.',
            '- Include at least one source hint per news item.',
            '- Use cautious wording when something is not confirmed.',
            '',
            'OUTPUT MODE: structured_script',
            'SCRIPT blocks are the main narration used to create the script.',
            'Return exactly this structure:',
            'TITLE:',
            'INTRO:',
            'NEWS ITEMS:',
            '1. HEADLINE:',
            'SUMMARY:',
            'SCRIPT:',
            'EDITORIAL ANGLE:',
            'SOURCE HINTS:',
            'OUTRO:',
            'NOTES:',
        ]));
    }


    private function sanitizePlainPrompt(string $prompt): string
    {
        $forbiddenMarkers = ['MODO AVANZADO', 'structured_script', 'TITLE:', 'INTRO:', 'NEWS ITEMS:', 'HEADLINE:', 'SUMMARY:', 'SCRIPT:', 'EDITORIAL ANGLE:', 'SOURCE HINTS:', 'OUTRO:', 'NOTES:'];

        foreach ($forbiddenMarkers as $marker) {
            if (str_contains(mb_strtoupper($prompt), mb_strtoupper($marker))) {
                logger()->warning('Plain final script prompt contained structured marker; replacing with safe plain prompt.', ['marker' => $marker]);
                return str_replace('Devuelve SOLO el guion final en texto plano continuo.', 'Devuelve SOLO el guion final en texto plano continuo. Evita cualquier formato estructurado.', $prompt);
            }
        }

        return $prompt;
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
