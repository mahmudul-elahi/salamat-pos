<?php

namespace App\Http\Controllers\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\IncomeFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Income\Income;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Setting\SettingService;

class WithdrawalReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $queryData = Income::query()
            ->filters(new IncomeFilter())
            ->with([
                'user:id,name,profile_photo',
                'createdBy:id,name',
                'category:id,name',
                'contact:id,name,profile_picture',
                'tenant:id,name',
            ])
            ->orderByDesc('id');

        $paginated = (clone $queryData)
            ->paginate($perPage)
            ->through(fn($income) => [
                ...$income->toArray(),
                'full_invoice_number' => 'WTH-' . $income->id,
            ]);

        $withdrawalTotalSum = (clone $queryData)
            ->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'amount' => $withdrawalTotalSum,
            ],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function withdrawalReportPdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $wareHouse = $request->input('warehouse');

        if ($isAppAdmin && !$wareHouse) {
            throw new GeneralException('Select warehouse first');
        }

        $tenantId = $isAppAdmin ? $wareHouse : tenant()->id;

        $queryData = Income::query()
            ->filters(new IncomeFilter())
            ->with('user:id,name')
            ->orderByDesc('id');

        $withdrawals = (clone $queryData)->get();
        $totalWithdrawal = (clone $queryData)->sum('amount');


        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);


        $pdf = Pdf::loadView('pdf.withdrawal-report', [
            'withdrawals' => $withdrawals,
            'total_withdrawal' => $totalWithdrawal,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('withdrawal-report.pdf');
    }

    /**
     * Get transaction title based on model type
     */
    private function getTransactionTitle($transaction): string
    {
        if (!$transaction->model) {
            return $transaction->note ?? 'Withdrawal';
        }

        $modelType = class_basename($transaction->model_type);

        switch ($modelType) {
            case 'Sale':
                return 'Credit Sales';
            case 'SaleReturn':
                return 'Credit Sales Return';
            case 'Purchase':
                return 'Credit Purchase';
            case 'PurchaseReturn':
                return 'Credit Purchase Return';
            case 'Expense':
                return 'Paid By Cash';
            default:
                return $transaction->note ?? 'Withdrawal';
        }
    }
}
