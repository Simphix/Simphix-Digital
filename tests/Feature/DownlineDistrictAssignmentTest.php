<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DownlineDistrictAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private function createRegionalManagerWithRegion(string $suffix): array
    {
        $rm = User::factory()->create([
            'role' => 'regional_manager',
            'account_tier' => 1,
            'status' => 'active',
        ]);

        $region = Region::create([
            'name' => 'Region ' . $suffix,
            'regional_manager_id' => $rm->id,
        ]);

        $state1 = State::create([
            'name' => 'State A ' . $suffix,
            'region_id' => $region->id,
        ]);

        $state2 = State::create([
            'name' => 'State B ' . $suffix,
            'region_id' => $region->id,
        ]);

        $district1 = SenatorialDistrict::create([
            'name' => 'District 1 ' . $suffix,
            'state_id' => $state1->id,
        ]);

        $district2 = SenatorialDistrict::create([
            'name' => 'District 2 ' . $suffix,
            'state_id' => $state2->id,
        ]);

        return compact('rm', 'region', 'state1', 'state2', 'district1', 'district2');
    }

    private function createCoordinator(User $upline, string $suffix): User
    {
        return User::factory()->create([
            'role' => 'coordinator',
            'referred_by' => $upline->id,
            'account_tier' => 1,
            'status' => 'active',
        ]);
    }

    public function test_regional_manager_sees_territory_assignment_section_for_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $response = $this->actingAs($setup['rm'])->get(route('network.downline.show', $coordinator));

        $response->assertOk();
        $response->assertSee('Territory (Senatorial District)');
        $response->assertSee('No Territory Assigned');
        $response->assertSee($setup['district1']->name);
        $response->assertSee($setup['district2']->name);
    }

    public function test_regional_manager_can_assign_existing_district_to_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $response = $this->actingAs($setup['rm'])->post(route('network.downline.assign-district', $coordinator), [
            'territory_mode' => 'existing',
            'district_id' => $setup['district1']->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $setup['district1']->id,
            'coordinator_id' => $coordinator->id,
        ]);

        // When viewing downline page, assigned territory is displayed
        $pageResponse = $this->actingAs($setup['rm'])->get(route('network.downline.show', $coordinator));
        $pageResponse->assertOk();
        $pageResponse->assertSee($setup['district1']->name);
        $pageResponse->assertSee('Unassign');
    }

    public function test_regional_manager_can_create_and_assign_new_district_to_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $newDistrictName = 'New Central ' . $suffix;

        $response = $this->actingAs($setup['rm'])->post(route('network.downline.assign-district', $coordinator), [
            'territory_mode' => 'new',
            'new_district_name' => $newDistrictName,
            'new_district_state_id' => $setup['state1']->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('senatorial_districts', [
            'name' => $newDistrictName,
            'state_id' => $setup['state1']->id,
            'coordinator_id' => $coordinator->id,
        ]);
    }

    public function test_regional_manager_can_unassign_district_from_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $setup['district1']->update(['coordinator_id' => $coordinator->id]);

        $response = $this->actingAs($setup['rm'])->post(route('network.downline.unassign-district', $coordinator));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $setup['district1']->id,
            'coordinator_id' => null,
        ]);
    }

    public function test_regional_manager_reassigning_district_updates_previous_district(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $setup['district1']->update(['coordinator_id' => $coordinator->id]);

        // Reassign to district 2
        $response = $this->actingAs($setup['rm'])->post(route('network.downline.assign-district', $coordinator), [
            'territory_mode' => 'existing',
            'district_id' => $setup['district2']->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $setup['district1']->id,
            'coordinator_id' => null,
        ]);

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $setup['district2']->id,
            'coordinator_id' => $coordinator->id,
        ]);
    }

    public function test_regional_manager_cannot_assign_district_outside_their_region(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        // Another region
        $otherSuffix = strtolower(Str::random(6));
        $otherRegion = Region::create(['name' => 'Other Region ' . $otherSuffix]);
        $otherState = State::create(['name' => 'Other State ' . $otherSuffix, 'region_id' => $otherRegion->id]);
        $otherDistrict = SenatorialDistrict::create(['name' => 'Other District ' . $otherSuffix, 'state_id' => $otherState->id]);

        $response = $this->actingAs($setup['rm'])->post(route('network.downline.assign-district', $coordinator), [
            'territory_mode' => 'existing',
            'district_id' => $otherDistrict->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'The selected district does not belong to your managed region.');

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $otherDistrict->id,
            'coordinator_id' => null,
        ]);
    }

    public function test_regional_manager_cannot_assign_district_occupied_by_another_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator1 = $this->createCoordinator($setup['rm'], $suffix);
        $coordinator2 = $this->createCoordinator($setup['rm'], $suffix . '2');

        // District 1 assigned to coordinator 2
        $setup['district1']->update(['coordinator_id' => $coordinator2->id]);

        $response = $this->actingAs($setup['rm'])->post(route('network.downline.assign-district', $coordinator1), [
            'territory_mode' => 'existing',
            'district_id' => $setup['district1']->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This senatorial district is already assigned to another coordinator.');

        $this->assertDatabaseHas('senatorial_districts', [
            'id' => $setup['district1']->id,
            'coordinator_id' => $coordinator2->id,
        ]);
    }

    public function test_unauthorized_user_cannot_assign_or_unassign_district(): void
    {
        $suffix = strtolower(Str::random(6));
        $setup = $this->createRegionalManagerWithRegion($suffix);
        $coordinator = $this->createCoordinator($setup['rm'], $suffix);

        $partner = User::factory()->create(['role' => 'partner']);

        // Partner trying to assign
        $response = $this->actingAs($partner)->post(route('network.downline.assign-district', $coordinator), [
            'territory_mode' => 'existing',
            'district_id' => $setup['district1']->id,
        ]);
        $response->assertForbidden();

        // Partner trying to unassign
        $response = $this->actingAs($partner)->post(route('network.downline.unassign-district', $coordinator));
        $response->assertForbidden();
    }
}
