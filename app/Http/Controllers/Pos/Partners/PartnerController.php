<?php

namespace App\Http\Controllers\Pos\Partners;

use App\Enums\StatusEnum;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Partners\Partners;
use Illuminate\Http\JsonResponse;

class PartnerController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Partners::query()
            ->filters($this->filter)
            ->where('status', StatusEnum::Pending)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PartnersRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;
        Partners::create($data);

        return successResponse('Partners created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Partners $partners): Partners
    {
        return $partners;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PartnersRequest $request, Partners $partners): JsonResponse
    {
        $partners->update($request->validated());

        return successResponse('Partners updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partners $partners): JsonResponse
    {
        $partners->delete();

        return successResponse('Partners deleted successfully');
    }
}
