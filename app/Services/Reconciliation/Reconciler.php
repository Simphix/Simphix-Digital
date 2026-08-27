<?php

namespace App\Services\Reconciliation;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface Reconciler
{
    /**
     * @return Collection<int, ReconciliationFinding>
     */
    public function findings(Carbon $from, Carbon $to): Collection;
}
