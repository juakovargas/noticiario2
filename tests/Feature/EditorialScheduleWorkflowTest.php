<?php

namespace Tests\Feature;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialScheduleWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_schedules_index(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('editor.editorial-schedules.index'))->assertOk();
    }

    public function test_viewer_cannot_access_schedules(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('editor.editorial-schedules.index'))->assertForbidden();
    }

    public function test_editor_can_create_daily_weekdays_and_once_schedules_and_slug_auto_generation(): void
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

    public function test_validation_prevents_invalid_target_duration(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->post(route('editor.editorial-schedules.store'), [
            'name' => 'Invalid Duration Schedule',
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'target_duration_seconds' => 10,
        ])->assertSessionHasErrors('target_duration_seconds');
    }

    public function test_editor_can_create_run_generate_prompt_receive_response_and_create_script(): void
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
        $run = EditorialScheduleRun::query()->firstOrFail();

        $this->assertSame('pending', $run->status);
        $this->assertNotNull($run->edition_id);

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.generate-prompt', $run))->assertRedirect();
        $run->refresh();
        $this->assertSame('prompt_ready', $run->status);
        $this->assertStringContainsString('Spain', $run->generated_prompt ?? '');
        $this->assertStringContainsString('General', $run->generated_prompt ?? '');
        $this->assertStringContainsString('Spanish', $run->generated_prompt ?? '');
        $this->assertStringContainsString('90', $run->generated_prompt ?? '');

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.receive-response', $run), [
            'response_text' => "Title: Demo\nIntro: Intro text\nBody: Body text\nOutro: Outro text",
        ])->assertRedirect();

        $run->refresh();
        $this->assertSame('response_received', $run->status);

        $this->actingAs($editor)->post(route('editor.editorial-schedule-runs.create-script', $run))->assertRedirect();

        $run->refresh();
        $this->assertNotNull($run->script_id);
        $this->assertSame('script_created', $run->status);
        $this->assertDatabaseHas('scripts', ['id' => $run->script_id, 'edition_id' => $run->edition_id]);
    }

    public function test_viewer_and_non_editor_cannot_access_run_actions_or_desk(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $user = $this->createUserWithPermissions([]);
        $schedule = EditorialSchedule::query()->create([
            'name' => 'x', 'slug' => 'x', 'edition_type' => 'morning', 'frequency_type' => 'daily', 'manual_ai_mode' => true, 'is_active' => true,
        ]);

        $this->actingAs($viewer)->get(route('editor.editorial-desk.index'))->assertForbidden();
        $this->actingAs($user)->get(route('editor.editorial-schedule-runs.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.editorial-schedules.runs.store', $schedule))->assertForbidden();
    }

    public function test_editor_can_access_editorial_desk(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('editor.editorial-desk.index'))->assertOk();
    }
}
