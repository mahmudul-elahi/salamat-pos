<?php

declare(strict_types=1);

namespace App\Mail\Hrm\Employee;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmployeeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $employee,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' — Your Account is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.hrm.employee.welcome',
            with: [
                'employee' => $this->employee,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => config('app.url').'/login',
                'appName' => config('app.name'),
            ],
        );
    }
}
