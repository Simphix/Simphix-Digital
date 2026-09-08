<?php

use App\Models\Sim;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('activated numbers export includes every active number regardless of dashboard filters or pagination', function () {
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
    expect(array_column($rows, 0))->toBe($numbers);
});

test('activated numbers export returns a header when there are no active sims', function () {
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
