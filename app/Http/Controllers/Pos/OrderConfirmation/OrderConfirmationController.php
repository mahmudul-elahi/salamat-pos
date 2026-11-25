<?php

namespace App\Http\Controllers\Pos\OrderConfirmation;

use App\Enums\StatusEnum;
use App\Filters\Pos\OrderConfirmation\OrderConfirmationFilter;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order\Order;
use App\Models\Ecommerce\Order\OrderList;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\OrderConfirmation\OrderConfirmation;
use App\Models\Pos\OrderConfirmation\OrderConfirmationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderConfirmationController extends Controller
{
    private const array RELATIONS = [
        'items.product:id,product_name',
        'items.variant.attributeValues.attribute',
        'orderPerson:id,name',
        'advanceMedia:id,name,color',
        'deliveryStatus:id,name,color',
        'deliveryBy:id,name,color',
        'order:id,invoice_number',
    ];

    public function index(Request $request)
    {
        return OrderConfirmation::query()
            ->filters(new OrderConfirmationFilter)
            ->with(self::RELATIONS)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10))
            ->through(fn (OrderConfirmation $order) => $this->transform($order));
    }

    public function store(Request $request): JsonResponse
    {
        [$data, $items] = $this->validated($request);

        DB::transaction(function () use ($data, $items) {
            $orderConfirmation = OrderConfirmation::query()->create($data + ['created_by' => auth()->id()]);
            $orderConfirmation->items()->createMany($items);

            // Adding it here already means the agent has the order in hand, so
            // it immediately raises a real Order (status Confirmed) rather than
            // waiting on the separate "called and confirmed" step.
            $order = $this->createOrderFromConfirmation($orderConfirmation);
            $orderConfirmation->update(['order_id' => $order->id]);
        });

        return successResponse('Order created successfully');
    }

    public function show(OrderConfirmation $orderConfirmation): array
    {
        $orderConfirmation->load(self::RELATIONS);

        return [
            ...$this->transform($orderConfirmation),
            // The edit form binds to the raw ids and unformatted dates, not the
            // display labels the list uses.
            'date_raw' => $orderConfirmation->date?->toDateString(),
            'confirmation_date_raw' => $orderConfirmation->confirmation_date?->toDateString(),
            'order_person_id' => $orderConfirmation->order_person_id,
            'advance_media_id' => $orderConfirmation->advance_media_id,
            'delivery_status_id' => $orderConfirmation->delivery_status_id,
            'delivery_by_id' => $orderConfirmation->delivery_by_id,
        ];
    }

    public function update(Request $request, OrderConfirmation $orderConfirmation): JsonResponse
    {
        [$data, $items] = $this->validated($request);

        // Replace rather than diff: the form always posts the full set of
        // lines, so anything missing from it was deleted by the agent.
        DB::transaction(function () use ($orderConfirmation, $data, $items) {
            $orderConfirmation->update($data);
            $orderConfirmation->items()->delete();
            $orderConfirmation->items()->createMany($items);
        });

        return successResponse('Order updated successfully');
    }

    public function destroy(OrderConfirmation $orderConfirmation): JsonResponse
    {
        $orderConfirmation->delete();

        return successResponse('Order deleted successfully');
    }

    /**
     * One-click "I called them and they confirmed" from the list, which is the
     * single most repeated action on this screen. The real Order was already
     * raised when this entry was added (see store()), so this only tracks the
     * call itself.
     */
    public function confirm(Request $request, OrderConfirmation $orderConfirmation): JsonResponse
    {
        $request->validate([
            'confirmation_date' => ['nullable', 'date'],
        ]);

        $orderConfirmation->update([
            'confirmation_date' => $request->input('confirmation_date', now()->toDateString()),
        ]);

        return successResponse('Order confirmed successfully');
    }

    /**
     * Raise a real Order from a newly added order confirmation. There is no
     * warehouse/shop on an order confirmation - one is only picked later,
     * same as any other Confirmed order, when this gets Approved via the
     * serial-number step. For the same reason there is no district/area here
     * either; shipping is a flat amount typed on the form rather than one
     * computed from a delivery area. The free-text address carries over as-is.
     */
    private function createOrderFromConfirmation(OrderConfirmation $orderConfirmation): Order
    {
        $orderConfirmation->loadMissing('items');

        $order = Order::query()->create([
            'contact_id' => $orderConfirmation->contact_id,
            'payment_method' => 'cod',
            'address' => $orderConfirmation->address,
            'sub_total' => $orderConfirmation->selling_price,
            'shipping_charge' => $orderConfirmation->shipping_charge,
            'discount_total' => 0,
            'grand_total' => $orderConfirmation->selling_price + $orderConfirmation->shipping_charge,
            'payable_amount' => $orderConfirmation->advance_amount,
            'due_amount' => $orderConfirmation->cod,
            'invoice_number' => $this->generateOrderInvoiceNumber(),
            'status' => StatusEnum::Confirmed->value,
            'customer_note' => $orderConfirmation->note,
            'custom_value' => json_encode(['customer_name' => $orderConfirmation->customer_name]),
        ]);

        foreach ($orderConfirmation->items as $item) {
            // A legacy free-text line (no catalogue product) has nothing to
            // point an order line at, so it's left out of the raised order.
            if (! $item->product_id) {
                continue;
            }

            OrderList::query()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'purchase_point' => 0,
                'status' => 1,
            ]);
        }

        return $order;
    }

    private function generateOrderInvoiceNumber(): string
    {
        $maxOrderId = Order::query()->max('id') ?? 0;

        return 'ORD-'.date('Ymd').'-'.str_pad($maxOrderId + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Delivery-leg update from the list's action menu — status, courier,
     * invoice and tracking link move long after the order was first taken.
     */
    public function delivery(Request $request, OrderConfirmation $orderConfirmation): JsonResponse
    {
        $orderConfirmation->update($request->validate([
            'delivery_status_id' => ['nullable', 'integer', 'exists:order_confirmation_options,id'],
            'delivery_by_id' => ['nullable', 'integer', 'exists:order_confirmation_options,id'],
            'invoice_no' => ['nullable', 'string', 'max:191'],
            'track_url' => ['nullable', 'url', 'max:500'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
        ]));

        return successResponse('Delivery info updated successfully');
    }

    private function transform(OrderConfirmation $order): array
    {
        return [
            'id' => $order->id,
            'date' => $order->date?->format('d M Y'),
            'confirmation_date' => $order->confirmation_date?->format('d M Y'),
            'is_confirmed' => $order->is_confirmed,
            'order_id' => $order->order_id,
            'order_invoice_number' => $order->order?->invoice_number,
            'contact_id' => $order->contact_id,
            'customer_name' => $order->customer_name,
            'mobile' => $order->mobile,
            'address' => $order->address,
            'source_media' => $order->source_media,
            'order_type' => $order->order_type,
            'product_name' => $order->product_name,
            'items' => $order->items->map(fn (OrderConfirmationItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'variant_attributes' => $item->variant_attributes,
                'custom_product' => $item->custom_product,
                'product_name' => $item->product_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => $item->line_total,
            ])->values(),
            'order_person' => $order->orderPerson?->name,
            'advance_media' => $this->option($order->advanceMedia),
            'advance_amount' => (float) $order->advance_amount,
            'selling_price' => (float) $order->selling_price,
            'shipping_charge' => (float) $order->shipping_charge,
            'cod' => (float) $order->cod,
            'note' => $order->note,
            'delivery_status' => $this->option($order->deliveryStatus),
            'invoice_no' => $order->invoice_no,
            'delivery_by' => $this->option($order->deliveryBy),
            'track_url' => $order->track_url,
            'delivery_note' => $order->delivery_note,
        ];
    }

    private function option($option): ?array
    {
        return $option ? [
            'id' => $option->id,
            'name' => $option->name,
            'color' => $option->color,
        ] : null;
    }

    /**
     * @return array{0: array, 1: array} the order columns, and its item rows
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.custom_product' => ['nullable', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'address' => ['required', 'string', 'max:1000'],
            'source_media' => ['nullable', 'string', 'max:50'],
            'order_type' => ['nullable', 'in:online,offline'],
            'order_person_id' => ['nullable', 'integer', 'exists:users,id'],
            'advance_media_id' => ['nullable', 'integer', 'exists:order_confirmation_options,id'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'cod' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
            'delivery_status_id' => ['nullable', 'integer', 'exists:order_confirmation_options,id'],
            'invoice_no' => ['nullable', 'string', 'max:191'],
            'delivery_by_id' => ['nullable', 'integer', 'exists:order_confirmation_options,id'],
            'track_url' => ['nullable', 'url', 'max:500'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
        ]);

        // Name and mobile always come from the picked contact — it is the only
        // way to set a customer here, so there is nothing to type and nothing
        // to keep in sync by hand. They are still stored on the row so the list
        // and search stay fast and the order keeps a record of who it was for.
        $contact = Contact::find($data['contact_id']);

        $data['customer_name'] = $contact->name;
        $data['mobile'] = $contact->phone;

        $items = collect($data['items'])->map(function (array $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            return [
                // A catalogue product wins; the free-text field is only for
                // what we don't stock, so never keep both.
                'product_id' => $item['product_id'] ?? null,
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'custom_product' => empty($item['product_id']) ? ($item['custom_product'] ?? null) : null,
                // Decimal casts want strings; handing them floats is deprecated
                // in brick/math and rounds unpredictably.
                'quantity' => number_format($quantity, 2, '.', ''),
                'unit_price' => number_format($unitPrice, 2, '.', ''),
            ];
        })->all();

        unset($data['items']);

        $data['order_type'] ??= 'online';
        $data['advance_amount'] = number_format((float) ($data['advance_amount'] ?? 0), 2, '.', '');
        $data['shipping_charge'] = number_format((float) ($data['shipping_charge'] ?? 0), 2, '.', '');
        $data['cod'] = number_format((float) ($data['cod'] ?? 0), 2, '.', '');

        // Derived, never posted — the order total is the sum of its lines, so
        // there is no way for the two to drift apart.
        $data['selling_price'] = number_format(
            collect($items)->sum(fn (array $item) => (float) $item['quantity'] * (float) $item['unit_price']),
            2,
            '.',
            ''
        );

        return [$data, $items];
    }
}
