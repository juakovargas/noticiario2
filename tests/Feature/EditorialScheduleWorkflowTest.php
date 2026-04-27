<?php

namespace Tests\Feature;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialScheduleWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    #[Test]
    public function editor_can_access_schedules_index(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('editor.editorial-schedules.index'))->assertOk();
    }

    #[Test]
    public function viewer_cannot_access_schedules(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('editor.editorial-schedules.index'))->assertForbidden();
    }

    #[Test]
    public function editor_can_create_daily_weekdays_and_once_schedules_and_slug_auto_generation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $language = Language::factory()->create(['code' => 'es']);

        $this->actingAs($editor)->post(route('editor.editorial-schedules.store'), [
            'name' => 'Daily Schedule',
            'slug' => '',
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'scheduled_time' => '08:00',
            'manual_ai_mode' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($editor)->post(route('editor.editorial-schedules.store'), [
            'name' => 'Weekdays Schedule',
            'edition_type' => 'morning',
            'frequency_type' => 'weekdays',
            'scheduled_time' => '09:00',
            'weekdays' => ['mon', 'tue', 'wed'],
            'manual_ai_mode' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($editor)->post(route('editor.editorial-schedules.store'), [
            'name' => 'One Off Schedule',
            'edition_type' => 'special',
            'frequency_type' => 'once',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '18:00',
            'manual_ai_mode' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('editorial_schedules', ['name' => 'Daily Schedule']);
        $this->assertDatabaseHas('editorial_schedules', ['name' => 'Weekdays Schedule', 'frequency_type' => 'weekdays']);
        $this->assertDatabaseHas('editorial_schedules', ['name' => 'One Off Schedule', 'frequency_type' => 'once']);
        $this->assertNotNull(EditorialSchedule::query()->where('name', 'Daily Schedule')->value('slug'));
    }

    #[Test]
    public function validation_prevents_invalid_target_duration(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->post(route('editor.editorial-schedules.store'), [
            'name' => 'Invalid Duration Schedule',
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'target_duration_seconds' => 10,
        ])->assertSessionHasErrors('target_duration_seconds');
    }

    #[Test]
    public function editor_can_create_run_generate_prompt_receive_response_and_create_script(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $run = $this->createRunFixture();

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.generate-prompt', $run))->assertRedirect();
        $run->refresh();

        $this->assertSame('prompt_ready', $run->status);
        $this->assertStringContainsString('Spain', $run->generated_prompt ?? '');
        $this->assertStringContainsString('General', $run->generated_prompt ?? '');
        $this->assertStringContainsString('Spanish', $run->generated_prompt ?? '');
        $this->assertStringContainsString('90', $run->generated_prompt ?? '');
        $this->assertStringContainsString('Do not invent facts', $run->generated_prompt ?? '');
        $this->assertStringContainsString('SOURCE HINTS', $run->generated_prompt ?? '');

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.receive-response', $run), [
            'response_text' => "TITLE:\nDemo\n\nINTRO:\nIntro text\n\nNEWS ITEMS:\n1. HEADLINE:\nItem one\n\nSUMMARY:\nSummary one\n\nSCRIPT:\nBody text\n\nEDITORIAL ANGLE:\nWhy now\n\nSOURCE HINTS:\n- https://example.com\n\nOUTRO:\nOutro text",
        ])->assertRedirect();

        $run->refresh();
        $this->assertSame('response_received', $run->status);
        $this->assertIsArray($run->parsed_response);
        $this->assertSame('Demo', $run->parsed_response['title']);

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.create-script', $run))->assertRedirect();

        $run->refresh();
        $this->assertNotNull($run->script_id);
        $this->assertSame('script_created', $run->status);

        /** @var Script $script */
        $script = Script::query()->findOrFail($run->script_id);
        $this->assertSame('Demo', $script->title);
        $this->assertSame('Intro text', $script->intro);
        $this->assertStringContainsString('Item one', $script->body);
        $this->assertSame('Outro text', $script->outro);
        $this->assertTrue((bool) data_get($script->metadata, 'parsed_response_used'));
    }

    #[Test]
    public function unstructured_response_still_saves_safely_and_falls_back_when_creating_script(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $run = $this->createRunFixture();
        $text = 'Plain response without sections.';

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.receive-response', $run), [
            'response_text' => $text,
        ])->assertRedirect();

        $run->refresh();
        $this->assertSame('response_received', $run->status);
        $this->assertSame($text, data_get($run->parsed_response, 'body'));
        $this->assertNotEmpty($run->parser_warnings);

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.create-script', $run))->assertRedirect();

        $script = Script::query()->findOrFail($run->fresh()->script_id);
        $this->assertSame($text, $script->body);
        $this->assertFalse((bool) data_get($script->metadata, 'parsed_response_used'));
    }

    #[Test]
    public function creating_script_twice_does_not_duplicate_scripts(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $run = $this->createRunFixture();

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.receive-response', $run), [
            'response_text' => "TITLE:\nDemo\n\nNEWS ITEMS:\n1. HEADLINE:\nOne\n\nSUMMARY:\nTwo\n\nSCRIPT:\nThree",
        ])->assertRedirect();

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.create-script', $run))->assertRedirect();
        $firstScriptId = $run->fresh()->script_id;

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.create-script', $run->fresh()))->assertRedirect();

        $run->refresh();
        $this->assertSame($firstScriptId, $run->script_id);
        $this->assertSame(1, Script::query()->where('id', $firstScriptId)->count());
    }

    #[Test]
    public function viewer_and_non_editor_cannot_access_run_actions_or_desk(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $user = $this->createUserWithPermissions([]);
        $schedule = EditorialSchedule::query()->create([
            'name' => 'x',
            'slug' => 'x',
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'manual_ai_mode' => true,
            'is_active' => true,
        ]);

        $run = EditorialScheduleRun::query()->create(['editorial_schedule_id' => $schedule->id, 'status' => 'pending']);

        $this->actingAs($viewer)->get(route('editor.editorial-desk.index'))->assertForbidden();
        $this->actingAs($user)->get(route('editor.editorial-schedule-runs.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.editorial-schedules.runs.store', $schedule))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.editorial-schedule-runs.receive-response', $run), ['response_text' => 'x'])->assertForbidden();
        $this->actingAs($user)->post(route('editor.editorial-schedule-runs.create-script', $run), [])->assertForbidden();
    }

    #[Test]
    public function editor_can_access_editorial_desk(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('editor.editorial-desk.index'))->assertOk();
    }

    private function createRunFixture(): EditorialScheduleRun
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $location = Location::factory()->create(['name' => 'Spain']);
        $category = NewsCategory::factory()->create(['name' => 'General']);
        $language = Language::factory()->create(['name' => 'Spanish', 'code' => 'es']);

        $schedule = EditorialSchedule::query()->create([
            'name' => 'Spain Morning Briefing',
            'slug' => 'spain-morning-briefing',
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'scheduled_time' => '08:00',
            'target_duration_seconds' => 90,
            'manual_ai_mode' => true,
            'is_active' => true,
        ]);

        $this->actingAs($editor)->post(route('editor.editorial-schedules.runs.store', $schedule))->assertRedirect();

        return EditorialScheduleRun::query()->firstOrFail();
    }
}
