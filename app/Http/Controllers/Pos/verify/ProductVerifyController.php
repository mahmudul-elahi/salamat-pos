<?php

namespace App\Http\Controllers\Pos\verify;

use App\Enums\StatusEnum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Filters\Landlord\Common\NameFilter;
use App\Models\Ecommerce\ProductVerify\ProductVerify;

class ProductVerifyController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        $statusEnum = $this->getStatusEnum($status);

        return ProductVerify::query()
            ->filters($this->filter)
            ->with('product:id,product_name', 'area:id,name,thana,postcode')
            ->when($statusEnum, fn($query) => $query->where('status', $statusEnum->value))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn($productVerify) => $this->transformProductVerify($productVerify));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(ProductVerifyRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;
        ProductVerify::create($data);
        return successResponse('Expense Category created successfully');
    }

    /**
     * Display the specified resource.
     */

    public function show(Warranty $expense_category): Warranty
    {
        return $expense_category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarrantyRequest $request, Warranty $expense_category): \Illuminate\Http\JsonResponse
    {
        $expense_category->update($request->validated());

        return successResponse('Expense Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Warranty $expense_category): \Illuminate\Http\JsonResponse
    {
        $expense_category->delete();
        return successResponse('Expense Category deleted successfully');
    }

    /**
     * Map string status to StatusEnum (case-insensitive)
     */
    private function getStatusEnum(?string $status): ?StatusEnum
    {
        return match ($status) {
            'active' => StatusEnum::Active,
            'rejected' => StatusEnum::Rejected,
            'approved' => StatusEnum::Approved,
            default => null,
        };
    }
    /**
     * Transform warranty data for API response
     */
    private function transformProductVerify(ProductVerify $productVerify): array
    {
        $statusEnum = StatusEnum::from($productVerify->status);

        return [
            ...$productVerify->toArray(),
            'status' => [
                'name' => $statusEnum->name,
                'color' => $statusEnum->color(),
            ],
        ];
    }
        public function productVerifyStatus(Request $request, ProductVerify $productVerify, $action)
    {
        $statusMap = [
            'rejected' => 3,
            'approved' => 4,
        ];


        if (!isset($statusMap[$action])) {
            return response()->json(['message' => 'Invalid status action'], 400);
        }


        $productVerify->status = $statusMap[$action];
        $productVerify->save();

        return response()->json([
            'message' => "Product Verify status updated to $action successfully.",
            'status' => $productVerify->status,
        ]);
    }
}
