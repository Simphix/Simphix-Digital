<?php

namespace Tests\Feature;

use App\Mail\OnboardingInviteMail;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegionalManagerInviteCoordinatorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_regional_manager_sees_territory_assignment_section_on_invite_page(): void
    {
        $suffix = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix, 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'State ' . $suffix, 'region_id' => $region->id]);
        $district = SenatorialDistrict::create(['name' => 'District ' . $suffix, 'state_id' => $state->id]);

        $response = $this->actingAs($rm)->get(route('onboarding.invite'));

        $response->assertOk();
        $response->assertSee('Territory / Senatorial District Assignment');
        $response->assertSee($region->name);
        $response->assertSee($district->name);
        $response->assertSee($state->name);
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
        $suffix = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix, 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'State ' . $suffix, 'region_id' => $region->id]);
        $district = SenatorialDistrict::create(['name' => 'District ' . $suffix, 'state_id' => $state->id]);
        $email = 'coord_' . $suffix . '@example.com';

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Babatunde',
            'last_name' => 'Fashola',
            'email' => $email,
            'phone' => '0802' . rand(1000000, 9999999),
            'territory_mode' => 'existing',
            'district_id' => $district->id,
        ]);

        $response->assertRedirect(route('network'));
        $response->assertSessionHas('success');

        $coordinator = User::where('email', $email)->first();
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
        $suffix = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix, 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'State ' . $suffix, 'region_id' => $region->id]);
        $email = 'coord_new_' . $suffix . '@example.com';
        $newDistrictName = 'District New ' . $suffix;

        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Chinedu',
            'last_name' => 'Okeke',
            'email' => $email,
            'phone' => '0803' . rand(1000000, 9999999),
            'territory_mode' => 'new',
            'new_district_name' => $newDistrictName,
            'new_district_state_id' => $state->id,
        ]);

        $response->assertRedirect(route('network'));
        $response->assertSessionHas('success');

        $coordinator = User::where('email', $email)->first();
        $this->assertNotNull($coordinator);
        $this->assertEquals('coordinator', $coordinator->role);

        $newDistrict = SenatorialDistrict::where('name', $newDistrictName)->first();
        $this->assertNotNull($newDistrict);
        $this->assertEquals($state->id, $newDistrict->state_id);
        $this->assertEquals($coordinator->id, $newDistrict->coordinator_id);
        $this->assertEquals("{$newDistrictName} · State {$suffix} · Region {$suffix}", $coordinator->jurisdictionLabel());

        Mail::assertSent(OnboardingInviteMail::class, function ($mail) use ($coordinator) {
            return $mail->invitee->id === $coordinator->id;
        });
    }

    public function test_cannot_assign_district_belonging_to_another_region(): void
    {
        $suffix1 = strtolower(Str::random(6));
        $suffix2 = strtolower(Str::random(6));
        $rm1 = User::factory()->create(['role' => 'regional_manager']);
        $region1 = Region::create(['name' => 'Region ' . $suffix1, 'regional_manager_id' => $rm1->id]);
        $state1 = State::create(['name' => 'State ' . $suffix1, 'region_id' => $region1->id]);

        $rm2 = User::factory()->create(['role' => 'regional_manager']);
        $region2 = Region::create(['name' => 'Region ' . $suffix2, 'regional_manager_id' => $rm2->id]);
        $state2 = State::create(['name' => 'State ' . $suffix2, 'region_id' => $region2->id]);
        $otherDistrict = SenatorialDistrict::create(['name' => 'District ' . $suffix2, 'state_id' => $state2->id]);

        $email = 'ahmad_' . $suffix1 . '@example.com';
        $response = $this->actingAs($rm1)->post(route('onboarding.invite.send'), [
            'first_name' => 'Ahmad',
            'last_name' => 'Sani',
            'email' => $email,
            'phone' => '0804' . rand(1000000, 9999999),
            'territory_mode' => 'existing',
            'district_id' => $otherDistrict->id,
        ]);

        $response->assertSessionHasErrors('district_id');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_cannot_assign_district_already_assigned_to_another_coordinator(): void
    {
        $suffix = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix, 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'State ' . $suffix, 'region_id' => $region->id]);

        $existingCoordinator = User::factory()->create(['role' => 'coordinator']);
        $district = SenatorialDistrict::create([
            'name' => 'District ' . $suffix,
            'state_id' => $state->id,
            'coordinator_id' => $existingCoordinator->id,
        ]);

        $email = 'nnamdi_' . $suffix . '@example.com';
        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Nnamdi',
            'last_name' => 'Asuni',
            'email' => $email,
            'phone' => '0805' . rand(1000000, 9999999),
            'territory_mode' => 'existing',
            'district_id' => $district->id,
        ]);

        $response->assertSessionHasErrors('district_id');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_cannot_create_district_with_state_outside_region(): void
    {
        $suffix1 = strtolower(Str::random(6));
        $suffix2 = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix1, 'regional_manager_id' => $rm->id]);
        State::create(['name' => 'State ' . $suffix1, 'region_id' => $region->id]);

        $otherRegion = Region::create(['name' => 'Other ' . $suffix2]);
        $otherState = State::create(['name' => 'OtherState ' . $suffix2, 'region_id' => $otherRegion->id]);

        $email = 'titi_' . $suffix1 . '@example.com';
        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Titi',
            'last_name' => 'Ade',
            'email' => $email,
            'phone' => '0806' . rand(1000000, 9999999),
            'territory_mode' => 'new',
            'new_district_name' => 'District ' . $suffix1,
            'new_district_state_id' => $otherState->id,
        ]);

        $response->assertSessionHasErrors('new_district_state_id');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_cannot_create_district_with_duplicate_name(): void
    {
        $suffix = strtolower(Str::random(6));
        $rm = User::factory()->create(['role' => 'regional_manager']);
        $region = Region::create(['name' => 'Region ' . $suffix, 'regional_manager_id' => $rm->id]);
        $state = State::create(['name' => 'State ' . $suffix, 'region_id' => $region->id]);

        $existingName = 'District Dup ' . $suffix;
        SenatorialDistrict::create(['name' => $existingName, 'state_id' => $state->id]);

        $email = 'gbenga_' . $suffix . '@example.com';
        $response = $this->actingAs($rm)->post(route('onboarding.invite.send'), [
            'first_name' => 'Gbenga',
            'last_name' => 'Daniel',
            'email' => $email,
            'phone' => '0807' . rand(1000000, 9999999),
            'territory_mode' => 'new',
            'new_district_name' => $existingName,
            'new_district_state_id' => $state->id,
        ]);

        $response->assertSessionHasErrors('new_district_name');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_coordinator_onboarding_partner_does_not_require_district(): void
    {
        $suffix = strtolower(Str::random(6));
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $email = 'partner_' . $suffix . '@example.com';

        $response = $this->actingAs($coordinator)->post(route('onboarding.invite.send'), [
            'first_name' => 'Partner',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '0808' . rand(1000000, 9999999),
        ]);

        $response->assertRedirect(route('network'));
        $partner = User::where('email', $email)->first();
        $this->assertNotNull($partner);
        $this->assertEquals('partner', $partner->role);
    }
}
