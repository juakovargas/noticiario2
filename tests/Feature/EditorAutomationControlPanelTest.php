<?php

namespace Tests\Feature;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorAutomationControlPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_access_automation_page(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $this->actingAs($editor)->get(route('editor.automation.index'))->assertOk();
    }

    public function test_viewer_cannot_access_automation_page(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $this->actingAs($viewer)->get(route('editor.automation.index'))->assertForbidden();
    }

    public function test_editor_can_toggle_schedule_and_compute_next_run(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $language = Language::factory()->create();
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $bulletin = BulletinType::factory()->create(['language_id'=>$language->id,'location_id'=>$location->id,'news_category_id'=>$category->id]);
        $schedule = EditorialSchedule::query()->create([
            'name'=>'Morning Run','slug'=>'morning-run','bulletin_type_id'=>$bulletin->id,'edition_type'=>'morning','frequency_type'=>'daily','run_frequency'=>'daily','run_time'=>'08:00','timezone'=>'UTC','is_active'=>false,'auto_create_prompt_run'=>true,'auto_generate_prompt'=>true,
        ]);

        $this->actingAs($editor)->post(route('editor.automation.schedules.toggle',$schedule))->assertRedirect();
        $this->assertTrue($schedule->refresh()->is_active);
        $this->assertNotNull($schedule->next_run_at);
    }
}
