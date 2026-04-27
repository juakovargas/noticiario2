<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditorialTemplate;
use App\Models\NewsItem;
use App\Models\Script;
use App\Support\EditorialLanguage;
use App\Support\EditorialTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScriptBuilderController extends Controller
{
    public function __construct(
        private readonly EditorialLanguage $editorialLanguage,
        private readonly EditorialTemplateRenderer $templateRenderer,
    ) {
    }

    public function create(Edition $edition): Response
    {
        $locale = app()->getLocale();

        $edition->load([
            'location:id,name,country_code',
            'newsItems' => fn ($query) => $query
                ->with([
                    'source:id,name',
                    'category:id,name',
                    'category.translations:id,news_category_id,language_code,name',
                    'location:id,name,country_code',
                ])
                ->orderBy('edition_news_item.sort_order'),
            'scripts:id,edition_id,title,status,language,estimated_duration_seconds',
        ]);

        if (! $edition->language) {
            $edition->language = $this->editorialLanguage->resolveCode($edition->location, null);
        }

        $includedNewsItems = $edition->newsItems
            ->filter(fn (NewsItem $item) => (bool) $item->pivot->included_in_script)
            ->values();

        $templates = $this->relevantTemplates($edition);
        $selectedTemplate = $templates->first();
        $rendered = $selectedTemplate ? $this->templateRenderer->render($selectedTemplate, $edition, $includedNewsItems) : null;

        $transitions = ['First', 'Next', 'Also', 'Finally'];

        $manualBody = $includedNewsItems
            ->values()
            ->map(function (NewsItem $item, int $index) use ($transitions): string {
                $prefix = $transitions[min($index, count($transitions) - 1)];
                $angle = $item->pivot->editorial_angle ? trim($item->pivot->editorial_angle).'.' : '';
                $summary = $item->summary ? trim($item->summary) : '';

                return trim($prefix.', '.$item->title.'. '.$angle.' '.$summary);
            })
            ->implode("\n\n");

        return Inertia::render('Editor/Scripts/Builder', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
                'status' => $edition->status,
            ],
            'selectedNewsItems' => $includedNewsItems->map(fn (NewsItem $item) => [
                'id' => $item->id,
                'sort_order' => $item->pivot->sort_order,
                'title' => $item->title,
                'summary' => $item->summary,
                'editorial_angle' => $item->pivot->editorial_angle,
                'source' => $item->source?->name,
                'category' => $item->category?->displayName($locale),
                'location' => $item->location,
                'included_in_script' => (bool) $item->pivot->included_in_script,
            ])->values(),
            'existingScripts' => $edition->scripts->map(fn (Script $script) => [
                'id' => $script->id,
                'title' => $script->title,
                'status' => $script->status,
                'language' => $script->language,
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
            ])->values(),
            'templates' => $templates->values(),
            'prefill' => [
                'title' => 'Draft script for '.$edition->title,
                'status' => 'draft',
                'language' => $edition->language,
                'intro' => $rendered['intro'] ?? ('Hello. This is '.$edition->title.'. These are the main stories.'),
                'body' => $rendered['body'] ?? $manualBody,
                'outro' => $rendered['outro'] ?? 'That is all for this edition. Follow us for more updates.',
                'estimated_duration_seconds' => $selectedTemplate?->target_duration_seconds,
                'selected_editorial_template_id' => $selectedTemplate?->id,
            ],
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(['draft', 'review'])],
            'language' => ['nullable', 'string', 'max:10'],
            'editorial_template_id' => ['nullable', 'exists:editorial_templates,id'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'outro' => ['nullable', 'string'],
            'estimated_duration_seconds' => ['nullable', 'integer', 'min:1'],
        ]);

        $edition->loadMissing('location.defaultLanguage', 'newsItems');

        $newsItemIds = $edition->newsItems()
            ->wherePivot('included_in_script', true)
            ->orderBy('edition_news_item.sort_order')
            ->pluck('news_items.id')
            ->values()
            ->all();

        $languageCode = $this->editorialLanguage->resolveCode($edition->location, $data['language'] ?? $edition->language);

        Script::query()->create([
            'edition_id' => $edition->id,
            'title' => $data['title'],
            'status' => $data['status'] ?: 'draft',
            'language' => $languageCode,
            'intro' => $data['intro'] ?? null,
            'body' => $data['body'] ?? null,
            'outro' => $data['outro'] ?? null,
            'estimated_duration_seconds' => $data['estimated_duration_seconds'] ?? null,
            'metadata' => [
                'created_from' => 'manual_builder',
                'editorial_template_id' => $data['editorial_template_id'] ?? null,
                'news_item_ids' => $newsItemIds,
                'template_version' => 'manual_v1',
            ],
        ]);

        return to_route('editor.scripts.show', $edition->scripts()->latest('id')->first())->with('success', 'Draft script created successfully.');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function relevantTemplates(Edition $edition): Collection
    {
        return EditorialTemplate::query()
            ->with('language:id,code,name,native_name,flag_emoji', 'location:id,name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (EditorialTemplate $template) => $this->templateScore($template, $edition))
            ->reverse()
            ->values()
            ->map(fn (EditorialTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'language' => $template->language,
                'location' => $template->location,
                'edition_type' => $template->edition_type,
                'target_duration_seconds' => $template->target_duration_seconds,
                'intro_template' => $template->intro_template,
                'body_template' => $template->body_template,
                'outro_template' => $template->outro_template,
            ]);
    }

    private function templateScore(EditorialTemplate $template, Edition $edition): int
    {
        $score = 0;

        if ($template->edition_type && $template->edition_type === $edition->edition_type) {
            $score += 5;
        }

        if ($template->location_id && $edition->location_id && $template->location_id === $edition->location_id) {
            $score += 40;
        } elseif ($template->location_id === null) {
            $score += 10;
        }

        $templateLanguageCode = $template->language?->code;
        if ($templateLanguageCode && $edition->language && $templateLanguageCode === $edition->language) {
            $score += 30;
        }

        return $score;
    }
}
