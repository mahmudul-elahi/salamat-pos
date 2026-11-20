<?php

namespace App\Mail\Auth;

use App\Helpers\Notification\NotificationHelper;
use App\Mail\Tags\Auth\PasswordChangeConfirmationTag;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangeConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $template;

    public function __construct(protected $user)
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
        $tags = new PasswordChangeConfirmationTag($this->user);

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
            ->on('password_change_confirmation')
            ->mail();
    }
}
