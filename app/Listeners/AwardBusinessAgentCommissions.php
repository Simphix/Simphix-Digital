<?php

namespace App\Listeners;

use App\Events\SimActivated;
use App\Models\CommissionEarning;
use App\Models\Report;
use App\Models\Service;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\EnterprisePricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pays out the Enterprise Partner chain (business/business_agent) once a
 * SIM they distributed is activated, reading the EP's own configured
 * price/commission from BusinessCommissionRate instead of the admin-owned
 * ServicePrice table AwardCommissions reads for every other role — there is
 * deliberately no admin ceiling on this split (confirmed product decision).
 * The business_agent gets the configured commission; the EP keeps whatever
 * margin (price - commission) is left.
 *
 * Runs synchronously alongside AwardCommissions, which never fires for
 * these two roles since ServicePrice has no rows for them.
 */
class AwardBusinessAgentCommissions
{
    public function handle(SimActivated $event): void
    {
        if (!$event->awardCommission) {
            return;
        }

        $sim = $event->sim;

        if (!$sim->business_id) {
            return;
        }

        $rate = EnterprisePricing::rateFor($sim->business_id, $sim->category);

        if (!$rate) {
            return;
        }

        $price = (float) $rate->price;
        $commission = min((float) $rate->commission, $price);
        $margin = $price - $commission;

        $service = Service::where('name', 'simcard')->first();
        $periodWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        DB::transaction(function () use ($sim, $service, $periodWeek, $commission, $margin) {
            if ($sim->business_agent_id && $sim->business_agent_id !== $sim->business_id && $commission > 0.00) {
                $this->credit($sim, $sim->business_agent_id, $commission, 'business_agent', $service, $periodWeek);
            }

            if ($margin > 0.00) {
                $this->credit($sim, $sim->business_id, $margin, 'business', $service, $periodWeek);
            }
        });
    }

    private function credit(Sim $sim, int $userId, float $amount, string $role, ?Service $service, string $periodWeek): void
    {
        $userModel = User::where('id', $userId)->lockForUpdate()->first();
        if (!$userModel) {
            return;
        }

        $wallet = Wallet::where('user_id', $userModel->id)->lockForUpdate()->first();
        if (!$wallet) {
            return;
        }

        $oldBalance = $wallet->bonus;
        $wallet->increment('bonus', $amount);
        $newBalance = $wallet->bonus;

        $ref = 'COMM-' . time() . '-' . rand(1000, 9999);

        Transaction::create([
            'transaction_ref' => $ref,
            'user_id'         => $userModel->id,
            'amount'          => $amount,
            'fee'             => 0.00,
            'net_amount'      => $amount,
            'description'     => "Commission earned on SIM Activation ({$sim->number}) as " . str_replace('_', ' ', $role),
            'type'            => 'credit',
            'status'          => 'completed',
            'metadata'        => json_encode([
                'sim_number' => $sim->number,
                'category'   => $sim->category,
                'type'       => 'activation_commission',
                'role'       => $role,
            ]),
            'performed_by' => 'System',
        ]);

        Report::create([
            'user_id'      => $userModel->id,
            'phone_number' => $sim->number,
            'network'      => $sim->provider,
            'ref'          => $ref,
            'amount'       => $amount,
            'status'       => 'completed',
            'type'         => 'commission',
            'description'  => "Commission earned on SIM Activation: {$sim->number} (" . str_replace('_', ' ', $role) . ')',
            'old_balance'  => $oldBalance,
            'new_balance'  => $newBalance,
            'service_id'   => $service?->id,
        ]);

        CommissionEarning::create([
            'user_id'     => $userModel->id,
            'role'        => $role,
            'sim_id'      => $sim->id,
            'amount'      => $amount,
            'period_week' => $periodWeek,
        ]);
    }
}
