<?php

namespace App\Http\Controllers\Pos\People;

use App\Enums\OnlinePaymentStatusEnum;
use App\Enums\StatusEnum;
use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Concerns\StoresPriceDocument;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Elite\ContactEliteHistory;
use App\Models\Ecommerce\Order\Order;
use App\Models\Ecommerce\Review\Review;
use App\Models\Pos\Address\CustomerAddress;
use App\Models\Pos\Contact\Contact;
use App\Models\SellDeviceRequest\SellDeviceRequest;
use App\Services\Upload\ChunkedUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineContactController extends Controller
{
    use StoresPriceDocument;

    public function priceDocumentChunk(
        Request $request,
        Contact $onlineContact,
        ChunkedUploadService $chunks
    ): JsonResponse {
        return $this->receivePriceDocumentChunk($request, $onlineContact, 'customer-price-documents', $chunks);
    }

    public function index()
    {
        return Contact::query()
            ->filters(new CustomerFilter)
            ->where('online', true)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($contact) => [
                ...$contact->toArray(),
                'created_at' => $contact->created_at->diffForHumans(),
            ]);
    }

    public function destroy(Contact $onlineContact): JsonResponse
    {
        $onlineContact->delete();

        return successResponse('Customer deleted successfully');
    }

    public function show(Contact $onlineContact)
    {
        $statusCounts = Order::where('contact_id', $onlineContact->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            ...$onlineContact->toArray(),
            'created_at' => $onlineContact->created_at->diffForHumans(),
            'order_stats' => [
                'total' => $statusCounts->sum(),
                'pending' => (int) $statusCounts->get(StatusEnum::Pending->value, 0),
                'approved' => (int) $statusCounts->get(StatusEnum::Approved->value, 0),
                'processing' => (int) $statusCounts->get(StatusEnum::Processing->value, 0),
                'on_way' => (int) $statusCounts->get(StatusEnum::OnWay->value, 0),
                'complete' => (int) $statusCounts->get(StatusEnum::Complete->value, 0),
                'rejected' => (int) $statusCounts->get(StatusEnum::Rejected->value, 0),
            ],
        ];
    }

    /**
     * Backs both the "Rate Customer" and "Note" actions on the contact list —
     * each modal PATCHes only its own field, so both are optional here and only
     * what was sent gets written.
     */
    public function update(Request $request, Contact $onlineContact)
    {
        $validated = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (empty($validated)) {
            return successResponse('Nothing to update');
        }

        $onlineContact->update($validated);

        return successResponse(
            array_key_exists('note', $validated)
                ? 'Customer note updated successfully'
                : 'Customer rating updated successfully'
        );
    }

    /**
     * Uploading replaces whatever price document was there before, file on disk
     * included. No disk is named — the file lands wherever FILESYSTEM_DISK
     * points. `txt` is allowed because spreadsheets often export CSVs as
     * text/plain.
     */
    public function priceDocument(Request $request, Contact $onlineContact)
    {
        $request->validate([
            'price_document' => ['required', 'file', 'mimes:pdf,xls,xlsx,csv,txt,png,jpg,jpeg', 'max:10240'],
        ]);

        $previous = $onlineContact->price_document;

        $path = $request->file('price_document')->store('customer-price-documents');

        $onlineContact->update(['price_document' => $path]);

        if ($previous && $previous !== $path) {
            Storage::delete($previous);
        }

        return successResponse('Price document uploaded successfully', [
            'price_document' => $path,
            'price_document_url' => $onlineContact->fresh()->price_document_url,
        ]);
    }

    public function orders(Contact $onlineContact)
    {
        return Order::where('contact_id', $onlineContact->id)
            ->with(['items.product:id,product_name', 'items.variant:id,sku', 'items.preOwned.product:id,product_name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($order) => [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'status' => StatusEnum::tryFrom($order->status)?->name ?? $order->status,
                'grand_total' => $order->grand_total,
                'payable_amount' => $order->payable_amount,
                'due_amount' => $order->due_amount,
                'payment_method' => $order->payment_method,
                'payment_status' => OnlinePaymentStatusEnum::tryFrom($order->payment_status)?->name ?? $order->payment_status,
                'created_at' => $order->created_at->format('d M Y'),
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product?->product_name ?? $item->preOwned?->product?->product_name ?? 'Unknown',
                    'variant' => $item->variant?->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->final_price,
                ]),
            ]);
    }

    public function eliteHistories(Contact $onlineContact)
    {
        return ContactEliteHistory::where('contact_id', $onlineContact->id)
            ->with('elite:id,status_name,logo,tagline,point')
            ->orderByDesc('achieved_at')
            ->get()
            ->map(fn ($history) => [
                'id' => $history->id,
                'elite' => $history->elite,
                'achieved_at' => $history->achieved_at?->format('d M Y'),
            ]);
    }

    public function addresses(Contact $onlineContact)
    {
        return CustomerAddress::where('contact_id', $onlineContact->id)
            ->with(['area', 'district'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($addr) => [
                'id' => $addr->id,
                'label' => $addr->label ? ucfirst($addr->label) : null,
                'street' => $addr->street,
                'area' => $addr->area?->name,
                'district' => $addr->district?->name,
                'is_default_shipping' => (bool) $addr->is_default_shipping,
                'is_default_billing' => (bool) $addr->is_default_billing,
                'full_address' => $addr->full_address,
            ]);
    }

    public function cart(Contact $onlineContact)
    {
        $cart = DB::table('carts')->where('contact_id', $onlineContact->id)->first();

        if (! $cart) {
            return ['items' => [], 'grand_total' => 0, 'sub_total' => 0];
        }

        $items = DB::table('cart_items')
            ->where('cart_id', $cart->id)
            ->get()
            ->map(function ($item) {
                $product = DB::table('products')
                    ->where('id', $item->product_id)
                    ->whereNull('deleted_at')
                    ->select('id', 'product_name', 'thumbnail_image')
                    ->first();

                $variant = $item->product_variant_id
                    ? DB::table('product_variants')->where('id', $item->product_variant_id)->whereNull('deleted_at')->value('sku')
                    : null;

                return [
                    'product_name' => $product?->product_name ?? 'Unknown',
                    'thumbnail' => $product?->thumbnail_image,
                    'variant' => $variant,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount_amount,
                    'is_select' => (bool) $item->is_select,
                    'attributes' => $item->attributes,
                ];
            });

        return [
            'items' => $items,
            'sub_total' => $cart->sub_total,
            'grand_total' => $cart->grand_total,
        ];
    }

    public function wishlist(Contact $onlineContact)
    {
        $wishlist = DB::table('wishlists')->where('contact_id', $onlineContact->id)->first();

        if (! $wishlist) {
            return ['items' => [], 'grand_total' => 0];
        }

        $items = DB::table('wishlist_items')
            ->where('wishlist_id', $wishlist->id)
            ->get()
            ->map(function ($item) {
                $product = DB::table('products')
                    ->where('id', $item->product_id)
                    ->whereNull('deleted_at')
                    ->select('id', 'product_name', 'thumbnail_image')
                    ->first();

                $variant = $item->product_variant_id
                    ? DB::table('product_variants')->where('id', $item->product_variant_id)->whereNull('deleted_at')->value('sku')
                    : null;

                return [
                    'product_name' => $product?->product_name ?? 'Unknown',
                    'thumbnail' => $product?->thumbnail_image,
                    'variant' => $variant,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'attributes' => $item->attributes,
                ];
            });

        return [
            'items' => $items,
            'grand_total' => $wishlist->grand_total,
        ];
    }

    public function reviews(Contact $onlineContact)
    {
        return Review::where('contact_id', $onlineContact->id)
            ->with('product:id,product_name,product_image')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($review) => [
                'id' => $review->id,
                'product' => $review->product ? [
                    'id' => $review->product->id,
                    'name' => $review->product->product_name,
                    'thumbnail' => $review->product->product_image,
                ] : null,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status == 1 ? 'Approved' : 'Pending',
                'created_at' => $review->created_at->format('d M Y'),
            ]);
    }

    public function sellDeviceRequests(Contact $onlineContact)
    {
        return SellDeviceRequest::where('contact_id', $onlineContact->id)
            ->with(['images', 'category:id,name', 'brand:id,name', 'newDistrict:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($request) => [
                'id' => $request->id,
                'ad_title' => $request->ad_title,
                'category' => $request->category?->name,
                'brand' => $request->brand?->name,
                'district' => $request->newDistrict?->name,
                'model' => $request->model,
                'condition' => $request->condition,
                'ram' => $request->ram,
                'storage' => $request->storage,
                'color' => $request->color,
                'is_exchange_possible' => $request->is_exchange_possible,
                'asking_price' => $request->asking_price,
                'is_negotiable' => $request->is_negotiable,
                'video_url' => $request->video_url,
                'description' => $request->description,
                'phone_number' => $request->phone_number,
                'status' => $request->status->name,
                'thumbnail' => $this->resolveImageUrl(
                    $request->images->firstWhere('is_primary', true)?->image
                        ?? $request->images->first()?->image
                ),
                'images' => $request->images->pluck('image')->map(fn ($image) => $this->resolveImageUrl($image)),
                'created_at' => $request->created_at->format('d M Y'),
            ]);
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::url($path);
    }
}
