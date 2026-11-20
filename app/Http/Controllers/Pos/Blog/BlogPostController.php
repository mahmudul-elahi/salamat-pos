<?php

namespace App\Http\Controllers\Pos\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Blog\BlogPostRequest;
use App\Models\Pos\Blog\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        return BlogPost::query()
            ->with(['category:id,name', 'author:id,name'])
            ->withCount('tags')
            ->when($request->search, fn ($q) => $q->where('title', 'like', '%'.$request->search.'%'))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('is_published', $request->status))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(BlogPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = auth()->id();

        $post = BlogPost::query()->create($data);
        $post->tags()->sync($request->input('tags', []));

        return successResponse('Blog post created successfully');
    }

    public function show(BlogPost $blogPost)
    {
        return $blogPost->load(['category:id,name', 'author:id,name', 'tags:id,name']);
    }

    public function update(BlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validated();

        $blogPost->update($data);
        $blogPost->tags()->sync($request->input('tags', []));

        return successResponse('Blog post updated successfully');
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return successResponse('Blog post deleted successfully');
    }

    private const array TOGGLEABLE_FIELDS = ['is_published', 'is_featured', 'is_trending', 'is_popular', 'is_pinned'];

    public function toggle(BlogPost $blogPost, string $field): JsonResponse
    {
        abort_unless(in_array($field, self::TOGGLEABLE_FIELDS, true), 404);

        $value = ! $blogPost->{$field};
        $data = [$field => $value];

        if ($field === 'is_published' && $value && ! $blogPost->published_at) {
            $data['published_at'] = now();
        }

        // A newly pinned post joins the end of the pinned run rather than
        // jumping the queue; unpinning releases its slot.
        if ($field === 'is_pinned') {
            $data['pinned_position'] = $value
                ? (int) BlogPost::query()->where('is_pinned', true)->max('pinned_position') + 1
                : 0;
        }

        $blogPost->update($data);

        return successResponse('Blog post updated successfully', [$field => $value]);
    }

    /**
     * The pinned posts, in the order they'll lead the storefront.
     *
     * A dedicated endpoint rather than a filter on index(): the reorder panel
     * only ever needs this handful, and pulling them through the main listing
     * would drag the full post bodies along with them.
     */
    public function pinned()
    {
        return BlogPost::query()
            ->select('id', 'title', 'slug', 'thumbnail', 'pinned_position', 'is_published', 'published_at')
            ->with(['category:id,name'])
            ->where('is_pinned', true)
            ->orderBy('pinned_position')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Saves the order of the pinned posts.
     *
     * Only the ids sent are touched — and only if they're actually pinned — so
     * this can never renumber (or accidentally pin) the rest of the archive,
     * however large it grows.
     */
    public function updatePinnedOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'posts' => ['required', 'array'],
            'posts.*.id' => ['required', 'integer', 'exists:blog_posts,id'],
            'posts.*.position' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['posts'] as $post) {
                BlogPost::query()
                    ->where('id', $post['id'])
                    ->where('is_pinned', true)
                    ->update(['pinned_position' => $post['position']]);
            }
        });

        return successResponse('Pinned order updated successfully');
    }
}
