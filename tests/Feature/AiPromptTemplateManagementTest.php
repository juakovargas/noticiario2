<?php

namespace Tests\Feature;

use App\Models\AiPromptTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AiPromptTemplateManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_manage_prompt_templates(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->post(route('editor.ai-prompt-templates.store'), [
            'name' => 'General Editorial Research',
            'slug' => '',
            'type' => 'editorial_research',
            'user_prompt' => 'Prompt body',
            'expected_output_format' => 'json',
            'is_active' => true,
        ])->assertRedirect(route('editor.ai-prompt-templates.index'));

        $template = AiPromptTemplate::query()->firstOrFail();

        $this->actingAs($editor)->put(route('editor.ai-prompt-templates.update', $template), [
            'name' => 'Updated Template',
            'slug' => '',
            'type' => 'script_generation',
            'user_prompt' => 'Prompt body 2',
            'expected_output_format' => 'text',
        ])->assertRedirect(route('editor.ai-prompt-templates.index'));

        $this->actingAs($editor)->delete(route('editor.ai-prompt-templates.destroy', $template))->assertRedirect(route('editor.ai-prompt-templates.index'));
        $this->assertSoftDeleted('ai_prompt_templates', ['id' => $template->id]);
    }

    public function test_viewer_cannot_access_prompt_templates(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('editor.ai-prompt-templates.index'))->assertForbidden();
    }

    public function test_slug_is_generated_when_empty(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->post(route('editor.ai-prompt-templates.store'), [
            'name' => 'Slug from Name',
            'slug' => '',
            'type' => 'summary',
            'user_prompt' => 'x',
            'expected_output_format' => 'text',
        ])->assertRedirect();

        $this->assertDatabaseHas('ai_prompt_templates', ['slug' => 'slug-from-name']);
    }
}
