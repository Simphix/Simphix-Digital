<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformWithdrawalAccount extends Model
{
    protected $table = 'platform_withdrawal_account';

    protected $fillable = [
        'bank_code',
        'bank_name',
        'account_no',
        'account_name',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The single shared row, or null if never configured yet.
     */
    public static function current(): ?self
    {
        return static::first();
    }
}
