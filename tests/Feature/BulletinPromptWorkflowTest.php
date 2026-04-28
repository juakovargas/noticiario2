<?php

namespace Tests\Feature;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Models\Script;
use Database\Seeders\DemoEditorialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class BulletinPromptWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    #[Test]
    public function editor_can_access_bulletin_types_index_and_viewer_cannot(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($editor)->get(route('editor.bulletin-types.index'))->assertOk();
        $this->actingAs($viewer)->get(route('editor.bulletin-types.index'))->assertForbidden();
    }

    #[Test]
    public function editor_can_create_bulletin_type_with_relations_and_slug_generation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $language = Language::factory()->create(['code' => 'es']);
        $profile = PromptProfile::query()->create(['name' => 'Default', 'slug' => 'default', 'is_active' => true]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.store'), [
            'name' => 'Spain Morning News',
            'slug' => '',
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'default_prompt_profile_id' => $profile->id,
            'edition_type' => 'morning',
            'target_duration_seconds' => 90,
            'default_schedule_time' => '08:00',
            'default_timezone' => 'Europe/Madrid',
        ])->assertRedirect();

        $type = BulletinType::query()->where('name', 'Spain Morning News')->firstOrFail();
        $this->assertNotEmpty($type->slug);
        $this->assertSame($location->id, $type->location_id);
        $this->assertSame($category->id, $type->news_category_id);
        $this->assertSame($language->id, $type->language_id);
        $this->assertSame($profile->id, $type->default_prompt_profile_id);
    }

    #[Test]
    public function editor_can_run_prompt_workflow_and_viewer_cannot_access_routes(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $location = Location::factory()->create(['name' => 'Spain']);
        $category = NewsCategory::factory()->create(['name' => 'General']);
        $language = Language::factory()->create(['name' => 'Spanish', 'code' => 'es']);
        $profile = PromptProfile::query()->create(['name' => 'Balanced News', 'slug' => 'balanced-news', 'happiness_level' => 5, 'optimism_level' => 5, 'humor_level' => 1, 'irony_level' => 0, 'is_active' => true]);
        $type = BulletinType::query()->create(['name' => 'Spain Morning General News', 'slug' => 'spain-morning-general-news', 'location_id' => $location->id, 'news_category_id' => $category->id, 'language_id' => $language->id, 'default_prompt_profile_id' => $profile->id, 'edition_type' => 'morning', 'target_duration_seconds' => 90, 'default_timezone' => 'Europe/Madrid', 'is_active' => true]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $type))->assertRedirect();
        $run = BulletinPromptRun::query()->firstOrFail();
        $this->assertNotNull($run->scheduled_for);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-prompt', $run))->assertRedirect();
        $run->refresh();
        $this->assertStringContainsString('Spain', $run->generated_prompt ?? '');
        $this->assertStringContainsString('General', $run->generated_prompt ?? '');
        $this->assertStringContainsString('Spanish', $run->generated_prompt ?? '');
        $this->assertStringContainsString('90', $run->generated_prompt ?? '');
        $this->assertStringContainsString('happiness level', strtolower($run->generated_prompt ?? ''));
        $this->assertStringContainsString('optimism level', strtolower($run->generated_prompt ?? ''));
        $this->assertStringContainsString('humor level', strtolower($run->generated_prompt ?? ''));
        $this->assertStringContainsString('irony level', strtolower($run->generated_prompt ?? ''));
        $this->assertStringContainsString('No inventes', $run->generated_prompt ?? '');
        $this->assertStringContainsString('SOURCE HINTS', $run->generated_prompt ?? '');
        $this->assertStringContainsString('Fecha de emisión', $run->generated_prompt ?? '');

        $responseText = "TITLE:\nDemo\n\nINTRO:\nIntro\n\nNEWS ITEMS:\n1. HEADLINE:\nHeadline\n\nSUMMARY:\nSummary\n\nSCRIPT:\nScript\n\nEDITORIAL ANGLE:\nAngle\n\nSOURCE HINTS:\n- https://example.com\n\nOUTRO:\nOutro";

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => $responseText])->assertRedirect();
        $run->refresh();
        $this->assertSame('response_received', $run->status);
        $this->assertSame('Demo', data_get($run->parsed_response, 'title'));
        $this->assertSame('Intro', data_get($run->parsed_response, 'intro'));
        $this->assertSame('Outro', data_get($run->parsed_response, 'outro'));
        $this->assertSame('Script', data_get($run->parsed_response, 'items.0.script'));

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertRedirect();
        $run->refresh();
        $this->assertNotNull($run->script_id);
        $this->assertSame('script_created', $run->status);

        $script = Script::query()->findOrFail($run->script_id);
        $this->assertSame($run->id, data_get($script->metadata, 'bulletin_prompt_run_id'));
        $this->assertTrue((bool) data_get($script->metadata, 'parsed_response_used'));
        $this->assertStringContainsString('Script', $script->body ?? '');
        $this->assertStringNotContainsString('Summary', $script->body ?? '');

        $this->actingAs($viewer)->get(route('editor.bulletin-prompt-runs.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.bulletin-types.prompt-runs.store', $type))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.generate-prompt', $run))->assertForbidden();
    }


    #[Test]
    public function prompt_run_creation_uses_default_schedule_and_falls_back_to_now(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $typeWithDefault = BulletinType::query()->create([
            'name' => 'With Default',
            'slug' => 'with-default',
            'default_schedule_time' => '08:00',
            'default_timezone' => 'Europe/Madrid',
        ]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $typeWithDefault))->assertRedirect();
        $this->assertSame('08:00', BulletinPromptRun::query()->latest('id')->firstOrFail()->scheduled_for?->timezone('Europe/Madrid')->format('H:i'));

        $typeWithoutDefault = BulletinType::query()->create([
            'name' => 'No Default',
            'slug' => 'no-default',
        ]);

        $before = now()->subMinute();
        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $typeWithoutDefault))->assertRedirect();
        $after = now()->addMinute();

        $latest = BulletinPromptRun::query()->latest('id')->firstOrFail();
        $this->assertTrue($latest->scheduled_for?->betweenIncluded($before, $after));
    }



    #[Test]
    public function unstructured_response_uses_full_response_as_script_body_fallback(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $type = BulletinType::query()->create([
            'name' => 'Fallback Type',
            'slug' => 'fallback-type',
            'default_timezone' => 'UTC',
            'prompt_language' => 'en',
        ]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $type))->assertRedirect();
        $run = BulletinPromptRun::query()->latest('id')->firstOrFail();

        $text = 'This is an unstructured AI response block without headings.';

        $this->actingAs($editor)
            ->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => $text])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame($text, data_get($run->parsed_response, 'body'));

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertRedirect();

        $script = Script::query()->findOrFail($run->script_id);
        $this->assertSame($text, $script->body);
        $this->assertFalse((bool) data_get($script->metadata, 'parsed_response_used'));
    }

    #[Test]
    public function existing_script_is_not_overwritten_when_creating_again_or_updating_response(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $type = BulletinType::query()->create([
            'name' => 'No Overwrite Type',
            'slug' => 'no-overwrite-type',
            'default_timezone' => 'UTC',
            'prompt_language' => 'en',
        ]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $type))->assertRedirect();
        $run = BulletinPromptRun::query()->latest('id')->firstOrFail();

        $firstResponse = "TITLE:
Demo

INTRO:
Intro

NEWS ITEMS:
HEADLINE:
One
SUMMARY:
S
SCRIPT:
First script body

OUTRO:
O";
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => $firstResponse])->assertRedirect();
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertRedirect();

        $run->refresh();
        $script = Script::query()->findOrFail($run->script_id);
        $this->assertStringContainsString('First script body', (string) $script->body);

        $secondResponse = "TITLE:
Demo

INTRO:
Intro

NEWS ITEMS:
HEADLINE:
Two
SUMMARY:
S
SCRIPT:
Second script body

OUTRO:
O";
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => $secondResponse])->assertRedirect();
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertRedirect();

        $this->assertSame($script->id, $run->fresh()->script_id);
        $this->assertStringContainsString('First script body', (string) $script->fresh()->body);
        $this->assertStringNotContainsString('Second script body', (string) $script->fresh()->body);
    }

    #[Test]
    public function viewer_cannot_save_ai_response_or_create_script(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $type = BulletinType::query()->create([
            'name' => 'Permissions Type',
            'slug' => 'permissions-type',
            'default_timezone' => 'UTC',
            'prompt_language' => 'en',
        ]);

        $this->actingAs($editor)->post(route('editor.bulletin-types.prompt-runs.store', $type))->assertRedirect();
        $run = BulletinPromptRun::query()->latest('id')->firstOrFail();

        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => 'TITLE:
No'])->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertForbidden();
    }

    #[Test]
    public function bulletin_prompt_seed_data_is_idempotent(): void
    {
        $this->seed(DemoEditorialSeeder::class);
        $this->seed(DemoEditorialSeeder::class);

        $this->assertGreaterThanOrEqual(4, PromptProfile::query()->count());
        $this->assertGreaterThanOrEqual(6, BulletinType::query()->count());
        $this->assertSame(1, PromptProfile::query()->where('slug', 'balanced-news')->count());
        $this->assertSame(1, BulletinType::query()->where('slug', 'spain-morning-general-news')->count());
        $morning = BulletinType::query()->where('slug', 'spain-morning-general-news')->firstOrFail();
        $this->assertSame('previous_period', $morning->coverage_mode);
        $this->assertSame(-1440, $morning->coverage_starts_offset_minutes);
    }
}
