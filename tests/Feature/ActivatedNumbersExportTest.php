<?php

use App\Mail\ActivatedSimsExportMail;
use App\Models\Sim;
use App\Models\SimExportSettings;
use App\Models\SimHistory;
use App\Models\User;
use App\Services\ActivatedSimsExportService;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

test('activated numbers export includes every active number regardless of dashboard filters or pagination', function () {
    $existingNumbers = Sim::where('status', SimStatus::ACTIVATED)->whereNotNull('number')->pluck('number')->all();

    $numbers = [];
    for ($i = 0; $i < 1005; $i++) {
        $numbers[] = '0804' . str_pad((string) $i, 7, '0', STR_PAD_LEFT);
    }
    foreach (array_chunk($numbers, 100) as $chunk) {
        Sim::insert(array_map(fn ($number) => [
            'number' => $number,
            'category' => 'POS SIM',
            'provider' => 'mtn',
            'status' => SimStatus::ACTIVATED,
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ], $chunk));
    }
    Sim::create(['number' => '08049999999', 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::UNASSIGNED]);
    Sim::create(['number' => '08049999998', 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::DEACTIVATED]);

    $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
        ->get(route('admin.sims-operations.activated-numbers.export', ['range' => 'today', 'user_id' => 999999, 'page' => 2]));

    $response->assertOk()->assertDownload('activated-sim-numbers-' . now()->format('Y-m-d') . '.csv');
    $rows = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), explode("\n", trim($response->streamedContent())));
    expect(array_shift($rows))->toBe(['Sim Number']);

    $exportedNumbers = array_column($rows, 0);
    foreach ($numbers as $num) {
        expect($exportedNumbers)->toContain($num);
    }
});

test('activated numbers export returns a header when there are no active sims', function () {
    // Temporarily mark any active SIMs as deactivated within the transaction
    Sim::where('status', SimStatus::ACTIVATED)->update(['status' => SimStatus::DEACTIVATED]);

    $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
        ->get(route('admin.sims-operations.activated-numbers.export'));

    $response->assertOk();
    expect(trim($response->streamedContent()))->toBe('"Sim Number"');
});

test('activated numbers export is restricted to super admins', function () {
    $this->get(route('admin.sims-operations.activated-numbers.export'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'partner']))
        ->get(route('admin.sims-operations.activated-numbers.export'))->assertForbidden();
});

test('export service filters to numbers activated in the given window without updated_at false positives', function () {
    $oldSim = Sim::create([
        'number'     => '08021111111',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $oldSim->id)->update([
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subHours(1), // Updated recently (e.g. top-up) but activated 5 days ago!
    ]);
    $hOld = SimHistory::create([
        'sim_id'     => $oldSim->id,
        'to_status'  => SimStatus::ACTIVATED,
    ]);
    SimHistory::where('id', $hOld->id)->update(['created_at' => now()->subDays(5)]);

    $recentSim = Sim::create([
        'number'     => '08022222222',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $recentSim->id)->update([
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    $historyRecentSim = Sim::create([
        'number'     => '08023333333',
        'category'   => 'POS SIM',
        'provider'   => 'glo',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $historyRecentSim->id)->update([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);
    $hRecent = SimHistory::create([
        'sim_id'     => $historyRecentSim->id,
        'to_status'  => SimStatus::ACTIVATED,
    ]);
    SimHistory::where('id', $hRecent->id)->update(['created_at' => now()->subHours(4)]);

    $exporter = app(ActivatedSimsExportService::class);
    $since = now()->subHours(24);
    $until = now();

    $past24hNumbers = $exporter->activatedNumbers($since, $until)->pluck('number')->all();
    expect($past24hNumbers)->toContain('08022222222');
    expect($past24hNumbers)->toContain('08023333333');
    // Old SIM updated 1h ago MUST NOT be included because its activation was 5 days ago!
    expect($past24hNumbers)->not->toContain('08021111111');

    $allNumbers = $exporter->activatedNumbers(null)->pluck('number')->all();
    expect($allNumbers)->toContain('08021111111');
    expect($allNumbers)->toContain('08022222222');
    expect($allNumbers)->toContain('08023333333');
});

test('nightly sims:export-activated command emails previous calendar day activations by default', function () {
    Mail::fake();

    $settings = SimExportSettings::current();
    $settings->update([
        'is_active'       => true,
        'recipient_email' => 'ops@simphix.com',
    ]);

    $yesterdayLagos = now('Africa/Lagos')->subDay()->setTime(14, 0);

    $yesterdaySim = Sim::create([
        'number'     => '08030000002',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $yesterdaySim->id)->update([
        'created_at' => $yesterdayLagos->copy()->setTimezone('UTC'),
        'updated_at' => $yesterdayLagos->copy()->setTimezone('UTC'),
    ]);
    $hYesterday = SimHistory::create([
        'sim_id'     => $yesterdaySim->id,
        'to_status'  => SimStatus::ACTIVATED,
    ]);
    SimHistory::where('id', $hYesterday->id)->update(['created_at' => $yesterdayLagos->copy()->setTimezone('UTC')]);

    $oldSim = Sim::create([
        'number'     => '08030000001',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $oldSim->id)->update([
        'created_at' => now('Africa/Lagos')->subDays(4)->setTimezone('UTC'),
        'updated_at' => now('Africa/Lagos')->subDays(4)->setTimezone('UTC'),
    ]);

    $this->artisan('sims:export-activated')
        ->expectsOutputToContain($yesterdayLagos->format('M d, Y'))
        ->assertSuccessful();

    Mail::assertSent(ActivatedSimsExportMail::class, function (ActivatedSimsExportMail $mail) use ($yesterdayLagos) {
        return $mail->hasTo('ops@simphix.com')
            && str_contains($mail->csv, '08030000002')
            && !str_contains($mail->csv, '08030000001')
            && $mail->since !== null
            && $mail->reportDate?->format('Y-m-d') === $yesterdayLagos->format('Y-m-d');
    });
});

test('nightly sims:export-activated command with --hours emails rolling period', function () {
    Mail::fake();

    $settings = SimExportSettings::current();
    $settings->update([
        'is_active'       => true,
        'recipient_email' => 'ops@simphix.com',
    ]);

    $recentSim = Sim::create([
        'number'     => '08030000088',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $recentSim->id)->update([
        'created_at' => now()->subHours(3),
        'updated_at' => now()->subHours(3),
    ]);

    $this->artisan('sims:export-activated', ['--hours' => 24])
        ->expectsOutputToContain('past 24 hours')
        ->assertSuccessful();

    Mail::assertSent(ActivatedSimsExportMail::class, function (ActivatedSimsExportMail $mail) {
        return $mail->hasTo('ops@simphix.com')
            && str_contains($mail->csv, '08030000088')
            && $mail->since !== null;
    });
});

test('nightly sims:export-activated command with --all exports all activated numbers', function () {
    Mail::fake();

    $settings = SimExportSettings::current();
    $settings->update([
        'is_active'       => true,
        'recipient_email' => 'ops@simphix.com',
    ]);

    $oldSim = Sim::create([
        'number'     => '08039999001',
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ACTIVATED,
    ]);
    Sim::where('id', $oldSim->id)->update([
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);

    $this->artisan('sims:export-activated', ['--all' => true])
        ->expectsOutputToContain('all-time')
        ->assertSuccessful();

    Mail::assertSent(ActivatedSimsExportMail::class, function (ActivatedSimsExportMail $mail) {
        return $mail->hasTo('ops@simphix.com')
            && str_contains($mail->csv, '08039999001')
            && $mail->since === null;
    });
});
