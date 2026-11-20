<?php

namespace App\Http\Controllers\Ecommerce\Outlet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\Outlet\OutletRequest;
use App\Models\Ecommerce\Outlet\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
{
    public function index(Request $request)
    {
        return Outlet::query()
            ->withCount('images')
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('phone', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(OutletRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $images = $data['images'] ?? [];
            unset($data['images']);

            $outlet = Outlet::query()->create($data);
            $this->syncImages($outlet, $images);
        });

        return successResponse('Outlet created successfully');
    }

    public function show(Outlet $outlet): Outlet
    {
        return $outlet->load('images');
    }

    public function update(OutletRequest $request, Outlet $outlet): JsonResponse
    {
        DB::transaction(function () use ($request, $outlet) {
            $data = $request->validated();
            $images = $data['images'] ?? [];
            unset($data['images']);

            $outlet->update($data);
            $this->syncImages($outlet, $images);
        });

        return successResponse('Outlet updated successfully');
    }

    public function destroy(Outlet $outlet): JsonResponse
    {
        $outlet->delete();

        return successResponse('Outlet deleted successfully');
    }

    protected function syncImages(Outlet $outlet, array $images = []): void
    {
        $outlet->images()->delete();

        $payload = collect($images)
            ->filter(fn ($image) => ! empty($image['image_url']))
            ->map(fn ($image) => [
                'image_url' => $image['image_url'],
                'type' => $image['type'] ?? 'gallery',
                'alt_text' => $image['alt_text'] ?? null,
                'sort_order' => $image['sort_order'] ?? 0,
            ])
            ->values()
            ->all();

        if (! empty($payload)) {
            $outlet->images()->createMany($payload);
        }
    }
}
