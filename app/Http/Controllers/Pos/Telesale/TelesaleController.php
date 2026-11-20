<?php

namespace App\Http\Controllers\Pos\Telesale;

use App\Filters\Pos\Telesale\TelesaleFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Telesale\Telesale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelesaleController extends Controller
{
    public function index(Request $request)
    {
        return Telesale::query()
            ->filters(new TelesaleFilter)
            ->with([
                'product:id,product_name',
                'result:id,name,color',
                'salesman:id,name',
                'contact:id,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10))
            ->through(fn (Telesale $telesale) => $this->transform($telesale));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        Telesale::query()->create($this->withCustomerMatch($data) + [
            'created_by' => auth()->id(),
        ]);

        return successResponse('Telesale entry created successfully');
    }

    public function show(Telesale $telesale): array
    {
        $telesale->load(['product:id,product_name', 'result:id,name,color', 'salesman:id,name', 'contact:id,name']);

        return [
            ...$this->transform($telesale),
            // The edit form binds to the raw ids, not the display labels.
            'product_id' => $telesale->product_id,
            'custom_product' => $telesale->custom_product,
            'telesale_result_id' => $telesale->telesale_result_id,
            'salesman_id' => $telesale->salesman_id,
        ];
    }

    public function update(Request $request, Telesale $telesale): JsonResponse
    {
        $data = $this->validated($request);

        // The number can be corrected on edit, so the match is recomputed —
        // but only when it actually changed, so an entry logged as "new" isn't
        // silently flipped just because the customer was added since.
        if ($data['customer_number'] !== $telesale->customer_number) {
            $data = $this->withCustomerMatch($data);
        }

        $telesale->update($data);

        return successResponse('Telesale entry updated successfully');
    }

    public function destroy(Telesale $telesale): JsonResponse
    {
        $telesale->delete();

        return successResponse('Telesale entry deleted successfully');
    }

    /**
     * Result-only update from the list's action menu — the common follow-up is
     * "the outcome changed", and reopening the whole entry form for one field
     * is friction the agents don't need.
     */
    public function result(Request $request, Telesale $telesale): JsonResponse
    {
        $request->validate([
            'telesale_result_id' => ['nullable', 'integer', 'exists:telesale_results,id'],
        ]);

        $telesale->update(['telesale_result_id' => $request->telesale_result_id]);

        return successResponse('Result updated successfully');
    }

    /**
     * Lets the entry form flag the number as existing/new while the agent is
     * still typing, before anything is saved.
     */
    public function checkNumber(Request $request): array
    {
        $request->validate([
            'customer_number' => ['required', 'string', 'max:191'],
        ]);

        $contact = Telesale::findContactByNumber($request->customer_number);

        return [
            'exists' => (bool) $contact,
            'contact' => $contact instanceof Contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'address' => $contact->address,
                'type' => $contact->type,
            ] : null,
        ];
    }

    private function transform(Telesale $telesale): array
    {
        return [
            'id' => $telesale->id,
            'date' => $telesale->date?->format('d M Y'),
            'customer_name' => $telesale->customer_name ?? $telesale->contact?->name,
            'customer_number' => $telesale->customer_number,
            'address' => $telesale->address,
            'customer_type' => $telesale->is_existing_customer ? 'existing' : 'new',
            'contact_id' => $telesale->contact_id,
            'demand_product' => $telesale->demand_product,
            'media' => $telesale->media,
            'note' => $telesale->note,
            'result' => $telesale->result ? [
                'id' => $telesale->result->id,
                'name' => $telesale->result->name,
                'color' => $telesale->result->color,
            ] : null,
            'salesman' => $telesale->salesman?->name,
        ];
    }

    /**
     * Resolves the caller against the contact book and records both the match
     * and whether they were already known at entry time.
     */
    private function withCustomerMatch(array $data): array
    {
        $contact = Telesale::findContactByNumber($data['customer_number']);

        $data['contact_id'] = $contact?->id;
        $data['is_existing_customer'] = (bool) $contact;

        // Only fill blanks — whatever the agent typed for this call wins, since
        // a delivery address can differ from the one on file.
        if ($contact instanceof Contact) {
            if (empty($data['customer_name'])) {
                $data['customer_name'] = $contact->name;
            }

            if (empty($data['address'])) {
                $data['address'] = $contact->address;
            }
        }

        return $data;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:191'],
            'customer_number' => ['required', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:1000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'custom_product' => ['nullable', 'string', 'max:191'],
            'media' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'telesale_result_id' => ['nullable', 'integer', 'exists:telesale_results,id'],
            'salesman_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // A catalogue product wins; the free-text field is only for what we
        // don't stock, so never keep both.
        if (! empty($data['product_id'])) {
            $data['custom_product'] = null;
        }

        return $data;
    }
}
