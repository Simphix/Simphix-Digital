<?php

namespace App\Services;

use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Confirms an Enterprise Partner's eligibility payment cleared — flips the
 * payment and application to their post-payment bookkeeping status and
 * parks the EP in 'pending_fulfillment'. Deliberately does NOT assign any
 * SIMs, unlock the EP's dashboard, or pay a referral bonus — the paid order
 * is now a request a super_admin must review and act on via
 * EnterprisePartnerFulfillmentService::fulfill().
 */
class EnterprisePartnerPaymentService
{
    /**
     * Idempotent — re-checks the payment is still 'pending' and the
     * application is still 'pending_payment' under row locks before doing
     * anything, so a retried webhook delivery (or the admin "Retry" action
     * on a webhook log) can never double-flip either row.
     */
    public function confirmPayment(EnterprisePartnerPayment $payment): void
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
            $application->update(['status' => 'awaiting_fulfillment', 'paid_at' => now()]);
            $ep->update(['status' => 'pending_fulfillment']);
        });
    }
}
