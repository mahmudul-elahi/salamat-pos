<?php

namespace App\Http\Controllers\Pos\Partners;

use App\Enums\CustomerTypeEnum;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Dealer\DealerRequest;
use App\Models\Pos\Partners\OfflineCustomer;
use App\Models\Pos\Partners\Partners;
use Illuminate\Http\JsonResponse;

class DealersController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OfflineCustomer::query()
            ->filters($this->filter)
            ->where('customer_type', CustomerTypeEnum::Dealer)
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
        Partners::query()->create($data);

        return successResponse('Partners created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(OfflineCustomer $dealer): OfflineCustomer
    {
        return $dealer;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DealerRequest $request, OfflineCustomer $dealer): JsonResponse
    {
        $dealer->update($request->validated());

        return successResponse('Dealers updated successfully');
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
