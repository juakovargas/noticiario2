<?php

namespace App\Http\Requests\Editor;

use App\Models\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var NewsSource $newsSource */
        $newsSource = $this->route('news_source');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news_sources', 'slug')->ignore($newsSource->id)],
            'type' => ['required', 'string', 'max:50', Rule::in(['rss', 'website', 'manual', 'api'])],
            'url' => ['nullable', 'url', 'max:255'],
            'feed_url' => ['nullable', 'url', 'max:255'],
            'default_news_category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'default_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string'],
            'ingestion_notes' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'is_demo' => ['boolean'],
            'trust_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'last_checked_at' => ['nullable', 'date'],
        ];
    }
}
