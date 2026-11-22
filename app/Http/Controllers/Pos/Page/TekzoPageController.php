<?php

namespace App\Http\Controllers\Pos\Page;

use App\Exceptions\GeneralException;
use App\Filters\Landlord\Common\TitleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Page\TekzoPageRequest;
use App\Models\Pos\Page\Page;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TekzoPageController extends Controller
{
    public function __construct(protected TitleFilter $filter) {}

    /**
     * @throws GeneralException
     */
    protected function checkTekzo()
    {
        $tekzo = Tenant::query()
            ->where('is_ecommerce', true)
            ->where('slug', 'tekzo')
            ->first();

        if (! $tekzo) {
            throw new GeneralException('TekZo is E-commerce not found');
        }

        return $tekzo;
    }

    /**
     * Display a listing of the resource.
     *
     * @throws GeneralException
     */
    public function index()
    {
        $tekzo = $this->checkTekzo();

        return Page::query()
            ->filters($this->filter)
            ->with('seo:id,model_id,model_type,tags')
            ->where('tenant_id', $tekzo->id)
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
    public function store(TekzoPageRequest $request): JsonResponse
    {
        $tekzo = $this->checkTekzo();

        $request->validate([
            'slug' => Rule::unique('pages', 'slug')->where('tenant_id', $tekzo->id)->where('deleted_at'),
        ]);

        try {
            DB::beginTransaction();
            $page = Page::query()->create([
                'title' => $request->title,
                'slug' => Str::slug($request->slug),
                'description' => $request->description,
                'tenant_id' => $tekzo->id,
            ]);

            $page->seo()->create([
                'tenant_id' => $tekzo->id,
                'title' => $request->meta_title,
                'description' => $request->meta_description,
                'tags' => $request->tags,
            ]);

            DB::commit();

            return successResponse('TekZo Page created successfully');

        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('TekZo page created has been failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Page $tekzoPage)
    {
        /*
         * tenant_id is left out on purpose: the edit modal posts its loaded data
         * back on save, and a tenant_id the user doesn't belong to would be
         * rejected by the tenant.check middleware. Update never needs it.
         */
        return [
            ...Arr::except($tekzoPage->toArray(), ['tenant_id']),
            'meta_title' => $tekzoPage->seo?->title,
            'meta_description' => $tekzoPage->seo?->description,
            'tags' => $tekzoPage->seo?->tags,
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws GeneralException
     * @throws Throwable
     */
    public function update(TekzoPageRequest $request, Page $tekzoPage): JsonResponse
    {
        $request->validate([
            'slug' => Rule::unique('pages', 'slug')
                ->where('tenant_id', $tekzoPage->tenant_id)
                ->where('deleted_at')
                ->ignore($tekzoPage->id),
        ]);

        try {
            DB::beginTransaction();
            $tekzoPage->update($request->validated());
            $tekzoPage->seo()->updateOrCreate([
                'tenant_id' => $tekzoPage->tenant_id,
            ], [
                'title' => $request->meta_title,
                'description' => $request->meta_description,
                'tags' => $request->tags,
            ]);
            DB::commit();

            return successResponse('TekZo page updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('TekZo page updated has been failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $tekzoPage): JsonResponse
    {
        $tekzoPage->delete();

        return successResponse('TekZo page deleted successfully');
    }
}
