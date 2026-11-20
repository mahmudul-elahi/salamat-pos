<?php

namespace App\Http\Controllers\Pos\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Blog\BlogTagRequest;
use App\Models\Pos\Blog\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogTagController extends Controller
{
    public function index(Request $request)
    {
        $selected = $request->query('selected');

        return BlogTag::query()
            ->withCount('posts')
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($selected, fn($q) => $q->whereIn('id', explode(',', $selected)))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(BlogTagRequest $request): JsonResponse
    {
        BlogTag::query()->create($request->validated());

        return successResponse('Blog tag created successfully');
    }

    public function show(BlogTag $blogTag): BlogTag
    {
        return $blogTag;
    }

    public function update(BlogTagRequest $request, BlogTag $blogTag): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $blogTag->update($data);

        return successResponse('Blog tag updated successfully');
    }

    public function destroy(BlogTag $blogTag): JsonResponse
    {
        $blogTag->delete();

        return successResponse('Blog tag deleted successfully');
    }
}
