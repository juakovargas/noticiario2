<?php

namespace Tests\Feature;

use App\Models\BulletinPromptRun;
use App\Models\NewsItem;
use App\Models\Script;
use App\Models\ScriptReviewItem;
use App\Models\SourceReference;
use App\Services\EditorialReview\SourceReferenceExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class SourceReferenceWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_source_references_index(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)->get(route('editor.source-references.index'))->assertOk();
    }

    public function test_viewer_cannot_access_source_references_routes(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $reference = SourceReference::factory()->create();

        $this->actingAs($viewer)->get(route('editor.source-references.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.source-references.show', $reference))->assertForbidden();
    }

    public function test_editor_can_update_source_reference_and_set_checked_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $reference = SourceReference::factory()->create(['verification_status' => 'pending']);

        $this->actingAs($editor)->put(route('editor.source-references.update', $reference), [
            'source_type' => 'official',
            'verification_status' => 'verified',
            'trust_level' => 8,
        ])->assertRedirect();

        $reference->refresh();
        $this->assertSame('verified', $reference->verification_status);
        $this->assertSame(8, $reference->trust_level);
        $this->assertNotNull($reference->checked_by);
        $this->assertNotNull($reference->checked_at);
    }

    public function test_invalid_fields_are_rejected(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $reference = SourceReference::factory()->create();

        $this->actingAs($editor)
            ->from(route('editor.source-references.show', $reference))
            ->put(route('editor.source-references.update', $reference), [
                'source_type' => 'invalid',
                'verification_status' => 'invalid',
                'trust_level' => 11,
            ])
            ->assertRedirect(route('editor.source-references.show', $reference))
            ->assertSessionHasErrors(['source_type', 'verification_status', 'trust_level']);
    }

    public function test_archive_filters_and_restore_work(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $active = SourceReference::factory()->create(['title' => 'Active source']);
        $archived = SourceReference::factory()->create(['title' => 'Archived source', 'archived_at' => now()]);

        $this->actingAs($editor)->get(route('editor.source-references.index'))
            ->assertInertia(fn ($page) => $page->where('sourceReferences.data.0.title', 'Active source'));

        $this->actingAs($editor)->get(route('editor.source-references.index', ['show_archived' => 1]))
            ->assertInertia(fn ($page) => $page->has('sourceReferences.data', 2));

        $this->actingAs($editor)->get(route('editor.source-references.index', ['only_archived' => 1]))
            ->assertInertia(fn ($page) => $page->where('sourceReferences.data.0.title', 'Archived source'));

        $this->actingAs($editor)->post(route('editor.source-references.archive', $active))->assertRedirect();
        $this->assertDatabaseHas('source_references', ['id' => $active->id, 'title' => 'Active source']);
        $this->assertNotNull($active->fresh()->archived_at);

        $this->actingAs($editor)->post(route('editor.source-references.restore', $active))->assertRedirect();
        $this->assertNull($active->fresh()->archived_at);
        $this->assertDatabaseHas('source_references', ['id' => $active->id, 'deleted_at' => null]);

        $this->assertNotNull($archived);
    }

    public function test_extractor_from_script_and_parsed_response_deduplicates(): void
    {
        $script = Script::factory()->create([
            'body' => "SOURCE HINTS:\nhttps://example.com/alpha\nDesk notes",
            'metadata' => ['source_hints' => ['Reuters https://example.com/alpha', 'BBC https://example.com/beta']],
        ]);

        ScriptReviewItem::query()->create([
            'script_id' => $script->id,
            'title' => 'Claim block',
            'content' => 'Sources: https://example.com/gamma',
            'source_hints' => ['https://example.com/gamma'],
            'verification_status' => 'pending',
        ]);

        $extractor = app(SourceReferenceExtractor::class);
        $extractor->extractFromScript($script);
        $first = SourceReference::query()->count();
        $extractor->extractFromScript($script);

        $this->assertSame($first, SourceReference::query()->count());
        $this->assertDatabaseHas('source_references', ['script_id' => $script->id, 'source_url' => 'https://example.com/alpha']);
        $this->assertDatabaseHas('source_references', ['script_id' => $script->id, 'source_url' => 'https://example.com/beta']);

        $extractor->extractFromParsedResponse(['items' => [['source_hints' => ['https://example.com/beta', 'Local desk source']]]], null, $script);
        $this->assertGreaterThanOrEqual($first, SourceReference::query()->count());
    }

    public function test_editor_can_extract_sources_from_context_routes(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $run = BulletinPromptRun::factory()->create([
            'ai_response_text' => "SOURCES:\nhttps://example.com/run-source",
            'parsed_response' => ['items' => [['source_hints' => ['https://example.com/run-hint']]]],
        ]);
        $script = Script::factory()->create(['body' => 'Reference https://example.com/script-source']);
        $newsItem = NewsItem::factory()->create(['source_url' => 'https://example.com/news-source']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.source-references.extract', $run))->assertRedirect();
        $this->actingAs($editor)->post(route('editor.scripts.source-references.extract', $script))->assertRedirect();
        $this->actingAs($editor)->post(route('editor.news-items.source-references.extract', $newsItem))->assertRedirect();

        $this->assertDatabaseHas('source_references', ['bulletin_prompt_run_id' => $run->id]);
        $this->assertDatabaseHas('source_references', ['script_id' => $script->id]);
        $this->assertDatabaseHas('source_references', ['news_item_id' => $newsItem->id]);
    }
}
