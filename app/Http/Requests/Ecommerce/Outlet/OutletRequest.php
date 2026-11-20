<?php

namespace App\Http\Requests\Ecommerce\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeOutlet = $this->route('outlet');
        $outletId = is_object($routeOutlet) ? $routeOutlet->id : $routeOutlet;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('outlets', 'slug')->ignore($outletId)],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'new_district_id' => ['nullable', 'exists:new_districts,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'google_map_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'string', 'max:500'],
            'opens' => ['required', 'date_format:H:i'],
            'closes' => ['required', 'date_format:H:i'],
            'close_day' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'status' => ['required', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*.image_url' => ['required', 'string', 'max:500'],
            'images.*.type' => ['required', Rule::in(['banner', 'gallery', 'thumbnail'])],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

