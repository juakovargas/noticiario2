<?php

namespace App\Http\Requests\Editor;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Location $location */
        $location = $this->route('location');

        return [
            'parent_id' => ['nullable', 'exists:locations,id', Rule::notIn([$location->id])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('locations', 'slug')->ignore($location->id)],
            'type' => ['required', 'string', 'max:50', Rule::in(['global', 'country', 'region', 'city', 'custom'])],
            'country_code' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'default_language_id' => ['nullable', 'exists:languages,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
