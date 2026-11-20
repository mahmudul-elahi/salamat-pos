<?php

namespace App\Http\Controllers\Ecommerce\Elite;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Elite\Elite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EliteController extends Controller
{
    public function index()
    {
        return Elite::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        Elite::query()->create($validated);
        return successResponse('Elite created successfully');
    }

    public function show(Elite $elite): Elite
    {
        return $elite;
    }

    public function update(Request $request, Elite $elite): JsonResponse
    {
        $elite->update($this->validated($request));
        return successResponse('Elite updated successfully');
    }

    public function destroy(Elite $elite): JsonResponse
    {
        $elite->delete();
        return successResponse('Elite deleted successfully');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'status_name' => ['required', 'string', 'max:255'],
            'tagline'     => ['nullable', 'string', 'max:255'],
            'logo'        => ['nullable', 'string'],
            'banner'      => ['nullable', 'string'],
            'point'       => ['nullable', 'integer'],
            'benefits'    => ['nullable', 'array'],
            'benefits.*'  => ['string'],
        ]);
    }
}
