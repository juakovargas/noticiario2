<?php

namespace Tests\Feature;

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

    public function test_user_without_editor_access_cannot_access_source_reference_routes(): void
    {
        $user = $this->createUserWithPermissions([]);
        $reference = SourceReference::factory()->create();

        $this->actingAs($user)->put(route('editor.source-references.update', $reference), [
            'source_type' => 'web',
            'verification_status' => 'pending',
        ])->assertForbidden();
    }

    public function test_editor_can_update_verification_status_and_sets_checked_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $reference = SourceReference::factory()->create(['verification_status' => 'pending']);

        $this->actingAs($editor)->put(route('editor.source-references.update', $reference), [
            'title' => 'Updated',
            'source_name' => 'Official Board',
            'source_url' => 'https://example.com/source',
            'source_type' => 'official',
            'verification_status' => 'verified',
            'trust_level' => 88,
            'notes' => 'Looks good',
        ])->assertRedirect();

        $reference->refresh();
        $this->assertSame('verified', $reference->verification_status);
        $this->assertNotNull($reference->checked_by);
        $this->assertNotNull($reference->checked_at);
        $this->assertSame(88, $reference->trust_level);
        $this->assertSame('Looks good', $reference->notes);
    }

    public function test_invalid_verification_status_and_source_type_are_rejected(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $reference = SourceReference::factory()->create();

        $this->actingAs($editor)
            ->from(route('editor.source-references.show', $reference))
            ->put(route('editor.source-references.update', $reference), [
                'source_type' => 'invalid',
                'verification_status' => 'bad_status',
            ])
            ->assertRedirect(route('editor.source-references.show', $reference))
            ->assertSessionHasErrors(['source_type', 'verification_status']);
    }

    public function test_extractor_extracts_urls_and_source_hints_without_duplicates(): void
    {
        $script = Script::factory()->create([
            'body' => "Check details at https://example.com/alpha and mirror https://example.com/beta",
            'metadata' => [
                'source_hints' => ['https://example.com/alpha', 'Official bulletin'],
            ],
        ]);

        $item = ScriptReviewItem::query()->create([
            'script_id' => $script->id,
            'title' => 'Claim block',
            'content' => 'Sources: https://example.com/gamma',
            'source_hints' => ['Manual desk note', 'https://example.com/gamma'],
            'verification_status' => 'pending',
        ]);

        $extractor = app(SourceReferenceExtractor::class);
        $extractor->extractFromScript($script);
        $firstCount = SourceReference::query()->count();

        $extractor->extractFromScript($script);
        $secondCount = SourceReference::query()->count();

        $this->assertGreaterThanOrEqual(3, $firstCount);
        $this->assertSame($firstCount, $secondCount);
        $this->assertDatabaseHas('source_references', ['script_id' => $script->id, 'source_url' => 'https://example.com/alpha']);
        $this->assertDatabaseHas('source_references', ['script_review_item_id' => $item->id, 'source_url' => 'https://example.com/gamma']);
    }

    public function test_editor_can_extract_sources_from_script_and_review_item_routes(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['body' => 'Reference: https://example.com/story']);
        $item = ScriptReviewItem::query()->create([
            'script_id' => $script->id,
            'title' => 'Review item',
            'source_hints' => ['https://example.com/item-hint'],
            'verification_status' => 'pending',
        ]);

        $this->actingAs($editor)->post(route('editor.scripts.source-references.extract', $script))->assertRedirect();
        $this->actingAs($editor)->post(route('editor.script-review-items.source-references.extract', $item))->assertRedirect();

        $this->assertDatabaseHas('source_references', ['script_id' => $script->id, 'source_url' => 'https://example.com/story']);
        $this->assertDatabaseHas('source_references', ['script_review_item_id' => $item->id, 'source_url' => 'https://example.com/item-hint']);
    }

    public function test_editor_can_create_source_reference_from_news_item_source_url(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $newsItem = NewsItem::factory()->create(['source_url' => 'https://example.com/news']);

        $this->actingAs($editor)->post(route('editor.news-items.source-references.store', $newsItem))->assertRedirect();

        $this->assertDatabaseHas('source_references', [
            'news_item_id' => $newsItem->id,
            'source_url' => 'https://example.com/news',
        ]);
    }

    public function test_script_with_blocking_sources_cannot_be_approved(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'verified']);
        SourceReference::factory()->create([
            'script_id' => $script->id,
            'verification_status' => 'rejected',
            'source_url' => 'https://example.com/rejected',
        ]);

        $this->actingAs($editor)->post(route('editor.scripts.review.approve', $script))->assertRedirect();

        $this->assertDatabaseMissing('scripts', ['id' => $script->id, 'review_status' => 'approved']);
    }

    public function test_demo_source_references_seed_without_duplicates(): void
    {
        $this->seed([\Database\Seeders\RolesAndPermissionsSeeder::class, \Database\Seeders\DemoEditorialSeeder::class]);

        $this->assertDatabaseHas('source_references', ['verification_status' => 'verified']);
        $this->assertDatabaseHas('source_references', ['verification_status' => 'pending']);
        $this->assertDatabaseHas('source_references', ['verification_status' => 'weak']);
        $this->assertDatabaseHas('source_references', ['verification_status' => 'missing']);
        $this->assertDatabaseHas('source_references', ['verification_status' => 'rejected']);

        $count = SourceReference::query()->count();
        $this->seed([\Database\Seeders\RolesAndPermissionsSeeder::class, \Database\Seeders\DemoEditorialSeeder::class]);

        $this->assertSame($count, SourceReference::query()->count());
    }
}
