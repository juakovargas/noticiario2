<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = ['en', 'es', 'fr'];

        if (Schema::hasTable('languages')) {
            $codes = Language::query()->where('is_active', true)->pluck('code')->all();

            if (! empty($codes)) {
                $locales = $codes;
            }
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'preferred_locale' => ['nullable', 'string', Rule::in($locales)],
            'timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', Rule::in(['locale_default', 'dd/mm/yyyy', 'yyyy-mm-dd', 'mm/dd/yyyy'])],
            'time_format' => ['nullable', Rule::in(['24h', '12h'])],
            'appearance' => ['nullable', Rule::in(['light', 'dark', 'system'])],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
