<?php

namespace App\Http\Controllers\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\DepositReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Investment\Investment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\Setting\SettingService;

class DepositReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $transactionsQuery = Investment::query()
            ->filters(new DepositReportFilter())
            ->with([
                'user:id,name,profile_photo',
                'createdBy:id,name',
                'investmentCategory:id,name',
                'contact:id,name,profile_picture',
                'tenant:id,name',
            ])
            ->orderByDesc('id');


        $paginated = (clone $transactionsQuery)
            ->paginate($perPage)
            ->through(fn($investment) => [
                ...$investment->toArray(),
                'full_invoice_number' => 'INV-' . $investment->id,
            ]);

        $totalDeposit = (clone $transactionsQuery)->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'amount' => $totalDeposit,
            ],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function depositReportPdf(Request $request)
    {
        $isAdmin = auth()->user()->isAppAdmin();
        $warehouse = $request->get('warehouse');

        if ($isAdmin && !$warehouse) {
            throw new GeneralException('Select warehouse first');
        }
        $tenantId = $isAdmin ? $warehouse : tenant()->id;

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $deposits = Investment::query()
            ->filters(new DepositReportFilter())
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get();

        $totalDeposit = Investment::query()
            ->filters(new DepositReportFilter())
            ->sum('amount');


        $pdf = Pdf::loadView('pdf.deposit-report', [
            'deposits' => $deposits,
            'totalDeposit' => $totalDeposit,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('deposit-report.pdf');
    }

}
