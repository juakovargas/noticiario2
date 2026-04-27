<?php

namespace Tests\Unit;

use App\Models\EditorialRequest;
use App\Services\Ai\PromptRenderer;
use Tests\TestCase;

class PromptRendererTest extends TestCase
{
    public function test_it_replaces_basic_placeholders(): void
    {
        $request = new EditorialRequest(['title' => 'My Edition', 'edition_type' => 'morning', 'target_duration_seconds' => 90, 'editorial_instructions' => 'Keep concise']);
        $request->setRelation('candidates', collect());

        $output = (new PromptRenderer())->render('Title {{edition_title}} | {{edition_type}} | {{target_duration_seconds}} | {{editorial_instructions}}', $request);

        $this->assertStringContainsString('My Edition', $output);
        $this->assertStringContainsString('morning', $output);
        $this->assertStringContainsString('90', $output);
    }

    public function test_unknown_placeholders_are_left_untouched(): void
    {
        $request = new EditorialRequest(['title' => 'A']);
        $request->setRelation('candidates', collect());

        $output = (new PromptRenderer())->render('Unknown {{does_not_exist}}', $request);

        $this->assertSame('Unknown {{does_not_exist}}', $output);
    }
}
