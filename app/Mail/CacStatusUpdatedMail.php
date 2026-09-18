<?php

namespace App\Mail;

use App\Models\CacRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CacStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CacRegistration $registration, public string $updateMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'SmartSIM CAC Registration ' . $this->registration->reference . ' — Update');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cac_status_updated');
    }
}
