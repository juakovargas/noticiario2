<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;

class StoreEditionNewsItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'news_item_id' => ['required', 'exists:news_items,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'editorial_angle' => ['nullable', 'string'],
            'included_in_script' => ['required', 'boolean'],
        ];
    }
}
