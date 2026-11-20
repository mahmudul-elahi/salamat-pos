<?php

namespace Database\Seeders\OrderConfirmation;

use App\Models\Pos\OrderConfirmation\OrderConfirmationOption;
use Illuminate\Database\Seeder;

/**
 * Seeds the badge values the team already uses on the order sheet. These are
 * only a starting set — every one is editable from the UI, so this seeder never
 * overwrites a row that already exists.
 */
class OrderConfirmationOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            // Advance payment media
            ['advance_media', 'Bkash 22', 'pink', 1],
            ['advance_media', 'Bkash 26', 'pink', 2],
            ['advance_media', 'Nagad 28', 'orange', 3],
            ['advance_media', 'SSL', 'success', 4],
            ['advance_media', 'EMI', 'purple', 5],
            ['advance_media', 'Cash', 'teal', 6],

            // Delivery status
            ['delivery_status', 'Pending', 'secondary', 1],
            ['delivery_status', 'On Way', 'info', 2],
            ['delivery_status', 'Delivered', 'success', 3],
            ['delivery_status', 'Returned', 'danger', 4],
            ['delivery_status', 'Cancelled', 'brown', 5],

            // Courier
            ['delivery_by', 'REDX', 'danger', 1],
            ['delivery_by', 'Pathao', 'orange', 2],
            ['delivery_by', 'SteadFast', 'primary', 3],
            ['delivery_by', 'Paperfly', 'info', 4],
            ['delivery_by', 'eCourier', 'success', 5],
            ['delivery_by', 'CarryBee', 'warning', 6],
            ['delivery_by', 'ParcelDex', 'purple', 7],
            ['delivery_by', 'CourrierFast', 'indigo', 8],
            ['delivery_by', 'Sundarban', 'teal', 9],
            ['delivery_by', 'SA Paribahan', 'brown', 10],
            ['delivery_by', 'Janani Express', 'pink', 11],
            ['delivery_by', 'Karatoa', 'secondary', 12],
            ['delivery_by', 'AJR', 'indigo', 13],
            ['delivery_by', 'Own Delivery', 'teal', 14],
        ];

        // An earlier run seeded this courier as "Stead Fast"; the team spells it
        // "SteadFast". Rename rather than leave a near-duplicate in the
        // dropdown — but only when the new spelling isn't already there, so a
        // deliberate second entry is never clobbered.
        $legacy = OrderConfirmationOption::query()
            ->where('type', 'delivery_by')
            ->where('name', 'Stead Fast')
            ->first();

        $newSpellingExists = OrderConfirmationOption::query()
            ->where('type', 'delivery_by')
            ->where('name', 'SteadFast')
            ->exists();

        if ($legacy && ! $newSpellingExists) {
            $legacy->update(['name' => 'SteadFast']);
            $this->command?->info('Renamed courier "Stead Fast" to "SteadFast".');
        }

        $added = [];

        foreach ($options as [$type, $name, $color, $sortOrder]) {
            $option = OrderConfirmationOption::query()->firstOrCreate(
                ['type' => $type, 'name' => $name],
                ['color' => $color, 'sort_order' => $sortOrder, 'status' => true]
            );

            if ($option->wasRecentlyCreated) {
                $added[] = "{$type}: {$name}";
            }
        }

        $this->command?->info($added ? 'Options added: '.implode(', ', $added) : 'No new options to add.');
    }
}
