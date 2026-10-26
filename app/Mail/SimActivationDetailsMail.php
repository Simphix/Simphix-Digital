<?php

namespace App\Mail;

use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SimActivationDetailsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sim $sim,
        public ?SimCustomer $customer,
        public ?User $sender,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SimPhix - SIM Activation Details (' . $this->sim->number . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sim_activation_details',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
