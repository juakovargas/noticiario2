<?php

namespace Tests\Unit;

use App\Models\AiPromptTemplate;
use App\Models\EditorialRequest;
use App\Services\Ai\MockAiClient;
use Tests\TestCase;

class MockAiClientTest extends TestCase
{
    public function test_it_returns_deterministic_content(): void
    {
        $request = new EditorialRequest(['title' => 'Demo']);
        $request->setRelation('aiPromptTemplate', new AiPromptTemplate(['type' => 'editorial_research']));

        $responseA = (new MockAiClient())->generate($request, 'prompt');
        $responseB = (new MockAiClient())->generate($request, 'prompt');

        $this->assertSame($responseA->content, $responseB->content);
        $this->assertIsArray($responseA->parsedJson);
    }
}
