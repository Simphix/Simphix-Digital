<?php

use App\Models\AdminNotification;
use App\Models\Service;
use App\Models\User;
use App\Support\StaffAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);
});

test('admin cannot assign stale roles staff or checker via user edit', function (string $staleRole) {
    $targetUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $response = $this->actingAs($this->admin)->put(route('admin.manage.users.update', $targetUser), [
        'first_name' => $targetUser->first_name,
        'middle_name' => $targetUser->middle_name,
        'last_name' => $targetUser->last_name,
        'email' => $targetUser->email,
        'phone' => '08012345678',
        'status' => 'active',
        'role' => $staleRole,
    ]);

    $response->assertSessionHasErrors('role');
    expect($targetUser->fresh()->role)->toBe('personal');
})->with(['staff', 'checker']);

test('admin cannot assign stale roles staff or checker via access update', function (string $staleRole) {
    $targetUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $response = $this->actingAs($this->admin)->put(route('admin.manage.access.update', $targetUser), [
        'role' => $staleRole,
    ]);

    $response->assertSessionHasErrors('role');
    expect($targetUser->fresh()->role)->toBe('personal');
})->with(['staff', 'checker']);

test('admin can assign valid platform roles including business_sales_manager, business_agent, and support', function (string $validRole) {
    $targetUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $response = $this->actingAs($this->admin)->put(route('admin.manage.access.update', $targetUser), [
        'role' => $validRole,
    ]);

    $response->assertSessionHasNoErrors();
    expect($targetUser->fresh()->role)->toBe($validRole);
})->with([
    'personal', 'agent', 'partner', 'business', 'business_agent',
    'regional_manager', 'coordinator', 'business_sales_manager', 'support',
]);

test('admin cannot assign super_admin via access update or user edit', function () {
    $targetUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $accessResponse = $this->actingAs($this->admin)->put(route('admin.manage.access.update', $targetUser), [
        'role' => 'super_admin',
    ]);
    $accessResponse->assertSessionHasErrors('role');
    expect($targetUser->fresh()->role)->toBe('personal');

    $editResponse = $this->actingAs($this->admin)->put(route('admin.manage.users.update', $targetUser), [
        'first_name' => $targetUser->first_name,
        'middle_name' => $targetUser->middle_name,
        'last_name' => $targetUser->last_name,
        'email' => $targetUser->email,
        'phone' => '08012345678',
        'status' => 'active',
        'role' => 'super_admin',
    ]);
    $editResponse->assertSessionHasErrors('role');
    expect($targetUser->fresh()->role)->toBe('personal');
});

test('existing super_admin role cannot be modified via access update', function () {
    $otherAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

    $response = $this->actingAs($this->admin)->put(route('admin.manage.access.update', $otherAdmin), [
        'role' => 'support',
    ]);

    $response->assertSessionHas('error');
    expect($otherAdmin->fresh()->role)->toBe('super_admin');
});

test('make:admin command promotes a user to super_admin by email', function () {
    $user = User::factory()->create([
        'role' => 'personal',
        'status' => 'active',
    ]);

    $this->artisan('make:admin', ['email' => $user->email])
        ->expectsOutputToContain("Successfully promoted {$user->first_name} {$user->last_name}")
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe('super_admin');
});

test('make:admin alias admin:make also works', function () {
    $user = User::factory()->create([
        'role' => 'agent',
        'status' => 'active',
    ]);

    $this->artisan('admin:make', ['email' => $user->email])
        ->expectsOutputToContain("Successfully promoted {$user->first_name} {$user->last_name}")
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe('super_admin');
});

test('make:admin fails when user is not found', function () {
    $this->artisan('make:admin', ['email' => 'nonexistent@example.com'])
        ->expectsOutputToContain("No user found with email 'nonexistent@example.com'")
        ->assertFailed();
});

test('make:admin can revoke super_admin via --revoke flag', function () {
    $adminUser = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->artisan('make:admin', ['email' => $adminUser->email, '--revoke' => true])
        ->expectsOutputToContain("Successfully revoked Super Admin")
        ->assertSuccessful();

    expect($adminUser->fresh()->role)->toBe('personal');
});

test('StaffAccess::isInternal reflects only active internal roles', function () {
    $support = User::factory()->make(['role' => 'support']);
    $bsm = User::factory()->make(['role' => 'business_sales_manager']);
    $admin = User::factory()->make(['role' => 'super_admin']);
    $staff = User::factory()->make(['role' => 'staff']);
    $checker = User::factory()->make(['role' => 'checker']);
    $partner = User::factory()->make(['role' => 'partner']);

    expect(StaffAccess::isInternal($support))->toBeTrue()
        ->and(StaffAccess::isInternal($bsm))->toBeTrue()
        ->and(StaffAccess::isInternal($admin))->toBeTrue()
        ->and(StaffAccess::isInternal($staff))->toBeFalse()
        ->and(StaffAccess::isInternal($checker))->toBeFalse()
        ->and(StaffAccess::isInternal($partner))->toBeFalse();
});

test('admin service price reject staff and checker and accept business_sales_manager', function () {
    $service = Service::firstOrCreate(
        ['name' => 'mtn_sme_data'],
        ['display_name' => 'MTN SME Data', 'status' => 'active']
    );

    $responseStale = $this->actingAs($this->admin)->post(route('admin.services.prices.store', $service), [
        'price_target' => 'role',
        'user_type' => 'staff',
        'price' => 500,
    ]);
    $responseStale->assertSessionHasErrors('user_type');

    $responseValid = $this->actingAs($this->admin)->post(route('admin.services.prices.store', $service), [
        'price_target' => 'role',
        'user_type' => 'business_sales_manager',
        'price' => 450,
    ]);
    $responseValid->assertSessionHasNoErrors();
});

test('admin notification ROLES does not contain staff or checker', function () {
    expect(AdminNotification::ROLES)->not->toContain('staff')
        ->and(AdminNotification::ROLES)->not->toContain('checker')
        ->and(AdminNotification::ROLES)->toContain('business_sales_manager')
        ->and(AdminNotification::ROLES)->toContain('business_agent')
        ->and(AdminNotification::ROLES)->toContain('support');
});
