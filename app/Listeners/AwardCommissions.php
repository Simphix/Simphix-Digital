<?php

namespace App\Listeners;

use App\Events\SimActivated;
use App\Models\CommissionEarning;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pays out every role that holds a SIM once it's activated (user_id, partner_id,
 * coordinator_id, regional_manager_id), reading each role's per-category
 * commission from ServicePrice — the same table admin manages per SIM
 * category/role at /admin/services, so every payout is admin-configurable
 * with no code changes needed.
 *
 * Runs synchronously (not queued) so a payout is guaranteed the moment the
 * SIM is marked ACTIVATED.
 */
class AwardCommissions
{
    public function handle(SimActivated $event): void
    {
        if (!$event->awardCommission) {
            return;
        }

        $sim = $event->sim;

        $service = Service::where('name', 'simcard')->first();
        $field = $service
            ? ServiceField::where('service_id', $service->id)->where('field_name', $sim->category)->first()
            : null;

        if (!$field) {
            return;
        }

        $periodWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $holders = array_unique(array_filter([
            $sim->user_id,
            $sim->partner_id,
            $sim->coordinator_id,
            $sim->regional_manager_id,
        ]));

        foreach ($holders as $userId) {
            $this->awardViaServicePrice($sim, $userId, $field, $service, $periodWeek);
        }
    }

    private function awardViaServicePrice(Sim $sim, int $userId, ServiceField $field, Service $service, string $periodWeek): void
    {
        $userModel = User::where('id', $userId)->lockForUpdate()->first();
        if (!$userModel || $userModel->role === 'super_admin') {
            return;
        }

        $servicePrice = ServicePrice::where('service_fields_id', $field->id)
            ->where('user_type', $userModel->role)
            ->whereNull('user_id')
            ->first();

        $commission = $servicePrice ? (float) $servicePrice->commission : 0.00;
        if ($commission <= 0.00) {
            return;
        }

        DB::transaction(function () use ($sim, $userModel, $commission, $service, $periodWeek) {
            $wallet = Wallet::where('user_id', $userModel->id)->lockForUpdate()->first();
            if (!$wallet) {
                return;
            }

            $oldBalance = $wallet->bonus;
            $wallet->increment('bonus', $commission);
            $newBalance = $wallet->bonus;

            $ref = 'COMM-' . time() . '-' . rand(1000, 9999);

            Transaction::create([
                'transaction_ref' => $ref,
                'user_id'         => $userModel->id,
                'amount'          => $commission,
                'fee'             => 0.00,
                'net_amount'      => $commission,
                'description'     => "Commission earned on SIM Activation ({$sim->number}) as " . str_replace('_', ' ', $userModel->role),
                'type'            => 'credit',
                'status'          => 'completed',
                'metadata'        => json_encode([
                    'sim_number' => $sim->number,
                    'category'   => $sim->category,
                    'type'       => 'activation_commission',
                    'role'       => $userModel->role,
                ]),
                'performed_by' => 'System',
            ]);

            Report::create([
                'user_id'      => $userModel->id,
                'phone_number' => $sim->number,
                'network'      => $sim->provider,
                'ref'          => $ref,
                'amount'       => $commission,
                'status'       => 'completed',
                'type'         => 'commission',
                'description'  => "Commission earned on SIM Activation: {$sim->number} (" . str_replace('_', ' ', $userModel->role) . ')',
                'old_balance'  => $oldBalance,
                'new_balance'  => $newBalance,
                'service_id'   => $service->id,
            ]);

            CommissionEarning::create([
                'user_id'     => $userModel->id,
                'role'        => $userModel->role,
                'sim_id'      => $sim->id,
                'amount'      => $commission,
                'period_week' => $periodWeek,
            ]);
        });
    }
}
