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
            ? $this->generateEnglishPrompt($type, $profile, $context)
            : $this->generateSpanishPrompt($type, $profile, $context);
    }

    private function generateSpanishPrompt($type, $profile, array $context): string
    {
        [$minItems, $maxItems] = $this->resolveNewsItemRange($type->min_news_items, $type->max_news_items, $type->target_duration_seconds);

        $lines = [
            'Eres un redactor y guionista de informativos en vídeo para España.',
            'Tu tarea es escribir un guion periodístico claro, coherente y fácil de escuchar.',
            'No inventes datos ni hechos.',
            '',
            'CONFIGURACIÓN DEL NOTICIARIO:',
            '- Tipo de noticiario: '.$type->name,
            '- Ubicación: '.($type->location?->name ?? 'Global'),
            '- Categoría: '.($type->newsCategory?->name ?? 'General'),
            '- Idioma final del guion: '.($type->language?->name ?? 'No especificado'),
            '- Duración objetivo (segundos): '.($type->target_duration_seconds ?? 'N/A'),
            '',
            'FECHA Y HORA DE EMISIÓN:',
            '- Fecha de emisión: '.$context['scheduled_for']->format('Y-m-d'),
            '- Hora de emisión: '.$context['scheduled_for']->format('H:i'),
            '- Zona horaria: '.$context['timezone'],
            '',
            'VENTANA DE COBERTURA:',
            '- Modo de cobertura: '.$context['coverage_mode'],
            '- Desde: '.$this->formatWindow($context['coverage_from'], $context['timezone']),
            '- Hasta: '.$this->formatWindow($context['coverage_to'], $context['timezone']),
            '- Significado editorial: '.($context['description'] ?: $this->defaultCoverageMeaningEs($context['coverage_mode'])),
            '- Agenda futura: '.($type->include_future_agenda ? 'Incluir próximos eventos y marcarlos como agenda.' : 'No incluir agenda futura salvo necesidad editorial crítica.'),
            '- Contexto histórico: '.($type->include_historical_context ? 'Incluir contexto breve cuando ayude a entender la noticia.' : 'Usar contexto histórico mínimo.'),
            '',
            'REGLAS DE SELECCIÓN DE NOTICIAS:',
            '- Selecciona las noticias más relevantes para la ubicación y categoría configuradas.',
            '- Respeta estrictamente la ventana de cobertura.',
            '- Prioriza información reciente y confirmada.',
            '- Evita historias desactualizadas salvo que sigan en desarrollo.',
            '',
            'REQUISITOS DE FUENTES Y CALIDAD:',
            '- Prioriza fuentes reconocidas y verificables.',
            '- Da preferencia a fuentes oficiales, agencias y medios de alta reputación.',
            '- Ejemplos orientativos para España: EFE, Europa Press, RTVE, La Moncloa, BOE, ministerios e instituciones públicas, AEMET, INE, gobiernos autonómicos cuando aplique, y grandes medios nacionales.',
            '- En deportes prioriza clubes oficiales, ligas/federaciones, UEFA/FIFA/LaLiga/RFEF; y medios deportivos reputados cuando sea útil (Marca, AS, Mundo Deportivo, Sport).',
            '- En ciencia/salud prioriza instituciones oficiales, universidades, centros de investigación y autoridades sanitarias.',
            '- Si usas una fuente poco conocida, marca claramente que requiere verificación.',
            '- Incluye al menos una pista de fuente por noticia.',
            '- Si no hay fuente fiable, escribe exactamente: "requiere verificación".',
            '',
            'NÚMERO DE NOTICIAS:',
            '- Mínimo de noticias: '.$minItems,
            '- Máximo de noticias: '.$maxItems,
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
            'MODO DE SALIDA: '.$type->output_mode,
        ];

        if ($type->output_mode === 'plain_script') {
            $lines = array_merge($lines, [
                '- Entrega únicamente el guion final listo para presentador.',
                '- Sin markdown, sin tablas y sin bloques técnicos.',
            ]);
        } else {
            $lines = array_merge($lines, [
                '- Devuelve exactamente estos encabezados y en este orden:',
                'TITLE:',
                'Título breve del boletín completo.',
                '',
                'INTRO:',
                'Texto de apertura listo para presentador.',
                '',
                'NEWS ITEMS:',
                '1. HEADLINE:',
                'Titular breve de la noticia.',
                '',
                'SUMMARY:',
                'Resumen factual breve de apoyo editorial, no para locución final.',
                '',
                'SCRIPT:',
                'Bloque principal de narración para presentador. Este bloque se usa para construir el guion final.',
                '',
                'EDITORIAL ANGLE:',
                'Por qué importa esta noticia o cómo enmarcarla.',
                '',
                'SOURCE HINTS:',
                '- Nombre de fuente y URL si está disponible.',
                '- Si la fuente es débil o poco clara, marca "needs verification" o "requiere verificación".',
                '',
                'OUTRO:',
                'Texto de cierre listo para presentador.',
                '',
                'NOTES:',
                'Advertencias, incertidumbre, límites de fuentes y notas de verificación.',
                '',
                'REGLAS DE FORMATO IMPORTANTES:',
                '- Los bloques SCRIPT son la narración principal.',
                '- SUMMARY es solo apoyo interno/editorial.',
                '- No pongas toda la narración únicamente en SUMMARY.',
                '- Usa frases cortas y naturales para SCRIPT.',
                '- Escribe SCRIPT en el idioma final del boletín.',
                '- No uses tablas Markdown.',
                '- Mantén los encabezados exactamente como se solicitaron.',
                '- Incluye al menos una pista de fuente por noticia cuando sea posible.',
            ]);
        }

        return trim(implode("\n", $lines));
    }

    private function generateEnglishPrompt($type, $profile, array $context): string
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
            '',
            'SOURCE QUALITY REQUIREMENTS:',
            '- Prioritize recognized and verifiable sources.',
            '- Prefer official sources, agencies, and major reputable media.',
            '- Include at least one source hint per news item.',
            '- If no reliable source is available, explicitly write "requires verification".',
            '',
            'NEWS COUNT:',
            '- Minimum news items: '.$minItems,
            '- Maximum news items: '.$maxItems,
            '',
            'OUTPUT MODE: '.$type->output_mode,
        ];

        if ($type->output_mode === 'plain_script') {
            $lines[] = '- Return only clean presenter-ready script text. No markdown.';
        } else {
            $lines = array_merge($lines, [
                '- Return exactly these headings and keep this order:',
                'TITLE:',
                'Short title for the whole bulletin.',
                '',
                'INTRO:',
                'Presenter-ready opening text.',
                '',
                'NEWS ITEMS:',
                '1. HEADLINE:',
                'Short headline for this item.',
                '',
                'SUMMARY:',
                'Brief factual summary, not for narration.',
                '',
                'SCRIPT:',
                'Presenter-ready narration text for this item. This is the main block used to build the final script.',
                '',
                'EDITORIAL ANGLE:',
                'Why this item matters or how it should be framed.',
                '',
                'SOURCE HINTS:',
                '- Source name and URL when available.',
                '- If source is weak/unclear, mark as "needs verification".',
                '',
                'OUTRO:',
                'Presenter-ready closing text.',
                '',
                'NOTES:',
                'Warnings, uncertainty, source limitations, or verification notes.',
                '',
                'IMPORTANT FORMAT RULES:',
                '- SCRIPT sections are the main narration blocks.',
                '- SUMMARY is only internal/editorial support.',
                '- Do not place the full narration only in SUMMARY.',
                '- Use short natural sentences for SCRIPT.',
                '- Write SCRIPT in the final bulletin language.',
                '- Do not use Markdown tables.',
                '- Keep headings exactly as requested.',
                '- Include at least one source hint per news item when possible.',
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
