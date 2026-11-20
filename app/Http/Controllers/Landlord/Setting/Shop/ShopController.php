<?php

namespace App\Http\Controllers\Landlord\Setting\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Shop\ShopRequest;
use App\Models\Setting\Setting;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        return Tenant::query()
            ->where('is_ecommerce', false)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(ShopRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->create($request->only('name'));

        $settingValues = [
            'company_logo' => $request->logo ?? null,
            'company_name' => $tenant->name,
            'company_phone' => $request->phone ?? null,
            'company_email' => $request->email ?? null,
        ];

        $settings = collect($settingValues)->map(fn ($value, $name) => [
            'name' => $name,
            'value' => $value,
            'settingable_type' => $tenant::class,
            'settingable_id' => $tenant->id,
            'context' => 'tenant',
        ])->values()->toArray();

        Setting::query()->insert($settings);

        return successResponse('Shop created successfully');
    }

    public function show(Tenant $shop)
    {
        $settings = $shop->settings()
            ->whereIn('name', ['company_logo', 'company_phone', 'company_email'])
            ->pluck('value', 'name');

        return response()->json([
            'id' => $shop->id,
            'name' => $shop->name,
            'logo' => $settings['company_logo'] ?? null,
            'phone' => $settings['company_phone'] ?? null,
            'email' => $settings['company_email'] ?? null,
        ]);
    }

    public function update(ShopRequest $request, Tenant $shop)
    {
        $shop->update($request->only('name'));

        $settingsToUpdate = [
            'company_name' => $shop->name,
            'company_phone' => $request->phone,
            'company_email' => $request->email,
            'company_logo' => $request->logo,
        ];

        foreach ($settingsToUpdate as $name => $value) {
            $shop->settings()->updateOrCreate(
                ['name' => $name],
                ['value' => $value, 'context' => 'tenant']
            );
        }

        return successResponse('Shop updated successfully');
    }

    public function destroy(Tenant $shop)
    {
        $shop->delete();

        return successResponse('Shop deleted successfully');
    }

    public function updateStatus(Request $request, Tenant $shop)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $shop->update(['status' => $request->status]);

        return successResponse('Status updated successfully');
    }
}
