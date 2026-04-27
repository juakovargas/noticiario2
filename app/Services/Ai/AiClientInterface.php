<?php

namespace App\Services\Ai;

use App\Data\AiResponse;
use App\Models\EditorialRequest;

interface AiClientInterface
{
    public function generate(EditorialRequest $request, string $prompt): AiResponse;
}
