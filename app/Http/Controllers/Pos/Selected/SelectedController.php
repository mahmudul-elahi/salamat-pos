<?php

namespace App\Http\Controllers\Pos\Selected;

use App\Enums\StatusEnum;
use App\Exceptions\GeneralException;
use App\Filters\Landlord\Common\NameFilter;
use App\Filters\Landlord\Product\ProductFilter;
use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Controller;
use App\Models\Coupon\CustomerLevel;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Care\Care;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Country\Country;
use App\Models\Pos\Income\IncomeCategory;
use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\Pos\Product\Attribute;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;
use App\Models\Pos\Stock\Stock;
use App\Models\PreOwned\PreOwned;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectedController extends Controller
{
    public function productList()
    {
        return Product::query()
            ->filters(new ProductFilter)
            ->where('status', StatusEnum::Active)
            ->select(['id', 'product_name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function color()
    {
        return DB::table('colors')
            ->orderByDesc('id')
            ->get();
    }

    public function attribute()
    {
        return Attribute::query()
            ->get();
    }

    public function attributeValue($attributeId)
    {
        return DB::table('attribute_values')
            ->where('attribute_id', $attributeId)
            ->orderBy('value', 'asc')
            ->get();
    }

    public function country()
    {
        return Country::query()
            ->filters(new NameFilter)
            ->paginate(request('per_page', 10));
    }

    public function contact()
    {
        $perPage = request('per_page', 10);

        return Contact::query()
            ->filters(new CustomerFilter)
            ->select(['id', 'name', 'phone', 'business_name', 'business_logo', 'profile_picture'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Contacts for the telesale entry form. The option label carries the phone
     * number because that, not the name, is what identifies a caller — names
     * repeat, numbers don't. Contacts without a phone are skipped: the entry
     * form needs a number to save.
     */
    public function telesaleCustomer()
    {
        // Editing an entry: the select resolves its preselected value by
        // refetching with ?selected=<id> and looking for that id in the
        // response, so answer with exactly that contact. Page one of thousands
        // would almost never contain it, leaving the field looking empty.
        if ($selected = request('selected')) {
            return Contact::query()
                ->whereKey($selected)
                ->select(['id', 'name', 'phone', 'address'])
                ->paginate(1)
                ->through(fn (Contact $contact) => $this->telesaleCustomerOption($contact));
        }

        // Deliberately not CustomerFilter: its OR branches aren't grouped, so
        // the "must have a phone" guard below would leak. Name and phone are
        // also all this dropdown shows, so there's nothing else to match on.
        $search = request('search');

        return Contact::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->when($search, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->select(['id', 'name', 'phone', 'address'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn (Contact $contact) => $this->telesaleCustomerOption($contact));
    }

    private function telesaleCustomerOption(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => trim(($contact->name ?: 'Unnamed').' — '.$contact->phone),
            'customer_name' => $contact->name,
            'phone' => $contact->phone,
            'address' => $contact->address,
        ];
    }

    public function product()
    {
        return Product::query()
            ->filters(new ProductFilter)
            ->select(['id', 'product_name as name'])
            ->where('status', StatusEnum::Active)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function productVariant()
    {
        return Product::query()
            ->filters(new ProductFilter)
            ->where('status', StatusEnum::Active)
            ->with(['variants' => function ($query) {
                $query
                    ->where('status', '1')
                    ->with(['price', 'attributeValues']);
            }])
            ->select(['id', 'product_name as name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'base_price' => $product->variant->price?->max_retail_price ?? 0,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'sku' => $variant->sku,
                    'max_retail_price' => $variant->price?->max_retail_price ?? 0,
                    'attribute_values' => $variant->attributeValues
                        ->pluck('value')
                        ->implode('/'),
                ]),
            ]);
    }

    public function productSku($id)
    {
        $variants = ProductVariant::query()
            ->where('product_id', $id)
            ->with([
                'product',
                'price',
                'attributeValues.attribute', // eager load attributes
            ])
            ->paginate(request('per_page', 10));

        // Transform each variant to include attribute names + values
        $variants->getCollection()->transform(fn ($variant) => [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product?->product_name ?? null,
            'warranty_day' => $variant->product?->warranty_day ?? null,
            'sku' => $variant->sku,
            'attributes' => $variant->attributeValues->map(fn ($attrValue) => [
                'attribute_name' => $attrValue->attribute?->name ?? null,
                'value' => $attrValue->value,
            ]),
        ]);

        return response()->json($variants);
    }

    public function productAttribute($id)
    {
        return ProductVariant::query()
            ->where('product_id', $id)
            ->where('status', '1')
            ->with([
                'product',
                'price',
                'attributeValues.attribute',
            ])
            ->get()->map(function ($variant) {
                $joinedAttributes = $variant->attributeValues
                    ->sortBy(fn ($attrValue) => $attrValue->attribute->name)->map(fn ($attrValue) => $attrValue->value)->join(' / ');

                return [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'max_retail_price' => $variant->price?->max_retail_price ?? null,
                    'product_name' => $variant->product?->product_name ?? null,
                    'warranty_day' => $variant->product?->warranty_day ?? null,
                    'sku' => $variant->sku,
                    'attributes' => $joinedAttributes ?: 'N/A',
                ];
            });
    }

    public function productVariantSerial($variantId)
    {

        return ProductSerial::query()
            ->where('product_variant_id', $variantId)
            ->whereHas('tenantProductSerials', function ($query) {
                $query->whereIn('status', ['purchase', 'sale_return'])
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->orderByDesc('id')
            ->get(['id', 'serial_number']);
    }

    public function district()
    {
        return NewDistrict::query()
            ->filters(new NameFilter)
            ->paginate(request('per_page', 10));
    }

    public function area($id)
    {
        return Area::query()
            ->filters(new NameFilter)
            ->where('new_district_id', $id)
            ->paginate(request('per_page', 10));
    }

    public function shop()
    {
        /** @var User $user */
        $user = auth()->user();

        return Tenant::query()
            ->filters(new NameFilter)
            ->where('is_ecommerce', false)
            ->where('status', 'active')
            ->when($user->is_employee && ! $user->isAppAdmin(), function ($query) use ($user) {
                $query->whereIn('id', $user->tenants->pluck('id'));
            })
            ->paginate(request('per_page', 10));
    }

    public function warehouse()
    {
        return Tenant::query()
            ->filters(new NameFilter)
            ->where('is_ecommerce', false)
            ->where('status', 'active')
            ->paginate(request('per_page', 10));
    }

    public function ecommerce()
    {
        return Tenant::query()
            ->filters(new NameFilter)
            ->where('status', 'active')
            ->where('is_ecommerce', true)
            ->paginate(request('per_page', 10));
    }

    public function searchProduct()
    {
        return $this->searchBySerialStatus();
    }

    public function purchaseSearchProduct()
    {
        return $this->searchBySerialStatus();
    }

    /**
     * @throws GeneralException
     */
    public function saleSearchProduct()
    {
        $searchQuery = request('query');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId = $isAppAdmin ? request('tenant_id') : null;

        if ($isAppAdmin && ! $tenantId) {
            throw new GeneralException('The warehouse/store field is required');
        }

        $products = Product::query()
            ->withWhereHas('serial', function ($query) use ($searchQuery, $tenantId) {
                $query->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                    $query->when($tenantId, function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->whereIn('status', ['purchase', 'sale_return']);
                    });
                })->where('serial_number', $searchQuery)
                    ->with('variant', function ($query) {
                        $query->with([
                            'price:id,product_variant_id,max_retail_price',
                            'attributeValues' => fn ($query) => $query
                                ->select('attribute_values.id', 'value', 'attribute_id')
                                ->orderBy('attribute_values.id', 'desc'),
                        ]);
                    });
            })
            ->select(['id', 'product_name'])
            ->get();

        if ($products->isEmpty()) {
            return successResponse('No product found');
        }

        $data = $products->map(function ($product) use ($tenantId) {
            $serial = $product->serial;

            return [
                'id' => $product->id,
                'name' => $product->product_name,
                'product_variant_id' => $serial?->product_variant_id,
                'serial_number' => $serial?->serial_number,
                'sku' => $serial?->variant?->sku,
                'purchase_price' => $this->getPurchasesPrice($serial, $tenantId),
                'max_retail_price' => $serial->max_retail_price ?? $serial?->variant?->price?->max_retail_price,
                'attribute_values' => $serial?->variant?->attributeValues
                    ->pluck('value')
                    ->implode('/'),
            ];
        });

        return successResponse('Products retrieved successfully', $data);
    }

    private function getPurchasesPrice($serial, $tenantId)
    {
        $purchases = PurchaseItem::query()
            ->whereHas('purchase', fn ($query) => $query->when($tenantId)->where('tenant_id', $tenantId))
            ->where('product_id', $serial->product_id)
            ->where('product_variant_id', $serial?->product_variant_id)
            ->where('serial_number', $serial->serial_number)
            ->latest()
            ->first();
        if (! $purchases) {
            return 0;
        }

        return $purchases->unit_price;
    }

    public function purchaseReturnSearchProduct()
    {
        $searchQuery = request('query');

        if (! $searchQuery) {
            return errorResponse('Search query is required');
        }

        $productSerial = ProductSerial::query()
            ->whereHas('tenantProductSerials', fn ($query) => $query->whereIn('status', ['purchase', 'sale_return']))
            ->where('serial_number', $searchQuery)
            ->first();

        if (! $productSerial) {
            return successResponse('No purchase found');
        }

        // Step 2: Find purchase that contains matching item. Not scoped to the
        // currently selected warehouse: a serial's purchase can belong to a
        // different tenant than the one it was later stock-transferred into,
        // and a return is against that original purchase regardless of where
        // the item physically sits now. Bypassed for every user (not just an
        // app admin) - an employee holding the item at their own shop needs
        // to find it too, and TenantScope would otherwise silently restrict
        // this lookup to their shop even though the purchase never was one.
        $purchase = Purchase::withoutGlobalScope(TenantScope::class)
            ->whereHas('items', function ($q) use ($productSerial) {
                $q->where('product_variant_id', $productSerial->product_variant_id)
                    ->where('product_id', $productSerial->product_id)
                    ->where('serial_number', $productSerial->serial_number)
                    ->whereDoesntHave('purchaseReturnItems');
            })
            ->with([
                'contact:id,name',
                'items' => function ($item) use ($productSerial) {
                    $item->where('product_variant_id', $productSerial->product_variant_id)
                        ->where('product_id', $productSerial->product_id)
                        ->where('serial_number', $productSerial->serial_number)
                        ->whereDoesntHave('purchaseReturnItems')
                        ->with([
                            'product:id,product_name',
                            'productVariant:id,product_id,sku',
                            'productVariant.attributeValues',
                        ]);
                },
            ])
            ->first();

        if (! $purchase || $purchase->items->isEmpty()) {
            return successResponse('No purchase found');
        }

        // Step 3: Format response
        $items = $purchase->items->map(fn ($item) => [
            'id' => $item->id,
            'purchase_id' => $item->purchase_id,
            'product_id' => $item->product_id,
            'supplier' => $purchase?->contact?->name,
            'product_variant_id' => $item->product_variant_id,
            'name' => $item->product?->product_name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'serial_number' => $item->serial_number,
            'sku' => $item->productVariant?->sku,
            'attribute_values' => $item->productVariant?->attributeValues
                ->pluck('value')
                ->implode('/'),
            'note' => $item->note,
        ]);

        return successResponse('Purchase items retrieved successfully', [
            'supplier' => $purchase->contact,
            'items' => $items->values(),
        ]);
    }

    public function saleReturnSearchProduct()
    {
        $searchQuery = request('query');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId = request('tenant_id');

        if (! $searchQuery) {
            return errorResponse('Search query is required');
        }

        $productSerial = ProductSerial::query()
            ->where('serial_number', $searchQuery)
            ->whereHas('tenantProductSerials', fn ($query) => $query->where('status', 'sale'))
            ->first();

        if (! $productSerial) {
            return successResponse('No sale found');
        }

        // Step 2: Find purchase that contains matching item
        $sale = Sale::query()
            ->when($isAppAdmin, fn ($query) => $query->where('sales.tenant_id', $tenantId))
            ->with('contact:id,name')
            ->withSum('payments as received_amount', 'amount')
            ->whereHas('items', function ($q) use ($productSerial) {
                $q->where('product_variant_id', $productSerial->product_variant_id)
                    ->where('product_id', $productSerial->product_id)
                    ->where('serial_number', $productSerial->serial_number)
                    ->whereDoesntHave('saleReturnItems');
            })
            ->with([
                'items' => function ($item) use ($productSerial) {
                    $item->where('product_variant_id', $productSerial->product_variant_id)
                        ->where('product_id', $productSerial->product_id)
                        ->where('serial_number', $productSerial->serial_number)
                        ->whereDoesntHave('saleReturnItems')
                        ->with([
                            'product:id,product_name',
                            'productVariant:id,product_id,sku',
                            'productVariant.attributeValues',
                        ]);
                },
            ])
            ->first();

        if (! $sale || $sale->items->isEmpty()) {
            return successResponse('No sale found');
        }

        // Step 3: Format response
        $items = $sale->items->map(fn ($item) => [
            'id' => $item->id,
            'sale_id' => $item->sale_id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'name' => $item->product?->product_name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'serial_number' => $item->serial_number,
            'sku' => $item->productVariant?->sku,
            'discount_type' => $item->discount_type,
            'discount_amount' => $item->discount_amount,
            'attribute_values' => $item->productVariant?->attributeValues
                ->pluck('value')
                ->implode('/'),
            'note' => $item->note,
        ]);

        return successResponse('Sale items retrieved successfully', [
            'contact' => $sale?->contact,
            'received_amount' => $sale->received_amount,
            'items' => $items->values(),
        ]);
    }

    /**
     * Common reusable search handler
     */
    private function searchBySerialStatus()
    {
        $searchQuery = request('query');
        $products = Product::query()
            ->withWhereHas('serial', function ($query) use ($searchQuery) {
                $query->where('serial_number', $searchQuery)
                    ->with('variant', function ($query) {
                        $query->with([
                            'price:id,product_variant_id,max_retail_price',
                            'attributeValues' => fn ($query) => $query->select('attribute_values.id', 'value', 'attribute_id')
                                ->orderBy('attribute_values.id', 'desc'),
                        ]);
                    });
            })
            ->select(['id', 'product_name'])
            ->get();
        if ($products->isEmpty()) {
            return successResponse('No product found');
        }
        // Format data for frontend
        $data = $products->map(function ($product) {
            $serial = $product->serial;

            return [
                'id' => $product->id,
                'name' => $product->product_name,
                'product_variant_id' => $serial?->product_variant_id,
                'serial_number' => $serial?->serial_number,
                'sku' => $serial?->variant?->sku,
                'max_retail_price' => $serial?->variant?->price?->max_retail_price,
                'attribute_values' => $serial?->variant?->attributeValues
                    ->pluck('value')
                    ->implode('/'),
            ];
        });

        return successResponse('Products retrieved successfully', $data);
    }

    public function category()
    {
        $brand = request('brand');
        $brandCategoryProducts = Product::query()
            ->where('brand_id', $brand)
            ->pluck('category_id');

        if (count($brandCategoryProducts) > 0) {
            return Category::query()
                ->filters(new NameFilter)
                ->whereIn('id', $brandCategoryProducts)
                ->paginate(request('per_page', 10));
        }

        return Category::query()
            ->filters(new NameFilter)
            ->paginate(request('per_page', 10));
    }

    public function nestedCategories()
    {
        return Category::query()
            ->filters(new NameFilter)
            ->whereNull('parent_id')
            ->with(['childrenRecursive' => function ($query) {
                $query->filters(new NameFilter);
            }])
            ->orderByDesc('id')
            ->get();
    }

    public function parentSpecification(): LengthAwarePaginator
    {
        return SpecificationGroup::query()
            ->filters(new NameFilter)
            ->whereNull('parent_id')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function allSpecifications(): LengthAwarePaginator
    {
        return SpecificationGroup::query()
            ->filters(new NameFilter)
            ->whereNull('parent_id')
            ->with('subSpecificationGroups')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function getSpecification($id)
    {
        return SpecificationGroup::with('subSpecificationGroups')->findOrFail($id);
    }

    public function customerLevel(): LengthAwarePaginator
    {
        return CustomerLevel::query()
            ->filters(new NameFilter)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function investmentCategory(Request $request)
    {
        $warehouse = $request->get('warehouse');

        return InvestmentCategory::query()
            ->filters(new NameFilter)
            ->orderByDesc('id')
            ->when($warehouse, function ($query) use ($warehouse) {
                $query->where('tenant_id', $warehouse);
            })
            ->paginate(request('per_page', 10));
    }

    public function searchProductSerial()
    {
        $searchQuery = request('search');

        $products = Product::query()
            ->withWhereHas('serial', function ($query) use ($searchQuery) {
                $query->where('serial_number', $searchQuery)
                    ->with('variant', function ($query) {
                        $query->with([
                            'price:id,product_variant_id,max_retail_price',
                            'attributeValues' => fn ($query) => $query->select('attribute_values.id', 'value', 'attribute_id')
                                ->orderBy('attribute_values.id', 'desc'),
                        ]);
                    });
            })
            ->select(['id', 'product_name'])
            ->get();

        if ($products->isEmpty()) {
            return successResponse('No product found');
        }

        // Format data for frontend
        $data = $products->map(function ($product) {
            $serial = $product->serial;

            return [
                'id' => $product->id,
                'name' => $product->product_name,
                'product_variant_id' => $serial?->product_variant_id,
                'serial_number' => $serial?->serial_number,
                'max_retail_price' => $serial?->variant?->price?->max_retail_price,
                'attribute_values' => $serial?->variant?->attributeValues
                    ->pluck('value')
                    ->implode('/'),
            ];
        });

        return successResponse('Products retrieved successfully', $data);
    }

    public function director()
    {
        return User::query()
            ->filters(new NameFilter)
            ->where('role', 'director')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Employees assigned to a tenant, for the "Seller" dropdown on Create Sale.
     */
    public function employee(Request $request)
    {
        $user = auth()->user();
        $tenantId = $request->get('tenant_id') ?? (! $user->isAppAdmin() ? tenant()->id : null);

        return User::query()
            ->filters(new NameFilter)
            ->where('is_employee', true)
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId));
            })
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($item) => [
                ...$item->toArray(),
                'profile_picture' => $item->profile_picture,
            ]);
    }

    /**
     * Shared by seller-assignment pickers (Create/Edit Sale, Order
     * Confirmation, Telesale, Assign Agent) AND report/statement filters
     * that need to filter historical records by a since-deactivated seller.
     * Only the assignment pickers pass active_only=1 - report filters must
     * keep seeing every seller who ever made a sale, active or not.
     */
    public function seller()
    {
        return User::query()
            ->filters(new NameFilter)
            ->where('is_employee', true)
            ->when(request()->boolean('active_only'), fn ($query) => $query->where('status', 'active'))
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($item) => [
                ...$item->toArray(),
                'profile_picture' => $item->profile_picture,
            ]);
    }

    public function categoryDirectors(InvestmentCategory $category)
    {

        $director = [];
        if ($category->director) {
            $director[] = [
                'id' => $category->director->id,
                'name' => $category->director->name,
            ];
        }

        return response()->json([
            'data' => $director,
        ]);
    }

    public function withdrawalCategoryDirector(IncomeCategory $category)
    {
        $director = [];
        if ($category->director) {
            $director[] = [
                'id' => $category->director->id,
                'name' => $category->director->name,
            ];
        }

        return response()->json([
            'data' => $director,
        ]);
    }

    public function incomeCategory(Request $request)
    {
        $warehouse = $request->get('warehouse');

        return IncomeCategory::query()
            ->when($warehouse, fn ($query) => $query->where('tenant_id', $warehouse))
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function ledgerContact(Contact $contact)
    {
        return $contact;
    }

    public function care()
    {
        return Care::query()
            ->get(['id', 'name', 'value']);
    }

    public function stockTransferSearchSerial()
    {
        $query = request('query');
        $fromTenantId = request('from_tenant_id');

        if (! $query) {
            return response()->json([]);
        }

        if (! $fromTenantId) {
            return $this->searchSerialAcrossTenants($query);
        }

        $serials = ProductSerial::query()
            ->where('serial_number', 'LIKE', "%{$query}%")
            ->whereHas('tenantProductSerials', function ($q) use ($fromTenantId) {
                $q->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $fromTenantId)
                    ->whereIn('status', ['purchase', 'sale_return']);
            })
            ->with([
                'product:id,product_name',
                'variant:id,sku,product_id',
                'variant.attributeValues:id,value',
            ])
            ->limit(20)
            ->get();

        return $serials->map(function ($serial) {
            $attributes = $serial->variant?->attributeValues
                ?->pluck('value')->implode(' / ') ?? '';

            return [
                'product_serial_id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'product_id' => $serial->product_id,
                'product_variant_id' => $serial->product_variant_id,
                'product_name' => $serial->product?->product_name,
                'sku' => $serial->variant?->sku,
                'attributes' => $attributes ?: 'Default',
                'max_retail_price' => $serial->max_retail_price,
            ];
        })->values();
    }

    /**
     * Search a serial across all shops and report which shop currently
     * holds it, so callers (e.g. Damage Product) can auto-select the shop
     * from a barcode scan instead of requiring it to be picked first.
     */
    private function searchSerialAcrossTenants(string $query)
    {
        $serials = ProductSerial::query()
            ->where('serial_number', 'LIKE', "%{$query}%")
            ->with([
                'product:id,product_name',
                'variant:id,sku,product_id',
                'variant.attributeValues:id,value',
                'tenantProductSerials' => function ($q) {
                    $q->withoutGlobalScope(TenantScope::class)
                        ->whereIn('status', ['purchase', 'sale_return'])
                        ->with('tenant:id,name');
                },
            ])
            ->limit(20)
            ->get();

        return $serials
            ->filter(fn ($serial) => $serial->tenantProductSerials->isNotEmpty())
            ->map(function ($serial) {
                $attributes = $serial->variant?->attributeValues
                    ?->pluck('value')->implode(' / ') ?? '';

                $tenantProductSerial = $serial->tenantProductSerials->first();

                return [
                    'product_serial_id' => $serial->id,
                    'serial_number' => $serial->serial_number,
                    'product_id' => $serial->product_id,
                    'product_variant_id' => $serial->product_variant_id,
                    'product_name' => $serial->product?->product_name,
                    'sku' => $serial->variant?->sku,
                    'attributes' => $attributes ?: 'Default',
                    'max_retail_price' => $serial->max_retail_price,
                    'tenant_id' => $tenantProductSerial->tenant_id,
                    'tenant_name' => $tenantProductSerial->tenant?->name,
                ];
            })
            ->values();
    }

    public function preOwnedProducts()
    {
        $selectedId = request('selected');

        return PreOwned::query()
            ->with(['product:id,product_name', 'variant.attributeValues'])
            ->when($selectedId, fn ($q) => $q->where('id', $selectedId))
            ->when(! $selectedId && request('search'), function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('product', function ($inner) {
                        $inner->where('product_name', 'like', '%'.request('search').'%');
                    })->orWhere('serial_number', 'like', '%'.request('search').'%');
                });
            })
            // Available (is_sold = 0) items always sort above sold ones,
            // instead of hiding sold items outright.
            ->orderByRaw('CASE WHEN is_sold = 0 THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(function ($item) {
                $attrs = $item->variant?->attributeValues?->pluck('value')->implode(' / ') ?? '';
                $label = $item->product?->product_name ?? 'Unknown';
                if ($attrs) {
                    $label .= " ($attrs)";
                }
                if ($item->serial_number) {
                    $label .= " - {$item->serial_number}";
                }

                return ['id' => $item->id, 'name' => $label];
            });
    }
}
