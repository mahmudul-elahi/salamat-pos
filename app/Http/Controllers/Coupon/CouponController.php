<?php

namespace App\Http\Controllers\Coupon;

use App\Http\Controllers\Controller;
use App\Models\Coupon\CouponCustomerScope;
use App\Models\Coupon\CouponProductScope;
use App\Models\Coupon\NewCoupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    // -------------------------------------------------
    // 1. LIST COUPONS
    // -------------------------------------------------
    public function index(Request $request)
    {
        return NewCoupon::with(['productScopes', 'customerScopes'])
            ->orderBy('id', 'desc')
            ->when($request->search, function ($query) use ($request) {
                $query->where('code', 'like', "%{$request->search}%");
            })
            ->paginate(\request('per_page', 10));
    }

    // -------------------------------------------------
    // 2. CREATE COUPON
    // -------------------------------------------------
    public function store(Request $request)
    {
        $request->validate(
            [
                'type' => 'required|in:product,category,brand,customer,pre_owned,all',
                'code' => 'required|unique:new_coupons,code',
                'discount_value' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_public' => 'nullable|boolean',

                // Customer Scopes Validation
                'customer_scopes' => 'nullable|array',
                'customer_scopes.*.customer_level_id' => 'required_with:customer_scopes|exists:customer_levels,id',
                'customer_scopes.*.point_amount' => 'required_with:customer_scopes|numeric|min:0',
                'customer_scopes.*.discount_amount' => 'required_with:customer_scopes|numeric|min:0',
            ],
            [
                // Custom Messages
                'customer_scopes.*.customer_level_id.required_with' => 'Customer level field is required.',
                'customer_scopes.*.point_amount.required_with' => 'Point amount field is required.',
                'customer_scopes.*.point_amount.numeric' => 'Point amount must be a number.',
                'customer_scopes.*.discount_amount.required_with' => 'Discount amount field is required.',
                'customer_scopes.*.discount_amount.numeric' => 'Discount amount must be a number.',
            ]
        );

        // Create coupon
        $coupon = NewCoupon::create($request->only([
            'code',
            'type',
            'discount_type',
            'discount_value',
            'start_date',
            'end_date',
            'usage_limit',
            'min_purchase_amount',
            'status',
            'is_public',
        ]));

        // Save Product / Pre-Owned Scopes
        if ($request->type === 'all') {
            CouponProductScope::create([
                'new_coupon_id' => $coupon->id,
                'scope_type' => 'all',
            ]);
        } elseif ($request->product_scopes) {
            foreach ($request->product_scopes as $scope) {
                CouponProductScope::create([
                    'new_coupon_id' => $coupon->id,
                    'scope_type' => $scope['scope_type'],
                    'category_id' => $scope['category_id'] ?? null,
                    'product_id' => $scope['product_id'] ?? null,
                    'brand_id' => $scope['brand_id'] ?? null,
                    'pre_owned_id' => $scope['pre_owned_id'] ?? null,
                ]);
            }
        }

        // Save Customer Scopes
        if ($request->customer_scopes) {
            foreach ($request->customer_scopes as $scope) {
                CouponCustomerScope::create([
                    'new_coupon_id' => $coupon->id,
                    'customer_level_id' => $scope['customer_level_id'],
                    'point_amount' => $scope['point_amount'],
                    'discount_amount' => $scope['discount_amount'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => $coupon->load(['productScopes', 'customerScopes']),
        ], 201);
    }

    // -------------------------------------------------
    // 3. UPDATE COUPON
    // -------------------------------------------------
    public function update(Request $request, $id)
    {
        $coupon = NewCoupon::findOrFail($id);

        $request->validate(
            [
                'code' => 'required|unique:new_coupons,code,'.$id,
                'type' => 'required|in:product,category,brand,customer,pre_owned,all',
                'discount_value' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_public' => 'nullable|boolean',

                // Customer Scopes
                'customer_scopes' => 'nullable|array',
                'customer_scopes.*.customer_level_id' => 'required_with:customer_scopes|exists:customer_levels,id',
                'customer_scopes.*.point_amount' => 'required_with:customer_scopes|numeric|min:0',
                'customer_scopes.*.discount_amount' => 'required_with:customer_scopes|numeric|min:0',
            ],
            [
                // Custom messages
                'customer_scopes.*.customer_level_id.required_with' => 'Customer level field is required.',
                'customer_scopes.*.point_amount.required_with' => 'Point amount field is required.',
                'customer_scopes.*.point_amount.numeric' => 'Point amount must be a number.',
                'customer_scopes.*.discount_amount.required_with' => 'Discount amount field is required.',
                'customer_scopes.*.discount_amount.numeric' => 'Discount amount must be a number.',
            ]
        );

        // Update base coupon data
        $coupon->update($request->only([
            'code',
            'type',
            // 'discount_type',
            'discount_value',
            'start_date',
            'end_date',
            'usage_limit',
            'min_purchase_amount',
            'status',
            'is_public',
        ]));

        // Delete old scopes
        $coupon->productScopes()->delete();
        $coupon->customerScopes()->delete();

        // Insert new product / pre-owned scopes
        if ($request->type === 'all') {
            CouponProductScope::create([
                'new_coupon_id' => $coupon->id,
                'scope_type' => 'all',
            ]);
        } elseif ($request->product_scopes) {
            foreach ($request->product_scopes as $scope) {
                CouponProductScope::create([
                    'new_coupon_id' => $coupon->id,
                    'scope_type' => $scope['scope_type'],
                    'category_id' => $scope['category_id'] ?? null,
                    'product_id' => $scope['product_id'] ?? null,
                    'brand_id' => $scope['brand_id'] ?? null,
                    'pre_owned_id' => $scope['pre_owned_id'] ?? null,
                ]);
            }
        }

        // Insert new customer scopes
        if ($request->customer_scopes) {
            foreach ($request->customer_scopes as $scope) {
                CouponCustomerScope::create([
                    'new_coupon_id' => $coupon->id,
                    'customer_level_id' => $scope['customer_level_id'],
                    'point_amount' => $scope['point_amount'],
                    'discount_amount' => $scope['discount_amount'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Coupon updated successfully',
            'data' => $coupon->load(['productScopes', 'customerScopes']),
        ]);
    }

    // -------------------------------------------------
    // 4. SHOW COUPON
    // -------------------------------------------------

    public function show($id)
    {
        $coupon = NewCoupon::with(['productScopes', 'customerScopes'])->findOrFail($id);

        return response()->json([
            'message' => 'Coupon fetched successfully',
            'data' => $coupon,
        ]);
    }

    public function destroy(NewCoupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ]);
    }

    public function statusUpdate($id)
    {
        $coupon = NewCoupon::findOrFail($id);

        $newStatus = $coupon->status == 1 ? 0 : 1;

        $coupon->update([
            'status' => $newStatus,
        ]);

        return response()->json([
            'message' => 'Coupon status updated successfully',
        ], 200);
    }
}
