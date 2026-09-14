<?php

namespace App\Services;

use App\Models\Sim;
use App\Support\SimStatus;
use Illuminate\Support\LazyCollection;

/**
 * The single source of truth for "every activated SIM number" — backs both
 * the on-demand "Export All Activated Numbers" button
 * (Admin\SimsOperationsDashboardController::exportActivatedNumbersCsv) and
 * the nightly emailed export (sims:export-activated / ActivatedSimsExportMail),
 * so the two never drift apart.
 */
class ActivatedSimsExportService
{
    /**
     * Lazy, memory-safe stream of every activated SIM's number, distinct
     * and ordered. Callers iterate this directly (streamed HTTP download)
     * or via toCsvString() (materialized, for an email attachment).
     */
    public function activatedNumbers(): LazyCollection
    {
        return Sim::query()
            ->where('status', SimStatus::ACTIVATED)
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->select('number')
            ->distinct()
            ->orderBy('number')
            ->lazy(1000);
    }

    /** How many distinct activated SIM numbers exist right now. */
    public function count(): int
    {
        return Sim::query()
            ->where('status', SimStatus::ACTIVATED)
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->distinct('number')
            ->count('number');
    }

    /**
     * The full CSV as a string — small enough to hold in memory (a flat
     * list of phone numbers), unlike the streamed HTTP download, which
     * writes straight to the response instead.
     */
    public function toCsvString(): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Sim Number'], ',', '"', '');

        foreach ($this->activatedNumbers() as $sim) {
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
