<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'news_source_id' => ['nullable', 'exists:news_sources,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:news_items,slug'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'source_url' => ['nullable', 'url'],
            'author' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:10'],
            'published_at' => ['nullable', 'date'],
            'collected_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50', Rule::in(['draft', 'collected', 'selected', 'rejected', 'archived'])],
            'editorial_priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_evergreen' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
