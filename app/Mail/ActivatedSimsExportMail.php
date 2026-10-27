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
 * export of activated SIM numbers, sent to whichever inbox is
 * configured in Admin > SIMs Operations (SimExportSettings).
 */
class ActivatedSimsExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $csv,
        public int $count,
        public Carbon $generatedAt,
        public ?Carbon $since = null,
        public ?Carbon $until = null,
        public ?string $periodLabel = null,
        public ?Carbon $reportDate = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->periodLabel === 'all-time' => 'SimPhix - Activated SIMs Export (All-Time)',
            !empty($this->reportDate) => 'SimPhix - Daily Activated SIMs Export (' . $this->reportDate->format('M d, Y') . ')',
            !empty($this->since) => 'SimPhix - Daily Activated SIMs Export (' . $this->generatedAt->format('M d, Y') . ')',
            default => 'SimPhix - Activated SIMs Export (' . $this->generatedAt->format('M d, Y') . ')',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activated_sims_export',
            with: [
                'csv' => $this->csv,
                'count' => $this->count,
                'generatedAt' => $this->generatedAt,
                'since' => $this->since,
                'until' => $this->until,
                'periodLabel' => $this->periodLabel,
                'reportDate' => $this->reportDate,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $dateSuffix = $this->reportDate
            ? $this->reportDate->format('Y-m-d')
            : $this->generatedAt->format('Y-m-d');

        $prefix = $this->since ? 'daily-activated-sims-' : 'activated-sim-numbers-';

        return [
            Attachment::fromData(fn () => $this->csv, $prefix . $dateSuffix . '.csv')
                ->withMime('text/csv'),
        ];
    }
}
