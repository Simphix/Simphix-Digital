<?php

namespace Tests\Feature;

use App\Models\LoanRequest;
use App\Models\MoniepointPosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MoniepointPosAndLoanRequestTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private User $superAdmin;
    private User $regularAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'              => 'personal',
            'status'            => 'active',
            'first_name'        => 'Musa',
            'last_name'         => 'Garba',
            'phone'             => '080' . random_int(10000000, 99999999),
            'email'             => 'musa.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);

        $this->superAdmin = User::factory()->create([
            'role'              => 'super_admin',
            'status'            => 'active',
            'first_name'        => 'Admin',
            'last_name'         => 'Officer',
            'email'             => 'admin.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);

        $this->regularAgent = User::factory()->create([
            'role'              => 'agent',
            'status'            => 'active',
            'first_name'        => 'Chidi',
            'last_name'         => 'Agent',
            'email'             => 'chidi.' . uniqid() . '@example.com',
            'email_verified_at' => now(),
        ]);
    }

    public function test_dashboard_renders_sliding_banners_for_pos_and_loans(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Request Your Moniepoint POS Terminal Today', false);
        $response->assertSee('Official Moniepoint POS Partner', false);
        $response->assertSee('Instant Working Capital &amp; Business Loans', false);
        $response->assertSee('Disclaimer: Moniepoint Business Owners Only', false);
    }

    public function test_user_can_view_pos_index_and_tracking_pages(): void
    {
        $indexResponse = $this->actingAs($this->user)->get(route('moniepoint.pos.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Moniepoint POS Terminals Request', false);
        $indexResponse->assertSee('Moniepoint Smart POS (Android)', false);

        $trackingResponse = $this->actingAs($this->user)->get(route('moniepoint.pos.tracking'));
        $trackingResponse->assertOk();
        $trackingResponse->assertSee('Track Moniepoint POS Terminal Request', false);
    }

    public function test_user_can_submit_pos_request_successfully(): void
    {
        $payload = [
            'name'             => 'Musa Garba Ventures',
            'phone'            => '08012345678',
            'email'            => 'musa@example.com',
            'pos_type'         => 'smart_pos',
            'quantity'         => 2,
            'business_name'    => 'Garba Super Stores',
            'delivery_address' => 'Plot 12 Ahmadu Bello Way, Abuja',
            'state'            => 'FCT',
            'lga'              => 'Abuja Municipal',
            'notes'            => 'Please include extra receipt rolls.',
        ];

        $response = $this->actingAs($this->user)->post(route('moniepoint.pos.store'), $payload);

        $this->assertDatabaseHas('moniepoint_pos_requests', [
            'name'             => 'Musa Garba Ventures',
            'phone'            => '08012345678',
            'email'            => 'musa@example.com',
            'pos_type'         => 'smart_pos',
            'user_id'          => $this->user->id,
            'status'           => MoniepointPosRequest::STATUS_PENDING,
        ]);

        $created = MoniepointPosRequest::where('phone', '08012345678')->latest()->first();
        $this->assertNotNull($created);
        $response->assertRedirect(route('moniepoint.pos.tracking', ['ref' => $created->reference]));
    }

    public function test_user_can_track_pos_request_by_reference(): void
    {
        $posRequest = MoniepointPosRequest::create([
            'reference'        => MoniepointPosRequest::generateReference(),
            'user_id'          => $this->user->id,
            'name'             => 'Musa Garba',
            'phone'            => '08099887766',
            'email'            => 'musa.track@example.com',
            'pos_type'         => 'smart_pos',
            'quantity'         => 1,
            'status'           => MoniepointPosRequest::STATUS_DISPATCHED,
            'customer_notes'   => 'Dispatched via Courier #TRK12345',
        ]);

        $response = $this->actingAs($this->user)->get(route('moniepoint.pos.tracking', ['ref' => $posRequest->reference]));

        $response->assertOk();
        $response->assertSee($posRequest->reference);
        $response->assertSee('Dispatched via Courier #TRK12345');
        $response->assertSee('Moniepoint Smart POS (Android)');
    }

    public function test_user_can_view_loan_index_and_tracking_pages(): void
    {
        $indexResponse = $this->actingAs($this->user)->get(route('loans.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Moniepoint Business Working Capital Loans', false);
        $indexResponse->assertSee('Only Moniepoint business owners can request for loan', false);

        $trackingResponse = $this->actingAs($this->user)->get(route('loans.tracking'));
        $trackingResponse->assertOk();
        $trackingResponse->assertSee('Track Moniepoint Loan Application', false);
    }

    public function test_user_can_submit_loan_request_with_creds_and_optional_email(): void
    {
        // 1. Submit with email
        $payloadWithEmail = [
            'name'                      => 'Musa Business Owner',
            'phone'                     => '08022334455',
            'email'                     => 'musa.biz@example.com',
            'moniepoint_account_number' => '8123456789',
            'amount_requested'          => 1500000,
            'business_name'             => 'Garba Wholesale Store',
            'loan_tenure'               => '90_days',
            'purpose'                   => 'Stocking shop inventory for high season',
        ];

        $response1 = $this->actingAs($this->user)->post(route('loans.store'), $payloadWithEmail);

        $this->assertDatabaseHas('loan_requests', [
            'name'                      => 'Musa Business Owner',
            'phone'                     => '08022334455',
            'email'                     => 'musa.biz@example.com',
            'moniepoint_account_number' => '8123456789',
            'amount_requested'          => 1500000,
            'loan_tenure'               => '90_days',
            'status'                    => LoanRequest::STATUS_PENDING,
        ]);

        $created1 = LoanRequest::where('phone', '08022334455')->latest()->first();
        $this->assertNotNull($created1);
        $response1->assertRedirect(route('loans.tracking', ['ref' => $created1->reference]));

        // 2. Submit with optional email omitted
        $payloadWithoutEmail = [
            'name'                      => 'Amina Moniepoint Merchant',
            'phone'                     => '08099881122',
            'email'                     => '', // optional
            'moniepoint_account_number' => '8198765432',
            'amount_requested'          => 750000,
            'business_name'             => 'Amina Beauty Supplies',
            'loan_tenure'               => '30_days',
            'purpose'                   => 'Emergency cashflow smoothing',
        ];

        $response2 = $this->actingAs($this->user)->post(route('loans.store'), $payloadWithoutEmail);

        $this->assertDatabaseHas('loan_requests', [
            'name'                      => 'Amina Moniepoint Merchant',
            'phone'                     => '08099881122',
            'email'                     => null,
            'moniepoint_account_number' => '8198765432',
            'amount_requested'          => 750000,
        ]);

        $created2 = LoanRequest::where('phone', '08099881122')->latest()->first();
        $this->assertNotNull($created2);
        $response2->assertRedirect(route('loans.tracking', ['ref' => $created2->reference]));
    }

    public function test_user_can_track_loan_request_by_reference(): void
    {
        $loanRequest = LoanRequest::create([
            'reference'                 => LoanRequest::generateReference(),
            'user_id'                   => $this->user->id,
            'name'                      => 'Musa Garba',
            'phone'                     => '08055443322',
            'email'                     => null,
            'moniepoint_account_number' => '8123456789',
            'amount_requested'          => 2000000,
            'business_name'             => 'Garba Global',
            'loan_tenure'               => '180_days',
            'status'                    => LoanRequest::STATUS_APPROVED,
            'customer_notes'            => 'Loan approved! Moniepoint account funded.',
        ]);

        $response = $this->actingAs($this->user)->get(route('loans.tracking', ['ref' => $loanRequest->reference]));

        $response->assertOk();
        $response->assertSee($loanRequest->reference);
        $response->assertSee('8123456789');
        $response->assertSee('2,000,000.00');
        $response->assertSee('Loan approved! Moniepoint account funded.');
    }

    public function test_admin_can_view_pos_and_loan_admin_desks(): void
    {
        $posResponse = $this->actingAs($this->superAdmin)->get(route('admin.moniepoint-pos.index'));
        $posResponse->assertOk();
        $posResponse->assertSee('Moniepoint POS Requests Desk', false);

        $loanResponse = $this->actingAs($this->superAdmin)->get(route('admin.loan-requests.index'));
        $loanResponse->assertOk();
        $loanResponse->assertSee('Moniepoint Business Loans Desk', false);
    }

    public function test_admin_can_update_pos_request_status(): void
    {
        $posRequest = MoniepointPosRequest::create([
            'reference'        => MoniepointPosRequest::generateReference(),
            'user_id'          => $this->user->id,
            'name'             => 'Test Merchant',
            'phone'            => '08011223344',
            'email'            => 'merchant@example.com',
            'pos_type'         => 'smart_pos',
            'quantity'         => 1,
            'status'           => MoniepointPosRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.moniepoint-pos.update-status', $posRequest),
            [
                'status'         => MoniepointPosRequest::STATUS_DISPATCHED,
                'customer_notes' => 'Your device is on its way via DHL.',
                'admin_notes'    => 'Tracking number DHL-999-XYZ.',
            ]
        );

        $response->assertRedirect(route('admin.moniepoint-pos.show', $posRequest));

        $this->assertDatabaseHas('moniepoint_pos_requests', [
            'id'             => $posRequest->id,
            'status'         => MoniepointPosRequest::STATUS_DISPATCHED,
            'customer_notes' => 'Your device is on its way via DHL.',
            'admin_notes'    => 'Tracking number DHL-999-XYZ.',
        ]);
    }

    public function test_admin_can_update_loan_request_status(): void
    {
        $loanRequest = LoanRequest::create([
            'reference'                 => LoanRequest::generateReference(),
            'user_id'                   => $this->user->id,
            'name'                      => 'Loan Applicant',
            'phone'                     => '08022334455',
            'moniepoint_account_number' => '8123456789',
            'amount_requested'          => 1000000,
            'status'                    => LoanRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.loan-requests.update-status', $loanRequest),
            [
                'status'         => LoanRequest::STATUS_APPROVED,
                'customer_notes' => 'Turnover verified. Approved for N1,000,000.',
                'admin_notes'    => 'Moniepoint MFB turnover audit cleared.',
            ]
        );

        $response->assertRedirect(route('admin.loan-requests.show', $loanRequest));

        $this->assertDatabaseHas('loan_requests', [
            'id'             => $loanRequest->id,
            'status'         => LoanRequest::STATUS_APPROVED,
            'customer_notes' => 'Turnover verified. Approved for N1,000,000.',
            'admin_notes'    => 'Moniepoint MFB turnover audit cleared.',
        ]);
    }

    public function test_non_admin_cannot_access_pos_or_loan_admin_desks(): void
    {
        $posResponse = $this->actingAs($this->regularAgent)->get(route('admin.moniepoint-pos.index'));
        $posResponse->assertStatus(403);

        $loanResponse = $this->actingAs($this->regularAgent)->get(route('admin.loan-requests.index'));
        $loanResponse->assertStatus(403);
    }
}
