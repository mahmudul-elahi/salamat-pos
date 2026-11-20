<?php

namespace App\Http\Requests\Pos\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     * Ensures each variant and color_images element exists for proper validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure variants have 'selected' key
        $variants = $this->input('variants', []);
        foreach ($variants as $index => $variant) {
            if (!isset($variant['selected'])) {
                $variants[$index]['selected'] = 0;
            }
        }
        $this->merge(['variants' => $variants]);

        // Ensure color_images are arrays
        $colorImages = $this->input('color_images', []);
        foreach ($colorImages as $index => $color) {
            if (!is_array($color)) {
                $colorImages[$index] = [];
            }
        }
        $this->merge(['color_images' => $colorImages]);
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? null;


        return [
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'tag' => ['nullable', 'string', 'max:255'],
            'warranty_text' => ['nullable', 'string'],
            'service_warranty' => ['nullable', 'string', 'max:190'],
            'booking_amount' => ['nullable', 'numeric'],
            'purchase_point' => ['nullable', 'numeric'],

            'meta_title' => 'string|nullable|max:120',
            'meta_desc' => 'string|nullable|max:255',

            'thumbnail_image' => ['nullable', 'url'],
            'video_link' => ['nullable', 'string', 'max:500'],

            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],

            // Attributes
            'attributes' => ['required', 'array', 'min:1'],
            'attributes.*.attribute_id' => ['required', 'exists:attributes,id'],
            'attributes.*.attribute_value_id' => ['required', 'exists:attribute_values,id'],

            // Variants
            'variants' => ['required', 'array'],
            'variants.*.attribute_values' => ['required_if:variants.*.selected,1'],
            'variants.*.b2b_price' => ['nullable', 'numeric'],
            'variants.*.dealer_price' => ['nullable', 'numeric'],
            'variants.*.retail_price' => ['nullable', 'numeric'],
            'variants.*.max_retail_price' => ['required_if:variants.*.selected,1', 'numeric'],
            'variants.*.discount_percent' => ['nullable', 'numeric', 'max:100'],
            'variants.*.discount_fixed' => ['nullable', 'numeric'],
            'variants.*.stock' => ['required_if:variants.*.selected,1', 'boolean'],
            'variants.*.selected' => ['required', 'boolean'],

            'variants.*.sku' => ['required'],


            // Color Images
            'color_images' => ['nullable', 'array', 'min:1'],

            'color_images.*.featured_image' => [
                'nullable',
                'url'
            ],

            'video_links'   => ['nullable', 'array'],
            'video_links.*' => ['nullable', 'string', 'max:500'],

            'color_images.*.video_links' => ['nullable', 'array'],
            'color_images.*.video_links.*' => ['nullable', 'string', 'max:500'],
            'color_images.*.remove_video_links' => ['nullable', 'array'],
            'color_images.*.remove_video_links.*' => ['nullable', 'integer'],


            'color_images.*.slider_images.*' => [
                'nullable',
                'url',
            ]
        ];
    }

    public function attributes(): array
    {
        return [
            'brand_id' => 'brand',
            'category_id' => 'category',
        ];
    }

    public function messages(): array
    {
        return [
            'attributes.*.attribute_id' => 'The attribute field is required.',
            'attributes.*.attribute_value_id' => 'The attribute values field is required.',
            'variants.*.sku.required_if' => 'SKU is required for selected variants.',
            'variants.*.attribute_values.required_if' => 'Attribute values are required for selected variants.',
            'variants.*.max_retail_price.required_if' => 'Max retail price is required for selected variants.',
            'variants.*.stock.required_if' => 'Stock is required for selected variants.',
            'color_images.*.featured_image.required' => 'The featured image field is required.',
            'color_images.*.featured_image.unique' => 'The featured image has already been taken.',
            'color_images.*.slider_images.*.unique' => 'The slider image has already been taken.',
        ];
    }
}
