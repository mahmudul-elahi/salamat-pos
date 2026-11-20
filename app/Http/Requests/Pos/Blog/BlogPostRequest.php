<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('blog_categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'thumbnail' => ['required', 'string'],
            'banner' => ['nullable', 'string'],
            'reading_time' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_featured' => ['boolean'],
            'is_trending' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:191'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('blog_tags', 'id')],
        ];
    }
}
