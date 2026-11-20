<?php

namespace App\Http\Controllers\Pos\People;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Supplier\SupplierRequest;
use App\Http\Requests\Pos\Contact\ContactRequest;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Supplier\Supplier;
use App\Services\Pos\Contact\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function __construct(protected ContactService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        return Contact::query()
            ->filters(new CustomerFilter)
            ->orderByDesc('id')
            ->paginate($perPage);
    }


    /**
     * Store a newly created resource in storage.
     * @throws GeneralException
     * @throws \Throwable
     */

    public function store(ContactRequest $request): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();

            if ($request->type === 'customer') {
                $this->service
                    ->setAttributes($request->all())
                    ->createCustomer();
            }

            //            $supplier = Contact::query()->create([
            //                'type' => $request->type,
            //                'name' => $request->name,
            //                'email' => $request->email,
            //                'phone' => $request->phone,
            //                'secondary_phone' => $request->secondary_phone,
            //                'new_district_id' => $request->new_district_id,
            //                'area_id' => $request->area_id,
            //                'address' => $request->address,
            //            ]);

            //            $supplier->tenants()->attach($request->tenant_id ?? tenant()->id);
            //
            //            if ($request->hasFile('image')) {
            //                $supplier->addMediaFromRequest('image')->toMediaCollection('company_logo');
            //            }
            DB::commit();
            return successResponse('People created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('People created has been failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }


    /**
     * Display the specified resource.
     */

    public function show(Contact $contact)
    {
        return [
            ...$contact->toArray(),
            //'image' => $contact->getImageAttribute(),
        ];
    }

    /**
     * Update the specified resource in storage.
     * @throws GeneralException|\Throwable
     */

    public function update(ContactRequest $request, Contact $contact): \Illuminate\Http\JsonResponse
    {
        //        $request->validate([
        //            'type' => ['required', 'in:customer,supplier'],
        //            'tenant_id' => ['nullable', 'required_if:type,supplier', 'exists:tenants,id'],
        //            'name' => ['required', 'string'],
        //            'email' => ['nullable', 'max:100', Rule::unique('contacts', 'email')->ignore($contact)],
        //            'phone' => ['required', 'max:20', Rule::unique('contacts', 'phone')->ignore($contact)],
        //            'secondary_phone' => ['nullable', 'max:20'],
        //            'country_id' => ['nullable', 'required_if:type,supplier', 'numeric', 'exists:countries,id'],
        //            'new_district_id' => ['nullable', 'numeric', 'exists:new_districts,id'],
        //            'area_id' => ['nullable', 'numeric', 'exists:areas,id'],
        //            'address' => ['required_if:type,supplier', 'max:190'],
        //        ]);

        try {
            DB::beginTransaction();
            if ($request->type === 'customer') {
                $this->service
                    ->setModel($contact)
                    ->setAttributes($request->all())
                    ->updateCustomer();
            }
            //            $supplier->tenants()->sync($request->tenant_id ?? tenant()->id);
            //            if ($request->hasFile('image')) {
            //                $mediaItem = $supplier->getFirstMedia('company_logo');
            //                $mediaItem?->delete();
            //                $supplier->addMediaFromRequest('image')->toMediaCollection('company_logo');
            //            }
            DB::commit();
            return successResponse('People updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('People updated has been failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Supplier $supplier): \Illuminate\Http\JsonResponse
    {
        $supplier->delete();
        return successResponse('Supplier deleted successfully');
    }
}
