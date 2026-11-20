<?php

namespace App\Http\Controllers\Pos\Page;

use App\Exceptions\GeneralException;
use App\Filters\Landlord\Common\TitleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Page\PageRequest;
use App\Models\Pos\Page\Page;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PageController extends Controller
{
    public function __construct(protected TitleFilter $filter) {}

    /**
     * @throws GeneralException
     */
    protected function checkSalamatInnovation()
    {
        $salamatInnovation = Tenant::query()
            ->where('is_ecommerce', true)
            ->where('slug', 'salamat-innovation')
            ->first();

        if (! $salamatInnovation) {
            throw new GeneralException('Salamat Innovation is E-commerce not found');
        }

        return $salamatInnovation;
    }

    /**
     * Display a listing of the resource.
     *
     * @throws GeneralException
     */
    public function index()
    {
        $salamatInnovation = $this->checkSalamatInnovation();

        return Page::query()
            ->filters($this->filter)
            ->with('seo:id,model_id,model_type,tags')
            ->where('tenant_id', $salamatInnovation->id)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($item) => [
                ...$item->toArray(),
                'tags' => $item->seo?->tags,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws GeneralException
     * @throws Throwable
     */
    public function store(PageRequest $request): JsonResponse
    {

        $salamatInnovation = $this->checkSalamatInnovation();

        $request->validate([
            'slug' => Rule::unique('pages', 'slug')
                ->where('tenant_id', $salamatInnovation->id)->where('deleted_at'),
        ]);

        try {
            DB::beginTransaction();

            $page = Page::query()->create([
                'title' => $request->title,
                'slug' => Str::slug($request->slug),
                'description' => $request->description,
                'tenant_id' => $salamatInnovation->id,
            ]);

            $page->seo()->create([
                'tenant_id' => $salamatInnovation->id,
                'title' => $request->meta_title,
                'description' => $request->meta_description,
                'tags' => $request->tags,
            ]);

            DB::commit();

            return successResponse('Page created successfully');

        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Page created has been failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {

        return [
            ...$page->toArray(),
            'meta_title' => $page->seo?->title,
            'meta_description' => $page->seo?->description,
            'tags' => $page->seo?->tags,
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws GeneralException
     * @throws Throwable
     */
    public function update(PageRequest $request, Page $page): JsonResponse
    {

        $request->validate([
            'slug' => Rule::unique('pages', 'slug')
                ->where('tenant_id', $page->tenant_id)
                ->where('deleted_at')
                ->ignore($page->id),
        ]);
        try {
            DB::beginTransaction();
            $page->update($request->validated());
            $page->seo()->updateOrCreate([
                'tenant_id' => $page->tenant_id,
            ], [
                'title' => $request->meta_title,
                'description' => $request->meta_description,
                'tags' => $request->tags,
            ]);
            DB::commit();

            return successResponse('Page updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Page updated has been failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return successResponse('Page deleted successfully');
    }

    public function allPageUpdate(Request $request, Page $page)
    {

        $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'primary_logo' => ['required', 'url'],
            'secondary_logo' => ['nullable', 'url'],
            'shop_banner' => ['nullable', 'url'],
            'info_banner' => ['nullable', 'url'],
        ]);

        $page->fill([
            'title' => $request->title,
            'primary_logo' => $request->primary_logo,
            'secondary_logo' => $request->secondary_logo,
            'shop_banner' => $request->shop_banner,
            'info_banner' => $request->info_banner,
        ]);

        if (! $page->isDirty()) {
            return successResponse('No changes detected');
        }

        $page->save();

        return successResponse('Page updated successfully');

    }

    public function allPage()
    {
        return Page::query()
            ->filters($this->filter)
            ->with('tenant:id,name')
            ->paginate(request('per_page', 10));
    }
}
