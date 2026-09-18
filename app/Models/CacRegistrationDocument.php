<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CacRegistrationDocument extends Model
{
    protected $fillable = [
        'cac_registration_id', 'slot', 'label', 'path', 'original_name', 'uploaded_by', 'is_deliverable',
    ];

    protected $casts = ['is_deliverable' => 'boolean'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CacRegistration::class, 'cac_registration_id');
    }
}
