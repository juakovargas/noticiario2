<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:news_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:news_categories,slug'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
