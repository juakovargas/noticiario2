<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use App\Models\AiProvider;
use App\Models\Edition;
use App\Models\EditorialRequest;
use App\Models\EditorialRequestCandidate;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Services\Ai\AiClientManager;
use App\Services\Ai\PromptRenderer;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EditorialRequestController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly PromptRenderer $promptRenderer,
        private readonly AiClientManager $aiClientManager,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'location_id' => (string) $request->query('location_id', ''),
            'news_category_id' => (string) $request->query('news_category_id', ''),
            'language_id' => (string) $request->query('language_id', ''),
            'show_archived' => $request->boolean('show_archived'),
            'only_archived' => $request->boolean('only_archived'),
        ];

        return Inertia::render('Editor/EditorialRequests/Index', [
            'requests' => EditorialRequest::query()
                ->with(['aiProvider:id,name', 'aiPromptTemplate:id,name,type', 'location:id,name', 'newsCategory:id,name', 'language:id,code,name'])
                ->when($filters['search'] !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$filters['search'].'%'))
                ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
                ->when($filters['location_id'] !== '', fn (Builder $query) => $query->where('location_id', $filters['location_id']))
                ->when($filters['news_category_id'] !== '', fn (Builder $query) => $query->where('news_category_id', $filters['news_category_id']))
                ->when($filters['language_id'] !== '', fn (Builder $query) => $query->where('language_id', $filters['language_id']))
                ->when(! $filters['show_archived'] && ! $filters['only_archived'], fn (Builder $query) => $query->where('status', '!=', 'archived'))
                ->when($filters['only_archived'], fn (Builder $query) => $query->where('status', 'archived'))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => ['draft', 'ready', 'running', 'completed', 'failed', 'converted', 'archived'],
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/EditorialRequests/Create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['requested_by'] = $request->user()?->id;
        $data['status'] = $data['status'] ?? 'draft';

        $editorialRequest = EditorialRequest::query()->create($data);

        return to_route('editor.editorial-requests.show', $editorialRequest)->with('success', 'Editorial request created successfully.');
    }

    public function show(EditorialRequest $editorialRequest): Response
    {
        $editorialRequest->load([
            'requestedBy:id,name', 'aiProvider:id,name,provider_type,default_model', 'aiPromptTemplate:id,name,type,expected_output_format',
            'location:id,name', 'newsCategory:id,name', 'language:id,code,name,native_name,flag_emoji', 'edition:id,title',
            'candidates.suggestedCategory:id,name', 'candidates.suggestedLocation:id,name', 'candidates.newsItem:id,title',
        ]);

        return Inertia::render('Editor/EditorialRequests/Show', [
            'requestItem' => $editorialRequest,
        ]);
    }

    public function edit(EditorialRequest $editorialRequest): Response
    {
        return Inertia::render('Editor/EditorialRequests/Edit', ['requestItem' => $editorialRequest, ...$this->formOptions()]);
    }

    public function update(Request $request, EditorialRequest $editorialRequest): RedirectResponse
    {
        $data = $this->validated($request);
        $editorialRequest->update($data);

        return to_route('editor.editorial-requests.show', $editorialRequest)->with('success', 'Editorial request updated successfully.');
    }

    public function destroy(EditorialRequest $editorialRequest): RedirectResponse
    {
        $editorialRequest->delete();

        return to_route('editor.editorial-requests.index')->with('success', 'Editorial request deleted successfully.');
    }

    public function archive(EditorialRequest $editorialRequest): RedirectResponse
    {
        if ($editorialRequest->status !== 'archived') {
            $metadata = is_array($editorialRequest->metadata) ? $editorialRequest->metadata : [];
            $metadata['previous_status'] = $editorialRequest->status;
            $editorialRequest->update(['status' => 'archived', 'metadata' => $metadata]);
        }

        return back()->with('success', 'Editorial request archived successfully.');
    }

    public function restore(EditorialRequest $editorialRequest): RedirectResponse
    {
        $metadata = is_array($editorialRequest->metadata) ? $editorialRequest->metadata : [];
        $previousStatus = (string) ($metadata['previous_status'] ?? '');
        $restored = $previousStatus !== '' && $previousStatus !== 'archived' ? $previousStatus : 'draft';
        $editorialRequest->update(['status' => $restored]);

        return back()->with('success', 'Editorial request restored successfully.');
    }

    public function run(EditorialRequest $editorialRequest): RedirectResponse
    {
        if ($editorialRequest->status === 'running') {
            return back()->with('error', 'Request is already running.');
        }

        try {
            DB::transaction(function () use ($editorialRequest): void {
                $editorialRequest->load(['aiProvider', 'aiPromptTemplate', 'location', 'newsCategory', 'language', 'candidates']);

                if (! $editorialRequest->aiProvider || ! $editorialRequest->aiPromptTemplate) {
                    throw new \RuntimeException('AI provider and prompt template are required to run the request.');
                }

                $editorialRequest->update([
                    'status' => 'running',
                    'error_message' => null,
                    'requested_at' => now(),
                    'completed_at' => null,
                ]);

                $prompt = trim(implode("\n\n", array_filter([
                    $this->promptRenderer->render($editorialRequest->aiPromptTemplate->system_prompt, $editorialRequest),
                    $this->promptRenderer->render($editorialRequest->aiPromptTemplate->user_prompt, $editorialRequest),
                ])));

                $client = $this->aiClientManager->resolve($editorialRequest->aiProvider);
                $aiResponse = $client->generate($editorialRequest, $prompt);

                $parsed = null;
                if ($editorialRequest->aiPromptTemplate->expected_output_format === 'json') {
                    $parsed = $aiResponse->parsedJson ?? json_decode($aiResponse->content, true, 512, JSON_THROW_ON_ERROR);
                }

                $editorialRequest->candidates()->delete();

                $candidates = $parsed['candidate_news_items'] ?? [];
                foreach ($candidates as $index => $candidate) {
                    EditorialRequestCandidate::query()->create([
                        'editorial_request_id' => $editorialRequest->id,
                        'title' => (string) ($candidate['title'] ?? 'Untitled candidate'),
                        'summary' => $candidate['summary'] ?? null,
                        'source_hint' => $candidate['source_hint'] ?? null,
                        'source_url' => $candidate['source_url'] ?? null,
                        'suggested_category_id' => NewsCategory::query()->where('name', $candidate['suggested_category'] ?? '')->value('id'),
                        'suggested_location_id' => Location::query()->where('name', $candidate['suggested_location'] ?? '')->value('id'),
                        'relevance_score' => max(1, min(5, (int) ($candidate['relevance_score'] ?? 3))),
                        'editorial_angle' => $candidate['editorial_angle'] ?? null,
                        'why_it_matters' => $candidate['why_it_matters'] ?? null,
                        'is_selected' => true,
                        'sort_order' => $index,
                        'metadata' => ['created_from' => 'mock_ai'],
                    ]);
                }

                $editorialRequest->update([
                    'status' => 'completed',
                    'prompt_snapshot' => $prompt,
                    'response_snapshot' => $aiResponse->content,
                    'parsed_response' => $parsed,
                    'completed_at' => now(),
                    'metadata' => array_filter([
                        'input_tokens' => $aiResponse->inputTokens,
                        'output_tokens' => $aiResponse->outputTokens,
                        'cost_cents' => $aiResponse->costCents,
                    ]),
                ]);
            });
        } catch (Throwable $exception) {
            $editorialRequest->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return back()->with('error', 'Request failed.');
        }

        return back()->with('success', 'Request completed.');
    }

    public function createNewsItems(EditorialRequest $editorialRequest): RedirectResponse
    {
        $editorialRequest->load(['candidates', 'language', 'location', 'newsCategory']);
        $this->ensureNewsItemsForSelectedCandidates($editorialRequest);

        return back()->with('success', 'News items created from selected candidates.');
    }

    public function convertToEdition(EditorialRequest $editorialRequest): RedirectResponse
    {
        $editorialRequest->load(['candidates', 'language', 'location', 'newsCategory', 'edition']);

        if (! $editorialRequest->edition_id) {
            $edition = Edition::query()->create([
                'title' => $editorialRequest->title,
                'slug' => $this->uniqueSlug(Edition::class, $editorialRequest->title),
                'location_id' => $editorialRequest->location_id,
                'edition_type' => $editorialRequest->edition_type ?? 'special',
                'language' => $editorialRequest->language?->code,
                'status' => 'planning',
                'target_duration_seconds' => $editorialRequest->target_duration_seconds,
                'description' => $editorialRequest->editorial_instructions,
                'metadata' => ['created_from' => 'editorial_request', 'editorial_request_id' => $editorialRequest->id],
            ]);

            $editorialRequest->update(['edition_id' => $edition->id]);
            $editorialRequest->refresh();
        }

        $this->ensureNewsItemsForSelectedCandidates($editorialRequest);
        $editorialRequest->load('candidates');

        foreach ($editorialRequest->candidates->where('is_selected', true) as $candidate) {
            if (! $candidate->news_item_id) {
                continue;
            }

            DB::table('edition_news_item')->updateOrInsert([
                'edition_id' => $editorialRequest->edition_id,
                'news_item_id' => $candidate->news_item_id,
            ], [
                'sort_order' => $candidate->sort_order,
                'editorial_angle' => $candidate->editorial_angle,
                'included_in_script' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $editorialRequest->update(['status' => 'converted']);

        return to_route('editor.editions.show', $editorialRequest->edition_id)->with('success', 'Editorial request converted to edition.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:1'],
            'ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'ai_prompt_template_id' => ['nullable', 'exists:ai_prompt_templates,id'],
            'editorial_instructions' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function formOptions(): array
    {
        $defaultProviderId = AiProvider::query()->where('is_default', true)->value('id');

        return [
            'providers' => AiProvider::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'provider_type', 'default_model', 'is_default']),
            'defaultProviderId' => $defaultProviderId,
            'promptTemplates' => AiPromptTemplate::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type', 'expected_output_format']),
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'native_name', 'flag_emoji']),
            'locations' => Location::query()->with('defaultLanguage:id,code')->orderBy('name')->get(['id', 'name', 'default_language_id'])->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'default_language_code' => $location->defaultLanguage?->code,
            ]),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ['draft', 'ready', 'running', 'completed', 'failed', 'converted'],
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ];
    }

    private function ensureNewsItemsForSelectedCandidates(EditorialRequest $editorialRequest): void
    {
        foreach ($editorialRequest->candidates->where('is_selected', true) as $candidate) {
            if ($candidate->news_item_id) {
                continue;
            }

            $title = $candidate->title;
            $newsItem = NewsItem::query()->create([
                'title' => $title,
                'slug' => $this->uniqueSlug(NewsItem::class, $title),
                'summary' => $candidate->summary,
                'body' => trim(implode("\n\n", array_filter([$candidate->summary, $candidate->why_it_matters, $candidate->editorial_angle]))),
                'source_url' => $candidate->source_url,
                'news_category_id' => $candidate->suggested_category_id ?: $editorialRequest->news_category_id,
                'location_id' => $candidate->suggested_location_id ?: $editorialRequest->location_id,
                'language' => $editorialRequest->language?->code,
                'status' => 'collected',
                'editorial_priority' => max(1, min(5, (int) $candidate->relevance_score)),
                'collected_at' => now(),
                'metadata' => [
                    'created_from' => 'editorial_request',
                    'editorial_request_id' => $editorialRequest->id,
                    'candidate_id' => $candidate->id,
                    'source_hint' => $candidate->source_hint,
                ],
            ]);

            $candidate->update(['news_item_id' => $newsItem->id]);
        }
    }
}
