<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CacRegistrationEvent extends Model
{
    protected $fillable = [
        'cac_registration_id', 'user_id', 'type', 'status_from', 'status_to', 'message', 'is_admin', 'visible_to_user',
    ];

    protected $casts = ['is_admin' => 'boolean', 'visible_to_user' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
