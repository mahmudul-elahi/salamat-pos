<?php

namespace App\Jobs\Auth;

use App\Helpers\Setting\SetEmailConfig;
use App\Mail\Auth\ForgotPasswordMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $user, protected $token)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(new ForgotPasswordMail($this->user, $this->token));
    }

    public function middleware(): array
    {
        resolve(SetEmailConfig::class)->set();
        return [];
    }
}
