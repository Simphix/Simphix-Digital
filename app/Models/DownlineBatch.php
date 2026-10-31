<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DownlineBatch extends Model
{
    public const STATUS_HELD = 'held';
    public const STATUS_PARTIALLY_TRANSFERRED = 'partially_transferred';
    public const STATUS_TRANSFERRED = 'transferred';

    protected $fillable = [
        'batch_reference',
        'source_user_id',
        'source_role',
        'source_type',
        'master_manager_id',
        'title',
        'notes',
        'initial_count',
        'current_count',
        'transferred_count',
        'status',
        'created_by',
    ];

    protected $casts = [
        'initial_count' => 'integer',
        'current_count' => 'integer',
        'transferred_count' => 'integer',
    ];

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function masterManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batchUsers(): HasMany
    {
        return $this->hasMany(DownlineBatchUser::class, 'batch_id');
    }

    /**
     * Generate unique reference code for a new batch.
     */
    public static function generateReference(string $prefix, User $user): string
    {
        $roleCode = strtoupper(substr(str_replace('_', '', $user->role), 0, 3));
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(5));

        return "BATCH-{$prefix}-{$roleCode}-{$date}-{$random}";
    }

    /**
     * Synchronize and recalculate batch counts and status based on child records.
     */
    public function syncCounts(): void
    {
        $total = $this->batchUsers()->count();
        $transferred = $this->batchUsers()->where('status', 'transferred')->count();
        $held = $total - $transferred;

        $status = self::STATUS_HELD;
        if ($transferred >= $total && $total > 0) {
            $status = self::STATUS_TRANSFERRED;
        } elseif ($transferred > 0) {
            $status = self::STATUS_PARTIALLY_TRANSFERRED;
        }

        $this->update([
            'current_count' => $held,
            'transferred_count' => $transferred,
            'status' => $status,
        ]);
    }
}
