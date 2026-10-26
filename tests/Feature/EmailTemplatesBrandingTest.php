<?php

use App\Models\CacRegistration;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Mail\ActivatedSimsExportMail;
use App\Mail\AdminNotificationMail;
use App\Mail\AdminRepliedMail;
use App\Mail\CacStatusUpdatedMail;
use App\Mail\DeviceLoginOtpMail;
use App\Mail\KycWelcomeMail;
use App\Mail\OnboardingInviteMail;
use App\Mail\OtpVerificationMail;
use App\Mail\PaymentNotifyMail;
use App\Mail\SimActivationDetailsMail;
use App\Mail\TicketClosedMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('all email templates render successfully with SimPhix logo, name, and branding', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
    ]);

    // 1. ActivatedSimsExportMail
    $mail1 = new ActivatedSimsExportMail('number', 10, now());
    $html1 = $mail1->render();
    expect($html1)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 2. AdminNotificationMail
    $ticket = Ticket::create([
        'user_id' => $user->id,
        'subject' => 'Test Ticket',
        'category' => 'general',
        'priority' => 'medium',
        'status' => 'open',
    ]);
    $msg = TicketMessage::create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'message' => 'Help needed with SIM activation',
    ]);
    $mail2 = new AdminNotificationMail($ticket, $msg, 'created');
    $html2 = $mail2->render();
    expect($html2)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Support Desk');

    // 3. AdminRepliedMail
    $mail3 = new AdminRepliedMail($ticket, $msg);
    $html3 = $mail3->render();
    expect($html3)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 4. CacStatusUpdatedMail
    $cac = CacRegistration::create([
        'user_id' => $user->id,
        'reference' => 'CAC-TEST-12345',
        'category' => 'cac_business_name',
        'proposed_name_1' => 'Apex Tech Ltd',
        'status' => 'approved',
        'amount' => 25000,
        'form_data' => ['company_name' => 'Apex Tech Ltd'],
    ]);
    $mail4 = new CacStatusUpdatedMail($cac, 'Your CAC registration is approved.');
    $html4 = $mail4->render();
    expect($html4)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 5. DeviceLoginOtpMail
    $mail5 = new DeviceLoginOtpMail('123456', 'Chrome on Windows', '127.0.0.1', now()->toDateTimeString());
    $html5 = $mail5->render();
    expect($html5)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 6. KycWelcomeMail
    $mail6 = new KycWelcomeMail($user);
    $html6 = $mail6->render();
    expect($html6)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 7. OnboardingInviteMail
    $invitee = User::factory()->create(['role' => 'agent']);
    $mail7 = new OnboardingInviteMail($invitee, $user, 'https://example.com/accept');
    $html7 = $mail7->render();
    expect($html7)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 8. OtpVerificationMail
    $mail8 = new OtpVerificationMail('654321');
    $html8 = $mail8->render();
    expect($html8)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 9. PaymentNotifyMail
    $mail9 = new PaymentNotifyMail([
        'amount' => 5000,
        'ref' => 'REF-PAY-987654',
        'bankName' => 'Wema Bank',
        'type' => 'bank_transfer',
    ]);
    $html9 = $mail9->render();
    expect($html9)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('5,000.00')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 10. SimActivationDetailsMail
    $sim = Sim::create([
        'number' => '08031234567',
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => 'activated',
        'user_id' => $user->id,
    ]);
    $customer = SimCustomer::create([
        'sim_id' => $sim->id,
        'full_name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'address' => '123 Market Road, Lagos',
    ]);
    $mail10 = new SimActivationDetailsMail($sim, $customer, $user);
    $html10 = $mail10->render();
    expect($html10)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');

    // 11. TicketClosedMail
    $mail11 = new TicketClosedMail($ticket);
    $html11 = $mail11->render();
    expect($html11)->toContain('assets/images/logo/logo1.png')
        ->toContain('SimPhix')
        ->toContain('Empowering Businesses Through Smart Connectivity');
});
