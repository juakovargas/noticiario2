<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'edition_id' => ['required', 'exists:editions,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(['draft', 'review', 'approved', 'rejected', 'archived'])],
            'language' => ['nullable', 'string', 'max:10'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'outro' => ['nullable', 'string'],
            'estimated_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'approved_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
