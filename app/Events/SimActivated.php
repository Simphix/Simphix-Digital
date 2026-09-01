<?php

namespace App\Events;

use App\Models\Sim;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Sim $sim,
        public User $actor,
        public bool $awardCommission = true,
    ) {
    }
}
