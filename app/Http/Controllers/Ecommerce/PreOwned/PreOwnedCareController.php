<?php

namespace App\Http\Controllers\Ecommerce\PreOwned;

use App\Http\Controllers\Controller;
use App\Models\PreOwned\PreOwned;
use App\Models\PreOwned\PreOwnedCare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreOwnedCareController extends Controller
{
    public function index(PreOwned $preOwned): JsonResponse
    {
        return response()->json(
            $preOwned->cares()->with('care:id,name,value')->get()
        );
    }

    public function store(Request $request, PreOwned $preOwned): JsonResponse
    {
        $request->validate([
            'care_items'           => ['required', 'array', 'min:1'],
            'care_items.*.care_id' => ['required', 'integer', 'exists:cares,id'],
        ]);

        $preOwned->cares()->delete();

        $cares = collect($request->care_items)->map(fn($item) => [
            'pre_owned_id' => $preOwned->id,
            'care_id'      => $item['care_id'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->toArray();

        PreOwnedCare::insert($cares);

        return response()->json(['message' => 'Pre-owned care saved successfully.']);
    }
}
