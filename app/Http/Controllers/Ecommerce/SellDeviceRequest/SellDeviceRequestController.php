<?php

namespace App\Http\Controllers\Ecommerce\SellDeviceRequest;

use App\Http\Controllers\Controller;
use App\Models\SellDeviceRequest\SellDeviceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SellDeviceRequestController extends Controller
{
    public function index(Request $request)
    {
        return SellDeviceRequest::query()
            ->with(['contact:id,name,phone', 'category:id,name', 'brand:id,name', 'newDistrict:id,name', 'images'])
            ->orderByDesc('id')
            ->when($request->search, function ($query) use ($request) {
                $query->where('ad_title', 'like', '%'.$request->search.'%');
            })
            ->paginate($request->per_page ?? 10)
            ->through(fn ($r) => [
                'id' => $r->id,
                'ad_title' => $r->ad_title,
                'customer' => $r->contact?->name,
                'phone_number' => $r->phone_number,
                'category' => $r->category?->name,
                'brand' => $r->brand?->name,
                'model' => $r->model,
                'condition' => $r->condition,
                'district' => $r->newDistrict?->name,
                'asking_price' => $r->asking_price,
                'is_negotiable' => $r->is_negotiable,
                'is_exchange_possible' => $r->is_exchange_possible,
                'status' => $r->status->value,
                'status_label' => $r->status->name,
                'thumbnail' => $this->resolveImageUrl(
                    $r->images->firstWhere('is_primary', true)?->image ?? $r->images->first()?->image
                ),
                'created_at' => $r->created_at->format('d M Y'),
            ]);
    }

    public function show(SellDeviceRequest $sellDeviceRequest)
    {
        $sellDeviceRequest->load(['contact', 'category:id,name', 'brand:id,name', 'newDistrict:id,name', 'images', 'features']);

        return [
            ...$sellDeviceRequest->toArray(),
            'created_at' => $sellDeviceRequest->created_at->format('d M Y'),
            'images' => $sellDeviceRequest->images->map(fn ($img) => $this->resolveImageUrl($img->image)),
        ];
    }

    public function update(Request $request, SellDeviceRequest $sellDeviceRequest)
    {
        $request->validate([
            'status' => ['required', 'integer'],
        ]);

        $sellDeviceRequest->update([
            'status' => $request->status,
        ]);

        return successResponse('Sell device request status updated successfully');
    }

    public function destroy(SellDeviceRequest $sellDeviceRequest)
    {
        $sellDeviceRequest->delete();

        return successResponse('Sell device request deleted successfully');
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::url($path);
    }
}
