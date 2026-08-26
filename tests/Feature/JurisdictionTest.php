<?php

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function jurisdictionAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

// -----------------------------------------------------------------------
// Regions CRUD
// -----------------------------------------------------------------------

test('super admin can create, update, and delete a region', function () {
    $admin = jurisdictionAdmin();
    $rm = User::factory()->create(['role' => 'regional_manager']);

    $this->actingAs($admin)->post(route('admin.jurisdictions.regions.store'), [
        'name' => 'North Central',
        'regional_manager_id' => $rm->id,
    ])->assertRedirect();

    $region = Region::where('name', 'North Central')->firstOrFail();
    expect($region->regional_manager_id)->toBe($rm->id);

    $this->actingAs($admin)->put(route('admin.jurisdictions.regions.update', $region), [
        'name' => 'North Central Renamed',
        'regional_manager_id' => $rm->id,
    ])->assertRedirect();
    expect($region->fresh()->name)->toBe('North Central Renamed');

    $this->actingAs($admin)->delete(route('admin.jurisdictions.regions.destroy', $region))->assertRedirect();
    expect(Region::find($region->id))->toBeNull();
});

test('non-super-admins are forbidden from every jurisdiction route', function () {
    $user = User::factory()->create(['role' => 'partner']);
    $region = Region::create(['name' => 'Test Region']);
    $state = State::create(['name' => 'Test State', 'region_id' => $region->id]);
    $district = SenatorialDistrict::create(['name' => 'Test District', 'state_id' => $state->id]);

    $this->actingAs($user)->get(route('admin.jurisdictions.regions.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.jurisdictions.regions.store'), ['name' => 'X'])->assertForbidden();
    $this->actingAs($user)->put(route('admin.jurisdictions.regions.update', $region), ['name' => 'X'])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.jurisdictions.regions.destroy', $region))->assertForbidden();

    $this->actingAs($user)->get(route('admin.jurisdictions.states.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.jurisdictions.states.store'), ['name' => 'X'])->assertForbidden();
    $this->actingAs($user)->put(route('admin.jurisdictions.states.update', $state), ['name' => 'X'])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.jurisdictions.states.destroy', $state))->assertForbidden();

    $this->actingAs($user)->get(route('admin.jurisdictions.districts.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.jurisdictions.districts.store'), ['name' => 'X', 'state_id' => $state->id])->assertForbidden();
    $this->actingAs($user)->put(route('admin.jurisdictions.districts.update', $district), ['name' => 'X', 'state_id' => $state->id])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.jurisdictions.districts.destroy', $district))->assertForbidden();
});

// -----------------------------------------------------------------------
// Assignment validation
// -----------------------------------------------------------------------

test('assigning a user without the regional_manager role to a region is rejected', function () {
    $admin = jurisdictionAdmin();
    $wrongRole = User::factory()->create(['role' => 'partner']);

    $this->actingAs($admin)->post(route('admin.jurisdictions.regions.store'), [
        'name' => 'North Central',
        'regional_manager_id' => $wrongRole->id,
    ])->assertSessionHasErrors('regional_manager_id');

    expect(Region::where('name', 'North Central')->exists())->toBeFalse();
});

test('assigning an already-claimed regional manager to a second region is rejected with the first untouched', function () {
    $admin = jurisdictionAdmin();
    $rm = User::factory()->create(['role' => 'regional_manager']);
    $regionA = Region::create(['name' => 'Region A', 'regional_manager_id' => $rm->id]);

    $this->actingAs($admin)->post(route('admin.jurisdictions.regions.store'), [
        'name' => 'Region B',
        'regional_manager_id' => $rm->id,
    ])->assertSessionHasErrors('regional_manager_id');

    expect(Region::where('name', 'Region B')->exists())->toBeFalse();
    expect($regionA->fresh()->regional_manager_id)->toBe($rm->id);
});

test('assigning a user without the coordinator role to a district is rejected', function () {
    $admin = jurisdictionAdmin();
    $state = State::create(['name' => 'Lagos']);
    $wrongRole = User::factory()->create(['role' => 'partner']);

    $this->actingAs($admin)->post(route('admin.jurisdictions.districts.store'), [
        'name' => 'Lagos East',
        'state_id' => $state->id,
        'coordinator_id' => $wrongRole->id,
    ])->assertSessionHasErrors('coordinator_id');

    expect(SenatorialDistrict::where('name', 'Lagos East')->exists())->toBeFalse();
});

test('assigning an already-claimed coordinator to a second district is rejected with the first untouched', function () {
    $admin = jurisdictionAdmin();
    $state = State::create(['name' => 'Lagos']);
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $districtA = SenatorialDistrict::create(['name' => 'District A', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $this->actingAs($admin)->post(route('admin.jurisdictions.districts.store'), [
        'name' => 'District B',
        'state_id' => $state->id,
        'coordinator_id' => $coordinator->id,
    ])->assertSessionHasErrors('coordinator_id');

    expect(SenatorialDistrict::where('name', 'District B')->exists())->toBeFalse();
    expect($districtA->fresh()->coordinator_id)->toBe($coordinator->id);
});

// -----------------------------------------------------------------------
// The core remap cascade
// -----------------------------------------------------------------------

test('moving a state to a different region cascades the coordinator jurisdiction with zero writes to the district', function () {
    $admin = jurisdictionAdmin();
    $regionA = Region::create(['name' => 'Region A']);
    $regionB = Region::create(['name' => 'Region B']);
    $state = State::create(['name' => 'Lagos', 'region_id' => $regionA->id]);
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $district = SenatorialDistrict::create(['name' => 'Lagos East', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    expect($coordinator->fresh()->jurisdictionLabel())->toContain('Region A');

    $originalUpdatedAt = $district->updated_at;

    $this->actingAs($admin)->put(route('admin.jurisdictions.states.update', $state), [
        'name' => 'Lagos',
        'region_id' => $regionB->id,
    ])->assertRedirect();

    expect($coordinator->fresh()->jurisdictionLabel())->toContain('Region B');

    $district->refresh();
    expect($district->coordinator_id)->toBe($coordinator->id);
    expect($district->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

// -----------------------------------------------------------------------
// Role-change safety net
// -----------------------------------------------------------------------

test('changing a regional manager away from that role via updateUser clears the stale region assignment', function () {
    $admin = jurisdictionAdmin();
    $rm = User::factory()->create(['role' => 'regional_manager', 'status' => 'active']);
    $region = Region::create(['name' => 'Region A', 'regional_manager_id' => $rm->id]);

    $this->actingAs($admin)->put(route('admin.manage.users.update', $rm), [
        'first_name' => $rm->first_name,
        'middle_name' => $rm->middle_name,
        'last_name' => $rm->last_name,
        'email' => $rm->email,
        'phone' => $rm->phone,
        'status' => 'active',
        'role' => 'partner',
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($region->fresh()->regional_manager_id)->toBeNull();
});

test('resubmitting the same role via updateUser leaves the region assignment untouched', function () {
    $admin = jurisdictionAdmin();
    $rm = User::factory()->create(['role' => 'regional_manager', 'status' => 'active']);
    $region = Region::create(['name' => 'Region A', 'regional_manager_id' => $rm->id]);

    $this->actingAs($admin)->put(route('admin.manage.users.update', $rm), [
        'first_name' => $rm->first_name,
        'middle_name' => $rm->middle_name,
        'last_name' => $rm->last_name,
        'email' => $rm->email,
        'phone' => $rm->phone,
        'status' => 'active',
        'role' => 'regional_manager',
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($region->fresh()->regional_manager_id)->toBe($rm->id);
});

test('changing a coordinator away from that role via updateAccess clears the stale district assignment', function () {
    $admin = jurisdictionAdmin();
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $state = State::create(['name' => 'Lagos']);
    $district = SenatorialDistrict::create(['name' => 'Lagos East', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $this->actingAs($admin)->put(route('admin.manage.access.update', $coordinator), [
        'role' => 'partner',
    ])->assertRedirect();

    expect($district->fresh()->coordinator_id)->toBeNull();
});

// -----------------------------------------------------------------------
// Deletion blocking
// -----------------------------------------------------------------------

test('a region with states cannot be deleted, but deletes cleanly once empty', function () {
    $admin = jurisdictionAdmin();
    $region = Region::create(['name' => 'Region A']);
    $state = State::create(['name' => 'Lagos', 'region_id' => $region->id]);

    $this->actingAs($admin)->delete(route('admin.jurisdictions.regions.destroy', $region))
        ->assertRedirect()
        ->assertSessionHas('error');
    expect(Region::find($region->id))->not->toBeNull();

    $state->delete();

    $this->actingAs($admin)->delete(route('admin.jurisdictions.regions.destroy', $region))->assertRedirect();
    expect(Region::find($region->id))->toBeNull();
});

test('a state with districts cannot be deleted, but deletes cleanly once empty', function () {
    $admin = jurisdictionAdmin();
    $state = State::create(['name' => 'Lagos']);
    $district = SenatorialDistrict::create(['name' => 'Lagos East', 'state_id' => $state->id]);

    $this->actingAs($admin)->delete(route('admin.jurisdictions.states.destroy', $state))
        ->assertRedirect()
        ->assertSessionHas('error');
    expect(State::find($state->id))->not->toBeNull();

    $district->delete();

    $this->actingAs($admin)->delete(route('admin.jurisdictions.states.destroy', $state))->assertRedirect();
    expect(State::find($state->id))->toBeNull();
});

test('a senatorial district always deletes regardless of coordinator assignment', function () {
    $admin = jurisdictionAdmin();
    $state = State::create(['name' => 'Lagos']);
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $district = SenatorialDistrict::create(['name' => 'Lagos East', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $this->actingAs($admin)->delete(route('admin.jurisdictions.districts.destroy', $district))->assertRedirect();
    expect(SenatorialDistrict::find($district->id))->toBeNull();
});

// -----------------------------------------------------------------------
// Display: dashboard + profile
// -----------------------------------------------------------------------

test('an assigned regional manager sees their region label on dashboard and profile', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Region::create(['name' => 'North Central', 'regional_manager_id' => $rm->id]);

    $this->actingAs($rm)->get(route('dashboard'))->assertOk()->assertSee('North Central');
    $this->actingAs($rm)->get(route('profile.edit'))->assertOk()->assertSee('North Central');
});

test('an unassigned coordinator sees an unassigned jurisdiction indicator on dashboard and profile', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator']);

    $this->actingAs($coordinator)->get(route('dashboard'))->assertOk()->assertSee('Jurisdiction unassigned');
    $this->actingAs($coordinator)->get(route('profile.edit'))->assertOk()->assertSee('Unassigned');
});

test('a coordinator with an assigned district sees the full district/state/region label', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $region = Region::create(['name' => 'North Central']);
    $state = State::create(['name' => 'Lagos', 'region_id' => $region->id]);
    SenatorialDistrict::create(['name' => 'Lagos East', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $label = $coordinator->fresh()->jurisdictionLabel();
    expect($label)->toBe('Lagos East · Lagos · North Central');

    $this->actingAs($coordinator)->get(route('dashboard'))->assertOk()->assertSee($label);
});

test('roles without jurisdiction show no jurisdiction chip on dashboard', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('dashboard'))->assertOk()->assertDontSee('Jurisdiction unassigned');
});
