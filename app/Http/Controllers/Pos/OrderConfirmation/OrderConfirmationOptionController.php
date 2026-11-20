<?php

namespace App\Http\Controllers\Pos\OrderConfirmation;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\OrderConfirmation\OrderConfirmation;
use App\Models\Pos\OrderConfirmation\OrderConfirmationOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderConfirmationOptionController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderConfirmationOption::query()
            ->ofType($request->type)
            ->when($request->search, fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id');

        // The dropdowns that consume this only want the enabled ones.
        if ($request->boolean('only_active')) {
            $query->where('status', true);
        }

        return $query->paginate($request->input('per_page', 10));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        OrderConfirmationOption::query()->create($this->validated($request));

        return successResponse('Option created successfully');
    }

    public function show(OrderConfirmationOption $orderConfirmationOption): OrderConfirmationOption
    {
        return $orderConfirmationOption;
    }

    public function update(Request $request, OrderConfirmationOption $orderConfirmationOption): \Illuminate\Http\JsonResponse
    {
        $orderConfirmationOption->update($this->validated($request, $orderConfirmationOption));

        return successResponse('Option updated successfully');
    }

    /**
     * @throws GeneralException
     */
    public function destroy(OrderConfirmationOption $orderConfirmationOption): \Illuminate\Http\JsonResponse
    {
        // Order rows null the option out on delete, which would silently blank
        // the column on old entries — refuse instead.
        if ($this->isInUse($orderConfirmationOption)) {
            throw new GeneralException('This option is already used in order confirmations. Disable it instead of deleting.');
        }

        $orderConfirmationOption->delete();

        return successResponse('Option deleted successfully');
    }

    private function isInUse(OrderConfirmationOption $option): bool
    {
        $column = match ($option->type) {
            OrderConfirmationOption::TYPE_ADVANCE_MEDIA   => 'advance_media_id',
            OrderConfirmationOption::TYPE_DELIVERY_STATUS => 'delivery_status_id',
            OrderConfirmationOption::TYPE_DELIVERY_BY     => 'delivery_by_id',
            default                                       => null,
        };

        return $column && OrderConfirmation::withTrashed()->where($column, $option->id)->exists();
    }

    private function validated(Request $request, ?OrderConfirmationOption $option = null): array
    {
        // Names only have to be unique within their own list — "Cash" can be
        // both an advance media and a courier without clashing.
        $type = $option?->type ?? $request->type;

        return $request->validate([
            'type'       => ['required', Rule::in(OrderConfirmationOption::TYPES)],
            'name'       => [
                'required',
                'string',
                'max:191',
                Rule::unique('order_confirmation_options', 'name')
                    ->where(fn($query) => $query->where('type', $type))
                    ->ignore($option?->id),
            ],
            'color'      => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status'     => ['nullable', 'boolean'],
        ]);
    }
}
