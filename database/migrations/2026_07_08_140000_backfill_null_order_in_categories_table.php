<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Categories created outside the drag-and-drop reorder flow were left with a
     * null `order`. Backfill them per parent group (root categories vs each
     * parent's subcategories), continuing on from that group's current max order
     * so already-arranged categories keep their position.
     */
    public function up(): void
    {
        $parentIds = DB::table('categories')
            ->whereNull('order')
            ->distinct()
            ->pluck('parent_id');

        foreach ($parentIds as $parentId) {
            $maxOrder = DB::table('categories')
                ->when($parentId === null, fn($q) => $q->whereNull('parent_id'), fn($q) => $q->where('parent_id', $parentId))
                ->max('order') ?? 0;

            $nullOrderIds = DB::table('categories')
                ->when($parentId === null, fn($q) => $q->whereNull('parent_id'), fn($q) => $q->where('parent_id', $parentId))
                ->whereNull('order')
                ->orderBy('id')
                ->pluck('id');

            foreach ($nullOrderIds as $index => $id) {
                DB::table('categories')->where('id', $id)->update(['order' => $maxOrder + $index + 1]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible — original null state isn't worth restoring.
    }
};
