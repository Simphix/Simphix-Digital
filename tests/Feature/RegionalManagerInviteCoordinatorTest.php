<?php

namespace Tests\Feature;

use App\Mail\OnboardingInviteMail;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegionalManagerInviteCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_regional_manager_sees_territory_assignment_section_on_invite_page(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South West', 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'Lagos', 'region_id' => $region->id]);
        $district = SenatorialDistrict::create(['name' => 'Lagos West', 'state_id' => $state->id]);

        $response = $this->actingAs($rm)->get(route('onboarding.invite'));

        $response->assertOk();
        $response->assertSee('Territory / Senatorial District Assignment');
        $response->assertSee('South West Region');
        $response->assertSee('Lagos West');
        $response->assertSee('Lagos');
        $response->assertSee('Create New District');
    }

    public function test_regional_manager_without_region_sees_warning_on_invite_page(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);

        $response = $this->actingAs($rm)->get(route('onboarding.invite'));

        $response->assertOk();
        $response->assertSee('No Managed Region Assigned');
    }

    public function test_regional_manager_can_invite_coordinator_with_existing_district(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South West', 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'Lagos', 'region_id' => $region->id]);
        $district = SenatorialDistrict::create(['name' => 'Lagos Central', 'state_id' => $state->id]);

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Babatunde',
            'last_name' => 'Fashola',
            'email' => 'tunde@example.com',
            'phone' => '08023456789',
            'territory_mode' => 'existing',
            'district_id' => $district->id,
        ]);

        $response->assertRedirect(route('network'));
        $response->assertSessionHas('success');

        $coordinator = User::where('email', 'tunde@example.com')->first();
        $this->assertNotNull($coordinator);
        $this->assertEquals('coordinator', $coordinator->role);
        $this->assertEquals('invited', $coordinator->status);
        $this->assertEquals($rm->id, $coordinator->referred_by);

        // Verify district assignment
        $district->refresh();
        $this->assertEquals($coordinator->id, $district->coordinator_id);
        $this->assertEquals($district->id, $coordinator->districtAsCoordinator->id);

        Mail::assertSent(OnboardingInviteMail::class, function ($mail) use ($coordinator) {
            return $mail->invitee->id === $coordinator->id;
        });
    }

    public function test_regional_manager_can_create_and_assign_new_district_from_same_page(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South East', 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'Enugu', 'region_id' => $region->id]);

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Chinedu',
            'last_name' => 'Okeke',
            'email' => 'chinedu@example.com',
            'phone' => '08034567890',
            'territory_mode' => 'new',
            'new_district_name' => 'Enugu North',
            'new_district_state_id' => $state->id,
        ]);

        $response->assertRedirect(route('network'));
        $response->assertSessionHas('success');

        $coordinator = User::where('email', 'chinedu@example.com')->first();
        $this->assertNotNull($coordinator);
        $this->assertEquals('coordinator', $coordinator->role);

        $newDistrict = SenatorialDistrict::where('name', 'Enugu North')->first();
        $this->assertNotNull($newDistrict);
        $this->assertEquals($state->id, $newDistrict->state_id);
        $this->assertEquals($coordinator->id, $newDistrict->coordinator_id);
        $this->assertEquals('Enugu North · Enugu · South East', $coordinator->jurisdictionLabel());

        Mail::assertSent(OnboardingInviteMail::class, function ($mail) use ($coordinator) {
            return $mail->invitee->id === $coordinator->id;
        });
    }

    public function test_cannot_assign_district_belonging_to_another_region(): void
    {
        $rm1 = User::factory()->create(['role' => 'regional_manager']);
        $region1 = Region::create(['name' => 'North Central', 'regional_manager_id' => $rm1->id]);
        $state1 = State::create(['name' => 'Benue', 'region_id' => $region1->id]);

        $rm2 = User::factory()->create(['role' => 'regional_manager']);
        $region2 = Region::create(['name' => 'North West', 'regional_manager_id' => $rm2->id]);
        $state2 = State::create(['name' => 'Kano', 'region_id' => $region2->id]);
        $otherDistrict = SenatorialDistrict::create(['name' => 'Kano Central', 'state_id' => $state2->id]);

        $response = $this->actingAs($rm1)->post(route('onboarding.invite.send'), [
            'first_name' => 'Ahmad',
            'last_name' => 'Sani',
            'email' => 'ahmad@example.com',
            'phone' => '08045678901',
            'territory_mode' => 'existing',
            'district_id' => $otherDistrict->id,
        ]);

        $response->assertSessionHasErrors('district_id');
        $this->assertDatabaseMissing('users', ['email' => 'ahmad@example.com']);
    }

    public function test_cannot_assign_district_already_assigned_to_another_coordinator(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South South', 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'Rivers', 'region_id' => $region->id]);

        $existingCoordinator = User::factory()->create(['role' => 'coordinator']);
        $district = SenatorialDistrict::create([
            'name' => 'Rivers South East',
            'state_id' => $state->id,
            'coordinator_id' => $existingCoordinator->id,
        ]);

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Nnamdi',
            'last_name' => 'Asuni',
            'email' => 'nnamdi@example.com',
            'phone' => '08056789012',
            'territory_mode' => 'existing',
            'district_id' => $district->id,
        ]);

        $response->assertSessionHasErrors('district_id');
        $this->assertDatabaseMissing('users', ['email' => 'nnamdi@example.com']);
    }

    public function test_cannot_create_district_with_state_outside_region(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South West', 'regional_manager_id' => $rm->id]);
        State::create(['name' => 'Ogun', 'region_id' => $region->id]);

        $otherRegion = Region::create(['name' => 'North East']);
        $otherState = State::create(['name' => 'Borno', 'region_id' => $otherRegion->id]);

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Titi',
            'last_name' => 'Ade',
            'email' => 'titi@example.com',
            'phone' => '08067890123',
            'territory_mode' => 'new',
            'new_district_name' => 'Borno North',
            'new_district_state_id' => $otherState->id,
        ]);

        $response->assertSessionHasErrors('new_district_state_id');
        $this->assertDatabaseMissing('users', ['email' => 'titi@example.com']);
    }

    public function test_cannot_create_district_with_duplicate_name(): void
    {
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'South West', 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'Oyo', 'region_id' => $region->id]);

        SenatorialDistrict::create(['name' => 'Oyo South', 'state_id' => $state->id]);

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Gbenga',
            'last_name' => 'Daniel',
            'email' => 'gbenga@example.com',
            'phone' => '08078901234',
            'territory_mode' => 'new',
            'new_district_name' => 'Oyo South',
            'new_district_state_id' => $state->id,
        ]);

        $response->assertSessionHasErrors('new_district_name');
        $this->assertDatabaseMissing('users', ['email' => 'gbenga@example.com']);
    }

    public function test_coordinator_onboarding_partner_does_not_require_district(): void
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $response = $this->actingAs($coordinator)->post(route('onboarding.invite.send'), [
            'first_name' => 'Partner',
            'last_name' => 'User',
            'email' => 'partner@example.com',
            'phone' => '08089012345',
        ]);

        $response->assertRedirect(route('network'));
        $partner = User::where('email', 'partner@example.com')->first();
        $this->assertNotNull($partner);
        $this->assertEquals('partner', $partner->role);
    }
}
