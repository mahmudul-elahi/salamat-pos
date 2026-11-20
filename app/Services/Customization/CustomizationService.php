<?php

namespace App\Services\Customization;

use App\Models\Customization\Customization;

class CustomizationService
{
    public function get(string $type): array
    {
        return Customization::where('type', $type)
            ->get(['name', 'value'])
            ->pluck('value', 'name')
            ->toArray();
    }

    public function update(string $type, array $data): void
    {
        foreach ($data as $name => $value) {
            Customization::updateOrCreate(
                ['name' => $name, 'type' => $type],
                ['value' => $value]
            );
        }
    }
}
