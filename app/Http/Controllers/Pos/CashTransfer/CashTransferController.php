<?php

namespace App\Http\Controllers\Pos\CashTransfer;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\CashTransfer\CashTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashTransferController extends Controller
{
    public function index()
    {
        return CashTransfer::query()
            ->with(['fromTenant:id,name', 'toTenant:id,name'])
            ->when(request('status'), fn($q, $s) => $q->where('status', $s))
            ->when(
                request('search'),
                fn($q, $search) => $q->where('reference_no', 'LIKE', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'created_at' => Carbon::parse($item->created_at)
                        ->timezone('Asia/Dhaka')
                        ->format('d-M-Y h:i A'),
                ];
            });
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_tenant_id' => 'required|integer|exists:tenants,id',
            'to_tenant_id'   => 'required|integer|exists:tenants,id|different:from_tenant_id',
            'amount'         => 'required|numeric|min:0.01',
            'transfer_date'  => 'required|date',
            'note'           => 'nullable|string|max:1000',
        ]);

        $transfer = CashTransfer::create([
            'from_tenant_id' => $request->from_tenant_id,
            'to_tenant_id'   => $request->to_tenant_id,
            'amount'         => $request->amount,
            'reference_no'   => CashTransfer::generateReferenceNo(),
            'transfer_date'  => $request->transfer_date,
            'status'         => 'draft',
            'note'           => $request->note,
            'created_by'     => auth()->id(),
        ]);

        return successResponse('Cash transfer created successfully', ['id' => $transfer->id]);
    }

    public function show(CashTransfer $cashTransfer)
    {
        $cashTransfer->load([
            'fromTenant:id,name',
            'toTenant:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
            'cancelledBy:id,name',
        ]);

        return [
            'id'            => $cashTransfer->id,
            'reference_no'  => $cashTransfer->reference_no,
            'from_tenant'   => $cashTransfer->fromTenant,
            'to_tenant'     => $cashTransfer->toTenant,
            'amount'        => $cashTransfer->amount,
            'transfer_date' => $cashTransfer->transfer_date,
            'status'        => $cashTransfer->status,
            'note'          => $cashTransfer->note,
            'created_by'    => $cashTransfer->createdBy?->name,
            'approved_by'   => $cashTransfer->approvedBy?->name,
            'cancelled_by'  => $cashTransfer->cancelledBy?->name,
            'created_at'    => Carbon::parse($cashTransfer->created_at)
                ->timezone('Asia/Dhaka')
                ->format('d-M-Y h:i A'),
        ];
    }

    public function approve(CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            throw new GeneralException('Only draft transfers can be approved.', 400);
        }

        $cashTransfer->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return successResponse('Cash transfer approved successfully.');
    }

    public function cancel(CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            throw new GeneralException('Only draft transfers can be cancelled.', 400);
        }

        $cashTransfer->update([
            'status'       => 'cancelled',
            'cancelled_by' => auth()->id(),
        ]);

        return successResponse('Cash transfer cancelled.');
    }

    public function destroy(CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            throw new GeneralException('Only draft transfers can be deleted.', 400);
        }

        $cashTransfer->delete();

        return successResponse('Cash transfer deleted successfully.');
    }
}
