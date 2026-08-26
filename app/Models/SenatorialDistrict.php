<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenatorialDistrict extends Model
{
    protected $fillable = [
        'name',
        'state_id',
        'coordinator_id',
    ];

    /**
     * The state this district belongs to.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The Coordinator assigned to this district, if any.
     */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }
}
