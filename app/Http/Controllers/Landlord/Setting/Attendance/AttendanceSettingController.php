<?php

namespace App\Http\Controllers\Landlord\Setting\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Customization\CustomizationService;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    private const TYPE = 'attendance';

    private const ARRAY_KEYS = ['allowed_ips', 'restricted_ips'];

    public function __construct(protected CustomizationService $service) {}

    public function index(): array
    {
        $settings = $this->service->get(self::TYPE);

        foreach (self::ARRAY_KEYS as $key) {
            $settings[$key] = isset($settings[$key])
                ? (json_decode($settings[$key], true) ?? [])
                : [];
        }

        return [
            'punch_in_tolerance'           => $settings['punch_in_tolerance'] ?? null,
            'work_availability_percentage' => $settings['work_availability_percentage'] ?? null,
            'geo_provider'                 => $settings['geo_provider'] ?? 'none',
            'google_map_api_key'           => $settings['google_map_api_key'] ?? null,
            'mapbox_api_key'               => $settings['mapbox_api_key'] ?? null,
            'ip_geolocation_api_key'       => $settings['ip_geolocation_api_key'] ?? null,
            'ip_validation'                => $settings['ip_validation'] ?? 'none',
            'allowed_ips'                  => $settings['allowed_ips'],
            'restricted_ips'               => $settings['restricted_ips'],
        ];
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'punch_in_tolerance'           => ['nullable', 'integer', 'min:0'],
            'work_availability_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'geo_provider'                 => ['nullable', 'in:google_map,mapbox,ip_geolocation,none'],
            'google_map_api_key'           => ['nullable', 'string'],
            'mapbox_api_key'               => ['nullable', 'string'],
            'ip_geolocation_api_key'       => ['nullable', 'string'],
            'ip_validation'                => ['nullable', 'in:allow,restrict,none'],
            'allowed_ips'                  => ['nullable', 'array'],
            'allowed_ips.*'                => ['ip'],
            'restricted_ips'               => ['nullable', 'array'],
            'restricted_ips.*'             => ['ip'],
        ]);

        foreach (self::ARRAY_KEYS as $key) {
            if (array_key_exists($key, $validated)) {
                $validated[$key] = json_encode($validated[$key] ?? []);
            }
        }

        $this->service->update(self::TYPE, $validated);

        return successResponse('Attendance settings updated successfully');
    }
}
