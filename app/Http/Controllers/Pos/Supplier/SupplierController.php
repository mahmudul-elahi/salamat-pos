<?php

namespace App\Http\Controllers\Pos\Supplier;

use App\Exceptions\AuthorizeException;
use App\Exceptions\GeneralException;
use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Supplier\SupplierRequest;
use App\Models\Pos\Contact\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    use \App\Http\Controllers\Concerns\StoresPriceDocument;

    public function priceDocumentChunk(
        \Illuminate\Http\Request $request,
        Contact $supplier,
        \App\Services\Upload\ChunkedUploadService $chunks
    ): \Illuminate\Http\JsonResponse {
        return $this->receivePriceDocumentChunk($request, $supplier, 'supplier-price-documents', $chunks);
    }



    /**
     * Display a listing of the resource.
     * @throws AuthorizeException
     */

    public function index()
    {
        $perPage = request('per_page', 10);

        return Contact::query()
            ->filters(new CustomerFilter)
            ->where('type', 'supplier')
            ->orderByDesc('id')
            ->paginate($perPage);

        // $totalDue = (clone $baseQuery)->get()->sum(fn($supplier) => $supplier->getDueAmountAttribute());

    }


    /**
     * Store a newly created resource in storage.
     * @throws GeneralException
     * @throws \Throwable
     */

    public function store(SupplierRequest $request): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();
            $supplier = Contact::query()->create(array_merge($request->validated(), [
                'type' => 'supplier'
            ]));

            DB::commit();
            // Callers that create a supplier inline (e.g. the "Add Supplier"
            // shortcut on the Create Purchase form) need the new record back
            // so they can select it immediately, instead of the supplier
            // being created but left unselected on the parent form.
            return successResponse('Supplier created successfully', $supplier->only(['id', 'name', 'business_name', 'phone', 'profile_picture']));
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Supplier created has been failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }


    /**
     * Display the specified resource.
     */

    public function show(Contact $supplier)
    {
        return [
            ...$supplier->toArray(),
            'image' => null,
        ];
    }

    /**
     * Update the specified resource in storage.
     * @throws GeneralException|\Throwable
     */

    public function update(SupplierRequest $request, Contact $supplier): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $supplier->update($request->validated());
            //            $supplier->tenants()->sync($request->tenant_id ?? tenant()->id);
            if ($request->hasFile('image')) {
                $mediaItem = $supplier->getFirstMedia('company_logo');
                $mediaItem?->delete();
                $supplier->addMediaFromRequest('image')->toMediaCollection('company_logo');
            }
            DB::commit();
            return successResponse('Supplier updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Supplier updated has been failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Contact $supplier): \Illuminate\Http\JsonResponse
    {
        $supplier->delete();
        return successResponse('Supplier deleted successfully');
    }

    public function rating(\Illuminate\Http\Request $request, Contact $supplier): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        $supplier->update(['rating' => $request->rating]);

        return successResponse('Supplier rating updated successfully');
    }

    public function note(\Illuminate\Http\Request $request, Contact $supplier): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $supplier->update(['note' => $request->note]);

        return successResponse('Supplier note updated successfully');
    }

    /**
     * A supplier keeps one current price list. Uploading replaces whatever was
     * there before — including the file on disk, so old uploads don't pile up.
     * `txt` is allowed because spreadsheets often export CSVs as text/plain.
     *
     * No disk is named anywhere here: the file lands on whichever disk
     * FILESYSTEM_DISK points at, same as employee documents.
     */
    public function priceDocument(\Illuminate\Http\Request $request, Contact $supplier): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'price_document' => ['required', 'file', 'mimes:pdf,xls,xlsx,csv,txt,png,jpg,jpeg', 'max:10240'],
        ]);

        $previous = $supplier->price_document;

        $path = $request->file('price_document')->store('supplier-price-documents');

        $supplier->update(['price_document' => $path]);

        if ($previous && $previous !== $path) {
            Storage::delete($previous);
        }

        return successResponse('Price document uploaded successfully', [
            'price_document'     => $path,
            'price_document_url' => $supplier->fresh()->price_document_url,
        ]);
    }

    public function supplierList()
    {
        $perPage = request('per_page', 10);
        return Contact::query()
            ->filters(new CustomerFilter)
            ->whereHas('purchases')
            ->select(['id', 'name'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
