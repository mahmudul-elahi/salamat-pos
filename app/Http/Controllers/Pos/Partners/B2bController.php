<?php

namespace App\Http\Controllers\Pos\Partners;

use App\Enums\CustomerTypeEnum;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Dealer\DealerRequest;
use App\Models\Pos\Partners\OfflineCustomer;
use Illuminate\Http\JsonResponse;

class B2bController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OfflineCustomer::query()
            ->filters($this->filter)
            ->where('customer_type', CustomerTypeEnum::B2B)
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
    public function show(OfflineCustomer $b2b): OfflineCustomer
    {
        return $b2b;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DealerRequest $request, OfflineCustomer $b2b): JsonResponse
    {
        $b2b->update($request->validated());

        return successResponse('B2b updated successfully');
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
