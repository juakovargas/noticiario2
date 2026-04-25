<?php

namespace App\Http\Requests\Admin;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Language $language */
        $language = $this->route('language');

        return [
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($language->id)],
            'flag_emoji' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
