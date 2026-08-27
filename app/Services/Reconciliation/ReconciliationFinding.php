<?php

namespace App\Services\Reconciliation;

/**
 * A single detected mismatch/gap surfaced by a Reconciler. Read-only value
 * object — reconciliation is detection-only in this app (no in-dashboard
 * corrective actions), so a finding never carries a "fix this" callback.
 */
final class ReconciliationFinding
{
    public function __construct(
        public readonly string $category,
        public readonly string $severity,
        public readonly string $summary,
        public readonly array $details = [],
        public readonly array $related_ids = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'severity' => $this->severity,
            'summary' => $this->summary,
            'details' => $this->details,
            'related_ids' => $this->related_ids,
        ];
    }
}
