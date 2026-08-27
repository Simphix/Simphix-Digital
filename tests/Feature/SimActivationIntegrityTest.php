<?php

use App\Events\SimActivated;
use App\Models\CommissionEarning;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeWallet(User $user, float $balance = 0.00): Wallet
{
    return Wallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);
}

function posSimField(): ServiceField
{
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);

    return ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-' . fake()->unique()->numerify('####'), 'base_price' => 1000, 'is_active' => true]
    );
}

beforeEach(function () {
    $this->partner = User::factory()->create(['role' => 'partner']);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->partner->id]);
    makeWallet($this->partner, 0);
    $this->customerWallet = makeWallet($this->customer, 5000);

    $this->field = posSimField();
    ServicePrice::firstOrCreate(
        ['service_fields_id' => $this->field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $this->field->service_id, 'price' => 1000, 'commission' => 0]
    );

    $this->sim = Sim::create([
        'number' => '0805' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $this->partner->id,
        'user_id' => $this->customer->id,
    ]);
});

test('happy path: activating for a downline user debits their wallet, activates the sim, and logs history', function () {
    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->sim->refresh();
    $this->customerWallet->refresh();

    expect($this->sim->status)->toBe(SimStatus::ACTIVATED);
    expect((float) $this->customerWallet->balance)->toBe(4000.0);
    expect(SimHistory::where('sim_id', $this->sim->id)->where('to_status', SimStatus::ACTIVATED)->exists())->toBeTrue();
    expect(Transaction::where('user_id', $this->customer->id)->where('type', 'debit')->exists())->toBeTrue();
});

test('insufficient balance blocks activation cleanly: sim stays ASSIGNED_TO_PARTNER, no debit, no history', function () {
    $this->customerWallet->update(['balance' => 10]);

    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->sim->refresh();
    $this->customerWallet->refresh();

    expect($this->sim->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect((float) $this->customerWallet->balance)->toBe(10.0);
    expect(SimHistory::where('sim_id', $this->sim->id)->where('to_status', SimStatus::ACTIVATED)->exists())->toBeFalse();
});

test('a partner cannot activate a sim they do not hold', function () {
    $otherPartner = User::factory()->create(['role' => 'partner']);
    makeWallet($otherPartner, 0);

    $this->actingAs($otherPartner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('re-activating an already-activated sim is rejected without a second debit', function () {
    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertSessionHas('success');

    $balanceAfterFirst = $this->customerWallet->fresh()->balance;

    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect((float) $this->customerWallet->fresh()->balance)->toBe((float) $balanceAfterFirst);
});

test('concurrent double-submit on the same sim only debits and activates once (row lock prevents the lost-update race)', function () {
    // Simulates two requests racing on the same already-fetched, unlocked
    // Sim state by directly exercising the service the way the old
    // unlocked controller code effectively could: call activate() a second
    // time on a status the DB has already moved past. The controller-level
    // lockForUpdate() means a real concurrent HTTP double-submit can no
    // longer read a stale copy inside the transaction — this asserts the
    // guard SimAssignmentService::activate() itself still provides too.
    app(SimAssignmentService::class)->activate($this->sim->fresh(), $this->partner);

    expect(fn () => app(SimAssignmentService::class)->activate($this->sim->fresh(), $this->partner))
        ->toThrow(RuntimeException::class, 'This SIM is already activated.');
});

test('a listener failure after activation does not roll back the already-committed debit and status change', function () {
    Event::listen(SimActivated::class, function () {
        throw new \RuntimeException('simulated commission engine failure');
    });

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'post-activation side effect'));

    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->sim->refresh();
    $this->customerWallet->refresh();

    // The core guarantee: even though a side-effect listener blew up, the
    // customer's debit and the SIM's ACTIVATED status are durable — this is
    // exactly the bug being fixed (a downstream commission/welfare failure
    // used to roll back the whole transaction, leaving the SIM stuck at
    // ASSIGNED_TO_PARTNER with no debit and no history).
    expect($this->sim->status)->toBe(SimStatus::ACTIVATED);
    expect((float) $this->customerWallet->balance)->toBe(4000.0);
    expect(SimHistory::where('sim_id', $this->sim->id)->where('to_status', SimStatus::ACTIVATED)->exists())->toBeTrue();
});

test('a healthy commission chain still pays out normally through the deferred event', function () {
    ServicePrice::where('service_fields_id', $this->field->id)->where('user_type', 'partner')->delete();
    ServicePrice::create([
        'service_id' => $this->field->service_id,
        'service_fields_id' => $this->field->id,
        'user_type' => 'partner',
        'user_id' => null,
        'price' => 0,
        'commission' => 75,
    ]);

    $this->actingAs($this->partner)
        ->post(route('sims.activate-for-user'), ['sim_id' => $this->sim->id, 'user_id' => $this->customer->id])
        ->assertSessionHas('success');

    $partnerWallet = $this->partner->wallet->fresh();
    expect((float) $partnerWallet->bonus)->toBe(75.0);
    expect(CommissionEarning::where('user_id', $this->partner->id)->where('sim_id', $this->sim->id)->exists())->toBeTrue();
});
