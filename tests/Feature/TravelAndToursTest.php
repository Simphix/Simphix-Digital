<?php

namespace Tests\Feature;

use App\Models\TravelPackage;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TravelAndToursTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private User $superAdmin;
    private User $regularAgent;

    protected function setUp(): void
    {
        parent::setUp();

        TravelPackage::seedDefaults();

        $this->user = User::factory()->create([
            'role'              => 'personal',
            'status'            => 'active',
            'first_name'        => 'Fatima',
            'last_name'         => 'Bello',
            'phone'             => '080' . random_int(10000000, 99999999),
            'email'             => 'fatima.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);

        $this->superAdmin = User::factory()->create([
            'role'              => 'super_admin',
            'status'            => 'active',
            'first_name'        => 'System',
            'last_name'         => 'Admin',
            'email'             => 'admin.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);

        $this->regularAgent = User::factory()->create([
            'role'              => 'agent',
            'status'            => 'active',
            'first_name'        => 'Aliyu',
            'last_name'         => 'Agent',
            'email'             => 'aliyu.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);
    }

    public function test_dashboard_renders_landscape_travel_banner_for_authenticated_users(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Umrah, Hajj &amp; International Travel Packages', false);
        $response->assertSee('Holy Pilgrimages &amp; Global Travels', false);
        $response->assertSee(route('travel.index'));
        $response->assertSee(route('travel.tracking'));
    }

    public function test_user_can_view_travel_packages_catalog(): void
    {
        $response = $this->actingAs($this->user)->get(route('travel.index'));

        $response->assertOk();
        $response->assertSee('Umrah, Hajj &amp; International Travel Packages', false);
        $response->assertSee('Economy Umrah Package');
        $response->assertSee('Standard Hajj Pilgrimage');
        $response->assertSee('Dubai &amp; UAE Leisure Vacation', false);
        $response->assertSee('Request This Package');
    }

    public function test_user_can_filter_travel_packages_by_category(): void
    {
        // Umrah filter
        $umrahResponse = $this->actingAs($this->user)->get(route('travel.index', ['category' => 'umrah']));
        $umrahResponse->assertOk();
        $umrahResponse->assertSee('Economy Umrah Package');

        // Hajj filter
        $hajjResponse = $this->actingAs($this->user)->get(route('travel.index', ['category' => 'hajj']));
        $hajjResponse->assertOk();
        $hajjResponse->assertSee('Standard Hajj Pilgrimage');

        // International filter
        $intlResponse = $this->actingAs($this->user)->get(route('travel.index', ['category' => 'international']));
        $intlResponse->assertOk();
        $intlResponse->assertSee('Dubai &amp; UAE Leisure Vacation', false);
    }

    public function test_user_can_submit_travel_request_with_optional_email(): void
    {
        $package = TravelPackage::where('category', 'umrah')->first();

        $payload = [
            'name'                => 'Musa Danladi',
            'phone'               => '08033221144',
            'email'               => '', // Email is optional!
            'travel_package_id'   => $package->id,
            'number_of_travelers' => 2,
            'departure_date'      => 'Ramadan 2027',
            'notes'               => 'Looking for 5-star hotel close to Haram in Makkah.',
        ];

        $response = $this->actingAs($this->user)->post(route('travel.store'), $payload);

        $createdRequest = TravelRequest::where('phone', '08033221144')->latest()->first();

        $this->assertNotNull($createdRequest);
        $this->assertEquals('Musa Danladi', $createdRequest->name);
        $this->assertNull($createdRequest->email);
        $this->assertEquals($package->id, $createdRequest->travel_package_id);
        $this->assertEquals($package->name, $createdRequest->package_name);
        $this->assertEquals('umrah', $createdRequest->package_type);
        $this->assertEquals(2, $createdRequest->number_of_travelers);
        $this->assertEquals('pending', $createdRequest->status);
        $this->assertStringStartsWith('TRV-', $createdRequest->reference);
        $this->assertEquals($this->user->id, $createdRequest->user_id);

        $response->assertRedirect(route('travel.tracking', ['ref' => $createdRequest->reference]));
        $response->assertSessionHas('success');
    }

    public function test_user_can_submit_custom_travel_itinerary_request(): void
    {
        $payload = [
            'name'                => 'Zainab Kabir',
            'phone'               => '08123456789',
            'email'               => 'zainab@example.com',
            'package_name'        => 'Custom Turkey Family Vacation',
            'package_type'        => 'custom',
            'number_of_travelers' => 4,
            'departure_date'      => 'December 2026',
            'notes'               => 'Need flight tickets and Schengen/Turkey visa consultation.',
        ];

        $response = $this->actingAs($this->user)->post(route('travel.store'), $payload);

        $createdRequest = TravelRequest::where('phone', '08123456789')->latest()->first();

        $this->assertNotNull($createdRequest);
        $this->assertEquals('Custom Turkey Family Vacation', $createdRequest->package_name);
        $this->assertEquals('zainab@example.com', $createdRequest->email);
        $this->assertEquals('custom', $createdRequest->package_type);
        $this->assertEquals(4, $createdRequest->number_of_travelers);

        $response->assertRedirect(route('travel.tracking', ['ref' => $createdRequest->reference]));
    }

    public function test_travel_request_validation_enforces_name_and_phone(): void
    {
        $response = $this->actingAs($this->user)->post(route('travel.store'), [
            'name'  => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_user_can_track_travel_request_by_reference_code(): void
    {
        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => 'Amina Suleiman',
            'phone'               => '08099887766',
            'package_name'        => 'VIP & Ramadan Premium Umrah',
            'package_type'        => 'umrah',
            'number_of_travelers' => 1,
            'status'              => TravelRequest::STATUS_CONTACTED,
            'customer_notes'      => 'Your visa submission is currently under embassy vetting.',
        ]);

        $response = $this->actingAs($this->user)->get(route('travel.tracking', ['ref' => $req->reference]));

        $response->assertOk();
        $response->assertSee($req->reference);
        $response->assertSee('VIP &amp; Ramadan Premium Umrah', false);
        $response->assertSee('Customer Will Be Contacted');
        $response->assertDontSee('Under Review');
        $response->assertSee('Your visa submission is currently under embassy vetting.');
    }

    public function test_user_can_view_history_of_all_their_travel_applications(): void
    {
        $req1 = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => $this->user->name,
            'phone'               => $this->user->phone,
            'package_name'        => 'Economy Umrah Package',
            'package_type'        => 'umrah',
            'number_of_travelers' => 1,
            'status'              => 'pending',
        ]);

        $req2 = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => $this->user->name,
            'phone'               => $this->user->phone,
            'package_name'        => 'Dubai & UAE Leisure Vacation',
            'package_type'        => 'international',
            'number_of_travelers' => 2,
            'status'              => 'contacted',
        ]);

        $response = $this->actingAs($this->user)->get(route('travel.tracking'));

        $response->assertOk();
        $response->assertSee($req1->reference);
        $response->assertSee($req2->reference);
        $response->assertSee('Dubai &amp; UAE Leisure Vacation', false);
        $response->assertSee('Your Travel Application History');
    }

    public function test_super_admin_can_access_travel_requests_management_desk(): void
    {
        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => 'Bello Mohammed',
            'phone'               => '08055443322',
            'package_name'        => 'Executive VIP Hajj Package',
            'package_type'        => 'hajj',
            'number_of_travelers' => 3,
            'status'              => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.index'));

        $response->assertOk();
        $response->assertSee('Travel &amp; Tours Requests Desk', false);
        $response->assertSee($req->reference);
        $response->assertSee('Bello Mohammed');
        $response->assertSee('Executive VIP Hajj Package');
    }

    public function test_non_admin_cannot_access_admin_travel_requests(): void
    {
        $response = $this->actingAs($this->regularAgent)->get(route('admin.travel-requests.index'));
        $response->assertForbidden();

        $customerResponse = $this->actingAs($this->user)->get(route('admin.travel-requests.index'));
        $customerResponse->assertForbidden();
    }

    public function test_super_admin_can_view_single_travel_request_details(): void
    {
        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => 'Salisu Garba',
            'phone'               => '08011223344',
            'email'               => 'salisu@example.com',
            'package_name'        => 'United Kingdom & Europe Holiday',
            'package_type'        => 'international',
            'number_of_travelers' => 2,
            'status'              => 'pending',
            'notes'               => 'Schengen visa required for two adults.',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.show', $req));

        $response->assertOk();
        $response->assertSee($req->reference);
        $response->assertSee('Salisu Garba');
        $response->assertSee('08011223344');
        $response->assertSee('salisu@example.com');
        $response->assertSee('Schengen visa required for two adults.');
    }

    public function test_super_admin_can_update_travel_request_status_and_notes(): void
    {
        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'name'                => 'Usman Danfodio',
            'phone'               => '08099112233',
            'package_name'        => 'Economy Umrah Package',
            'package_type'        => 'umrah',
            'number_of_travelers' => 1,
            'status'              => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.travel-requests.update-status', $req), [
            'status'         => 'contacted',
            'admin_notes'    => 'Officer Aisha assigned to manage passport collection.',
            'customer_notes' => 'Our travel desk has contacted you via WhatsApp to verify your passport biodata.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $req->refresh();
        $this->assertEquals('contacted', $req->status);
        $this->assertEquals('Officer Aisha assigned to manage passport collection.', $req->admin_notes);
        $this->assertEquals('Our travel desk has contacted you via WhatsApp to verify your passport biodata.', $req->customer_notes);
        $this->assertNotNull($req->contacted_at);

        // Verify the customer now sees the customer note when tracking
        $trackResponse = $this->actingAs($this->user)->get(route('travel.tracking', ['ref' => $req->reference]));
        $trackResponse->assertOk();
        $trackResponse->assertSee('Our travel desk has contacted you via WhatsApp to verify your passport biodata.');
    }

    public function test_super_admin_can_view_packages_configuration_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.packages.index'));

        $response->assertOk();
        $response->assertSee('Travel &amp; Tours Packages Configuration', false);
        $response->assertSee('Add New Package');
        $response->assertSee('Economy Umrah Package');
        $response->assertSee('Configurable Packages');
    }

    public function test_travel_requests_desk_displays_link_to_configure_packages(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.index'));

        $response->assertOk();
        $response->assertSee('Configure Packages');
        $response->assertSee(route('admin.travel-requests.packages.index'));
    }

    public function test_super_admin_can_view_packages_create_form(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.packages.create'));

        $response->assertOk();
        $response->assertSee('Create New Travel Package');
        $response->assertSee('Package Title');
        $response->assertSee('Subtitle / Tagline');
        $response->assertSee('Estimated Package Value');
        $response->assertSee('Package Inclusions');
    }

    public function test_super_admin_can_create_new_travel_package(): void
    {
        $payload = [
            'name'        => 'Luxury Maldives Honeymoon & Retreat',
            'category'    => 'international',
            'tagline'     => 'All-inclusive overwater villa stay with scenic seaplane transfers.',
            'price_range' => 'From ₦5,200,000 / couple',
            'duration'    => '6 Days / 5 Nights',
            'highlights'  => "Overwater luxury villa accommodation\nRound-trip seaplane scenic transfers\nAll-inclusive gourmet dining & drinks\nComplimentary sunset dolphin cruise\nDedicated island host & butler",
            'description' => 'Unmatched luxury experience in the Maldives Indian Ocean paradise.',
            'is_active'   => '1',
            'is_featured' => '1',
            'sort_order'  => 10,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.travel-requests.packages.store'), $payload);

        $response->assertRedirect(route('admin.travel-requests.packages.index'));
        $response->assertSessionHas('success');

        $created = TravelPackage::where('name', 'Luxury Maldives Honeymoon & Retreat')->first();
        $this->assertNotNull($created);
        $this->assertEquals('international', $created->category);
        $this->assertEquals('luxury-maldives-honeymoon-retreat', $created->slug);
        $this->assertEquals('All-inclusive overwater villa stay with scenic seaplane transfers.', $created->tagline);
        $this->assertEquals('From ₦5,200,000 / couple', $created->price_range);
        $this->assertEquals('6 Days / 5 Nights', $created->duration);
        $this->assertCount(5, $created->highlights);
        $this->assertEquals('Overwater luxury villa accommodation', $created->highlights[0]);
        $this->assertTrue($created->is_active);
        $this->assertTrue($created->is_featured);

        // Verify the customer catalog now renders the new package!
        $catalogResponse = $this->actingAs($this->user)->get(route('travel.index', ['category' => 'international']));
        $catalogResponse->assertOk();
        $catalogResponse->assertSee('Luxury Maldives Honeymoon &amp; Retreat', false);
        $catalogResponse->assertSee('From ₦5,200,000 / couple');
    }

    public function test_super_admin_can_update_travel_package_title_subtitle_price_and_inclusions(): void
    {
        $package = TravelPackage::where('category', 'umrah')->first();

        $payload = [
            'name'        => 'Updated VIP Executive Umrah Experience',
            'category'    => 'umrah',
            'tagline'     => 'Now featuring front-row Haram 5-star clock tower suites with VIP transport.',
            'price_range' => 'From ₦3,950,000 / person',
            'duration'    => '12 Days / 11 Nights',
            'highlights'  => "Express VIP e-Visa & private insurance\nSwissotel Makkah clock tower direct Haram view\nPrivate GMC luxury transfer vehicle\nFull board VIP gourmet buffet",
            'description' => 'Completely updated luxury Umrah experience.',
            'is_active'   => '1',
            'is_featured' => '1',
            'sort_order'  => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.travel-requests.packages.update', $package), $payload);

        $response->assertRedirect(route('admin.travel-requests.packages.index'));
        $response->assertSessionHas('success');

        $package->refresh();
        $this->assertEquals('Updated VIP Executive Umrah Experience', $package->name);
        $this->assertEquals('Now featuring front-row Haram 5-star clock tower suites with VIP transport.', $package->tagline);
        $this->assertEquals('From ₦3,950,000 / person', $package->price_range);
        $this->assertEquals('12 Days / 11 Nights', $package->duration);
        $this->assertCount(4, $package->highlights);
        $this->assertEquals('Express VIP e-Visa & private insurance', $package->highlights[0]);

        // Verify customer catalog reflects the update
        $catalogResponse = $this->actingAs($this->user)->get(route('travel.index', ['category' => 'umrah']));
        $catalogResponse->assertOk();
        $catalogResponse->assertSee('Updated VIP Executive Umrah Experience');
        $catalogResponse->assertSee('From ₦3,950,000 / person');
    }

    public function test_super_admin_can_toggle_package_active_status(): void
    {
        $package = TravelPackage::first();
        $initialStatus = $package->is_active;

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.travel-requests.packages.toggle-status', $package));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $package->refresh();
        $this->assertEquals(!$initialStatus, $package->is_active);
    }

    public function test_travel_request_show_page_displays_edit_package_button_and_modal(): void
    {
        $package = TravelPackage::first();

        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'travel_package_id'   => $package->id,
            'name'                => 'Ibrahim Sani',
            'phone'               => '08022334455',
            'package_name'        => $package->name,
            'package_type'        => $package->category,
            'number_of_travelers' => 2,
            'status'              => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.travel-requests.show', $req));

        $response->assertOk();
        $response->assertSee('Selected Package Configuration');
        $response->assertSee('Edit This Package');
        $response->assertSee('Modify Package Configuration');
    }

    public function test_super_admin_can_update_package_directly_from_travel_request_and_redirect_back(): void
    {
        $package = TravelPackage::first();

        $req = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $this->user->id,
            'travel_package_id'   => $package->id,
            'name'                => 'Ibrahim Sani',
            'phone'               => '08022334455',
            'package_name'        => $package->name,
            'package_type'        => $package->category,
            'number_of_travelers' => 2,
            'status'              => 'pending',
        ]);

        $redirectUrl = route('admin.travel-requests.show', $req);

        $payload = [
            'name'        => $package->name,
            'category'    => $package->category,
            'tagline'     => 'Customized subtitle saved directly from travel request desk.',
            'price_range' => 'From ₦2,999,999 / person',
            'duration'    => '15 Days / 14 Nights',
            'highlights'  => "Direct VIP Fast Track Entry\nExclusive Hotel Suite",
            'redirect_to' => $redirectUrl,
            'is_active'   => '1',
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.travel-requests.packages.update', $package), $payload);

        $response->assertRedirect($redirectUrl);
        $response->assertSessionHas('success');

        $package->refresh();
        $this->assertEquals('Customized subtitle saved directly from travel request desk.', $package->tagline);
        $this->assertEquals('From ₦2,999,999 / person', $package->price_range);
        $this->assertEquals('15 Days / 14 Nights', $package->duration);
        $this->assertCount(2, $package->highlights);
    }

    public function test_regular_user_cannot_access_package_configuration(): void
    {
        $package = TravelPackage::first();

        // Index
        $this->actingAs($this->user)->get(route('admin.travel-requests.packages.index'))
            ->assertForbidden();

        // Create
        $this->actingAs($this->user)->get(route('admin.travel-requests.packages.create'))
            ->assertForbidden();

        // Store
        $this->actingAs($this->user)->post(route('admin.travel-requests.packages.store'), ['name' => 'Hack'])
            ->assertForbidden();

        // Edit
        $this->actingAs($this->user)->get(route('admin.travel-requests.packages.edit', $package))
            ->assertForbidden();

        // Update
        $this->actingAs($this->user)->put(route('admin.travel-requests.packages.update', $package), ['name' => 'Hack'])
            ->assertForbidden();
    }
}
