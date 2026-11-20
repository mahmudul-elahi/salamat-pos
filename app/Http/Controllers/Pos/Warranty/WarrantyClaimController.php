<?php

namespace App\Http\Controllers\Pos\Warranty;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Warranty\WarrantyClaimRequest;
use App\Models\Ecommerce\Warranty\WarrantyClaim;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Sale\SaleItem;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WarrantyClaimController extends Controller
{
    /**
     * Display a listing of submitted warranty claims.
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $statusEnum = $this->getStatusEnum($status);

        return WarrantyClaim::query()
            ->with([
                'product:id,product_name,product_image,warranty_day',
                'productVariant.attributeValues:id,attribute_id,value',
                'sale:id,full_invoice_number',
            ])
            ->when($statusEnum, fn($query) => $query->where('status', $statusEnum->value))
            ->when($search, fn($query) => $query->where('serial_number', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn($claim) => $this->transformWarrantyClaim($claim));
    }

    /**
     * A staff member enters a customer's IMEI/SL number + a reason and this
     * both verifies the serial is real and logs the claim in one step,
     * mirroring the single-form "Claim Your Warranty" UI.
     */
    public function store(WarrantyClaimRequest $request)
    {
        $serialNumber = $request->serial_number;

        $productSerial = ProductSerial::query()
            ->where('serial_number', $serialNumber)
            ->with('product')
            ->first();

        if (!$productSerial) {
            return errorResponse('Invalid IMEI/SL number. Please check and try again.', null, 404);
        }

        // Only one claim per device: block re-submission once a claim already exists
        // for this serial number, regardless of its current status.
        if (WarrantyClaim::query()->where('serial_number', $serialNumber)->exists()) {
            return errorResponse('A warranty claim has already been submitted for this IMEI/SL number.', null, 422);
        }

        // A returned unit is no longer the customer's to claim against.
        if (SaleReturnItem::query()->where('serial_number', $serialNumber)->exists()) {
            return errorResponse('This product has been returned and is not eligible for a warranty claim.', null, 422);
        }

        // The serial registry (product_serials) doesn't carry a sale date, so
        // the actual sale (and therefore the warranty start date) is looked
        // up via the sale_items row that was created when it was sold.
        $saleItem = SaleItem::query()
            ->where('serial_number', $serialNumber)
            ->with('sale')
            ->first();

        $warrantyEndDate = null;
        $isWithinWarranty = false;

        if ($saleItem?->sale && $productSerial->product?->warranty_day) {
            $warrantyEndDate = Carbon::parse($saleItem->sale->date)
                ->addDays($productSerial->product->warranty_day);
            $isWithinWarranty = Carbon::now()->lte($warrantyEndDate);
        }

        $claim = WarrantyClaim::create([
            'serial_number' => $serialNumber,
            'product_id' => $productSerial->product_id,
            'product_variant_id' => $productSerial->product_variant_id,
            'sale_id' => $saleItem?->sale_id,
            'reason' => $request->reason,
            'warranty_end_date' => $warrantyEndDate,
            'is_within_warranty' => $isWithinWarranty,
            'status' => StatusEnum::Pending->value,
        ]);

        return successResponse('Your warranty claim has been submitted successfully.', [
            'id' => $claim->id,
            'product_name' => $productSerial->product?->product_name,
            'invoice_number' => $saleItem?->sale?->full_invoice_number,
            'warranty_day' => $productSerial->product?->warranty_day,
            'is_within_warranty' => $isWithinWarranty,
            'warranty_end_date' => $warrantyEndDate?->toDateString(),
        ]);
    }

    /**
     * Approve or reject a submitted claim.
     */
    public function warrantyClaimStatus(Request $request, WarrantyClaim $warrantyClaim, $action)
    {
        $statusMap = [
            'rejected' => StatusEnum::Rejected->value,
            'approved' => StatusEnum::Approved->value,
        ];

        if (!isset($statusMap[$action])) {
            return response()->json(['message' => 'Invalid status action'], 400);
        }

        $warrantyClaim->status = $statusMap[$action];
        $warrantyClaim->save();

        return response()->json([
            'message' => "Warranty claim status updated to $action successfully.",
            'status' => $warrantyClaim->status,
        ]);
    }

    /**
     * Map string status to StatusEnum (case-insensitive)
     */
    private function getStatusEnum(?string $status): ?StatusEnum
    {
        return match ($status) {
            'pending' => StatusEnum::Pending,
            'rejected' => StatusEnum::Rejected,
            'approved' => StatusEnum::Approved,
            default => null,
        };
    }

    /**
     * Transform warranty claim data for API response
     */
    private function transformWarrantyClaim(WarrantyClaim $claim): array
    {
        $statusEnum = StatusEnum::from($claim->status);

        return [
            ...$claim->toArray(),
            'product' => $claim->product ? [
                'name' => $claim->product->product_name,
                'image' => $claim->product->product_image,
                'variant' => $claim->productVariant?->attributeValues
                    ->pluck('value')
                    ->filter()
                    ->values(),
            ] : null,
            'invoice_number' => $claim->sale?->full_invoice_number,
            'warranty_day' => $claim->product?->warranty_day,
            'status' => [
                'name' => $statusEnum->name,
                'color' => $statusEnum->color(),
            ],
        ];
    }
}
