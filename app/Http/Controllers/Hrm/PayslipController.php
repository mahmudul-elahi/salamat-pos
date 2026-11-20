<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Mail\Hrm\Payrun\PayslipMail;
use App\Models\Hrm\Payrun;
use App\Models\Hrm\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class PayslipController extends Controller
{
    public function index()
    {
        return Payslip::query()
            ->with(['employee', 'employee.media', 'department:id,name', 'payrun:id,name,type,uuid'])
            ->when(request('payrun_id'), fn($q, $id) => $q->where('payrun_id', $id))
            ->when(request('employee_id'), fn($q, $id) => $q->where('employee_id', $id))
            ->when(request('year'), fn($q, $year) => $q->whereYear('start_date', $year))
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn(Payslip $payslip) => [
                'id' => $payslip->id,
                'employee' => $payslip->employee?->name,
                'profile_picture' => $payslip->employee?->profile_picture,
                'email' => $payslip->employee?->email,
                'department' => $payslip->department?->name,
                'payrun' => $payslip->payrun?->name,
                'payrun_uuid' => $payslip->payrun?->uuid,
                'payrun_type' => $payslip->payrun?->type,
                'payrun_period' => $payslip->period,
                'period' => $payslip->start_date->format('d M') . ' - ' . $payslip->end_date->format('d M Y'),
                'consider_type' => $payslip->consider_type,
                'basic_salary' => $payslip->basic_salary,
                'net_salary' => $payslip->net_salary,
                'status' => $payslip->status,
                'sent_at' => $payslip->sent_at ? to_user_timezone_datetime($payslip->sent_at, 'd M Y h:i a') : null,
            ]);
    }

    public function show(Payslip $payslip)
    {
        return $payslip->load([
            'employee:id,name,email',
            'department:id,name',
            'payrun:id,name',
            'items.beneficiary:id,name',
        ]);
    }

    /**
     * Render the payslip as a PDF — inline (?preview=1) or as a download.
     */
    public function download(Payslip $payslip)
    {
        $payslip->load([
            'employee:id,name,email',
            'department:id,name',
            'payrun:id,name',
            'items.beneficiary:id,name',
        ]);

        $fileName = 'payslip-' . $payslip->employee?->name . '-' . $payslip->start_date->format('M-Y') . '.pdf';

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'logo' => config('settings.application.company_logo'),
        ])->setPaper('a4', 'portrait')->setOption('isRemoteEnabled', true);

        if (request()->boolean('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        return $pdf->download($fileName);
    }

    /**
     * Send (or re-send) a single payslip by email.
     */
    public function send(Payslip $payslip)
    {
        $payslip->loadMissing('employee:id,name,email');

        if (!$payslip->employee?->email) {
            return errorResponse('This employee has no email address');
        }

        Mail::to($payslip->employee->email)->send(new PayslipMail($payslip));

        $payslip->update(['status' => Payslip::STATUS_SENT, 'sent_at' => now()]);

        return successResponse('Payslip sent successfully');
    }

    /**
     * Email every not-yet-sent payslip for a payrun. Queued (not sent
     * synchronously) so the request returns immediately for large batches.
     */
    public function sendAll(Payrun $payrun)
    {
        $payslips = $payrun->payslips()
            ->with('employee:id,name,email')
            ->where('status', '!=', Payslip::STATUS_SENT)
            ->get();

        foreach ($payslips as $payslip) {
            if (!$payslip->employee?->email) {
                continue;
            }

            Mail::to($payslip->employee->email)->queue(new PayslipMail($payslip));
            $payslip->update(['status' => Payslip::STATUS_SENT, 'sent_at' => now()]);
        }

        return successResponse("Queued {$payslips->count()} payslip(s) for sending");
    }
}
