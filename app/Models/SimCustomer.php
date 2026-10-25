<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sim_id',
        'partner_id',
        'full_name',
        'email',
        'phone_number',
        'address',
    ];

    /**
     * The SIM card that was activated for this customer.
     */
    public function sim(): BelongsTo
    {
        return $this->belongsTo(Sim::class, 'sim_id');
    }

    /**
     * The partner/agent who activated the SIM.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
