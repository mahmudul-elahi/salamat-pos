<?php

namespace App\Http\Controllers\Pos\Product;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Product\ProductSerialFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Product\ProductBarcodeRequest;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\PurchaseReturn\PurchaseReturnItem;
use App\Models\Pos\Sale\SaleItem;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use App\Scopes\TenantScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

class ProductBarcodeController extends Controller
{
    /**
     * @throws Exception
     */
    public function barcodeGenerate(ProductBarcodeRequest $request)
    {
        $tenantId = auth()->user()->isAppAdmin() ? $request['tenant_id'] : tenant()->id;
        $userId = auth()->id();
        $data = [];
        foreach ($request->items as $item) {

            $productVariant = ProductVariant::query()
                ->where('id', $item['variant_id'])
                ->where('product_id', $item['product_id'])
                ->first();

            if (! $productVariant) {
                throw new Exception('Product variant not found');
            }

            ProductSerial::query()->create([
                'serial_number' => $item['serial_number'],
                'product_id' => $item['product_id'],
                'product_variant_id' => $productVariant->id,
                'status' => 'generated',
                'max_retail_price' => $item['max_retail_price'] ?? 0,
                'tenant_id' => $tenantId,
                'generated_by' => $userId,
            ]);

            $qrcodeUrl = 'https://www.salamatinnovation.com'.'/product/verify?serial_number='.$item['serial_number'];

            $barcodePNG = (new DNS1D)->getBarcodePNG($item['serial_number'], 'C128');

            $qrCodePNG = (new DNS2D)->getBarcodePNG($qrcodeUrl, 'QRCODE');

            $data[] = [
                'barcode' => 'data:image/png;base64,'.$barcodePNG,
                'qrcode' => 'data:image/png;base64,'.$qrCodePNG,
                'serial_number' => $item['serial_number'],
                'product_name' => $productVariant->product->product_name ?? '',
                'attribute_values' => $productVariant?->attributeValues?->pluck('value')->implode('/'),
                'price' => $item['max_retail_price'] ?? 0,
                'warranty' => round(($productVariant->product->warranty_day ?? 0) / 30),
            ];

            //            $data[] = [
            //                'barcode' => 'data:image/png;base64,' . $barcodePNG,
            //                'serial_number' => $item['serial_number'],
            //            ];
        }

        // return view('pdf.product-barcodes', [
        //     'barcodes' => $data
        // ]);

        return $data;
    }

    public function barcodeList()
    {
        $wareHouse = request('warehouse');

        return ProductSerial::query()
            ->filters(new ProductSerialFilter)
            ->whereHas('variant', function ($q) {
                $q->withTrashed();
            })
            ->whereHas('tenantProductSerials', function ($query) use ($wareHouse) {
                $query->whereIn('status', ['purchase', 'sale_return'])
                    ->when($wareHouse, fn ($query) => $query->where('tenant_id', $wareHouse))
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->with([
                'tenantProductSerials' => function ($query) use ($wareHouse) {
                    $query->withoutGlobalScope(TenantScope::class)
                        ->whereIn('status', ['purchase', 'sale_return'])
                        ->when($wareHouse, fn ($query) => $query->where('tenant_id', $wareHouse))
                        ->distinct()
                        ->with('tenant:id,name');
                },
                'variant' => function ($q) {
                    $q->withTrashed()
                        ->with([
                            'product:id,product_name,product_image',
                            'attributeValues',
                            'purchaseItems' => function ($q) {
                                $q->select('id', 'product_variant_id', 'unit_price', 'serial_number', 'created_at');
                            },
                        ]);
                },
            ])
            ->orderByDesc('id')
            ->paginate(request()->input('per_page', 10))
            ->through(function ($item) {
                $serial = $item->serial_number;

                $purchasesHistory = $item->variant->purchaseItems
                    ->where('serial_number', $serial)
                    ->values();

                return [
                    'id' => $item->id,
                    'serial_number' => $serial,
                    'sku' => $item->variant->sku ?? '',
                    'product_name' => $item->variant->product->product_name ?? 'N/A',
                    'product_image' => $item->variant->product->product_image ?? '',
                    'product_id' => $item->variant->product->id ?? null,
                    'price' => $item->max_retail_price ?? $item->variant->price?->max_retail_price ?? 0,
                    'purchases_history' => $purchasesHistory,
                    'attribute_value' => $item->variant->attributeValues->pluck('value')->implode('/'),
                    'status' => $item->tenantProductSerials
                        ->unique('product_serial_id') // duplicate বাদ দিন
                        ->map(fn ($tSerial) => [
                            'tenant' => $tSerial->tenant?->name,
                            'status' => $tSerial->status,
                            'updated_at' => $tSerial->updated_at,
                        ])->values(),
                ];
            });
    }

    public function multiBarcodePdf(Request $request)
    {
        $barcodes = $request->input('barcodes', []);

        if (! is_array($barcodes)) {
            $barcodes = [$barcodes];
        }

        if ($barcodes === []) {
            return response()->json(['message' => 'No barcodes provided'], 422);
        }

        return view('pdf.product-barcodes', [
            'barcodes' => $barcodes,
        ]);
        // $pdf = Pdf::loadView('pdf.product-barcodes', [
        //     'barcodes' => $barcodes
        // ])
        //     ->setPaper([0, 0, 113, 170], 'landscape');

        // return $pdf->stream('pdf.product-barcodes');
    }

    public function barcodeSinglePdf(ProductBarcodeRequest $request)
    {
        $items = $request->input('items');

        if (! $items || ! isset($items[0])) {
            return response()->json(['message' => 'No barcode provided'], 422);
        }

        $item = $items[0];

        $productVariant = ProductVariant::query()
            ->where('sku', $item['sku'])
            ->where('product_id', $item['product_id'])
            ->first();

        if (! $productVariant) {
            return response()->json(['message' => 'Product variant not found'], 404);
        }
        ProductSerial::query()->create([
            'serial_number' => $item['serial_number'],
            'product_id' => $item['product_id'],
            'product_variant_id' => $productVariant->id,
            'status' => 'generated',
        ]);

        // Generate barcode
        $barcodePNG = (new DNS1D)->getBarcodePNG($item['serial_number'], 'C128');
        $qrcodeUrl = 'https://www.salamatinnovation.com'.'/product/verify?serial_number='.$item['serial_number'];
        $qrCodePNG = (new DNS2D)->getBarcodePNG($qrcodeUrl, 'QRCODE');

        $barcodeData = [
            'barcode' => 'data:image/png;base64,'.$barcodePNG,
            'qrcode' => 'data:image/png;base64,'.$qrCodePNG,
            'serial_number' => $item['serial_number'],
            'product_name' => $productVariant->product->product_name ?? '',
            'price' => $productVariant->price ?? 0,
            'warranty' => round(($productVariant->product->warranty_day ?? 0) / 30),
        ];

        $pdf = Pdf::loadView('pdf.product-barcodes', [
            'barcodes' => [$barcodeData],
        ])
            ->setPaper([0, 0, 113, 170], 'landscape');

        return $pdf->stream('product-barcode.pdf');
    }

    public function barcodeGeneratedPdf(Request $request)
    {
        $items = $request->input('items');

        if (! $items || ! isset($items[0])) {
            return response()->json(['message' => 'No barcode provided'], 422);
        }

        $item = $items[0];

        $productVariant = ProductVariant::query()
            ->where('sku', $item['sku'])
            ->where('product_id', $item['product_id'])
            ->first();

        if (! $productVariant) {
            return response()->json(['message' => 'Product variant not found'], 404);
        }

        // Generate barcode
        $barcodePNG = (new DNS1D)->getBarcodePNG($item['serial_number'], 'C128');
        $qrcodeUrl = 'https://www.salamatinnovation.com'.'/product/verify?serial_number='.$item['serial_number'];
        $qrCodePNG = (new DNS2D)->getBarcodePNG($qrcodeUrl, 'QRCODE');

        $barcodeData = [
            'barcode' => 'data:image/png;base64,'.$barcodePNG,
            'qrcode' => 'data:image/png;base64,'.$qrCodePNG,
            'serial_number' => $item['serial_number'],
            'product_name' => $productVariant->product->product_name ?? '',
            'price' => $productVariant->price ?? 0,
            'warranty' => round(($productVariant->product->warranty_day ?? 0) / 30),
        ];

        $pdf = Pdf::loadView('pdf.product-barcodes', [
            'barcodes' => [$barcodeData],
        ])
            ->setPaper([0, 0, 113, 170], 'landscape');

        return $pdf->stream('product-barcode.pdf');
    }

    /**
     * A serial's full life story - every purchase, sale, sale return and
     * purchase return it was ever part of, in chronological order. Previously
     * this was built one row per tenant_product_serials row, whose status
     * just gets overwritten as an item moves through purchase -> sale ->
     * sale_return, so only the latest step ever showed. Built directly from
     * the four transaction-item tables instead (each already stores the
     * serial_number), so nothing is lost when a serial cycles through
     * several events under the same warehouse.
     */
    public function productSerialHistory()
    {
        $paginated = ProductSerial::query()
            ->filters(new ProductSerialFilter)
            ->whereHas('variant', function ($q) {
                $q->withTrashed();
            })
            ->withCount(['tenantProductSerials' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->with([
                'variant' => function ($q) {
                    $q->withTrashed()
                        ->with([
                            'product' => function ($q) {
                                $q->withTrashed()
                                    ->select('id', 'product_name', 'product_image', 'warranty_day');
                            },
                            'attributeValues',
                        ]);
                },
            ])
            ->orderByDesc('id')
            ->paginate(10);

        $serialNumbers = $paginated->pluck('serial_number')->filter()->unique()->toArray();

        $allPurchaseItems = $this->getRelatedItems(PurchaseItem::class, 'purchase', 'purchase_id', $serialNumbers);
        $allSaleItems = $this->getRelatedItems(SaleItem::class, 'sale', 'sale_id', $serialNumbers);
        $allSaleReturnItems = $this->getRelatedItems(SaleReturnItem::class, 'saleReturn', 'sale_return_id', $serialNumbers);
        $allPurchaseReturnItems = $this->getRelatedItems(PurchaseReturnItem::class, 'purchaseReturn', 'purchase_return_id', $serialNumbers);

        return $paginated->through(function ($item) use ($allPurchaseItems, $allSaleItems, $allSaleReturnItems, $allPurchaseReturnItems) {
            $events = collect();

            foreach ($allPurchaseItems->get($item->serial_number, collect()) as $row) {
                $events->push($this->historyEvent('purchase', $row->purchase, $row->purchase?->contact?->name, null, (float) $row->unit_price, 0));
            }

            foreach ($allSaleItems->get($item->serial_number, collect()) as $row) {
                $events->push($this->historyEvent('sale', $row->sale, null, $row->sale?->contact?->name, 0, $this->saleDiscountHandel($row)));
            }

            foreach ($allSaleReturnItems->get($item->serial_number, collect()) as $row) {
                $events->push($this->historyEvent('sale_return', $row->saleReturn, null, $row->saleReturn?->contact?->name, 0, $this->saleDiscountHandel($row)));
            }

            foreach ($allPurchaseReturnItems->get($item->serial_number, collect()) as $row) {
                $events->push($this->historyEvent('purchase_return', $row->purchaseReturn, $row->purchaseReturn?->contact?->name, null, (float) $row->unit_price, 0));
            }

            return [
                'id' => $item->id,
                'tenant_product_serials_count' => $item->tenant_product_serials_count,
                'serial_number' => $item->serial_number,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'product_name' => $item->variant?->product?->product_name,
                'product_image' => $item->variant?->product?->product_image,
                'warranty_day' => $item->variant?->product?->warranty_day,
                'attribute_value' => $item->variant?->attributeValues?->pluck('value')->implode('/'),
                'price' => $item->variant?->price ?? 0,

                'stock_history' => $events->sortBy('updated_at')->values(),
            ];
        });
    }

    private function historyEvent($type, $parent, ?string $supplier, ?string $customer, float $purchasePrice, float $salePrice): array
    {
        return [
            'type' => $type,
            'status' => $type,
            'tenant' => $parent?->tenant?->name,
            'tenant_id' => $parent?->tenant_id,
            'supplier' => $supplier,
            'customer' => $customer,
            'purchase_price' => $purchasePrice,
            'sale_price' => $salePrice,
            'updated_at' => $parent?->created_at,
        ];
    }

    private function getRelatedItems(
        $modelClass,
        $relation,
        $foreignKey,
        $serialNumbers
    ) {
        $columns = [
            'id',
            $foreignKey,
            'unit_price',
            'serial_number',
        ];

        // Discount fields only exist on sale-side items.
        if (in_array($modelClass, [SaleItem::class, SaleReturnItem::class], true)) {
            $columns[] = 'discount_type';
            $columns[] = 'discount_amount';
        }

        return $modelClass::query()
            ->whereIn('serial_number', $serialNumbers)
            ->with([
                $relation => function ($q) {
                    $q->withoutGlobalScope(TenantScope::class)
                        ->select('id', 'tenant_id', 'contact_id', 'created_at')
                        ->with(['contact:id,name', 'tenant:id,name']);
                },
            ])
            ->get($columns)
            ->groupBy('serial_number');
    }

    private function saleDiscountHandel($saleData): float
    {
        if (empty($saleData)) {
            return 0;
        }

        $unitPrice = (float) ($saleData?->unit_price ?? 0);
        $discountAmount = (float) ($saleData?->discount_amount ?? 0);
        $discountType = $saleData?->discount_type ?? null;

        if ($discountAmount <= 0) {
            return $unitPrice;
        }

        if ($discountType === 'percent') {
            return $unitPrice - (($unitPrice * $discountAmount) / 100);
        }

        if ($discountType === 'fixed') {
            return $unitPrice - $discountAmount;
        }

        return $unitPrice;
    }

    // private function getRelatedItems($modelClass, $relation, $serialNumbers)
    // {
    //     return $modelClass::query()
    //         ->whereIn('serial_number', $serialNumbers)
    //         ->with([$relation => function ($q) {
    //             $q->withoutGlobalScope(\App\Scopes\TenantScope::class)
    //                 ->select('id', 'tenant_id', 'contact_id', 'created_at')
    //                 ->with('contact:id,name');
    //         }])
    //         ->get(['id', "{$relation}_id", 'unit_price', 'serial_number', 'discount_type', 'discount_amount'])
    //         ->groupBy('serial_number');
    // }

    /**
     * @throws Exception
     */
    public function printBarcode(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
        ]);

        try {

            $data = [];

            foreach ($request->items as $item) {

                $productVariant = ProductVariant::query()
                    ->where('id', $item['product_variant_id'])
                    ->with(['product:id,product_name,warranty_day', 'attributeValues'])
                    ->first();

                if (! $productVariant) {
                    throw ValidationException::withMessages([
                        'items' => ['Product variant not found'],
                    ]);
                }

                ProductSerial::query()
                    ->where('product_variant_id', $item['product_variant_id'])
                    ->where('serial_number', $item['serial_number'])
                    ->update([
                        'max_retail_price' => $item['max_retail_price'],
                    ]);

                $qrcodeUrl =
                    'https://www.salamatinnovation.com/product/verify?serial_number='.
                    $item['serial_number'];

                $barcodePNG = (new DNS1D)->getBarcodePNG($item['serial_number'], 'C128');

                $qrCodePNG = (new DNS2D)->getBarcodePNG($qrcodeUrl, 'QRCODE');

                $data[] = [
                    'barcode' => 'data:image/png;base64,'.$barcodePNG,
                    'qrcode' => 'data:image/png;base64,'.$qrCodePNG,
                    'serial_number' => $item['serial_number'],
                    'product_name' => $productVariant->product->product_name ?? '',
                    'attribute_values' => $productVariant?->attributeValues?->pluck('value')->implode('/'),
                    'price' => $item['max_retail_price'] ?? 0,
                    'warranty' => round(
                        ($productVariant->product->warranty_day ?? 0) / 30
                    ),
                ];
            }

            return view('pdf.product-barcodes', [
                'barcodes' => $data,
            ]);
        } catch (\Throwable $e) {
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * @throws GeneralException
     */
    public function destroy(ProductSerial $barcode)
    {
        $check = $barcode->loadCount('tenantProductSerials');

        if ($check->tenant_product_serials_count > 0) {
            throw new GeneralException('Cannot delete barcode with associated warehouse records');
        }

        $barcode->delete();

        return successResponse('Barcode deleted successfully');
    }

    public function generatedBarcode()
    {
        return ProductSerial::query()
            ->filters(new ProductSerialFilter)
            ->with(
                'tenant:id,name',
                'generatedBy',
                'product:id,product_name,product_image',
                'variant.attributeValues',
                'variant.price'
            )
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 10))
            ->through(fn ($item) => [
                'id' => $item->id,
                'serial_number' => $item->serial_number,
                'product_name' => $item->product->product_name,
                'product_image' => $item->product->product_image,
                'attribute_value' => $item->variant->attributeValues->pluck('value')->implode('/'),
                'price' => $item->variant?->price?->max_retail_price ?? 0,
                'tenant' => $item->tenant,
                'generated_by' => $item->generatedBy,
            ]);
    }
}
