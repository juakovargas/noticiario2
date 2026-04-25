<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'exists:locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:editions,slug'],
            'edition_type' => ['required', 'string', 'max:50', Rule::in(['morning', 'afternoon', 'night', 'special'])],
            'scheduled_for' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string', 'max:50', Rule::in(['draft', 'planning', 'scripting', 'approved', 'archived'])],
            'target_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
