<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PlaceholderPageController extends Controller
{
    public function newsItems(): Response
    {
        return Inertia::render('Editor/NewsItems/Index');
    }

    public function newsSources(): Response
    {
        return Inertia::render('Editor/NewsSources/Index');
    }

    public function newsCategories(): Response
    {
        return Inertia::render('Editor/NewsCategories/Index');
    }

    public function locations(): Response
    {
        return Inertia::render('Editor/Locations/Index');
    }

    public function editions(): Response
    {
        return Inertia::render('Editor/Editions/Index');
    }

    public function scripts(): Response
    {
        return Inertia::render('Editor/Scripts/Index');
    }

    public function audio(): Response
    {
        return Inertia::render('Editor/Audio/Index');
    }

    public function video(): Response
    {
        return Inertia::render('Editor/Video/Index');
    }

    public function mediaRenders(): Response
    {
        return Inertia::render('Editor/MediaRenders/Index');
    }

    public function publications(): Response
    {
        return Inertia::render('Editor/Publications/Index');
    }

    public function socialChannels(): Response
    {
        return Inertia::render('Editor/SocialChannels/Index');
    }

    public function editorialTemplates(): Response
    {
        return Inertia::render('Editor/EditorialTemplates/Index');
    }

    public function aiPromptTemplates(): Response
    {
        return Inertia::render('Editor/AiPromptTemplates/Index');
    }
}
