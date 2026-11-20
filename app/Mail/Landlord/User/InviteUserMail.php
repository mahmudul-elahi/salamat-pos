<?php

namespace App\Mail\Landlord\User;

use App\Helpers\Notification\NotificationHelper;
use App\Mail\Tags\Landlord\UserInvitationTag;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteUserMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $template;

    /**
     * Create a new message instance.
     */
    public function __construct(protected $user, protected $userProfile)
    {
        $this->template = $this->template();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subjectReplace(['{app_name}' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $tags = new UserInvitationTag($this->user, $this->userProfile);

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
            ->on('user_invitation')
            ->mail();
    }
}
