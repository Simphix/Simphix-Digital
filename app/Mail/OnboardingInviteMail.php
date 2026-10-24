<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $invitee,
        public User $onboarder,
        public string $acceptUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join SimPhix',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-invite',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
