<?php

namespace Tests\Feature;

use App\Models\EditorialRequest;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\SourceReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AdditionalArchiveFiltersTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editorial_schedule_runs_hide_archived_by_default_and_can_show_archived(): void
    {
        $editor = $this->createEditor();
        $schedule = EditorialSchedule::query()->create(['name' => 'Morning Plan', 'slug' => 'morning-plan', 'frequency_type' => 'daily', 'is_active' => true]);
        EditorialScheduleRun::query()->create(['editorial_schedule_id' => $schedule->id, 'status' => 'pending']);
        EditorialScheduleRun::query()->create(['editorial_schedule_id' => $schedule->id, 'status' => 'archived']);

        $this->actingAs($editor)
            ->get(route('editor.editorial-schedule-runs.index'))
            ->assertInertia(fn (Assert $page) => $page->has('runs.data', 1));

        $this->actingAs($editor)
            ->get(route('editor.editorial-schedule-runs.index', ['show_archived' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('runs.data', 2));
    }

    public function test_editor_can_archive_and_restore_editorial_request(): void
    {
        $editor = $this->createEditor();
        $request = EditorialRequest::query()->create(['title' => 'Request A', 'status' => 'draft']);

        $this->actingAs($editor)->post(route('editor.editorial-requests.archive', $request))->assertRedirect();
        $this->assertSame('archived', $request->fresh()->status);

        $this->actingAs($editor)->post(route('editor.editorial-requests.restore', $request))->assertRedirect();
        $this->assertSame('draft', $request->fresh()->status);
    }

    public function test_editor_can_archive_and_restore_source_reference(): void
    {
        $editor = $this->createEditor();
        $reference = SourceReference::factory()->create();

        $this->actingAs($editor)->post(route('editor.source-references.archive', $reference))->assertRedirect();
        $this->assertNotNull($reference->fresh()->archived_at);

        $this->actingAs($editor)->post(route('editor.source-references.restore', $reference))->assertRedirect();
        $this->assertNull($reference->fresh()->archived_at);
    }
}
