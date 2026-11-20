<?php

namespace App\Http\Controllers\Pos\Transaction;

use App\Filters\Pos\Transaction\TransactionSaleFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Transaction\Transaction;

class TransactionController extends Controller
{
    public function saleTransaction()
    {
        return Transaction::query()
            ->filters(new TransactionSaleFilter())
            ->sales()
            ->with(['model:id,contact_id,status,grand_total', 'model.contact:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page'))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'customer' => $item->model->contact,
                    'status' => $item->model->status,
                    'grand_total' => $item->model->grand_total,
                    'due_amount' => $item->model->grand_total - $item->amount,
                ];
            });


    }

    public function purchaseTransaction()
    {
        return Transaction::query()
            ->filters(new TransactionSaleFilter())
            ->purchases()
            ->with(['model:id,contact_id,status,grand_total', 'model.contact:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page'))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'supplier' => $item->model->contact,
                    'status' => $item->model->status,
                    'grand_total' => $item->model->grand_total,
                    'due_amount' => $item->model->grand_total - $item->amount,
                ];
            });
    }

    public function purchaseReturnTransaction()
    {
        return Transaction::query()
            ->purchaseReturns()
            ->with(['model:id,contact_id,status,grand_total', 'model.contact:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page'))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'supplier' => $item->model->contact,
                    'status' => $item->model->status,
                    'grand_total' => $item->model->grand_total,
                    'due_amount' => $item->model->grand_total - $item->amount,
                ];
            });
    }

    public function saleReturnTransaction()
    {
        return Transaction::query()
            ->saleReturns()
            ->with(['model:id,contact_id,status,grand_total', 'model.contact:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page'))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'customer' => $item->model->contact,
                    'status' => $item->model->status,
                    'grand_total' => $item->model->grand_total,
                    'due_amount' => $item->model->grand_total - $item->amount,
                ];
            });
    }

    public function expenseTransaction()
    {
        return Transaction::query()
            ->filters(new TransactionSaleFilter())
            ->expenses()
            ->with(['model:id,title,amount,status', 'paymentMethod:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page'))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'title' => $item->model->title,
                    'status' => $item->model->status,
                    'grand_total' => $item->model->amount,
                    'due_amount' => $item->model->amount - $item->amount,
                ];
            });
    }
}
