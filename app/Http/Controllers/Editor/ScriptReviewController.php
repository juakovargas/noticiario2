<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Script;
use App\Models\ScriptReviewItem;
use App\Services\EditorialReview\ScriptReviewItemGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScriptReviewController extends Controller
{
    public function __construct(private readonly ScriptReviewItemGenerator $generator)
    {
    }

    public function show(Script $script): Response
    {
        $script->load([
            'edition:id,title',
            'reviewItems.newsItem:id,title',
            'reviewItems.reviewedBy:id,name',
            'reviewedBy:id,name',
            'approvedBy:id,name',
            'rejectedBy:id,name',
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
                'edition' => $script->edition,
                'reviewed_at' => $script->reviewed_at?->toDateTimeString(),
                'approved_at' => $script->approved_at?->toDateTimeString(),
                'rejected_at' => $script->rejected_at?->toDateTimeString(),
                'reviewed_by' => $script->reviewedBy?->name,
                'approved_by' => $script->approvedBy?->name,
                'rejected_by' => $script->rejectedBy?->name,
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
                    'reviewed_by' => $item->reviewedBy?->name,
                    'news_item' => $item->newsItem ? ['id' => $item->newsItem->id, 'title' => $item->newsItem->title] : null,
                    'metadata' => $item->metadata,
                ])->values(),
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

        if (! $canApproveByStatus && ! $canApproveByItems) {
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

        return back()->with('success', 'Script rejected.');
    }
}
