<?php

use App\Models\EnterprisePartnerReferralSettings;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('current() creates a single default row and is idempotent', function () {
    $first = EnterprisePartnerReferralSettings::current();
    $second = EnterprisePartnerReferralSettings::current();

    expect($first->id)->toBe($second->id);
    expect(EnterprisePartnerReferralSettings::count())->toBe(1);
    expect($first->is_active)->toBeTrue();
});

test('amountForRole returns the configured amount per eligible inviter role, zero for anything else', function () {
    $settings = EnterprisePartnerReferralSettings::current();
    $settings->update([
        'regional_manager_amount' => 5000,
        'coordinator_amount' => 3000,
        'partner_amount' => 1000,
    ]);

    expect($settings->amountForRole('regional_manager'))->toBe(5000.0);
    expect($settings->amountForRole('coordinator'))->toBe(3000.0);
    expect($settings->amountForRole('partner'))->toBe(1000.0);
    expect($settings->amountForRole('business'))->toBe(0.0);
    expect($settings->amountForRole('agent'))->toBe(0.0);
});

test('amountForRole returns zero for every role while settings are inactive', function () {
    $settings = EnterprisePartnerReferralSettings::current();
    $settings->update(['partner_amount' => 1000, 'is_active' => false]);

    expect($settings->amountForRole('partner'))->toBe(0.0);
});

test('non-admins are refused the referral-settings page', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('admin.enterprise-partners.referral-settings.edit'))->assertForbidden();
});

test('admin can view and update the referral-settings page', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->get(route('admin.enterprise-partners.referral-settings.edit'))->assertOk();

    $this->actingAs($admin)->put(route('admin.enterprise-partners.referral-settings.update'), [
        'regional_manager_amount' => 4000,
        'coordinator_amount' => 2500,
        'partner_amount' => 1200,
        'is_active' => 1,
    ])->assertRedirect();

    $settings = EnterprisePartnerReferralSettings::current();
    expect((float) $settings->regional_manager_amount)->toBe(4000.0);
    expect((float) $settings->coordinator_amount)->toBe(2500.0);
    expect((float) $settings->partner_amount)->toBe(1200.0);
    expect($settings->is_active)->toBeTrue();
});
