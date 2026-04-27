<?php

namespace Tests\Feature;

use App\Models\PromptProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class PromptProfileManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    #[Test]
    public function admin_can_access_prompt_profiles_index(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->get(route('admin.prompt-profiles.index'))->assertOk();
    }

    #[Test]
    public function editor_cannot_manage_admin_prompt_profiles(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('admin.prompt-profiles.index'))->assertForbidden();
    }

    #[Test]
    public function admin_can_create_and_update_prompt_profile_and_enforce_single_default(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.prompt-profiles.store'), [
            'name' => 'Balanced',
            'happiness_level' => 5,
            'optimism_level' => 5,
            'seriousness_level' => 7,
            'humor_level' => 0,
            'irony_level' => 0,
            'formality_level' => 7,
            'negativity_tolerance' => 4,
            'controversy_tolerance' => 4,
            'source_strictness_level' => 9,
            'is_default' => true,
            'is_active' => true,
        ])->assertRedirect(route('admin.prompt-profiles.index'));

        $first = PromptProfile::query()->where('name', 'Balanced')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.prompt-profiles.store'), [
            'name' => 'Positive',
            'happiness_level' => 9,
            'optimism_level' => 8,
            'seriousness_level' => 6,
            'humor_level' => 2,
            'irony_level' => 1,
            'formality_level' => 5,
            'negativity_tolerance' => 2,
            'controversy_tolerance' => 3,
            'source_strictness_level' => 8,
            'is_default' => true,
            'is_active' => true,
        ])->assertRedirect(route('admin.prompt-profiles.index'));

        $first->refresh();
        $this->assertFalse($first->is_default);
        $this->assertSame(1, PromptProfile::query()->where('is_default', true)->count());

        $second = PromptProfile::query()->where('name', 'Positive')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.prompt-profiles.update', $second), [
            'name' => 'Positive Updated',
            'happiness_level' => 8,
            'optimism_level' => 8,
            'seriousness_level' => 6,
            'humor_level' => 1,
            'irony_level' => 1,
            'formality_level' => 5,
            'negativity_tolerance' => 2,
            'controversy_tolerance' => 3,
            'source_strictness_level' => 8,
            'is_default' => true,
            'is_active' => true,
        ])->assertRedirect(route('admin.prompt-profiles.index'));

        $this->assertDatabaseHas('prompt_profiles', ['name' => 'Positive Updated']);
    }

    #[Test]
    public function numeric_levels_are_validated_between_zero_and_ten(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.prompt-profiles.store'), [
            'name' => 'Invalid',
            'happiness_level' => 11,
            'optimism_level' => -1,
            'seriousness_level' => 7,
            'humor_level' => 0,
            'irony_level' => 0,
            'formality_level' => 5,
            'negativity_tolerance' => 5,
            'controversy_tolerance' => 5,
            'source_strictness_level' => 7,
        ])->assertSessionHasErrors(['happiness_level', 'optimism_level']);
    }
}
