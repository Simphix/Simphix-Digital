<?php

use App\Models\ActivationBonusSettings;
use App\Models\User;
use App\Models\Wallet;
use App\Support\UtilityAccess;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function utilitiesTestUser(string $role): User
{
    $user = User::factory()->create(['role' => $role]);
    Wallet::create([
        'user_id' => $user->id,
        'balance' => 50000.00,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);

    return $user->fresh();
}

// -----------------------------------------------------------------------
// Suspension is confirmed active
// -----------------------------------------------------------------------

test('UtilityAccess::UTILITIES_SUSPENDED is on, so canUse is false regardless of role', function () {
    expect(UtilityAccess::UTILITIES_SUSPENDED)->toBeTrue();
    expect(UtilityAccess::canUse(utilitiesTestUser('personal')))->toBeFalse();
    expect(UtilityAccess::canUse(utilitiesTestUser('business')))->toBeFalse();
});

// -----------------------------------------------------------------------
// Previously-allowed roles now get 403 on every endpoint
// -----------------------------------------------------------------------

test('a personal user is now blocked from Airtime and Data endpoints', function () {
    $user = utilitiesTestUser('personal');

    $this->actingAs($user)->get(route('airtime'))->assertForbidden();
    $this->actingAs($user)->post(route('buyairtime'), [])->assertForbidden();
    $this->actingAs($user)->get(route('buy-sme-data'))->assertForbidden();
    $this->actingAs($user)->post(route('buy-sme-data.submit'), [])->assertForbidden();
    $this->actingAs($user)->get(route('sme.fetch.type'))->assertForbidden();
    $this->actingAs($user)->get(route('sme.fetch.plan'))->assertForbidden();
    $this->actingAs($user)->get(route('sme.fetch.price'))->assertForbidden();
});

test('a business user is now blocked from Airtime and Data endpoints', function () {
    $user = utilitiesTestUser('business');

    $this->actingAs($user)->get(route('airtime'))->assertForbidden();
    $this->actingAs($user)->get(route('buy-sme-data'))->assertForbidden();
});

test('the 403 shows the temporarily-unavailable message, not the role-based message', function () {
    $user = utilitiesTestUser('personal');

    $response = $this->actingAs($user)->get(route('airtime'));
    $response->assertForbidden();
    $response->assertSee('temporarily unavailable', false);
});

test('already-excluded roles still get 403, unchanged behavior', function () {
    $agent = utilitiesTestUser('agent');
    $partner = utilitiesTestUser('partner');

    $this->actingAs($agent)->get(route('airtime'))->assertForbidden();
    $this->actingAs($partner)->get(route('buy-sme-data'))->assertForbidden();
});

// -----------------------------------------------------------------------
// Verification (BVN/NIN) is untouched
// -----------------------------------------------------------------------

test('canVerify is unaffected by the utilities suspension', function () {
    $personal = utilitiesTestUser('personal');
    $agent = utilitiesTestUser('agent');

    expect(UtilityAccess::canVerify($personal))->toBeTrue();
    expect(UtilityAccess::canVerify($agent))->toBeTrue();
});

test('a personal user still has normal access to Verification pages', function () {
    $user = utilitiesTestUser('personal');

    $this->actingAs($user)->get(route('bvn.verification.index'))->assertOk();
    $this->actingAs($user)->get(route('nin.verification.index'))->assertOk();
});

// -----------------------------------------------------------------------
// UI removal
// -----------------------------------------------------------------------

test('sidebar no longer shows Buy Airtime or Buy Data for a personal user', function () {
    $user = utilitiesTestUser('personal');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
    $response->assertDontSee('Buy Airtime');
    $response->assertDontSee(route('airtime'), false);
    $response->assertDontSee(route('buy-sme-data'), false);
});

test('wallet page no longer shows the Buy Airtime quick action', function () {
    $user = utilitiesTestUser('personal');

    $response = $this->actingAs($user)->get(route('wallet'));
    $response->assertOk();
    $response->assertDontSee(route('airtime'), false);
});

test('global search no longer returns Buy Airtime or Buy Data Bundle results', function () {
    $user = utilitiesTestUser('personal');

    $response = $this->actingAs($user)->get(route('search', ['q' => 'airtime']));
    $response->assertOk();
    $response->assertDontSee('Buy Airtime');

    $response = $this->actingAs($user)->get(route('search', ['q' => 'data']));
    $response->assertOk();
    $response->assertDontSee('Buy Data Bundle');
});

// -----------------------------------------------------------------------
// Regression guard: the SIM activation data bonus is completely unaffected
// -----------------------------------------------------------------------

test('the SIM activation data bonus still grants normally while utilities are suspended', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $sim = \App\Models\Sim::create([
        'number' => '0806' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => \App\Support\SimStatus::UNASSIGNED,
    ]);

    $plan = \App\Models\SmeData::create([
        'data_id' => 'PLAN-' . fake()->unique()->numerify('####'),
        'provider' => 'fadeel', // routes to the generic SmeDataPurchaseApi adapter
        'network' => 'mtn',
        'plan_type' => 'SME',
        'personal_price' => 0,
        'agent_price' => 0,
        'partner_price' => 0,
        'business_price' => 0,
        'vendor_amount' => 100,
        'size' => '1GB',
        'validity' => '30 days',
        'status' => 'enabled',
    ]);
    ActivationBonusSettings::forProvider('mtn')->update(['is_active' => true, 'sme_data_id' => $plan->id]);

    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-1'], 200)]);

    app(\App\Services\SimAssignmentService::class)->adminAssign($sim, $admin, $admin);
    app(\App\Services\SimAssignmentService::class)->activate($sim->fresh(), $admin);

    expect(\App\Models\Report::where('phone_number', $sim->number)->where('type', 'activation_bonus_data')->where('status', 'completed')->exists())->toBeTrue();
    Http::assertSentCount(1);
});

test('sim:grant-bulk-activation-bonus still queues and grants normally while utilities are suspended', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $simOwner = utilitiesTestUser('personal');
    $sim = \App\Models\Sim::create([
        'number' => '0806' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => \App\Support\SimStatus::ACTIVATED,
        'user_id' => $simOwner->id,
    ]);

    $plan = \App\Models\SmeData::create([
        'data_id' => 'PLAN-' . fake()->unique()->numerify('####'),
        'provider' => 'fadeel',
        'network' => 'mtn',
        'plan_type' => 'SME',
        'personal_price' => 0,
        'agent_price' => 0,
        'partner_price' => 0,
        'business_price' => 0,
        'vendor_amount' => 100,
        'size' => '1GB',
        'validity' => '30 days',
        'status' => 'enabled',
    ]);
    ActivationBonusSettings::forProvider('mtn')->update(['is_active' => true, 'sme_data_id' => $plan->id]);

    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-1'], 200)]);

    $this->artisan('sim:grant-bulk-activation-bonus')->assertExitCode(0);

    expect(\App\Models\Report::where('phone_number', $sim->number)->where('type', 'activation_bonus_data')->where('status', 'completed')->exists())->toBeTrue();
});
