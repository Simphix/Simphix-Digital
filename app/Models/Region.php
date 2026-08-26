<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'regional_manager_id',
    ];

    /**
     * States that belong to this region.
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * The Regional Manager assigned to this region, if any.
     */
    public function regionalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regional_manager_id');
    }
}
