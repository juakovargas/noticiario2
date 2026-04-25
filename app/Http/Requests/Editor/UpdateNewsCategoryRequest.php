<?php

namespace App\Http\Requests\Editor;

use App\Models\NewsCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var NewsCategory $newsCategory */
        $newsCategory = $this->route('news_category');

        return [
            'parent_id' => ['nullable', 'exists:news_categories,id', Rule::notIn([$newsCategory->id])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news_categories', 'slug')->ignore($newsCategory->id)],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
