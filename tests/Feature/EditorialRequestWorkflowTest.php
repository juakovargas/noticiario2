<?php

namespace Tests\Feature;

use App\Models\AiPromptTemplate;
use App\Models\AiProvider;
use App\Models\EditorialRequest;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialRequestWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_create_editorial_request(): void
    {
        [$editor, $provider, $template, $location, $category, $language] = $this->baseData();

        $this->actingAs($editor)->post(route('editor.editorial-requests.store'), [
            'title' => 'Demo request',
            'ai_provider_id' => $provider->id,
            'ai_prompt_template_id' => $template->id,
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('editorial_requests', ['title' => 'Demo request']);
    }

    public function test_viewer_cannot_create_editorial_request(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->post(route('editor.editorial-requests.store'), ['title' => 'x'])->assertForbidden();
    }

    public function test_editor_can_run_editorial_request_with_mock_provider_and_create_candidates(): void
    {
        [$editor, $provider, $template, $location, $category, $language] = $this->baseData();

        $request = EditorialRequest::query()->create([
            'requested_by' => $editor->id,
            'title' => 'Run me',
            'ai_provider_id' => $provider->id,
            'ai_prompt_template_id' => $template->id,
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'status' => 'draft',
        ]);

        $this->actingAs($editor)->post(route('editor.editorial-requests.run', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('completed', $request->status);
        $this->assertNotNull($request->prompt_snapshot);
        $this->assertNotNull($request->response_snapshot);
        $this->assertDatabaseCount('editorial_request_candidates', 2);
    }

    public function test_failed_provider_stores_failed_status_and_error(): void
    {
        [$editor,, $template, $location, $category, $language] = $this->baseData();

        $provider = AiProvider::query()->create(['name' => 'Custom', 'slug' => 'custom', 'provider_type' => 'custom']);

        $request = EditorialRequest::query()->create([
            'requested_by' => $editor->id,
            'title' => 'Fail me',
            'ai_provider_id' => $provider->id,
            'ai_prompt_template_id' => $template->id,
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'status' => 'draft',
        ]);

        $this->actingAs($editor)->post(route('editor.editorial-requests.run', $request))->assertRedirect();

        $this->assertDatabaseHas('editorial_requests', ['id' => $request->id, 'status' => 'failed']);
    }

    public function test_editor_can_create_news_items_and_convert_to_edition(): void
    {
        [$editor, $provider, $template, $location, $category, $language] = $this->baseData();

        $request = EditorialRequest::query()->create([
            'requested_by' => $editor->id,
            'title' => 'Convert me',
            'ai_provider_id' => $provider->id,
            'ai_prompt_template_id' => $template->id,
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'status' => 'draft',
        ]);

        $this->actingAs($editor)->post(route('editor.editorial-requests.run', $request));
        $this->actingAs($editor)->post(route('editor.editorial-requests.create-news-items', $request))->assertRedirect();
        $this->actingAs($editor)->post(route('editor.editorial-requests.convert-to-edition', $request))->assertRedirect();

        $request->refresh();
        $this->assertNotNull($request->edition_id);
        $this->assertSame('converted', $request->status);
        $this->assertDatabaseHas('edition_news_item', ['edition_id' => $request->edition_id]);
    }

    public function test_user_without_editor_access_cannot_access_request_routes(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('editor.editorial-requests.index'))->assertForbidden();
    }

    private function baseData(): array
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $language = Language::factory()->create(['code' => 'es']);
        $location = Location::factory()->create(['default_language_id' => $language->id]);
        $category = NewsCategory::factory()->create(['name' => 'General', 'slug' => 'general']);
        $provider = AiProvider::query()->create(['name' => 'Mock AI Provider', 'slug' => 'mock-ai-provider', 'provider_type' => 'mock', 'is_default' => true, 'is_active' => true]);
        $template = AiPromptTemplate::query()->create(['name' => 'General Editorial Research', 'slug' => 'general-editorial-research', 'type' => 'editorial_research', 'user_prompt' => 'x', 'expected_output_format' => 'json']);

        return [$editor, $provider, $template, $location, $category, $language];
    }
}
