<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = [
        'name',
        'region_id',
    ];

    /**
     * The region this state currently belongs to, if any.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Senatorial districts within this state.
     */
    public function senatorialDistricts(): HasMany
    {
        return $this->hasMany(SenatorialDistrict::class);
    }
}
