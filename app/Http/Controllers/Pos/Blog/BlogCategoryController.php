<?php

namespace App\Http\Controllers\Pos\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Blog\BlogCategoryRequest;
use App\Models\Pos\Blog\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $selected = $request->query('selected');

        return BlogCategory::query()
            ->withCount('posts')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($selected, fn ($q) => $q->whereIn('id', explode(',', $selected)))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(BlogCategoryRequest $request): JsonResponse
    {
        BlogCategory::query()->create($request->validated());

        return successResponse('Blog category created successfully');
    }

    public function show(BlogCategory $blogCategory): BlogCategory
    {
        return $blogCategory;
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blogCategory): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $blogCategory->update($data);

        return successResponse('Blog category updated successfully');
    }

    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        $blogCategory->delete();

        return successResponse('Blog category deleted successfully');
    }
}
