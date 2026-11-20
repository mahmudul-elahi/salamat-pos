<?php

namespace App\Http\Controllers\Landlord\Setting\DeliveryCharge;

use App\Filters\Pos\Address\AreaFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Address\Area;
use Illuminate\Http\Request;

class AreaShippingChargeController extends Controller
{
    public function index()
    {
        return Area::query()
            ->with('district:id,name')
            ->filters(new AreaFilter)
            ->orderBy('name')
            ->paginate(request('per_page', 10));
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $area->update($validated);

        return successResponse('Area shipping charge updated successfully');
    }
}
