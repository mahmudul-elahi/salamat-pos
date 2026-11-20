<?php

namespace App\Http\Controllers\Landlord\Setting\PaymentMethod;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Services\Setting\SettingService;
use App\Http\Requests\Landlord\PaymentMethod\PaymentMethodRequest;

class PaymentMethodController extends Controller
{

    public function __construct(protected SettingService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PaymentMethod::query()
            ->with('director')
            ->filters(new NameFilter())
            ->when(
                fn($q) => $q->whereNotNull('director_id')
                     ->orWhere('name', 'Cash')
            )
            ->orderByRaw("CASE WHEN name = 'Cash' THEN 0 ELSE 1 END")

            ->orderByRaw("CASE WHEN name = 'Cash' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(request('per_page', 20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentMethodRequest $request): \Illuminate\Http\JsonResponse
    {
        $paymentMethod = PaymentMethod::query()
            ->create(array_merge($request->only('name', 'logo', 'director_id'), [
                'tenant_id' => null
            ]));

        return successResponse('Payment method created successfully');
    }


    public function show(PaymentMethod $payment_method): PaymentMethod
    {
        return $payment_method;
    }


    public function update(PaymentMethodRequest $request, PaymentMethod $payment_method): \Illuminate\Http\JsonResponse
    {
        $payment_method->update($request->only('name', 'logo', 'director_id'));
        return successResponse('Payment method updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $payment_method): \Illuminate\Http\JsonResponse
    {
        $payment_method->settings()->delete();
        $payment_method->delete();

        return successResponse('Payment method deleted successfully');
    }
}
