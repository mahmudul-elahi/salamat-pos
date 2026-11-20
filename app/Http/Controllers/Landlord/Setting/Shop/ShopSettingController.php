<?php

declare(strict_types=1);

namespace App\Http\Controllers\Landlord\Setting\Shop;

use App\Http\Controllers\Controller;
use App\Services\Setting\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    public function __construct(protected SettingService $service) {}

    public function index()
    {
        if (config('theme29.installed')) {

            return $this->service->getFormattedSettings();
        }

        return [];

    }

    public function update(Request $request): JsonResponse
    {

        $request->validate([
            'shop_name' => 'required',
        ]);

        $this->ImageValidation($request);

        $this->service->update();

        return successResponse('Settings updated successfully');
    }

    public function ImageValidation(Request $request): void
    {
        if ($request->file('shop_logo')) {
            $request->validate([
                'shop_logo' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048',
            ], [
                'shop_logo.max' => 'Sorry! Maximum allowed size for an image is 2MB',
            ]);
        }

    }
}
