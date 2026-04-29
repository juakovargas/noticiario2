<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Script;
use App\Models\ScriptReviewItem;
use App\Models\SourceReference;
use App\Services\EditorialReview\ScriptReviewItemGenerator;
use App\Services\Messages\InternalMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScriptReviewController extends Controller
{
    public function __construct(private readonly ScriptReviewItemGenerator $generator, private readonly InternalMessageService $messageService)
    {
    }

    public function show(Script $script): Response
    {
        $script->load([
            'edition:id,title',
            'reviewItems.newsItem:id,title',
            'reviewItems.reviewedBy:id,name,email,profile_image_id',
            'reviewItems.sourceReferences:id,script_review_item_id,verification_status,title,source_name',
            'reviewedBy:id,name,email,profile_image_id',
            'approvedBy:id,name,email,profile_image_id',
            'rejectedBy:id,name,email,profile_image_id',
            'sourceReferences:id,script_id,verification_status',
        ]);

        return Inertia::render('Editor/Scripts/Review', [
            'script' => [
                'id' => $script->id,
                'title' => $script->title,
                'status' => $script->status,
                'review_status' => $script->review_status ?? 'pending',
                'review_notes' => $script->review_notes,
                'fact_check_notes' => $script->fact_check_notes,
                'rejection_reason' => $script->rejection_reason,
                'language' => $script->language,
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
                'final_title' => $script->final_title,
                'intro' => $script->intro,
                'body' => $script->body,
                'outro' => $script->outro,
                'edition' => $script->edition,
                'reviewed_at' => $script->reviewed_at?->toDateTimeString(),
                'approved_at' => $script->approved_at?->toDateTimeString(),
                'rejected_at' => $script->rejected_at?->toDateTimeString(),
                'reviewed_by' => $script->reviewedBy ? [
                    'id' => $script->reviewedBy->id,
                    'name' => $script->reviewedBy->name,
                    'email' => $script->reviewedBy->email,
                    'avatar_url' => $script->reviewedBy->avatar_url,
                    'initials' => $script->reviewedBy->initials,
                ] : null,
                'approved_by' => $script->approvedBy ? [
                    'id' => $script->approvedBy->id,
                    'name' => $script->approvedBy->name,
                    'email' => $script->approvedBy->email,
                    'avatar_url' => $script->approvedBy->avatar_url,
                    'initials' => $script->approvedBy->initials,
                ] : null,
                'rejected_by' => $script->rejectedBy ? [
                    'id' => $script->rejectedBy->id,
                    'name' => $script->rejectedBy->name,
                    'email' => $script->rejectedBy->email,
                    'avatar_url' => $script->rejectedBy->avatar_url,
                    'initials' => $script->rejectedBy->initials,
                ] : null,
                'review_items' => $script->reviewItems->map(fn (ScriptReviewItem $item) => [
                    'id' => $item->id,
                    'sort_order' => $item->sort_order,
                    'type' => $item->type,
                    'title' => $item->title,
                    'content' => $item->content,
                    'source_hints' => $item->source_hints ?? [],
                    'verification_status' => $item->verification_status,
                    'verification_notes' => $item->verification_notes,
                    'required_action' => $item->required_action,
                    'reviewed_at' => $item->reviewed_at?->toDateTimeString(),
                    'reviewed_by' => $item->reviewedBy ? [
                        'id' => $item->reviewedBy->id,
                        'name' => $item->reviewedBy->name,
                        'email' => $item->reviewedBy->email,
                        'avatar_url' => $item->reviewedBy->avatar_url,
                        'initials' => $item->reviewedBy->initials,
                    ] : null,
                    'news_item' => $item->newsItem ? ['id' => $item->newsItem->id, 'title' => $item->newsItem->title] : null,
                    'metadata' => $item->metadata,
                    'source_references' => $item->sourceReferences->map(fn (SourceReference $reference) => [
                        'id' => $reference->id,
                        'title' => $reference->title,
                        'source_name' => $reference->source_name,
                        'verification_status' => $reference->verification_status,
                    ])->values(),
                ])->values(),
                'source_summary' => [
                    'total' => $script->sourceReferences->count(),
                    'missing' => $script->sourceReferences->whereIn('verification_status', ['missing', 'broken', 'rejected'])->count(),
                ],
            ],
            'reviewStatuses' => ['pending', 'in_review', 'needs_sources', 'needs_changes', 'verified', 'approved', 'rejected'],
            'verificationStatuses' => ['pending', 'verified', 'needs_source', 'needs_changes', 'rejected', 'not_applicable'],
            'requiredActions' => ['none', 'add_source', 'rewrite', 'remove', 'check_date', 'check_claim'],
        ]);
    }

    public function generateItems(Script $script): RedirectResponse
    {
        $this->generator->generateForScript($script);

        return back()->with('success', 'Review items generated successfully.');
    }

    public function update(Request $request, Script $script): RedirectResponse
    {
        $data = $request->validate([
            'review_notes' => ['nullable', 'string'],
            'fact_check_notes' => ['nullable', 'string'],
            'review_status' => ['nullable', Rule::in(['pending', 'in_review', 'needs_sources', 'needs_changes', 'verified', 'approved', 'rejected'])],
        ]);

        $script->update($data);

        return back()->with('success', 'Review notes saved.');
    }

    public function updateItem(Request $request, Script $script, ScriptReviewItem $scriptReviewItem): RedirectResponse
    {
        if ($scriptReviewItem->script_id !== $script->id) {
            abort(404);
        }

        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['pending', 'verified', 'needs_source', 'needs_changes', 'rejected', 'not_applicable'])],
            'verification_notes' => ['nullable', 'string'],
            'required_action' => ['nullable', Rule::in(['none', 'add_source', 'rewrite', 'remove', 'check_date', 'check_claim'])],
        ]);

        if (($scriptReviewItem->verification_status ?? 'pending') !== $data['verification_status'] && $data['verification_status'] !== 'pending') {
            $data['reviewed_by'] = $request->user()->id;
            $data['reviewed_at'] = now();
        }

        $scriptReviewItem->update($data);

        return back()->with('success', 'Review item saved.');
    }

    public function markInReview(Request $request, Script $script): RedirectResponse
    {
        $script->update([
            'review_status' => 'in_review',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Script moved to in review.');
    }

    public function markVerified(Request $request, Script $script): RedirectResponse
    {
        $hasBlockingItems = $script->reviewItems()
            ->whereIn('verification_status', ['needs_source', 'needs_changes', 'rejected'])
            ->exists();

        if ($hasBlockingItems) {
            return back()->with('error', 'This script cannot be approved until review issues are resolved.');
        }

        $script->update([
            'review_status' => 'verified',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Script marked as verified.');
    }

    public function approve(Request $request, Script $script): RedirectResponse
    {
        $canApproveByItems = ! $script->reviewItems()
            ->whereNotIn('verification_status', ['verified', 'not_applicable'])
            ->exists();

        $canApproveByStatus = $script->review_status === 'verified';
        $hasBlockingSources = $script->sourceReferences()
            ->whereIn('verification_status', ['missing', 'broken', 'rejected'])
            ->exists();

        if ($hasBlockingSources || (! $canApproveByStatus && ! $canApproveByItems)) {
            return back()->with('error', 'This script cannot be approved until review issues are resolved.');
        }

        $attributes = [
            'review_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ];

        if (in_array('status', $script->getFillable(), true)) {
            $attributes['status'] = 'approved';
        }

        $script->update($attributes);

        return back()->with('success', 'Script approved.');
    }

    public function reject(Request $request, Script $script): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $attributes = [
            'review_status' => 'rejected',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ];

        if (in_array('status', $script->getFillable(), true)) {
            $attributes['status'] = 'rejected';
        }

        $script->update($attributes);

        $this->messageService->sendScriptRejected($script->fresh(['bulletinPromptRun.createdBy.manager']), $request->user(), $data['rejection_reason']);

        return back()->with('success', 'Script rejected.');
    }
}
