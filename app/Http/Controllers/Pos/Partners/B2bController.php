<?php

namespace App\Http\Controllers\Pos\Partners;

use Illuminate\Http\Request;
use App\Enums\CustomerTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Pos\Partners\OfflineCustomer;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Requests\Landlord\Dealer\DealerRequest;

class B2bController extends Controller
{
    public function __construct(protected NameFilter $filter)
    {
    }

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

    public function store(PartnersRequest $request): \Illuminate\Http\JsonResponse
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
    public function update(DealerRequest $request, OfflineCustomer $b2b): \Illuminate\Http\JsonResponse
    {
        $b2b->update($request->validated());

        return successResponse('B2b updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Partners $partners): \Illuminate\Http\JsonResponse
    {
        $partners->delete();
        return successResponse('Partners deleted successfully');
    }
}
