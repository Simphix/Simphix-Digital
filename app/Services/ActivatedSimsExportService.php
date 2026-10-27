<?php

namespace App\Services;

use App\Models\Sim;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * The single source of truth for activated SIM numbers.
 * Supports both:
 * 1. The on-demand "Export All Activated Numbers" button
 *    (Admin\SimsOperationsDashboardController::exportActivatedNumbersCsv) - exports all activated numbers.
 * 2. The nightly emailed export (sims:export-activated / ActivatedSimsExportMail)
 *    which exports only numbers activated in the past 24 hours.
 */
class ActivatedSimsExportService
{
    /**
     * Base query for activated SIMs, optionally filtered to those
     * activated within a given timestamp range (e.g. previous calendar day).
     *
     * Uses SimHistory records (to_status = ACTIVATED) as the authoritative
     * source of activation events, or falls back to Sim::created_at for SIMs
     * created directly in ACTIVATED status with no history.
     * Does NOT use updated_at, preventing unrelated updates (e.g. top-ups, edits)
     * from falsely counting as activations.
     */
    public function query(?Carbon $since = null, ?Carbon $until = null): Builder
    {
        $query = Sim::query()
            ->where('status', SimStatus::ACTIVATED)
            ->whereNotNull('number')
            ->where('number', '!=', '');

        if ($since && $until) {
            $query->where(function (Builder $q) use ($since, $until) {
                $q->whereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$since, $until]))
                  ->orWhere(fn ($q2) => $q2->whereDoesntHave('history')->whereBetween('created_at', [$since, $until]));
            });
        } elseif ($since) {
            $query->where(function (Builder $q) use ($since) {
                $q->whereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->where('created_at', '>=', $since))
                  ->orWhere(fn ($q2) => $q2->whereDoesntHave('history')->where('created_at', '>=', $since));
            });
        } elseif ($until) {
            $query->where(function (Builder $q) use ($until) {
                $q->whereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->where('created_at', '<=', $until))
                  ->orWhere(fn ($q2) => $q2->whereDoesntHave('history')->where('created_at', '<=', $until));
            });
        }

        return $query;
    }

    /**
     * Lazy, memory-safe stream of activated SIM numbers, distinct
     * and ordered. Callers iterate this directly (streamed HTTP download)
     * or via toCsvString() (materialized, for an email attachment).
     */
    public function activatedNumbers(?Carbon $since = null, ?Carbon $until = null): LazyCollection
    {
        return $this->query($since, $until)
            ->select('number')
            ->distinct()
            ->orderBy('number')
            ->lazy(1000);
    }

    /** How many distinct activated SIM numbers exist matching the criteria. */
    public function count(?Carbon $since = null, ?Carbon $until = null): int
    {
        return $this->query($since, $until)
            ->distinct('number')
            ->count('number');
    }

    /**
     * The full CSV as a string — small enough to hold in memory (a flat
     * list of phone numbers), unlike the streamed HTTP download, which
     * writes straight to the response instead.
     */
    public function toCsvString(?Carbon $since = null, ?Carbon $until = null): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Sim Number'], ',', '"', '');

        foreach ($this->activatedNumbers($since, $until) as $sim) {
            fputcsv($handle, [$this->escapeForCsv((string) $sim->number)], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Guards against CSV/formula injection when a spreadsheet app opens
     * this file — a number starting with =, +, -, @, or a tab/CR/LF is
     * otherwise interpreted as a formula by Excel/Sheets.
     */
    public function escapeForCsv(string $value): string
    {
        return preg_match('/^[=+\-@\t\r\n]/', $value) ? "'" . $value : $value;
    }
}
