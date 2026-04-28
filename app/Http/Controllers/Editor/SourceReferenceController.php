<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinPromptRun;
use App\Models\NewsItem;
use App\Models\Script;
use App\Models\SourceReference;
use App\Models\User;
use App\Services\EditorialReview\SourceReferenceExtractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SourceReferenceController extends Controller
{
    public function __construct(private readonly SourceReferenceExtractor $extractor)
    {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'verification_status' => (string) $request->query('verification_status', ''),
            'source_type' => (string) $request->query('source_type', ''),
            'trust_level' => (string) $request->query('trust_level', ''),
            'checked_by' => (string) $request->query('checked_by', ''),
            'has_url' => (string) $request->query('has_url', ''),
            'script_id' => (string) $request->query('script_id', ''),
            'news_item_id' => (string) $request->query('news_item_id', ''),
            'bulletin_prompt_run_id' => (string) $request->query('bulletin_prompt_run_id', ''),
            'show_archived' => $request->boolean('show_archived'),
            'only_archived' => $request->boolean('only_archived'),
        ];

        $sourceReferences = SourceReference::query()
            ->with([
                'checkedBy.profileImage:id,public_path,disk,path,visibility,mime_type,size_bytes',
                'script:id,title',
                'newsItem:id,title',
                'bulletinPromptRun:id,title',
                'edition:id,title',
                'scriptReviewItem:id,title',
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn (Builder $q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('source_name', 'like', "%{$search}%")
                    ->orWhere('source_url', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%"));
            })
            ->when($filters['verification_status'] !== '', fn (Builder $query) => $query->where('verification_status', $filters['verification_status']))
            ->when($filters['source_type'] !== '', fn (Builder $query) => $query->where('source_type', $filters['source_type']))
            ->when($filters['trust_level'] !== '', fn (Builder $query) => $query->where('trust_level', $filters['trust_level']))
            ->when($filters['checked_by'] !== '', fn (Builder $query) => $query->where('checked_by', $filters['checked_by']))
            ->when($filters['has_url'] === 'yes', fn (Builder $query) => $query->whereNotNull('source_url')->where('source_url', '!=', ''))
            ->when($filters['has_url'] === 'no', fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('source_url')->orWhere('source_url', '')))
            ->when($filters['script_id'] !== '', fn (Builder $query) => $query->where('script_id', $filters['script_id']))
            ->when($filters['news_item_id'] !== '', fn (Builder $query) => $query->where('news_item_id', $filters['news_item_id']))
            ->when($filters['bulletin_prompt_run_id'] !== '', fn (Builder $query) => $query->where('bulletin_prompt_run_id', $filters['bulletin_prompt_run_id']))
            ->when(! $filters['show_archived'] && ! $filters['only_archived'], fn (Builder $query) => $query->withoutArchived())
            ->when($filters['only_archived'], fn (Builder $query) => $query->onlyArchived())
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SourceReference $reference) => $this->sourcePayload($reference));

        return Inertia::render('Editor/SourceReferences/Index', [
            'sourceReferences' => $sourceReferences,
            'filters' => array_map(fn ($value) => is_bool($value) ? ($value ? '1' : '') : $value, $filters),
            'verificationStatuses' => SourceReference::VERIFICATION_STATUSES,
            'sourceTypes' => SourceReference::SOURCE_TYPES,
            'checkedByUsers' => User::query()->whereHas('permissions', fn (Builder $query) => $query->where('name', 'editor.access'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(SourceReference $sourceReference): Response
    {
        $sourceReference->load(['script:id,title', 'newsItem:id,title', 'bulletinPromptRun:id,title', 'edition:id,title', 'scriptReviewItem:id,title', 'checkedBy:id,name,email,profile_image_id']);

        return Inertia::render('Editor/SourceReferences/Show', [
            'sourceReference' => $this->sourcePayload($sourceReference),
            'verificationStatuses' => SourceReference::VERIFICATION_STATUSES,
            'sourceTypes' => SourceReference::SOURCE_TYPES,
        ]);
    }

    public function update(Request $request, SourceReference $sourceReference): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'max:2000'],
            'source_type' => ['required', Rule::in(SourceReference::SOURCE_TYPES)],
            'verification_status' => ['required', Rule::in(SourceReference::VERIFICATION_STATUSES)],
            'trust_level' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($sourceReference->verification_status !== $data['verification_status']) && $data['verification_status'] !== 'pending') {
            $data['checked_by'] = $request->user()->id;
            $data['checked_at'] = now();
        }

        $sourceReference->update($data);

        return back()->with('success', 'Source reference saved');
    }

    public function archive(Request $request, SourceReference $sourceReference): RedirectResponse
    {
        $sourceReference->update(['archived_at' => now(), 'archived_by' => $request->user()?->id]);

        return back()->with('success', 'Source reference archived.');
    }

    public function restore(SourceReference $sourceReference): RedirectResponse
    {
        $sourceReference->update(['archived_at' => null, 'archived_by' => null]);

        return back()->with('success', 'Source reference restored.');
    }

    public function extractFromBulletinPromptRun(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $created = $this->extractor->extractFromBulletinPromptRun($bulletinPromptRun);

        return back()->with('success', $created->isEmpty() ? 'No source references found' : 'Source references extracted');
    }

    public function extractFromScript(Script $script): RedirectResponse
    {
        $created = $this->extractor->extractFromScript($script);

        return back()->with('success', $created->isEmpty() ? 'No source references found' : 'Source references extracted');
    }

    public function extractFromScriptReviewItem(\App\Models\ScriptReviewItem $scriptReviewItem): RedirectResponse
    {
        $created = $this->extractor->extractFromReviewItem($scriptReviewItem);

        return back()->with('success', $created->isEmpty() ? 'No source references found' : 'Source references extracted');
    }

    public function extractFromNewsItem(NewsItem $newsItem): RedirectResponse
    {
        $created = $this->extractor->extractFromNewsItem($newsItem);

        return back()->with('success', $created->isEmpty() ? 'No source references found' : 'Source references extracted');
    }

    private function sourcePayload(SourceReference $reference): array
    {
        return [
            'id' => $reference->id,
            'title' => $reference->title,
            'source_name' => $reference->source_name,
            'source_url' => $reference->source_url,
            'source_type' => $reference->source_type,
            'verification_status' => $reference->verification_status,
            'trust_level' => $reference->trust_level,
            'checked_at' => $reference->checked_at?->toDateTimeString(),
            'checked_by' => $reference->checkedBy ? ['id' => $reference->checkedBy->id, 'name' => $reference->checkedBy->name, 'email' => $reference->checkedBy->email, 'avatar_url' => $reference->checkedBy->avatar_url, 'initials' => $reference->checkedBy->initials] : null,
            'notes' => $reference->notes,
            'script' => $reference->script ? ['id' => $reference->script->id, 'title' => $reference->script->title] : null,
            'news_item' => $reference->newsItem ? ['id' => $reference->newsItem->id, 'title' => $reference->newsItem->title] : null,
            'bulletin_prompt_run' => $reference->bulletinPromptRun ? ['id' => $reference->bulletinPromptRun->id, 'title' => $reference->bulletinPromptRun->title] : null,
            'edition' => $reference->edition ? ['id' => $reference->edition->id, 'title' => $reference->edition->title] : null,
            'script_review_item' => $reference->scriptReviewItem ? ['id' => $reference->scriptReviewItem->id, 'title' => $reference->scriptReviewItem->title] : null,
            'archived_at' => $reference->archived_at?->toDateTimeString(),
        ];
    }
}
