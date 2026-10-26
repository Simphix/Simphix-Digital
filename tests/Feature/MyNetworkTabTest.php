<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('my network page places the active tab before the invited tab and defaults to active', function () {
    $user = User::factory()->create(['role' => 'partner']);

    // Create an active referral and an invited referral
    $activeMember = User::factory()->create([
        'referred_by' => $user->id,
        'status' => 'active',
        'first_name' => 'Active',
        'last_name' => 'Member',
    ]);

    $invitedMember = User::factory()->create([
        'referred_by' => $user->id,
        'status' => 'invited',
        'first_name' => 'Pending',
        'last_name' => 'Invitee',
    ]);

    $response = $this->actingAs($user)->get(route('network'));

    $response->assertOk();

    $html = $response->getContent();

    // Check Alpine default tab is active
    expect($html)->toContain("currentTab: 'active'");

    // Check Active tab button comes before Invited tab button in the HTML
    $activeTabPos = strpos($html, 'Active (');
    $invitedTabPos = strpos($html, 'Invited (');

    expect($activeTabPos)->not->toBeFalse();
    expect($invitedTabPos)->not->toBeFalse();
    expect($activeTabPos)->toBeLessThan($invitedTabPos);

    // Check Active Panel comes before Invited Panel in the HTML
    $activePanelPos = strpos($html, "x-show=\"currentTab === 'active'\"");
    $invitedPanelPos = strpos($html, "x-show=\"currentTab === 'invited'\"");

    expect($activePanelPos)->not->toBeFalse();
    expect($invitedPanelPos)->not->toBeFalse();
    expect($activePanelPos)->toBeLessThan($invitedPanelPos);
});

test('my network switches default tab to invited when invited_page param is present', function () {
    $user = User::factory()->create(['role' => 'partner']);

    $response = $this->actingAs($user)->get(route('network', ['invited_page' => 2]));

    $response->assertOk();
    expect($response->getContent())->toContain("currentTab: 'invited'");
});
