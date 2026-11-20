<?php

namespace App\Http\Controllers\Pos\Warranty;

use App\Enums\StatusEnum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Filters\Landlord\Common\NameFilter;
use App\Models\Ecommerce\Warranty\Warranty;

class WarrantyController extends Controller
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

        return Warranty::query()
            ->filters($this->filter)
            ->with('product:id,product_name')
            ->when($statusEnum, fn($query) => $query->where('status', $statusEnum->value))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn($warranty) => $this->transformWarranty($warranty));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(WarrantyRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;
        Warranty::create($data);
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
    private function transformWarranty(Warranty $warranty): array
    {
        $statusEnum = StatusEnum::from($warranty->status);

        return [
            ...$warranty->toArray(),
            'status' => [
                'name' => $statusEnum->name,
                'color' => $statusEnum->color(),
            ],
        ];
    }
        public function warrantyStatus(Request $request, Warranty $warranty, $action)
    {
        $statusMap = [
            'rejected' => 3,
            'approved' => 4,
        ];


        if (!isset($statusMap[$action])) {
            return response()->json(['message' => 'Invalid status action'], 400);
        }


        $warranty->status = $statusMap[$action];
        $warranty->save();

        return response()->json([
            'message' => "Warranty status updated to $action successfully.",
            'status' => $warranty->status,
        ]);
    }
}
