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
            'timezone' => ['nullable', 'string', 'max:255', 'timezone'],
            'default_language_id' => ['nullable', 'exists:languages,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_zoom' => ['nullable', 'integer', 'between:1,18'],
            'marker_color' => ['nullable', 'string', 'max:30'],
            'marker_label' => ['nullable', 'string', 'max:255'],
            'show_on_map' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
