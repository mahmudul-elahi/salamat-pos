<?php

namespace App\Mail\Hrm\Payrun;

use App\Models\Hrm\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Deliberately does NOT implement ShouldQueue — Laravel's Mailer silently
 * turns ->send() into ->queue() for any Mailable that does, which would
 * break the single "send now" action. Bulk sending instead opts in
 * explicitly via Mail::queue() at the call site (see PayslipController).
 */
class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payslip $payslip)
    {
        $this->payslip->loadMissing(['employee:id,name,email', 'items.beneficiary:id,name']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Payslip — '.$this->payslip->start_date->format('M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.hrm.payslip',
            with: ['payslip' => $this->payslip],
        );
    }
}
