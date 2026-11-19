<?php

use App\Models\ActivationBonusSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SmeData;
use App\Models\SmeDataProviderSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function topUpAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

function activatedSimWithHolder(string $provider = 'mtn'): array
{
    $holder = User::factory()->create(['role' => 'personal', 'status' => 'active']);
    $sim = Sim::create([
        'number' => '081' . fake()->unique()->numerify('########'),
        'category' => 'POS SIM',
        'provider' => $provider,
        'status' => SimStatus::ACTIVATED,
        'user_id' => $holder->id,
    ]);

    return [$sim, $holder];
}

function enabledTopUpPlan(string $network = 'MTN', string $provider = 'kvdata'): SmeData
{
    return SmeData::create([
        'data_id' => 'PLAN-' . fake()->unique()->numerify('####'),
        'provider' => $provider,
        'network' => $network,
        'plan_type' => 'Daily',
        'personal_price' => 300,
        'agent_price' => 280,
        'partner_price' => 270,
        'business_price' => 260,
        'vendor_amount' => 250,
        'size' => '1GB',
        'validity' => '1 Day',
        'status' => 'enabled',
    ]);
}

beforeEach(function () {
    Cache::flush();
    // Force a known, single-HTTP-call vendor active for deterministic
    // tests, regardless of whatever the shared dev DB currently has active.
    SmeDataProviderSetting::activate('kvdata');
});

test('a non-super_admin is forbidden from the index and topup routes', function () {
    $partner = User::factory()->create(['role' => 'partner']);
    [$sim] = activatedSimWithHolder();
    $plan = enabledTopUpPlan();

    $this->actingAs($partner)->get(route('admin.sim-data-topup.index'))->assertForbidden();
    $this->actingAs($partner)->post(route('admin.sim-data-topup.topup', $sim), ['plan' => $plan->data_id])->assertForbidden();
});

test('searchSims returns only ACTIVATED sims with a current holder', function () {
    $admin = topUpAdmin();
    [$eligible] = activatedSimWithHolder();
    $unassigned = Sim::create(['number' => '081' . fake()->unique()->numerify('########'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::UNASSIGNED]);
    $activatedNoHolder = Sim::create(['number' => '081' . fake()->unique()->numerify('########'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED]);

    $response = $this->actingAs($admin)->getJson(route('admin.sim-data-topup.search-sims', ['q' => '081']));

    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($eligible->id);
    expect($ids)->not->toContain($unassigned->id);
    expect($ids)->not->toContain($activatedNoHolder->id);
});

test('usageReview aggregates prior top-up Report rows for the sim within the last 30 days, and excludes older or unrelated rows', function () {
    $admin = topUpAdmin();
    [$sim, $holder] = activatedSimWithHolder();

    $recent = Report::create([
        'user_id' => $holder->id, 'phone_number' => $sim->number, 'network' => 'MTN', 'ref' => 'REF-RECENT',
        'amount' => 300, 'status' => 'completed', 'type' => 'data', 'description' => '1GB Daily for ' . $sim->number . ' (MTN)',
        'old_balance' => 1000, 'new_balance' => 700,
    ]);
    $old = Report::create([
        'user_id' => $holder->id, 'phone_number' => $sim->number, 'network' => 'MTN', 'ref' => 'REF-OLD',
        'amount' => 300, 'status' => 'completed', 'type' => 'data', 'description' => '2GB Daily for ' . $sim->number . ' (MTN)',
        'old_balance' => 1000, 'new_balance' => 700,
    ]);
    // created_at isn't mass-assignable — force it back-dated after creation
    // so this row genuinely falls outside the 30-day review window.
    $old->timestamps = false;
    $old->created_at = now()->subDays(45);
    $old->save();

    $otherSimNumber = '081' . fake()->unique()->numerify('########');
    Report::create([
        'user_id' => $holder->id, 'phone_number' => $otherSimNumber, 'network' => 'MTN', 'ref' => 'REF-OTHER',
        'amount' => 300, 'status' => 'completed', 'type' => 'data', 'description' => '1GB Daily for ' . $otherSimNumber . ' (MTN)',
        'old_balance' => 1000, 'new_balance' => 700,
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.sim-data-topup.usage-review', $sim));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    expect((float) $response->json('total_mb'))->toBe(1024.0);
    expect($response->json('history.0.description'))->toContain($sim->number);
});

test('a successful top-up creates an admin_data_topup Report with zero amount and does not touch activation-bonus grants or any wallet', function () {
    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-1'], 200)]);
    $admin = topUpAdmin();
    [$sim, $holder] = activatedSimWithHolder();
    Wallet::create(['user_id' => $holder->id, 'balance' => 500, 'wallet_number' => 'WLT' . str_pad((string) $holder->id, 7, '0', STR_PAD_LEFT)]);
    $plan = enabledTopUpPlan('MTN', 'kvdata');

    $response = $this->actingAs($admin)->post(route('admin.sim-data-topup.topup', $sim), ['plan' => $plan->data_id]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $report = Report::where('phone_number', $sim->number)->where('type', 'admin_data_topup')->first();
    expect($report)->not->toBeNull();
    expect((float) $report->amount)->toBe(0.0);
    expect($report->status)->toBe('completed');
    expect($report->user_id)->toBe($holder->id);

    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->count())->toBe(0);
    expect((float) Wallet::where('user_id', $holder->id)->value('balance'))->toBe(500.0);
});

test('the same sim can be topped up more than once in the same calendar month — no monthly cap applies', function () {
    // A real vendor issues a distinct transaction ref per call — reports.ref
    // is globally unique, so the fake must vary the ref across the two
    // top-ups exactly as a real vendor response would.
    Http::fake(fn () => Http::response(['status' => 'success', 'transid' => 'VEND-' . uniqid()], 200));
    $admin = topUpAdmin();
    [$sim] = activatedSimWithHolder();
    $plan = enabledTopUpPlan('MTN', 'kvdata');

    $this->actingAs($admin)->post(route('admin.sim-data-topup.topup', $sim), ['plan' => $plan->data_id]);
    $this->actingAs($admin)->post(route('admin.sim-data-topup.topup', $sim), ['plan' => $plan->data_id])->assertSessionHas('success');

    expect(Report::where('phone_number', $sim->number)->where('type', 'admin_data_topup')->count())->toBe(2);
});

test('a plan whose network does not match the sim provider is rejected server-side', function () {
    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-1'], 200)]);
    $admin = topUpAdmin();
    [$sim] = activatedSimWithHolder('mtn');
    $wrongNetworkPlan = enabledTopUpPlan('GLO', 'kvdata');

    $response = $this->actingAs($admin)->post(route('admin.sim-data-topup.topup', $sim), ['plan' => $wrongNetworkPlan->data_id]);

    $response->assertSessionHas('error');
    expect(Report::where('phone_number', $sim->number)->where('type', 'admin_data_topup')->count())->toBe(0);
    Http::assertNothingSent();
});

test('sim activation bonus progress shows 1/12 immediately after initial activation grant', function () {
    [$sim] = activatedSimWithHolder('mtn');

    // Simulate the initial activation bonus grant (period 0)
    SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 0,
        'period_start' => now(),
        'period_end' => now()->addDays(28),
        'granted_at' => now(),
        'status' => SimActivationBonusGrant::STATUS_COMPLETED,
    ]);

    expect($sim->activationBonusCount())->toBe(1);
    expect($sim->activationBonusProgress())->toBe('1/12');
});

test('bulk activation bonus command triggers sims reaching 28 days without dispatching data', function () {
    [$sim] = activatedSimWithHolder('mtn');
    $plan = enabledTopUpPlan('MTN', 'kvdata');
    $settings = ActivationBonusSettings::forProvider('mtn');
    $settings->update(['is_active' => true, 'sme_data_id' => $plan->id]);

    // Initial grant 28 days ago
    $initialGrant = SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 0,
        'period_start' => now()->subDays(28),
        'period_end' => now(),
        'granted_at' => now()->subDays(28),
        'status' => SimActivationBonusGrant::STATUS_COMPLETED,
    ]);

    Http::fake();

    // Run command
    $this->artisan('sim:grant-bulk-activation-bonus')->assertSuccessful();

    // Verify vendor was NOT called (no automatic dispatch)
    Http::assertNothingSent();

    // Verify pending trigger was created for cycle 2/12
    $pending = SimActivationBonusGrant::where('sim_id', $sim->id)
        ->where('status', SimActivationBonusGrant::STATUS_PENDING)
        ->first();

    expect($pending)->not->toBeNull();
    expect($pending->period_index)->toBe(1);
    expect($pending->cycleLabel())->toBe('2/12');
});

test('admin can complete action on triggered sim so the sim receives data', function () {
    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-BONUS-1'], 200)]);
    $admin = topUpAdmin();
    [$sim, $holder] = activatedSimWithHolder('mtn');
    $plan = enabledTopUpPlan('MTN', 'kvdata');
    $settings = ActivationBonusSettings::forProvider('mtn');
    $settings->update(['is_active' => true, 'sme_data_id' => $plan->id]);

    // Initial grant (1/12)
    SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 0,
        'period_start' => now()->subDays(28),
        'period_end' => now(),
        'granted_at' => now()->subDays(28),
        'status' => SimActivationBonusGrant::STATUS_COMPLETED,
    ]);

    // Pending trigger for cycle 2/12
    $trigger = SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 1,
        'period_start' => now()->subDays(28),
        'period_end' => now(),
        'triggered_at' => now(),
        'status' => SimActivationBonusGrant::STATUS_PENDING,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.sim-data-topup.triggered.complete', $trigger));
    $response->assertSessionHas('success');

    $trigger->refresh();
    expect($trigger->status)->toBe(SimActivationBonusGrant::STATUS_COMPLETED);
    expect($trigger->resolution_type)->toBe('vendor_topup');
    expect($trigger->admin_id)->toBe($admin->id);
    expect($sim->activationBonusProgress())->toBe('2/12');

    Http::assertSentCount(1);
});

test('admin can resolve triggered sim silently without dispatching data', function () {
    Http::fake();
    $admin = topUpAdmin();
    [$sim, $holder] = activatedSimWithHolder('mtn');

    // Initial grant (1/12)
    SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 0,
        'period_start' => now()->subDays(28),
        'period_end' => now(),
        'granted_at' => now()->subDays(28),
        'status' => SimActivationBonusGrant::STATUS_COMPLETED,
    ]);

    // Pending trigger for cycle 2/12
    $trigger = SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 1,
        'period_start' => now()->subDays(28),
        'period_end' => now(),
        'triggered_at' => now(),
        'status' => SimActivationBonusGrant::STATUS_PENDING,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.sim-data-topup.triggered.resolve-silently', $trigger), [
        'notes' => 'Topped up manually through USSD',
    ]);
    $response->assertSessionHas('success');

    // Vendor was NOT called!
    Http::assertNothingSent();

    $trigger->refresh();
    expect($trigger->status)->toBe(SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY);
    expect($trigger->resolution_type)->toBe('silent');
    expect($trigger->admin_id)->toBe($admin->id);
    expect($trigger->admin_notes)->toContain('Topped up manually');
    // Count is now 2/12
    expect($sim->activationBonusProgress())->toBe('2/12');
});

test('admin can execute bulk actions on triggered grants', function () {
    $admin = topUpAdmin();
    [$sim1] = activatedSimWithHolder('mtn');
    [$sim2] = activatedSimWithHolder('airtel');

    $t1 = SimActivationBonusGrant::create([
        'sim_id' => $sim1->id, 'period_index' => 1, 'period_start' => now(), 'period_end' => now()->addDays(28),
        'status' => SimActivationBonusGrant::STATUS_PENDING,
    ]);
    $t2 = SimActivationBonusGrant::create([
        'sim_id' => $sim2->id, 'period_index' => 1, 'period_start' => now(), 'period_end' => now()->addDays(28),
        'status' => SimActivationBonusGrant::STATUS_PENDING,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.sim-data-topup.triggered.bulk-action'), [
        'action' => 'resolve_silent',
        'grant_ids' => [$t1->id, $t2->id],
    ]);

    $response->assertSessionHas('success');
    expect($t1->fresh()->status)->toBe(SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY);
    expect($t2->fresh()->status)->toBe(SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY);
});
