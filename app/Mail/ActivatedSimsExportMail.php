<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The nightly (or on-demand, via `php artisan sims:export-activated`)
 * export of every activated SIM number, sent to whichever inbox is
 * configured in Admin > SIMs Operations (SimExportSettings). The CSV is
 * generated once by the caller and handed in as a plain string — this
 * class never touches the database itself.
 */
class ActivatedSimsExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $csv,
        public int $count,
        public Carbon $generatedAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SimPhix - Activated SIMs Export (' . $this->generatedAt->format('M d, Y') . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activated_sims_export',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->csv, 'activated-sim-numbers-' . $this->generatedAt->format('Y-m-d') . '.csv')
                ->withMime('text/csv'),
        ];
    }
}
