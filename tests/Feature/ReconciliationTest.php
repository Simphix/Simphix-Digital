<?php

use App\Models\CashOutRequest;
use App\Models\CommissionEarning;
use App\Models\PlatformWallet;
use App\Models\Report;
use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WebhookLog;
use App\Services\Reconciliation\ReconciliationEngine;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function reconciliationAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

function walletFor(User $user, float $balance = 0.00, float $bonus = 0.00): Wallet
{
    return Wallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'bonus' => $bonus,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);
}

// -----------------------------------------------------------------------
// Access control
// -----------------------------------------------------------------------

test('super admin can view every reconciliation tab', function () {
    $admin = reconciliationAdmin();

    foreach (array_keys(ReconciliationEngine::RECONCILERS) as $tab) {
        $this->actingAs($admin)->get(route('admin.reconciliation.index', $tab))->assertOk();
    }
});

test('non-super-admin is forbidden on every reconciliation tab', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    foreach (array_keys(ReconciliationEngine::RECONCILERS) as $tab) {
        $this->actingAs($partner)->get(route('admin.reconciliation.index', $tab))->assertForbidden();
    }
});

test('an unknown tab 404s', function () {
    $this->actingAs(reconciliationAdmin())->get(route('admin.reconciliation.index', 'not-a-real-tab'))->assertNotFound();
});

// -----------------------------------------------------------------------
// P2P
// -----------------------------------------------------------------------

test('a matched P2P transfer pair produces no finding', function () {
    $sender = User::factory()->create(['role' => 'personal']);
    $receiver = User::factory()->create(['role' => 'personal']);
    $ref = 'TRF' . uniqid();
    $reportIds = [];

    foreach ([[$sender, $ref, 500], [$receiver, $ref . '-REC', 500]] as [$user, $r, $amount]) {
        Transaction::create(['transaction_ref' => $r, 'user_id' => $user->id, 'amount' => $amount, 'fee' => 0, 'net_amount' => $amount, 'description' => 'x', 'type' => 'credit', 'status' => 'completed', 'performed_by' => 'test']);
        $reportIds[] = Report::create(['user_id' => $user->id, 'ref' => $r, 'amount' => $amount, 'status' => 'completed', 'type' => 'transfer', 'description' => 'x', 'old_balance' => 0, 'new_balance' => $amount])->id;
    }

    $findings = app(ReconciliationEngine::class)->run('p2p', now()->subDay(), now()->addDay());
    $relatedToThisPair = $findings->filter(fn ($f) => count(array_intersect($f->related_ids, $reportIds)) > 0);
    expect($relatedToThisPair)->toHaveCount(0);
});

test('an orphaned P2P sender leg with no receiver leg is flagged', function () {
    $sender = User::factory()->create(['role' => 'personal']);
    $ref = 'TRF' . uniqid();

    Transaction::create(['transaction_ref' => $ref, 'user_id' => $sender->id, 'amount' => 500, 'fee' => 0, 'net_amount' => 500, 'description' => 'x', 'type' => 'debit', 'status' => 'completed', 'performed_by' => 'test']);
    $reportId = Report::create(['user_id' => $sender->id, 'ref' => $ref, 'amount' => 500, 'status' => 'completed', 'type' => 'transfer', 'description' => 'x', 'old_balance' => 500, 'new_balance' => 0])->id;

    $findings = app(ReconciliationEngine::class)->run('p2p', now()->subDay(), now()->addDay());
    $relatedToThisReport = $findings->first(fn ($f) => in_array($reportId, $f->related_ids, true));
    expect($relatedToThisReport)->not->toBeNull();
    expect($relatedToThisReport->severity)->toBe('critical');
});

// -----------------------------------------------------------------------
// Deposits
// -----------------------------------------------------------------------

test('a successful payin webhook with a matching completed transaction produces no finding', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $orderNo = 'ORDER' . uniqid();

    WebhookLog::create(['provider' => 'palmpay', 'event_type' => 'payin', 'payload' => ['merchantOrderNo' => $orderNo], 'status' => 'success']);
    Transaction::create(['transaction_ref' => $orderNo, 'user_id' => $user->id, 'amount' => 1000, 'fee' => 0, 'net_amount' => 1000, 'description' => 'x', 'type' => 'credit', 'status' => 'completed', 'performed_by' => 'test']);

    $findings = app(ReconciliationEngine::class)->run('deposits', now()->subDay(), now()->addDay());
    expect($findings->count())->toBe(0);
});

test('a successful payin webhook with no matching transaction is flagged critical', function () {
    WebhookLog::create(['provider' => 'palmpay', 'event_type' => 'payin', 'payload' => ['merchantOrderNo' => 'ORPHAN' . uniqid()], 'status' => 'success']);

    $findings = app(ReconciliationEngine::class)->run('deposits', now()->subDay(), now()->addDay());
    expect($findings->count())->toBe(1);
    expect($findings->first()->severity)->toBe('critical');
});

// -----------------------------------------------------------------------
// Cashouts
// -----------------------------------------------------------------------

test('a cashout pending past the stuck threshold is flagged', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $request = CashOutRequest::create([
        'user_id' => $user->id, 'transaction_ref' => 'WDL' . uniqid(), 'bank_code' => '001', 'bank_name' => 'Test Bank',
        'account_no' => '0000000000', 'account_name' => 'Test', 'amount' => 1000, 'fee' => 0, 'tax' => 0, 'total_charge' => 1000, 'status' => 'pending',
    ]);
    $request->forceFill(['created_at' => now()->subHours(2)])->save();

    $findings = app(ReconciliationEngine::class)->run('cashouts', now()->subDay(), now()->addDay(), ['stuckThresholdMinutes' => 60]);
    expect($findings->pluck('related_ids')->flatten()->contains($request->id))->toBeTrue();
});

test('a recently pending cashout under the stuck threshold is not flagged', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $request = CashOutRequest::create([
        'user_id' => $user->id, 'transaction_ref' => 'WDL' . uniqid(), 'bank_code' => '001', 'bank_name' => 'Test Bank',
        'account_no' => '0000000000', 'account_name' => 'Test', 'amount' => 1000, 'fee' => 0, 'tax' => 0, 'total_charge' => 1000, 'status' => 'pending',
    ]);

    $findings = app(ReconciliationEngine::class)->run('cashouts', now()->subDay(), now()->addDay(), ['stuckThresholdMinutes' => 60]);
    expect($findings->pluck('related_ids')->flatten()->contains($request->id))->toBeFalse();
});

// -----------------------------------------------------------------------
// Commissions
// -----------------------------------------------------------------------

test('a commission earning with a matching credit transaction produces no finding', function () {
    $user = User::factory()->create(['role' => 'partner']);
    $sim = Sim::create(['number' => '0803' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED]);

    CommissionEarning::create(['user_id' => $user->id, 'role' => 'partner', 'sim_id' => $sim->id, 'amount' => 75, 'period_week' => now()->startOfWeek()->toDateString()]);
    Transaction::create([
        'transaction_ref' => 'COMM-' . uniqid(), 'user_id' => $user->id, 'amount' => 75, 'fee' => 0, 'net_amount' => 75, 'description' => 'x',
        'type' => 'credit', 'status' => 'completed', 'performed_by' => 'System',
        'metadata' => json_encode(['sim_number' => $sim->number, 'category' => 'POS SIM', 'type' => 'activation_commission', 'role' => 'partner']),
    ]);

    $findings = app(ReconciliationEngine::class)->run('commissions', now()->subDay(), now()->addDay());
    expect($findings->count())->toBe(0);
});

test('a commission earning with no matching transaction is flagged', function () {
    $user = User::factory()->create(['role' => 'partner']);
    $sim = Sim::create(['number' => '0803' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED]);

    CommissionEarning::create(['user_id' => $user->id, 'role' => 'partner', 'sim_id' => $sim->id, 'amount' => 75, 'period_week' => now()->startOfWeek()->toDateString()]);

    $findings = app(ReconciliationEngine::class)->run('commissions', now()->subDay(), now()->addDay());
    expect($findings->count())->toBe(1);
    expect($findings->first()->severity)->toBe('critical');
});

// -----------------------------------------------------------------------
// Purchases
// -----------------------------------------------------------------------

test('a matched purchase pair produces no finding', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $ref = 'AIR' . uniqid();

    Transaction::create(['transaction_ref' => $ref, 'user_id' => $user->id, 'amount' => 500, 'fee' => 0, 'net_amount' => 500, 'description' => 'x', 'type' => 'debit', 'status' => 'completed', 'performed_by' => 'test']);
    Report::create(['user_id' => $user->id, 'ref' => $ref, 'amount' => 500, 'status' => 'completed', 'type' => 'airtime', 'description' => 'x', 'old_balance' => 500, 'new_balance' => 0]);

    $findings = app(ReconciliationEngine::class)->run('purchases', now()->subDay(), now()->addDay());
    expect($findings->count())->toBe(0);
});

test('a purchase report with an amount mismatch against its transaction is flagged', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $ref = 'AIR' . uniqid();

    Transaction::create(['transaction_ref' => $ref, 'user_id' => $user->id, 'amount' => 500, 'fee' => 0, 'net_amount' => 500, 'description' => 'x', 'type' => 'debit', 'status' => 'completed', 'performed_by' => 'test']);
    Report::create(['user_id' => $user->id, 'ref' => $ref, 'amount' => 450, 'status' => 'completed', 'type' => 'airtime', 'description' => 'x', 'old_balance' => 500, 'new_balance' => 50]);

    $findings = app(ReconciliationEngine::class)->run('purchases', now()->subDay(), now()->addDay());
    expect($findings->where('severity', 'critical')->count())->toBeGreaterThan(0);
});

// -----------------------------------------------------------------------
// Balance integrity
// -----------------------------------------------------------------------

test('a wallet whose balance matches its transaction history produces no finding', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $wallet = walletFor($user, 500, 0);
    $wallet->forceFill(['last_activity' => now()])->save();

    Transaction::create(['transaction_ref' => 'DEP' . uniqid(), 'user_id' => $user->id, 'amount' => 500, 'fee' => 0, 'net_amount' => 500, 'description' => 'x', 'type' => 'credit', 'status' => 'completed', 'performed_by' => 'test']);

    $findings = app(ReconciliationEngine::class)->run('balance_integrity', now()->subDay(), now()->addDay());
    expect($findings->pluck('related_ids')->flatten()->contains($wallet->id))->toBeFalse();
});

test('a wallet whose balance drifts from its transaction history is flagged', function () {
    $user = User::factory()->create(['role' => 'personal']);
    $wallet = walletFor($user, 5000, 0);
    $wallet->forceFill(['last_activity' => now()])->save();

    Transaction::create(['transaction_ref' => 'DEP' . uniqid(), 'user_id' => $user->id, 'amount' => 500, 'fee' => 0, 'net_amount' => 500, 'description' => 'x', 'type' => 'credit', 'status' => 'completed', 'performed_by' => 'test']);

    $findings = app(ReconciliationEngine::class)->run('balance_integrity', now()->subDay(), now()->addDay());
    expect($findings->pluck('related_ids')->flatten()->contains($wallet->id))->toBeTrue();
});

test('a claimed bonus resolves to zero drift in both balance and bonus buckets', function () {
    $user = User::factory()->create(['role' => 'partner']);
    $wallet = walletFor($user, 75, 0);
    $wallet->forceFill(['last_activity' => now()])->save();

    Transaction::create([
        'transaction_ref' => 'COMM-' . uniqid(), 'user_id' => $user->id, 'amount' => 75, 'fee' => 0, 'net_amount' => 75, 'description' => 'x',
        'type' => 'credit', 'status' => 'completed', 'performed_by' => 'System',
        'metadata' => json_encode(['sim_number' => '08000000000', 'category' => 'POS SIM', 'type' => 'activation_commission', 'role' => 'partner']),
    ]);
    Transaction::create(['transaction_ref' => 'BONUS-' . uniqid(), 'user_id' => $user->id, 'amount' => 75, 'fee' => 0, 'net_amount' => 75, 'description' => 'x', 'type' => 'credit', 'status' => 'completed', 'performed_by' => 'test']);

    $findings = app(ReconciliationEngine::class)->run('balance_integrity', now()->subDay(), now()->addDay());
    expect($findings->pluck('related_ids')->flatten()->contains($wallet->id))->toBeFalse();
});

// -----------------------------------------------------------------------
// Platform wallet
// -----------------------------------------------------------------------

test('a platform wallet whose totals match its balance produces no finding', function () {
    $wallet = PlatformWallet::create(['type' => 'welfare_test_ok_' . uniqid(), 'name' => 'Test Welfare', 'balance' => 100, 'total_credited' => 100, 'total_debited' => 0]);

    $findings = app(ReconciliationEngine::class)->run('platform_wallet', now()->subDay(), now()->addDay());
    expect($findings->pluck('related_ids')->flatten()->contains($wallet->id))->toBeFalse();
});

test('a platform wallet whose totals drift from its balance is flagged', function () {
    $wallet = PlatformWallet::create(['type' => 'welfare_test_bad_' . uniqid(), 'name' => 'Test Welfare', 'balance' => 999, 'total_credited' => 100, 'total_debited' => 0]);

    $findings = app(ReconciliationEngine::class)->run('platform_wallet', now()->subDay(), now()->addDay());
    expect($findings->pluck('related_ids')->flatten()->contains($wallet->id))->toBeTrue();
});

// -----------------------------------------------------------------------
// SIM bonus backfill audit
// -----------------------------------------------------------------------

test('two activation bonus grants landing in the same rolling period are flagged', function () {
    $admin = reconciliationAdmin();
    $simOwner = User::factory()->create(['role' => 'personal']);
    $sim = Sim::create(['number' => '0805' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED, 'user_id' => $simOwner->id]);
    \App\Models\SimHistory::create(['sim_id' => $sim->id, 'from_status' => SimStatus::ASSIGNED_TO_PARTNER, 'to_status' => SimStatus::ACTIVATED, 'actor_id' => $admin->id, 'note' => 'test']);

    $reportIds = [];
    foreach ([now()->subDays(2), now()->subDay()] as $when) {
        $report = Report::create(['user_id' => $simOwner->id, 'phone_number' => $sim->number, 'ref' => 'BON' . uniqid(), 'amount' => 0, 'status' => 'completed', 'type' => 'activation_bonus_data', 'description' => 'x', 'old_balance' => 0, 'new_balance' => 0]);
        $report->forceFill(['created_at' => $when])->save();
        $reportIds[] = $report->id;
    }

    $findings = app(ReconciliationEngine::class)->run('sim_bonus', now()->subWeek(), now()->addDay());
    $matching = $findings->first(fn ($f) => count(array_intersect($f->related_ids, $reportIds)) === 2);
    expect($matching)->not->toBeNull();
    expect($matching->severity)->toBe('critical');
});

test('two activation bonus grants in different rolling periods are not flagged', function () {
    $admin = reconciliationAdmin();
    $simOwner = User::factory()->create(['role' => 'personal']);
    $sim = Sim::create(['number' => '0805' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED, 'user_id' => $simOwner->id]);
    $history = \App\Models\SimHistory::create(['sim_id' => $sim->id, 'from_status' => SimStatus::ASSIGNED_TO_PARTNER, 'to_status' => SimStatus::ACTIVATED, 'actor_id' => $admin->id, 'note' => 'test']);
    $history->forceFill(['created_at' => now()->subYear()])->save();

    $reportIds = [];
    foreach ([now()->subDays(3), now()->subYear()->addMonthsNoOverflow(2)] as $when) {
        $report = Report::create(['user_id' => $simOwner->id, 'phone_number' => $sim->number, 'ref' => 'BON' . uniqid(), 'amount' => 0, 'status' => 'completed', 'type' => 'activation_bonus_data', 'description' => 'x', 'old_balance' => 0, 'new_balance' => 0]);
        $report->forceFill(['created_at' => $when])->save();
        $reportIds[] = $report->id;
    }

    $findings = app(ReconciliationEngine::class)->run('sim_bonus', now()->subYear(), now()->addDay());
    $matching = $findings->first(fn ($f) => count(array_intersect($f->related_ids, $reportIds)) >= 2);
    expect($matching)->toBeNull();
});
