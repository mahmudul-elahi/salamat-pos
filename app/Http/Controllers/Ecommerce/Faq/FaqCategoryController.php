<?php

namespace App\Http\Controllers\Ecommerce\Faq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\Faq\FaqCategoryRequest;
use App\Models\Ecommerce\Faq\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqCategoryController extends Controller
{
    public function index(Request $request)
    {
        return FaqCategory::query()
            ->withCount('faqs')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('order')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(FaqCategoryRequest $request): JsonResponse
    {

        FaqCategory::query()->create($request->validated());

        return successResponse('FAQ category created successfully');
    }

    public function show(FaqCategory $faqCategory): FaqCategory
    {
        return $faqCategory;
    }

    public function update(FaqCategoryRequest $request, FaqCategory $faqCategory): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $faqCategory->update($data);

        return successResponse('FAQ category updated successfully');
    }

    public function destroy(FaqCategory $faqCategory): JsonResponse
    {
        $faqCategory->delete();

        return successResponse('FAQ category deleted successfully');
    }

    public function allActive()
    {
        return FaqCategory::query()
            ->select(['id', 'name'])
            ->where('status', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }
}
