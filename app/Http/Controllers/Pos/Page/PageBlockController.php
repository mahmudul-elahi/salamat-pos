<?php

namespace App\Http\Controllers\Pos\Page;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Page\Page;
use App\Models\Pos\Page\PageBlock;
use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PageBlockController extends Controller
{
    // Container types may hold children; every other type is a leaf that
    // only carries its own `props`. Keep this list in sync with the Vue
    // block registry (resources/js/src/views/landlord/Ecommerce/page/PageBuilder/blocks.js).
    protected array $containerTypes = ['section', 'tabs', 'tab', 'row', 'column', 'timeline', 'timeline_item', 'cards', 'card'];

    public function index(Page $page)
    {
        $blocks = PageBlock::query()
            ->where('page_id', $page->id)
            ->orderBy('position')
            ->get(['id', 'parent_id', 'type', 'props', 'position', 'is_active']);

        return [
            'page' => $page,
            'blocks' => $this->buildTree($blocks, null),
        ];
    }

    protected function buildTree($blocks, ?int $parentId): array
    {
        return $blocks->where('parent_id', $parentId)->values()->map(function ($block) use ($blocks) {
            return [
                'id' => $block->id,
                'type' => $block->type,
                'props' => $block->props ?? [],
                'is_active' => $block->is_active,
                'children' => in_array($block->type, $this->containerTypes)
                    ? $this->buildTree($blocks, $block->id)
                    : [],
            ];
        })->all();
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function saveTree(Request $request, Page $page): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'blocks' => ['array'],
            'blocks.*.type' => ['required_with:blocks', 'string'],
            'blocks.*.props' => ['nullable', 'array'],
        ]);

        try {
            DB::beginTransaction();

            $existingIds = PageBlock::query()->where('page_id', $page->id)->pluck('id')->all();
            $keptIds = [];

            $this->persistNodes($request->input('blocks', []), $page, null, $keptIds);

            $toDelete = array_diff($existingIds, $keptIds);
            if (!empty($toDelete)) {
                PageBlock::query()->whereIn('id', $toDelete)->delete();
            }

            DB::commit();

            return successResponse('Page sections saved successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Page sections save has been failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    protected function persistNodes(array $nodes, Page $page, ?int $parentId, array &$keptIds): void
    {
        foreach ($nodes as $index => $node) {
            $attributes = [
                'tenant_id' => $page->tenant_id,
                'page_id' => $page->id,
                'parent_id' => $parentId,
                'type' => $node['type'],
                'props' => $node['props'] ?? [],
                'position' => $index,
                'is_active' => $node['is_active'] ?? true,
            ];

            $id = $node['id'] ?? null;

            if ($id && PageBlock::query()->where('page_id', $page->id)->where('id', $id)->exists()) {
                PageBlock::query()->where('id', $id)->update($attributes);
            } else {
                $id = PageBlock::query()->create($attributes)->id;
            }

            $keptIds[] = $id;

            $this->persistNodes($node['children'] ?? [], $page, $id, $keptIds);
        }
    }

    /**
     * Public, unauthenticated read for the storefront - resolved by tenant
     * + page slug (not X-domain) since this route sits outside the admin
     * auth/tenant-resolution middleware stack.
     *
     * @throws GeneralException
     */
    public function publicShow(string $tenantSlug, string $pageSlug)
    {
        $tenant = Tenant::query()->where('slug', $tenantSlug)->where('is_ecommerce', true)->first();
        if (!$tenant) {
            throw new GeneralException('Shop not found', 404);
        }

        $page = Page::query()->where('tenant_id', $tenant->id)->where('slug', $pageSlug)->first();
        if (!$page) {
            throw new GeneralException('Page not found', 404);
        }

        $blocks = PageBlock::query()
            ->where('page_id', $page->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'parent_id', 'type', 'props', 'position', 'is_active']);

        return [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'description' => $page->description,
            ],
            'blocks' => $this->buildTree($blocks, null),
        ];
    }
}
