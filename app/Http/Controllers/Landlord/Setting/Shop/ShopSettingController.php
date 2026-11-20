<?php

namespace App\Http\Controllers\Landlord\Setting\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Setting\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class ShopSettingController extends Controller
{
    public function __construct(protected SettingService $service)
    {

    }
    public function index()
    {
        if (config('theme29.installed')) {

                return $this->service->getFormattedSettings();
        }
        return [];

    }
    public function update(Request $request): \Illuminate\Http\JsonResponse
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
                'shop_logo' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048'
            ], [
                'shop_logo.max' => 'Sorry! Maximum allowed size for an image is 2MB'
            ]);
        }

    }
}