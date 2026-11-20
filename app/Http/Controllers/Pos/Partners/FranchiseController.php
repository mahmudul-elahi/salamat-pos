<?php

namespace App\Http\Controllers\Pos\Partners;

use App\Enums\CustomerTypeEnum;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Dealer\DealerRequest;
use App\Models\Pos\Partners\OfflineCustomer;
use Illuminate\Http\JsonResponse;

class FranchiseController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OfflineCustomer::query()
            ->filters($this->filter)
            ->where('customer_type', CustomerTypeEnum::Franchise)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DealerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;
        Partners::create($data);

        return successResponse('Partners created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(OfflineCustomer $franchise): OfflineCustomer
    {
        return $franchise;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DealerRequest $request, OfflineCustomer $franchise): JsonResponse
    {
        $franchise->update($request->validated());

        return successResponse('Franchise updated successfully');
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
