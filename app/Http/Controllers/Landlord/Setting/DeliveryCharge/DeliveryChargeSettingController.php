<?php

namespace App\Http\Controllers\Landlord\Setting\DeliveryCharge;

use App\Http\Controllers\Controller;
use App\Services\Customization\CustomizationService;
use Illuminate\Http\Request;

class DeliveryChargeSettingController extends Controller
{
    private const TYPE = 'delivery_charge';

    public function __construct(protected CustomizationService $service) {}

    public function index(): array
    {
        $settings = $this->service->get(self::TYPE);

        return [
            'inside_dhaka_charge' => $settings['inside_dhaka_charge'] ?? null,
            'outside_dhaka_charge' => $settings['outside_dhaka_charge'] ?? null,
        ];
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'inside_dhaka_charge' => ['required', 'numeric', 'min:0'],
            'outside_dhaka_charge' => ['required', 'numeric', 'min:0'],
        ]);

        $this->service->update(self::TYPE, $validated);

        return successResponse('Delivery charge settings updated successfully');
    }
}
