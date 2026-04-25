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
            'description' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'trust_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'last_checked_at' => ['nullable', 'date'],
        ];
    }
}
