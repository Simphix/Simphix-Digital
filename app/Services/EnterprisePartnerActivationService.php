<?php

namespace App\Services;

use App\Models\EnterprisePartnerPayment;
use App\Models\EnterprisePartnerReferralSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The integration point that makes an Enterprise Partner's eligibility
 * payment actually mean something: hands them their 100 (or whatever the
 * application snapshot says) real SIMs via the same adminAssign() path a
 * super_admin would use manually, unlocks their dashboard, and pays their
 * inviter's one-time referral bonus if they have one.
 */
class EnterprisePartnerActivationService
{
    public function __construct(private SimAssignmentService $simAssignmentService)
    {
    }

    /**
     * Idempotent — re-checks the payment is still 'pending' under a row
     * lock before doing anything, so a retried webhook delivery (or the
     * admin "Retry" action on a webhook log) can never double-assign SIMs
     * or double-pay a referral bonus.
     */
    public function activate(EnterprisePartnerPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = EnterprisePartnerPayment::where('id', $payment->id)->lockForUpdate()->first();
            if (!$lockedPayment || $lockedPayment->status !== 'pending') {
                return;
            }

            $application = $lockedPayment->application()->lockForUpdate()->first();
            if (!$application || $application->status !== 'pending_payment') {
                return;
            }

            $ep = User::where('id', $lockedPayment->user_id)->lockForUpdate()->first();
            if (!$ep) {
                return;
            }

            $lockedPayment->update(['status' => 'paid', 'paid_at' => now()]);
            $application->update(['status' => 'paid', 'paid_at' => now()]);

            $this->assignSims($application, $ep);

            $ep->update(['status' => 'active']);

            $this->creditReferralBonus($ep);
        });
    }

    private function assignSims($application, User $ep): void
    {
        $systemActor = User::where('role', 'super_admin')->first();
        if (!$systemActor) {
            Log::critical("Enterprise Partner activation for user {$ep->id}: no super_admin account exists to act as the assigning actor — SIMs could not be assigned. Needs a real admin-alert channel.");

            return;
        }

        $sims = Sim::where('status', SimStatus::UNASSIGNED)
            ->where('category', $application->sim_category)
            ->where('provider', $application->provider)
            ->lockForUpdate()
            ->limit($application->quantity)
            ->get();

        foreach ($sims as $sim) {
            $this->simAssignmentService->adminAssign($sim, $ep, $systemActor);
        }

        if ($sims->count() < $application->quantity) {
            // Known gap: this needs a real admin-alert channel (e.g. an
            // admin notification or a dedicated ops queue), not just a log
            // line — an EP who paid for 100 SIMs but only 40 were in stock
            // deserves more than a silent shortfall.
            Log::critical("Enterprise Partner activation for user {$ep->id}: only {$sims->count()} of {$application->quantity} {$application->sim_category} ({$application->provider}) SIMs were available to assign.");
        }
    }

    private function creditReferralBonus(User $ep): void
    {
        $inviter = $ep->referred_by ? User::where('id', $ep->referred_by)->lockForUpdate()->first() : null;
        if (!$inviter || !in_array($inviter->role, ['regional_manager', 'coordinator', 'partner'], true)) {
            return;
        }

        $amount = EnterprisePartnerReferralSettings::current()->amountForRole($inviter->role);
        if ($amount <= 0.00) {
            return;
        }

        $wallet = Wallet::where('user_id', $inviter->id)->lockForUpdate()->first();
        if (!$wallet) {
            return;
        }

        $oldBalance = $wallet->bonus;
        $wallet->increment('bonus', $amount);
        $newBalance = $wallet->bonus;

        $ref = 'EPREF-' . time() . '-' . rand(1000, 9999);

        // Deliberately a plain Transaction+Report, not a CommissionEarning —
        // that model is tied to per-SIM-activation semantics (sim_id,
        // period_week for the leaderboard) and doesn't fit a one-time
        // invite bonus with no associated SIM.
        Transaction::create([
            'transaction_ref' => $ref,
            'user_id'         => $inviter->id,
            'amount'          => $amount,
            'fee'             => 0.00,
            'net_amount'      => $amount,
            'description'     => "Referral bonus for inviting Enterprise Partner {$ep->first_name} {$ep->last_name}",
            'type'            => 'credit',
            'status'          => 'completed',
            'performed_by'    => 'System',
        ]);

        Report::create([
            'user_id'      => $inviter->id,
            'phone_number' => $ep->phone,
            'ref'          => $ref,
            'amount'       => $amount,
            'status'       => 'completed',
            'type'         => 'ep_referral_bonus',
            'description'  => "Referral bonus for inviting Enterprise Partner {$ep->first_name} {$ep->last_name}",
            'old_balance'  => $oldBalance,
            'new_balance'  => $newBalance,
        ]);
    }
}
