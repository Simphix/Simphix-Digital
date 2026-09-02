<?php

namespace App\Services;

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerReferralSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Support\Facades\DB;

/**
 * The admin side of an Enterprise Partner's order: a super_admin picks the
 * exact SIM(s) to grant per line item (never more than what the EP
 * ordered/paid for, and only ones still genuinely UNASSIGNED at commit time)
 * — never an automatic exact-match the moment payment clears. Only once this
 * runs does the EP's dashboard unlock and their inviter's referral bonus get
 * paid.
 */
class EnterprisePartnerFulfillmentService
{
    public function __construct(private SimAssignmentService $simAssignmentService)
    {
    }

    /**
     * Idempotent — re-checks the application is still 'awaiting_fulfillment'
     * under a row lock before doing anything, so a double-submit or a race
     * between two admins can never double-assign SIMs or double-pay the
     * bonus. Throws \RuntimeException (caller's responsibility to catch and
     * flash) if the application isn't in a fulfillable state.
     *
     * @param array<int,array<int>> $simIdsByItem item_id => admin-selected sim ids
     * @return array<int,array{item_id:int,requested:int,fulfilled:int}> per-item outcome
     */
    public function fulfill(EnterprisePartnerApplication $application, array $simIdsByItem, User $actor): array
    {
        return DB::transaction(function () use ($application, $simIdsByItem, $actor) {
            $lockedApplication = EnterprisePartnerApplication::where('id', $application->id)->lockForUpdate()->first();
            if (!$lockedApplication || $lockedApplication->status !== 'awaiting_fulfillment') {
                throw new \RuntimeException('This order is not awaiting fulfillment (already fulfilled, or payment not yet confirmed).');
            }

            $ep = User::where('id', $lockedApplication->user_id)->lockForUpdate()->first();
            if (!$ep) {
                throw new \RuntimeException('Enterprise Partner user not found.');
            }

            $items = EnterprisePartnerApplicationItem::where('application_id', $lockedApplication->id)
                ->lockForUpdate()
                ->get();

            $summary = [];
            foreach ($items as $item) {
                // Never process more sim ids than were originally ordered,
                // even if the admin's selection somehow exceeds that.
                $simIds = array_slice($simIdsByItem[$item->id] ?? [], 0, $item->quantity);

                $sims = !empty($simIds)
                    ? Sim::whereIn('id', $simIds)
                        ->where('category', $item->sim_category)
                        ->where('provider', $item->provider)
                        ->lockForUpdate()
                        ->get()
                    : collect();

                $fulfilledCount = 0;
                foreach ($sims as $sim) {
                    if ($sim->status !== SimStatus::UNASSIGNED) {
                        // Raced away since the admin picked it client-side —
                        // skip it rather than aborting the whole batch.
                        continue;
                    }
                    $this->simAssignmentService->adminAssign($sim, $ep, $actor);
                    $fulfilledCount++;
                }

                $item->update(['fulfilled_quantity' => $fulfilledCount]);

                $summary[] = [
                    'item_id' => $item->id,
                    'requested' => $item->quantity,
                    'fulfilled' => $fulfilledCount,
                ];
            }

            $lockedApplication->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
                'fulfilled_by' => $actor->id,
            ]);

            $ep->update(['status' => 'active']);

            $this->creditReferralBonus($ep);

            return $summary;
        });
    }

    /** Moved verbatim from the old EnterprisePartnerActivationService — now only reachable via fulfill(). */
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
