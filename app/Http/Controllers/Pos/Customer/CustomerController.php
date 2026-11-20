<?php

namespace App\Http\Controllers\Pos\Customer;

use App\Filters\Landlord\Common\NameFilter;
use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Customer\CustomerRequest;
use App\Models\Customer\Customer;
use App\Models\Pos\Contact\Contact;

class CustomerController extends Controller
{
    use \App\Http\Controllers\Concerns\StoresPriceDocument;

    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $perPage = request('per_page', 10);

        return Contact::query()
            ->filters(new CustomerFilter)
            ->where('type', 'consumer')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(CustomerRequest $request): \Illuminate\Http\JsonResponse
    {
        $customer = Contact::query()->create(array_merge($request->validated(), [
            'type' => 'consumer'
        ]));

        // Callers that create a customer inline (e.g. the "+" beside the
        // customer picker on Add Order) need the new record back so they can
        // select it immediately, instead of it being created but left
        // unselected on the parent form.
        return successResponse('Customer created successfully', $customer->only(['id', 'name', 'phone', 'address']));
    }

    /**
     * Display the specified resource.
     */

    public function show(Contact $customer): Contact
    {
        return $customer;
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(CustomerRequest $request, Contact $customer): \Illuminate\Http\JsonResponse
    {
        $customer->update($request->validated());
        return successResponse('Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Contact $customer): \Illuminate\Http\JsonResponse
    {
        $customer->delete();
        return successResponse('Customer deleted successfully');
    }

    public function rating(\Illuminate\Http\Request $request, Contact $customer): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        $customer->update(['rating' => $request->rating]);

        return successResponse('Customer rating updated successfully');
    }

    public function note(\Illuminate\Http\Request $request, Contact $customer): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer->update(['note' => $request->note]);

        return successResponse('Customer note updated successfully');
    }

    /**
     * Uploading replaces whatever price document was there before, file on disk
     * included. No disk is named — the file lands wherever FILESYSTEM_DISK
     * points. `txt` is allowed because spreadsheets often export CSVs as
     * text/plain.
     */
    public function priceDocumentChunk(
        \Illuminate\Http\Request $request,
        Contact $customer,
        \App\Services\Upload\ChunkedUploadService $chunks
    ): \Illuminate\Http\JsonResponse {
        return $this->receivePriceDocumentChunk($request, $customer, 'customer-price-documents', $chunks);
    }

    public function priceDocument(\Illuminate\Http\Request $request, Contact $customer): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'price_document' => ['required', 'file', 'mimes:pdf,xls,xlsx,csv,txt,png,jpg,jpeg', 'max:10240'],
        ]);

        $previous = $customer->price_document;

        $path = $request->file('price_document')->store('customer-price-documents');

        $customer->update(['price_document' => $path]);

        if ($previous && $previous !== $path) {
            \Illuminate\Support\Facades\Storage::delete($previous);
        }

        return successResponse('Price document uploaded successfully', [
            'price_document'     => $path,
            'price_document_url' => $customer->fresh()->price_document_url,
        ]);
    }

    public function customerList()
    {
        $perPage = request('per_page', 10);

        return Contact::query()
            ->filters(new CustomerFilter)
            ->whereHas('sales')
            ->select(['id', 'name'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
