<?php

namespace App\Http\Controllers\Pos\News;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\News\NewsRequest;
use App\Models\Pos\News\News;
use App\Services\Seo\SeoService;
use Illuminate\Http\JsonResponse;

class BlogController extends Controller
{
    private const BLOG_TENANT_ID = 4;

    public function __construct(protected NameFilter $filter, protected SeoService $seoService)
    {
    }

    public function index()
    {
        return News::query()
            ->where('tenant_id', self::BLOG_TENANT_ID)
            ->filters($this->filter)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(NewsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['image'] = $request->input('image');

        $blog = new News($data);
        $blog->tenant_id = self::BLOG_TENANT_ID;
        $blog->save();

        return successResponse('Blog created successfully');
    }

    public function show(int $blog): News
    {
        return $this->resolveBlog($blog);

    }

    public function update(NewsRequest $request, int $blog): JsonResponse
    {
        $blog = $this->resolveBlog($blog);

        $data = $request->validated();
        $data['image'] = $request->input('image');

        $blog->update($data);

        return successResponse('Blog updated successfully');
    }

    public function destroy(int $blog): JsonResponse
    {
        $blog = $this->resolveBlog($blog);
        $blog->delete();

        return successResponse('Blog deleted successfully');
    }

    private function resolveBlog(int $id): News
    {
        return News::query()
            ->where('tenant_id', self::BLOG_TENANT_ID)
            ->findOrFail($id);
    }
}

