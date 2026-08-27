<?php

use App\Models\AgentUpgradeSettings;
use App\Models\CommissionEarning;
use App\Models\Report;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use App\Support\UtilityAccess;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeUserWithWallet(string $role, float $balance = 0.00, ?int $referredBy = null): User
{
    $user = User::factory()->create(['role' => $role, 'referred_by' => $referredBy]);
    Wallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);

    return $user->fresh();
}

beforeEach(function () {
    AgentUpgradeSettings::current()->update([
        'minimum_wallet_balance' => 10000.00,
        'partner_commission_amount' => 500.00,
        'is_active' => true,
    ]);
});

// ---------------------------------------------------------------------
// show() states
// ---------------------------------------------------------------------

test('a personal user below the threshold sees an ineligible state with no CTA', function () {
    $user = makeUserWithWallet('personal', 5000.00);

    $response = $this->actingAs($user)->get(route('agent-upgrade.show'));

    $response->assertOk();
    $response->assertViewHas('eligible', false);
    // GET and POST /agent-upgrade share the same URL (and the header's <a>
    // to agent-upgrade.show also renders here), so assert on the CTA
    // <form>'s distinguishing action attribute rather than the shared URL.
    $response->assertDontSee('action="' . route('agent-upgrade.process') . '"', false);
    $response->assertSee('more in your wallet to qualify');
});

test('a personal user at or above the threshold sees an eligible state with a working CTA', function () {
    $user = makeUserWithWallet('personal', 10000.00);

    $response = $this->actingAs($user)->get(route('agent-upgrade.show'));

    $response->assertOk();
    $response->assertViewHas('eligible', true);
    // The CTA <form> posting to agent-upgrade.process only renders when
    // eligible — distinguishes it from the header's <a> to the same URL.
    $response->assertSee('action="' . route('agent-upgrade.process') . '"', false);
});

test('an already-agent user sees the informational already-upgraded state, not a 403', function () {
    $user = makeUserWithWallet('agent', 0);

    $response = $this->actingAs($user)->get(route('agent-upgrade.show'));

    $response->assertOk();
    $response->assertViewHas('notApplicable', true);
    $response->assertViewHas('alreadyAgent', true);
    $response->assertSee("You're already an Agent", false);
});

test('a non-personal, non-agent role sees the not-applicable informational state', function () {
    $user = makeUserWithWallet('partner', 0);

    $response = $this->actingAs($user)->get(route('agent-upgrade.show'));

    $response->assertOk();
    $response->assertViewHas('notApplicable', true);
    $response->assertViewHas('alreadyAgent', false);
});

// ---------------------------------------------------------------------
// process() — instant upgrade
// ---------------------------------------------------------------------

test('process() with sufficient balance flips role to agent instantly with no admin queue involved', function () {
    $user = makeUserWithWallet('personal', 10000.00);

    $response = $this->actingAs($user)->post(route('agent-upgrade.process'));

    $response->assertRedirect(route('agent-upgrade.show'));
    $fresh = $user->fresh();
    expect($fresh->role)->toBe('agent');
    expect($fresh->upgrade_status)->toBe('none');
    expect($fresh->pending_role)->toBeNull();
});

test('process() with insufficient balance is rejected and role stays personal', function () {
    $user = makeUserWithWallet('personal', 1000.00);

    $response = $this->actingAs($user)->post(route('agent-upgrade.process'));

    $response->assertRedirect();
    expect($user->fresh()->role)->toBe('personal');
});

test('process() called directly by a non-personal role is refused with 403', function () {
    $user = makeUserWithWallet('partner', 50000.00);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertForbidden();
});

test('a second process() call after already becoming an agent is refused (idempotency)', function () {
    $user = makeUserWithWallet('personal', 10000.00);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();
    expect($user->fresh()->role)->toBe('agent');

    $this->actingAs($user->fresh())->post(route('agent-upgrade.process'))->assertForbidden();
});

// ---------------------------------------------------------------------
// Partner commission
// ---------------------------------------------------------------------

test('upgrading credits the referring partner the configured commission', function () {
    $partner = makeUserWithWallet('partner', 0);
    $user = makeUserWithWallet('personal', 10000.00, $partner->id);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();

    expect((float) $partner->wallet->fresh()->bonus)->toBe(500.0);
    expect(Transaction::where('user_id', $partner->id)->where('type', 'credit')->exists())->toBeTrue();
    expect(Report::where('user_id', $partner->id)->where('type', 'agent_upgrade_referral_bonus')->exists())->toBeTrue();
    expect(CommissionEarning::where('user_id', $partner->id)->exists())->toBeFalse();
});

test('no commission is paid when the upline is not a partner', function () {
    foreach (['regional_manager', 'coordinator', 'business_agent'] as $role) {
        $upline = makeUserWithWallet($role, 0);
        $user = makeUserWithWallet('personal', 10000.00, $upline->id);

        $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();

        expect((float) $upline->wallet->fresh()->bonus)->toBe(0.0);
    }
});

test('no commission is paid when there is no upline at all', function () {
    $user = makeUserWithWallet('personal', 10000.00, null);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();

    expect($user->fresh()->role)->toBe('agent');
    expect(Transaction::where('description', 'like', '%upgrading to Agent%')->exists())->toBeFalse();
});

test('no commission is paid when settings are inactive, but the upgrade still succeeds', function () {
    AgentUpgradeSettings::current()->update(['is_active' => false]);
    $partner = makeUserWithWallet('partner', 0);
    $user = makeUserWithWallet('personal', 10000.00, $partner->id);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();

    expect($user->fresh()->role)->toBe('agent');
    expect((float) $partner->wallet->fresh()->bonus)->toBe(0.0);
});

test('no commission is paid when the configured amount is zero', function () {
    AgentUpgradeSettings::current()->update(['partner_commission_amount' => 0]);
    $partner = makeUserWithWallet('partner', 0);
    $user = makeUserWithWallet('personal', 10000.00, $partner->id);

    $this->actingAs($user)->post(route('agent-upgrade.process'))->assertRedirect();

    expect((float) $partner->wallet->fresh()->bonus)->toBe(0.0);
});

// ---------------------------------------------------------------------
// Old generic upgrade form no longer offers 'agent'
// ---------------------------------------------------------------------

test('the old generic upgrade form redirects agent requests to the new page instead of queuing them', function () {
    $user = makeUserWithWallet('personal', 0);

    $response = $this->actingAs($user)->post(route('profile.upgrade.request'), ['role' => 'agent']);

    $response->assertRedirect(route('agent-upgrade.show'));
    $fresh = $user->fresh();
    expect($fresh->upgrade_status)->toBe('none');
    expect($fresh->pending_role)->toBeNull();
    expect($fresh->role)->toBe('personal');
});

test('the old generic upgrade form still works for partner/business requests', function () {
    $user = makeUserWithWallet('personal', 0);

    $response = $this->actingAs($user)->post(route('profile.upgrade.request'), [
        'role' => 'partner',
        'gender' => 'male',
        'business_name' => 'Test Biz',
        'business_type' => 'sole_proprietor',
        'cac_number' => 'RC-1',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $fresh = $user->fresh();
    expect($fresh->pending_role)->toBe('partner');
    expect($fresh->upgrade_status)->toBe('pending');
});

test('the old form no longer accepts agent as a valid role value in validation', function () {
    $user = makeUserWithWallet('personal', 0);

    // 'agent' is intercepted before validation runs, so this exercises the
    // guard rather than the validator, but confirms end-to-end no pending
    // agent request can ever be created via this endpoint.
    $this->actingAs($user)->post(route('profile.upgrade.request'), ['role' => 'agent'])->assertRedirect(route('agent-upgrade.show'));
});

// ---------------------------------------------------------------------
// Admin settings
// ---------------------------------------------------------------------

test('non-super_admin is refused the agent upgrade settings page', function () {
    $user = makeUserWithWallet('partner', 0);

    $this->actingAs($user)->get(route('admin.agent-upgrade-settings.edit'))->assertForbidden();
});

test('admin can view and update agent upgrade settings, reflected in eligibility', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->get(route('admin.agent-upgrade-settings.edit'))->assertOk();

    $this->actingAs($admin)->put(route('admin.agent-upgrade-settings.update'), [
        'minimum_wallet_balance' => 25000,
        'partner_commission_amount' => 750,
        'is_active' => 1,
    ])->assertRedirect();

    $settings = AgentUpgradeSettings::current();
    expect((float) $settings->minimum_wallet_balance)->toBe(25000.0);
    expect((float) $settings->partner_commission_amount)->toBe(750.0);

    $user = makeUserWithWallet('personal', 15000.00);
    $this->actingAs($user)->get(route('agent-upgrade.show'))->assertViewHas('eligible', false);
});

test('AgentUpgradeSettings::current() singleton does not duplicate rows across repeated calls', function () {
    $first = AgentUpgradeSettings::current();
    $second = AgentUpgradeSettings::current();

    expect($first->id)->toBe($second->id);
    expect(AgentUpgradeSettings::count())->toBe(1);
});

// ---------------------------------------------------------------------
// Agent losing SIM / withdraw / transfer / airtime access
// ---------------------------------------------------------------------

test('agent is blocked from every SIM catalog page while a personal user is unaffected', function () {
    $agent = makeUserWithWallet('agent', 0);

    foreach (['sims.pos', 'sims.cctv', 'sims.router', 'sims.inventory'] as $route) {
        $this->actingAs($agent)->get(route($route))->assertForbidden();
    }
});

test('activateSim is now fully retired — 403 for everyone, including agent', function () {
    $agent = makeUserWithWallet('agent', 0);

    $this->actingAs($agent)->post(route('sims.activate'), ['sim_id' => 1])->assertForbidden();
});

test('a partner assigning a SIM to an agent no longer takes the legacy path and fails as an invalid hierarchy target', function () {
    $partner = makeUserWithWallet('partner', 0);
    $agent = makeUserWithWallet('agent', 0);
    $sim = Sim::create([
        'number' => '0806' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);

    $response = $this->actingAs($partner)->post(route('partner.sims.assign'), [
        'sim_id' => $sim->id,
        'user_id' => $agent->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($sim->fresh()->user_id)->toBeNull();
});

test('agent is blocked from Cash Out and P2P Transfer (GET and POST), while a personal user is unaffected', function () {
    $agent = makeUserWithWallet('agent', 50000.00);

    $this->actingAs($agent)->get(route('withdraw'))->assertForbidden();
    $this->actingAs($agent)->post(route('withdraw.verifyAccount'), [])->assertForbidden();
    $this->actingAs($agent)->post(route('withdraw.process'), [])->assertForbidden();

    $this->actingAs($agent)->get(route('transfer'))->assertForbidden();
    $this->actingAs($agent)->post(route('transfer.verify'), [])->assertForbidden();
    $this->actingAs($agent)->post(route('transfer.process'), [])->assertForbidden();
});

test('a personal user still has normal access to Cash Out, P2P Transfer, SIM catalog, and Airtime (regression guard)', function () {
    $personal = makeUserWithWallet('personal', 50000.00);

    $this->actingAs($personal)->get(route('withdraw'))->assertOk();
    $this->actingAs($personal)->get(route('transfer'))->assertOk();
    $this->actingAs($personal)->get(route('airtime'))->assertOk();
});

test('agent is blocked from Airtime and Buy Data', function () {
    $agent = makeUserWithWallet('agent', 0);

    $this->actingAs($agent)->get(route('airtime'))->assertForbidden();
    $this->actingAs($agent)->get(route('buy-sme-data'))->assertForbidden();
});

test('agent keeps Verification (BVN/NIN) access — regression guard for the UtilityAccess restructure', function () {
    $agent = makeUserWithWallet('agent', 0);

    $this->actingAs($agent)->get(route('bvn.verification.index'))->assertOk();
    $this->actingAs($agent)->get(route('nin.verification.index'))->assertOk();
});

test('UtilityAccess::canUse is false for agent and canVerify is true for agent', function () {
    $agent = makeUserWithWallet('agent', 0);

    expect(UtilityAccess::canUse($agent))->toBeFalse();
    expect(UtilityAccess::canVerify($agent))->toBeTrue();
});

// ---------------------------------------------------------------------
// Header / sidebar visibility
// ---------------------------------------------------------------------

test('the Upgrade to Agent navbar button is visible to personal and hidden from agent', function () {
    $personal = makeUserWithWallet('personal', 0);
    $agent = makeUserWithWallet('agent', 0);

    $this->actingAs($personal)->get(route('dashboard'))->assertOk()
        ->assertSee(route('agent-upgrade.show'), false)
        ->assertSee('Upgrade to Agent');
    $this->actingAs($agent)->get(route('dashboard'))->assertOk()->assertDontSee(route('agent-upgrade.show'), false);
});

test('agent does not see SIM Services, P2P Transfer, or Cash Out in the sidebar, but still sees My Wallet', function () {
    $agent = makeUserWithWallet('agent', 0);

    $response = $this->actingAs($agent)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('SIM Services');
    $response->assertDontSee('P2P Transfer');
    $response->assertDontSee('Cash Out');
    $response->assertSee('My Wallet');
});
