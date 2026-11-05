<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashoutSettings extends Model
{
    public const CONFIGURABLE_ROLES = [
        'business' => 'Enterprise Business',
        'business_agent' => 'Business Agent',
        'partner' => 'Partner',
        'coordinator' => 'Coordinator',
        'regional_manager' => 'Regional Manager',
        'agent' => 'Agent',
        'personal' => 'Personal',
    ];

    public const DEFAULT_ROLE_LIMITS = [
        'business' => ['min' => 5000, 'max' => 5000000],
        'business_agent' => ['min' => 1000, 'max' => 500000],
        'partner' => ['min' => 1000, 'max' => 1000000],
        'coordinator' => ['min' => 1000, 'max' => 1000000],
        'regional_manager' => ['min' => 1000, 'max' => 1000000],
        'agent' => ['min' => 500, 'max' => 100000],
        'personal' => ['min' => 500, 'max' => 50000],
    ];

    protected $fillable = [
        'is_enabled',
        'role_limits',
        'cashout_fee',
        'cashout_fee_type',
        'cashout_fee_description',
        'cashout_threshold_amount',
        'cashout_threshold_fee',
        'cashout_threshold_description',
        'inflow_fee',
        'inflow_fee_type',
        'inflow_fee_description',
        'inflow_threshold_amount',
        'inflow_threshold_fee',
        'inflow_threshold_description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'role_limits' => 'array',
        'cashout_fee' => 'decimal:2',
        'cashout_threshold_amount' => 'decimal:2',
        'cashout_threshold_fee' => 'decimal:2',
        'inflow_fee' => 'decimal:2',
        'inflow_threshold_amount' => 'decimal:2',
        'inflow_threshold_fee' => 'decimal:2',
    ];

    /**
     * Single global settings row, created on first access with cashouts
     * enabled (so existing behaviour is unchanged until an admin switches
     * them off). See PlatformWelfareSettings::current() for why this isn't
     * `firstOrCreate(['id' => 1], ...)`.
     */
    public static function current(): self
    {
        return static::first() ?? static::create(['is_enabled' => true]);
    }

    /**
     * Master switch for every cashout path (user + platform wallet), checked
     * by the `cashout_enabled` middleware and by the views that link to them.
     */
    public static function enabled(): bool
    {
        return static::current()->is_enabled;
    }

    /**
     * Get min and max cashout thresholds for a specific role.
     */
    public function getLimitsForRole(?string $role): array
    {
        $limits = ($this->role_limits ?? [])[$role] ?? null;
        $default = self::DEFAULT_ROLE_LIMITS[$role] ?? ['min' => 100, 'max' => 500000];

        return [
            'min' => isset($limits['min']) && is_numeric($limits['min']) ? (float) $limits['min'] : (float) $default['min'],
            'max' => isset($limits['max']) && is_numeric($limits['max']) ? (float) $limits['max'] : (float) $default['max'],
        ];
    }

    /**
     * Helper to get limits statically for a role.
     */
    public static function limitsForRole(?string $role): array
    {
        return static::current()->getLimitsForRole($role);
    }

    /**
     * Get all configurable roles and their active limits.
     */
    public function getAllRoleLimits(): array
    {
        $all = [];
        foreach (self::CONFIGURABLE_ROLES as $role => $label) {
            $limits = $this->getLimitsForRole($role);
            $all[$role] = [
                'role' => $role,
                'label' => $label,
                'min' => $limits['min'],
                'max' => $limits['max'],
            ];
        }

        return $all;
    }

    /**
     * Calculate cashout charges breakdown for a given amount and optional user.
     */
    public function calculateCashoutCharges(float $amount, ?User $user = null): array
    {
        $baseFee = 0.00;
        if ((float) $this->cashout_fee > 0) {
            if ($this->cashout_fee_type === 'percent') {
                $baseFee = round(($amount * (float) $this->cashout_fee) / 100, 2);
            } else {
                $baseFee = (float) $this->cashout_fee;
            }
        }

        $thresholdFee = 0.00;
        $thresholdMet = false;
        if ($this->cashout_threshold_amount !== null && (float) $this->cashout_threshold_fee > 0) {
            if ($amount > (float) $this->cashout_threshold_amount) {
                $thresholdFee = (float) $this->cashout_threshold_fee;
                $thresholdMet = true;
            }
        }

        $totalCharges = $baseFee + $thresholdFee;
        $totalDebit = $amount + $totalCharges;

        return [
            'amount' => $amount,
            'base_fee' => $baseFee,
            'base_fee_type' => $this->cashout_fee_type ?? 'fixed',
            'base_fee_rate' => (float) ($this->cashout_fee ?? 0),
            'base_fee_description' => $this->cashout_fee_description ?: 'Withdrawal Processing Fee',
            'has_base_fee' => $baseFee > 0,
            'threshold_amount' => $this->cashout_threshold_amount !== null ? (float) $this->cashout_threshold_amount : null,
            'threshold_fee' => $thresholdFee,
            'threshold_rate' => (float) ($this->cashout_threshold_fee ?? 0),
            'threshold_description' => $this->cashout_threshold_description ?: 'Transaction Levy / Stamp Duty',
            'threshold_met' => $thresholdMet,
            'has_threshold' => ($this->cashout_threshold_amount !== null && (float) $this->cashout_threshold_fee > 0),
            'total_charges' => $totalCharges,
            'total_debit' => $totalDebit,
            'net_payout' => $amount,
        ];
    }

    /**
     * Calculate inflow charges breakdown for incoming funds.
     */
    public function calculateInflowCharges(float $amount): array
    {
        $baseFee = 0.00;
        if ((float) $this->inflow_fee > 0) {
            if ($this->inflow_fee_type === 'percent') {
                $baseFee = round(($amount * (float) $this->inflow_fee) / 100, 2);
            } else {
                $baseFee = (float) $this->inflow_fee;
            }
        }

        $thresholdFee = 0.00;
        $thresholdMet = false;
        if ($this->inflow_threshold_amount !== null && (float) $this->inflow_threshold_fee > 0) {
            if ($amount > (float) $this->inflow_threshold_amount) {
                $thresholdFee = (float) $this->inflow_threshold_fee;
                $thresholdMet = true;
            }
        }

        $totalDeductions = $baseFee + $thresholdFee;
        $netCredited = max(0.00, $amount - $totalDeductions);

        return [
            'amount' => $amount,
            'base_fee' => $baseFee,
            'base_fee_type' => $this->inflow_fee_type ?? 'fixed',
            'base_fee_rate' => (float) ($this->inflow_fee ?? 0),
            'base_fee_description' => $this->inflow_fee_description ?: 'Wallet Funding Fee',
            'has_base_fee' => $baseFee > 0,
            'threshold_amount' => $this->inflow_threshold_amount !== null ? (float) $this->inflow_threshold_amount : null,
            'threshold_fee' => $thresholdFee,
            'threshold_rate' => (float) ($this->inflow_threshold_fee ?? 0),
            'threshold_description' => $this->inflow_threshold_description ?: 'Electronic Money Transfer Levy',
            'threshold_met' => $thresholdMet,
            'has_threshold' => ($this->inflow_threshold_amount !== null && (float) $this->inflow_threshold_fee > 0),
            'total_deductions' => $totalDeductions,
            'net_credited' => $netCredited,
        ];
    }
}
