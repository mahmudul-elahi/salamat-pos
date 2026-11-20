<?php

namespace Database\Seeders\Telesale;

use App\Models\Pos\Telesale\TelesaleResult;
use Illuminate\Database\Seeder;

/**
 * Seeds the outcomes the sales team already uses on the spreadsheet. These are
 * only a starting set — results are editable from the UI, so this seeder never
 * overwrites a row that already exists.
 */
class TelesaleResultSeeder extends Seeder
{
    public function run(): void
    {
        $results = [
            ['name' => 'Sale Done',         'color' => 'success',   'sort_order' => 1],
            ['name' => 'Follow Up',         'color' => 'warning',   'sort_order' => 2],
            ['name' => 'Product Missing',   'color' => 'danger',    'sort_order' => 3],
            ['name' => 'No More Follow Up', 'color' => 'secondary', 'sort_order' => 4],
            ['name' => 'Issue',             'color' => 'danger',    'sort_order' => 5],
            ['name' => 'Stock Out',         'color' => 'info',      'sort_order' => 6],
            ['name' => 'Not available',     'color' => 'primary',   'sort_order' => 7],
            ['name' => 'Waiting',           'color' => 'warning',   'sort_order' => 8],
            ['name' => 'Shop visit',        'color' => 'secondary', 'sort_order' => 9],
        ];

        $added = [];

        foreach ($results as $result) {
            $created = TelesaleResult::query()->firstOrCreate(
                ['name' => $result['name']],
                ['color' => $result['color'], 'sort_order' => $result['sort_order'], 'status' => true]
            );

            if ($created->wasRecentlyCreated) {
                $added[] = $result['name'];
            }
        }

        $this->command?->info($added ? 'Results added: '.implode(', ', $added) : 'No new results to add.');
    }
}
