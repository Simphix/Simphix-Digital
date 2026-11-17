<?php

namespace Tests\Feature;

use App\Models\CacRegistration;
use App\Models\LoanRequest;
use App\Models\MoniepointPosRequest;
use App\Models\PassportRequest;
use App\Models\Service;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PassportRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Service::firstOrCreate(['name' => 'passport'], [
            'display_name' => 'International Passport',
            'is_active'    => true,
        ]);
        Service::firstOrCreate(['name' => 'travel'], [
            'display_name' => 'Travel & Tours',
            'is_active'    => true,
        ]);
        Service::firstOrCreate(['name' => 'cac'], [
            'display_name' => 'CAC Registration',
            'is_active'    => true,
        ]);
        Service::firstOrCreate(['name' => 'moniepoint_pos'], [
            'display_name' => 'Moniepoint POS',
            'is_active'    => true,
        ]);
        Service::firstOrCreate(['name' => 'loans'], [
            'display_name' => 'Business Loans',
            'is_active'    => true,
        ]);
    }

    public function test_customer_can_view_passport_landing_and_create_pages(): void
    {
        $user = User::factory()->create(['role' => 'partner']);

        $response = $this->actingAs($user)->get(route('passport.index'));
        $response->assertStatus(200);
        $response->assertSee('International Passport Registration');

        $responseCreate = $this->actingAs($user)->get(route('passport.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Application for International Passport');
    }

    public function test_customer_can_submit_new_passport_request(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'partner']);

        $payload = [
            'application_type'        => 'new',
            'nin'                     => '12345678901',
            'surname'                 => 'IBRAHIM',
            'first_name'              => 'MUSA',
            'middle_name'             => 'DANLADI',
            'date_of_birth'           => '1995-05-12',
            'sex'                     => 'Male',
            'state_of_origin'         => 'Kano',
            'lga'                     => 'Nassarawa',
            'hometown'                => 'Kano City',
            'place_of_birth'          => 'Kano City',
            'mobile_number'           => '08012345678',
            'address'                 => '14 Bompai Road, Kano',
            'email'                   => 'musa.ibrahim@example.com',
            'occupation'              => 'Civil Servant',
            'next_of_kin_name'        => 'Amina Ibrahim',
            'next_of_kin_relationship'=> 'Spouse',
            'next_of_kin_phone'       => '08098765432',
            'next_of_kin_address'     => '14 Bompai Road, Kano',
            'passport_photo'          => UploadedFile::fake()->image('photo.jpg'),
            'state_of_origin_cert'    => UploadedFile::fake()->create('origin_cert.pdf', 100),
            'birth_certificate'       => UploadedFile::fake()->create('birth_cert.pdf', 100),
            'nin_document'            => UploadedFile::fake()->create('nin_slip.pdf', 100),
            'signature'               => UploadedFile::fake()->image('signature.png'),
        ];

        $response = $this->actingAs($user)->post(route('passport.store'), $payload);

        $this->assertDatabaseHas('passport_requests', [
            'user_id'         => $user->id,
            'application_type'=> 'new',
            'nin'             => '12345678901',
            'surname'         => 'IBRAHIM',
            'first_name'      => 'MUSA',
            'status'          => PassportRequest::STATUS_PENDING,
        ]);

        $passportRequest = PassportRequest::first();
        $this->assertNotNull($passportRequest);
        $response->assertRedirect(route('passport.show', $passportRequest));

        // Check storage
        Storage::disk('public')->assertExists($passportRequest->passport_photo);
        Storage::disk('public')->assertExists($passportRequest->signature);
    }

    public function test_customer_can_download_a4_pdf_slip(): void
    {
        $user = User::factory()->create(['role' => 'partner']);

        $passportRequest = PassportRequest::create([
            'reference'               => PassportRequest::generateReference(),
            'user_id'                 => $user->id,
            'application_type'        => 'new',
            'nin'                     => '98765432101',
            'surname'                 => 'OKONKWO',
            'first_name'              => 'CHIDIEBERE',
            'middle_name'             => 'PAUL',
            'date_of_birth'           => '1990-10-15',
            'sex'                     => 'Male',
            'state_of_origin'         => 'Anambra',
            'lga'                     => 'Ihiala',
            'hometown'                => 'Ihiala',
            'place_of_birth'          => 'Ihiala',
            'mobile_number'           => '08033334444',
            'address'                 => 'Plot 5 Awka Road, Onitsha',
            'email'                   => 'okonkwo@example.com',
            'occupation'              => 'Merchant',
            'next_of_kin_name'        => 'Ngozi Okonkwo',
            'next_of_kin_relationship'=> 'Sister',
            'next_of_kin_phone'       => '08055556666',
            'next_of_kin_address'     => 'Plot 5 Awka Road, Onitsha',
            'status'                  => PassportRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('passport.pdf', $passportRequest));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_super_admin_bsm_and_support_can_access_admin_passport_requests(): void
    {
        $passportRequest = PassportRequest::create([
            'reference'               => PassportRequest::generateReference(),
            'user_id'                 => null,
            'application_type'        => 'new',
            'nin'                     => '11122233344',
            'surname'                 => 'BALOGUN',
            'first_name'              => 'TAIWO',
            'date_of_birth'           => '1988-03-21',
            'sex'                     => 'Male',
            'state_of_origin'         => 'Lagos',
            'lga'                     => 'Ikeja',
            'hometown'                => 'Ikeja',
            'place_of_birth'          => 'Ikeja',
            'mobile_number'           => '08022223333',
            'address'                 => '10 Allen Avenue, Ikeja',
            'email'                   => 'taiwo.balogun@example.com',
            'occupation'              => 'Architect',
            'next_of_kin_name'        => 'Kehinde Balogun',
            'next_of_kin_relationship'=> 'Twin Brother',
            'next_of_kin_phone'       => '08033332222',
            'next_of_kin_address'     => '10 Allen Avenue, Ikeja',
            'status'                  => PassportRequest::STATUS_PENDING,
        ]);

        $roles = ['super_admin', 'business_sales_manager', 'support'];

        foreach ($roles as $role) {
            $staff = User::factory()->create(['role' => $role]);

            $indexResponse = $this->actingAs($staff)->get(route('admin.passport-requests.index'));
            $indexResponse->assertStatus(200);
            $indexResponse->assertSee($passportRequest->reference);

            $showResponse = $this->actingAs($staff)->get(route('admin.passport-requests.show', $passportRequest));
            $showResponse->assertStatus(200);
            $showResponse->assertSee('BALOGUN');

            $pdfResponse = $this->actingAs($staff)->get(route('admin.passport-requests.pdf', $passportRequest));
            $pdfResponse->assertStatus(200);
            $pdfResponse->assertHeader('content-type', 'application/pdf');
        }

        // Regular partner cannot access admin desk
        $regularUser = User::factory()->create(['role' => 'partner']);
        $deniedResponse = $this->actingAs($regularUser)->get(route('admin.passport-requests.index'));
        $deniedResponse->assertStatus(403);
    }

    public function test_staff_can_access_cac_travel_pos_loan_requests_and_download_pdf(): void
    {
        $user = User::factory()->create(['role' => 'partner']);

        // Create one request of each type
        $travelRequest = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => $user->id,
            'name'                => 'Fatima Al-Hassan',
            'phone'               => '08011112222',
            'email'               => 'fatima@example.com',
            'package_name'        => 'VIP Umrah Package',
            'package_type'        => 'umrah',
            'number_of_travelers' => 2,
            'status'              => TravelRequest::STATUS_PENDING,
        ]);

        $posRequest = MoniepointPosRequest::create([
            'reference'        => MoniepointPosRequest::generateReference(),
            'user_id'          => $user->id,
            'name'             => 'Emeka Stores',
            'phone'            => '08022223333',
            'email'            => 'emeka@example.com',
            'pos_type'         => 'smart_pos',
            'business_name'    => 'Emeka Groceries Ltd',
            'delivery_address' => '12 Market Road, Aba',
            'quantity'         => 1,
            'status'           => MoniepointPosRequest::STATUS_PENDING,
        ]);

        $loanRequest = LoanRequest::create([
            'reference'                 => LoanRequest::generateReference(),
            'user_id'                   => $user->id,
            'name'                      => 'Adewale Enterprise',
            'phone'                     => '08033334444',
            'email'                     => 'adewale@example.com',
            'moniepoint_account_number' => '8012345678',
            'amount_requested'          => 2500000.00,
            'business_name'             => 'Adewale Logistics',
            'loan_tenure'               => '6_months',
            'status'                    => LoanRequest::STATUS_PENDING,
        ]);

        $cacReg = CacRegistration::create([
            'reference'       => 'CAC-TEST9999',
            'user_id'         => $user->id,
            'category'        => 'business_name',
            'amount'          => 35000.00,
            'transaction_ref' => 'TX-CAC-9999',
            'status'          => 'submitted',
            'form_data'       => [
                'names'  => ['Smart Global Enterprise', 'Smart Logistics Global'],
                'people' => [
                    [
                        'full_name' => 'John Doe',
                        'phone'     => '08012345678',
                        'email'     => 'john@example.com',
                    ],
                ],
            ],
        ]);

        // Verify BSM and Support can access and download PDFs for all requests
        $staffRoles = ['business_sales_manager', 'support', 'super_admin'];

        foreach ($staffRoles as $role) {
            $staff = User::factory()->create(['role' => $role]);

            // CAC
            $resCac = $this->actingAs($staff)->get(route('admin.cac.index'));
            $resCac->assertStatus(200);
            $resCacPdf = $this->actingAs($staff)->get(route('admin.cac.pdf', $cacReg));
            $resCacPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

            // Travel
            $resTravel = $this->actingAs($staff)->get(route('admin.travel-requests.index'));
            $resTravel->assertStatus(200);
            $resTravelPdf = $this->actingAs($staff)->get(route('admin.travel-requests.pdf', $travelRequest));
            $resTravelPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

            // POS
            $resPos = $this->actingAs($staff)->get(route('admin.moniepoint-pos.index'));
            $resPos->assertStatus(200);
            $resPosPdf = $this->actingAs($staff)->get(route('admin.moniepoint-pos.pdf', $posRequest));
            $resPosPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

            // Loans
            $resLoan = $this->actingAs($staff)->get(route('admin.loan-requests.index'));
            $resLoan->assertStatus(200);
            $resLoanPdf = $this->actingAs($staff)->get(route('admin.loan-requests.pdf', $loanRequest));
            $resLoanPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');
        }
    }
}
