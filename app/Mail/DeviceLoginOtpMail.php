<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeviceLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $deviceLabel,
        public ?string $ip,
        public string $requestedAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Device Sign-In Verification - SmartSIM',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.device-otp',
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
