<?php

namespace App\Http\Controllers\Landlord\Setting\Ecommerce;

use App\Models\Setting\Setting;
use Illuminate\Http\Request;
use App\Models\Tenant\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Shop\ShopRequest;

class EcommerceController extends Controller
{
    public function index()
    {
        return Tenant::query()
            ->where('is_ecommerce', true)
            // ->filters($this->filter)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShopRequest $request): \Illuminate\Http\JsonResponse
    {
        $tenant = Tenant::query()->create(array_merge($request->validated(), ['is_ecommerce' => true]));

        $settings = [
            [
                'name' => 'company_logo',
                'value' => config('settings.application.company_logo'),
                'settingable_type' => get_class($tenant),
                'settingable_id' => $tenant->id,
                'context' => 'tenant'
            ],
            [
                'name' => 'company_icon',
                'value' => config('settings.application.company_icon'),
                'settingable_type' => get_class($tenant),
                'settingable_id' => $tenant->id,
                'context' => 'tenant'
            ],
            [
                'name' => 'company_name',
                'value' => $tenant->name,
                'settingable_type' => get_class($tenant),
                'settingable_id' => $tenant->id,
                'context' => 'tenant'
            ],

        ];

        Setting::query()->insert($settings);


        return successResponse('Ecommerce created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $ecommerce): Tenant
    {
        return $ecommerce;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShopRequest $request, Tenant $ecommerce)
    {
        $ecommerce->update(array_merge($request->validated(), ['is_ecommerce' => true]));

        return successResponse('Ecommerce updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $ecommerce)
    {
        $ecommerce->delete();

        return successResponse('Ecommerce deleted successfully');
    }

    public function updateStatus(Request $request, Tenant $shop)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive']
        ]);
        $shop->update([
            'status' => $request->status
        ]);
        return successResponse('Status updated successfully');
    }
}
