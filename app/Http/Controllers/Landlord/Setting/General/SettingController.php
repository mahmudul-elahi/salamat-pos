<?php

namespace App\Http\Controllers\Landlord\Setting\General;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Services\Setting\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{

    public function __construct(protected SettingService $service) {}

    public function index($tenant = null)
    {
        $tenant = Tenant::query()->find($tenant);
        if ($tenant) {
            return $this->service->getFormattedSettings('tenant', $tenant->id);
        } else {
            return $this->service->getFormattedSettings();
        }
    }

    public function update(Request $request, $tenant = null): \Illuminate\Http\JsonResponse
    {
        $tenant = Tenant::query()->find($tenant);

        if ($tenant) {
            $request->validate([
                'company_name' => ['required', 'max:50'],

                'business_email' => ['required', 'email', 'max:190'],
                'support_email' => ['required', 'email', 'max:190'],
                'primary_phone_number' => ['nullable', 'max:20'],
                'secondary_phone_number' => ['nullable', 'max:20'],
                'primary_address' => ['nullable', 'max:190'],
                'secondary_address' => ['nullable', 'max:190'],
                'meta_title' => ['nullable', 'max:190'],
                'meta_description' => ['nullable'],

                'youtube_link_for_home_section' => ['nullable', 'url', 'max:190'],
                'facebook_link' => ['nullable', 'url', 'max:190'],
                'twitter_link' => ['nullable', 'url', 'max:190'],
                'youtube_link' => ['nullable', 'url', 'max:190'],
                'linkedin_link' => ['nullable', 'url', 'max:190'],
                'instagram_link' => ['nullable', 'url', 'max:190'],
                'tiktok_link' => ['nullable', 'url', 'max:190'],
                'location_link' => ['nullable', 'url', 'max:500'],


            ]);
            $this->tenantLogoValidation($request);

            $this->service->update('tenant', $tenant->id);
        } else {
            $request->validate([
                'company_name' => ['required', 'max:50'],
                'date_format' => ['required', 'in:d-m-Y,m-d-Y,Y-m-d'],
                'time_format' => ['required'],
                'currency_symbol' => ['required'],
                'time_zone' => ['required'],
                'language' => ['required'],
                'company_website' => ['nullable', 'url'],
            ]);
            $this->ImageValidation($request);

            $this->service->update();
        }

        return successResponse('Settings updated successfully');
    }

    private function tenantLogoValidation(Request $request): void
    {
        if ($request->file('company_logo')) {
            $request->validate([
                'company_logo' => 'required|mimes:jpeg,png,jpg,svg|max:2048'
            ], [
                'company_logo.max' => 'Sorry! Maximum allowed size for an image is 2MB'
            ]);
        }


        if ($request->file('company_icon')) {
            $request->validate(
                ['company_icon' => 'nullable|mimes:png,svg,ico|max:2048'],
                [
                    'favicon_icon.mimes' => 'Only PNG, SVG Or Ico files are allowed.',
                    'company_icon.max' => 'Sorry! Maximum allowed size for an image is 2MB'
                ]
            );
        }
        if ($request->file('share_icon')) {
            $request->validate(
                ['share_icon' => 'nullable|mimes:jpeg,png,jpg|max:2048'],
                [
                    'favicon_icon.mimes' => 'Only JPEG, PNG, JPG files are allowed.',
                    'share_icon.max' => 'Sorry! Maximum allowed size for an image is 2MB'
                ]
            );
        }
    }


    public function ImageValidation(Request $request): void
    {
        if ($request->file('company_logo')) {
            $request->validate([
                'company_logo' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048'
            ], [
                'company_logo.max' => 'Sorry! Maximum allowed size for an image is 2MB'
            ]);
        }


        if ($request->file('company_icon')) {
            $request->validate(
                ['company_icon' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048'],
                [
                    'company_icon.max' => 'Sorry! Maximum allowed size for an image is 2MB'
                ]
            );
        }
        if ($request->file('company_banner')) {
            $request->validate(
                ['company_banner' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048'],
                [
                    'company_banner.max' => 'Sorry! Maximum allowed size for an image is 2MB'
                ]
            );
        }
    }
}
