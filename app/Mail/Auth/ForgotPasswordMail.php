<?php

namespace App\Mail\Auth;

use App\Helpers\Notification\NotificationHelper;
use App\Mail\Tags\Auth\ForgotPasswordTag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $template;

    /**
     * Create a new message instance.
     */
    public function __construct(protected $user, protected $token)
    {
        $this->template = $this->template();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subjectReplace(['{app_name}' => config('settings.application.company_name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $tags = new ForgotPasswordTag($this->user, $this->token);

        return new Content(
            view: 'mail.template',
            with: ['template' => $this->template->parse($tags->tag())]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function template()
    {
        return NotificationHelper::new()
            ->on('forget_password')
            ->mail();
    }
}
