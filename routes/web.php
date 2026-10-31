<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome2');
})->name('welcome2');

Route::get('/welcome2', function () {
    return redirect()->route('welcome2', [], 301);
});

Route::post('/palmpay/webhook', [\App\Http\Controllers\PaymentWebhookController::class, 'handleWebhook'])->name('palmpay.webhook');

// Enterprise Partner registration — reached via a referral link shared by an
// eligible upline (see enterprise-partner.invite); guest-only since the
// invitee doesn't have an account yet.
Route::middleware('guest')->group(function () {
    Route::get('/enterprise-partner/register', [\App\Http\Controllers\EnterprisePartnerController::class, 'registerForm'])->name('enterprise-partner.register');
    Route::post('/enterprise-partner/register', [\App\Http\Controllers\EnterprisePartnerController::class, 'register'])->name('enterprise-partner.register.store')->middleware('throttle:5,1');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/kyc', [\App\Http\Controllers\KycController::class, 'submit'])->name('kyc.submit');

    // Wallet Routes
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/claim-bonus', [\App\Http\Controllers\WalletController::class, 'claimBonus'])->name('wallet.claimBonus');
    Route::post('/virtual-account/create', [\App\Http\Controllers\WalletController::class, 'createWallet'])->name('virtual.account.create');
    Route::get('/wallet/balance', [\App\Http\Controllers\WalletController::class, 'getBalance'])->name('wallet.balance');

    // PIN Verification Route
    Route::post('/verify/pin', [\App\Http\Controllers\WalletController::class, 'verifyPin'])->name('verify.pin')->middleware('throttle:5,1');

    // Withdraw Routes
    Route::get('/withdraw', [\App\Http\Controllers\Action\WithdrawController::class, 'index'])->name('withdraw')->middleware('cashout_enabled');
    Route::get('/withdraw/sync-banks', [\App\Http\Controllers\Action\WithdrawController::class, 'syncBanks'])->name('withdraw.syncBanks');
    Route::post('/withdraw/verify-account', [\App\Http\Controllers\Action\WithdrawController::class, 'verifyAccount'])->name('withdraw.verifyAccount');
    Route::post('/withdraw/process', [\App\Http\Controllers\Action\WithdrawController::class, 'processWithdrawal'])->name('withdraw.process')->middleware('cashout_enabled');

    // Push Notification Subscription Routes
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    // Transactions Routes
    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions');
    Route::get('/receipt', [\App\Http\Controllers\TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::get('/thankyou', [\App\Http\Controllers\TransactionController::class, 'receipt'])->name('thankyou');

    // Onboarding & Network Routes
    Route::get('/onboarding/invite', [\App\Http\Controllers\OnboardingController::class, 'inviteForm'])->name('onboarding.invite');
    Route::post('/onboarding/invite', [\App\Http\Controllers\OnboardingController::class, 'sendInvite'])->name('onboarding.invite.send');
    Route::get('/network', [\App\Http\Controllers\NetworkController::class, 'index'])->name('network');
    Route::get('/network/claim/check', [\App\Http\Controllers\NetworkController::class, 'checkClaim'])->name('network.claim.check');
    Route::post('/network/claim', [\App\Http\Controllers\NetworkController::class, 'claim'])->name('network.claim');
    Route::patch('/network/invited/{user}', [\App\Http\Controllers\NetworkController::class, 'updateInvited'])->name('network.invited.update');
    Route::post('/network/invited/{user}/resend', [\App\Http\Controllers\NetworkController::class, 'resendInvite'])->name('network.invited.resend');

    // Enterprise Partner invite + info — registration itself is a guest
    // route (see below), reached via the shareable ?ref= link this page hands out.
    Route::get('/enterprise-partner/invite', [\App\Http\Controllers\EnterprisePartnerController::class, 'inviteForm'])->name('enterprise-partner.invite');
    Route::get('/enterprise-partner/info', [\App\Http\Controllers\EnterprisePartnerController::class, 'infoPage'])->name('enterprise-partner.info');
    Route::post('/enterprise-partner/cart/items', [\App\Http\Controllers\EnterprisePartnerController::class, 'addItem'])->name('enterprise-partner.cart.items.store');
    Route::patch('/enterprise-partner/cart/items/{item}', [\App\Http\Controllers\EnterprisePartnerController::class, 'updateItemQuantity'])->name('enterprise-partner.cart.items.update');
    Route::delete('/enterprise-partner/cart/items/{item}', [\App\Http\Controllers\EnterprisePartnerController::class, 'removeItem'])->name('enterprise-partner.cart.items.destroy');
    Route::get('/enterprise-partner/generate-account', [\App\Http\Controllers\EnterprisePartnerController::class, 'generateAccountPage'])->name('enterprise-partner.generate-account');
    Route::post('/enterprise-partner/generate-account', [\App\Http\Controllers\EnterprisePartnerController::class, 'generateAccount'])->name('enterprise-partner.generate-account.create');

    // Agent upgrade — self-service, instant. Personal-role users only; see
    // AgentUpgradeController.
    Route::get('/agent-upgrade', [\App\Http\Controllers\AgentUpgradeController::class, 'show'])->name('agent-upgrade.show');
    Route::post('/agent-upgrade', [\App\Http\Controllers\AgentUpgradeController::class, 'process'])->name('agent-upgrade.process');

    // Downline restriction actions — shared by catalog-role self-service (own
    // immediate downline only) and super_admin (anyone); see
    // DownlineAuthorization::authorize().
    Route::post('/network/downline/{user}/pnd', [\App\Http\Controllers\DownlineRestrictionController::class, 'placePnd'])->name('network.downline.pnd');
    Route::post('/network/downline/{user}/pnd/lift', [\App\Http\Controllers\DownlineRestrictionController::class, 'liftPnd'])->name('network.downline.pnd.lift');
    Route::post('/network/downline/{user}/suspend', [\App\Http\Controllers\DownlineRestrictionController::class, 'suspend'])->name('network.downline.suspend');
    Route::post('/network/downline/{user}/reinstate', [\App\Http\Controllers\DownlineRestrictionController::class, 'reinstate'])->name('network.downline.reinstate');
    Route::post('/network/downline/{user}/lien', [\App\Http\Controllers\DownlineRestrictionController::class, 'placeLien'])->name('network.downline.lien');
    Route::post('/network/downline/{user}/lien/lift', [\App\Http\Controllers\DownlineRestrictionController::class, 'liftLien'])->name('network.downline.lien.lift');

    // Team-member detail page — Info/Metrics/Tier/Actions tabs for a single
    // downline user; see DownlineDetailController.
    Route::get('/network/downline/{user}', [\App\Http\Controllers\DownlineDetailController::class, 'show'])->name('network.downline.show');
    Route::post('/network/downline/{user}/complete-onboarding', [\App\Http\Controllers\DownlineDetailController::class, 'completeOnboarding'])->name('network.downline.complete-onboarding');
    Route::post('/network/downline/{user}/virtual-account', [\App\Http\Controllers\DownlineDetailController::class, 'generateVirtualAccount'])->name('network.downline.virtual-account');
    Route::post('/network/downline/{user}/assign-district', [\App\Http\Controllers\DownlineDetailController::class, 'assignDistrict'])->name('network.downline.assign-district');
    Route::post('/network/downline/{user}/unassign-district', [\App\Http\Controllers\DownlineDetailController::class, 'unassignDistrict'])->name('network.downline.unassign-district');

    // Leaderboard & Commission Dashboard Routes
    Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('/commissions/dashboard', [\App\Http\Controllers\CommissionDashboardController::class, 'index'])->name('commissions.dashboard');
    Route::get('/sales-performance', [\App\Http\Controllers\SalesPerformanceController::class, 'index'])->name('sales-performance.index');
    Route::get('/sales-performance/export', [\App\Http\Controllers\SalesPerformanceController::class, 'export'])->name('sales-performance.export');

    // Airtime, SME Data & Verification Routes — restricted to Personal/Business
    // account roles (App\Support\UtilityAccess); resellers and back-office
    // roles manage the catalog/hierarchy instead of buying these for
    // themselves through this storefront flow.
    Route::middleware('utility_access')->group(function () {
        // Airtime Routes
        Route::get('/airtime', [\App\Http\Controllers\Action\AirtimeController::class, 'airtime'])->name('airtime');
        Route::post('/airtime', [\App\Http\Controllers\Action\AirtimeController::class, 'buyAirtime'])->name('buyairtime');

        // SME Data Routes
        Route::get('/buy-sme-data', [\App\Http\Controllers\Action\SmeDataController::class, 'index'])->name('buy-sme-data');
        Route::post('/buy-sme-data', [\App\Http\Controllers\Action\SmeDataController::class, 'buySMEdata'])->name('buy-sme-data.submit');
        Route::get('/sme/fetch-type', [\App\Http\Controllers\Action\SmeDataController::class, 'fetchDataType'])->name('sme.fetch.type');
        Route::get('/sme/fetch-plan', [\App\Http\Controllers\Action\SmeDataController::class, 'fetchDataPlan'])->name('sme.fetch.plan');
        Route::get('/sme/fetch-price', [\App\Http\Controllers\Action\SmeDataController::class, 'fetchSmeBundlePrice'])->name('sme.fetch.price');
    });

    // Profile PIN update requires verified email (financial action)
    Route::post('/profile/pin', [ProfileController::class, 'updatePin'])->name('profile.pin.update')->middleware('throttle:5,1');
    Route::post('/profile/withdrawal-account', [ProfileController::class, 'updateWithdrawalAccount'])->name('profile.withdrawal-account.update')->middleware('throttle:5,1');
    Route::post('/profile/device-registration', [ProfileController::class, 'toggleDeviceRegistration'])->name('profile.device-registration.update')->middleware('throttle:5,1');
    Route::delete('/profile/trusted-devices/{device}', [ProfileController::class, 'revokeTrustedDevice'])->name('profile.trusted-devices.revoke');
    Route::post('/profile/phone/send-otp', [ProfileController::class, 'sendPhoneVerificationOtp'])->name('profile.phone.send-otp')->middleware('throttle:5,1');
    Route::post('/profile/phone/verify-otp', [ProfileController::class, 'verifyPhoneOtp'])->name('profile.phone.verify-otp')->middleware('throttle:5,1');

    // CAC registration (customer accounts only — enforced in the controller)
    Route::prefix('cac')->name('cac.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CacRegistrationController::class, 'overview'])->name('overview');
        Route::get('/history', [\App\Http\Controllers\CacRegistrationController::class, 'history'])->name('history');
        Route::get('/history/{registration}', [\App\Http\Controllers\CacRegistrationController::class, 'show'])->name('show');
        Route::post('/history/{registration}/reply', [\App\Http\Controllers\CacRegistrationController::class, 'reply'])->name('reply');
        Route::get('/history/{registration}/documents/{document}', [\App\Http\Controllers\CacRegistrationController::class, 'download'])->name('download');
        Route::get('/{slug}/register', [\App\Http\Controllers\CacRegistrationController::class, 'create'])->name('create');
        Route::post('/{slug}/register', [\App\Http\Controllers\CacRegistrationController::class, 'store'])->name('store');
    });

    // Support Tickets Routes
    Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->name('support');
    Route::post('/support', [\App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [\App\Http\Controllers\SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [\App\Http\Controllers\SupportController::class, 'reply'])->name('support.reply');

    // SIM Services Routes
    Route::get('/sims', [\App\Http\Controllers\smartsim\SimsController::class, 'overview'])->name('sims.index');
    Route::get('/sims/pos', [\App\Http\Controllers\smartsim\SimsController::class, 'pos'])->name('sims.pos');
    Route::get('/sims/pos/request', [\App\Http\Controllers\smartsim\SimsController::class, 'posRequestForm'])->name('sims.pos.request');
    Route::get('/sims/cctv', [\App\Http\Controllers\smartsim\SimsController::class, 'cctv'])->name('sims.cctv');
    Route::get('/sims/cctv/request', [\App\Http\Controllers\smartsim\SimsController::class, 'cctvRequestForm'])->name('sims.cctv.request');
    Route::get('/sims/router', [\App\Http\Controllers\smartsim\SimsController::class, 'router'])->name('sims.router');
    Route::get('/sims/router/request', [\App\Http\Controllers\smartsim\SimsController::class, 'routerRequestForm'])->name('sims.router.request');
    Route::get('/sims/inventory', [\App\Http\Controllers\smartsim\SimsController::class, 'inventory'])->name('sims.inventory');
    Route::get('/sims/mine', [\App\Http\Controllers\smartsim\SimsController::class, 'mine'])->name('sims.mine');
    Route::get('/sims/available-numbers', [\App\Http\Controllers\smartsim\SimsController::class, 'getAvailableNumbers'])->name('sims.available');
    Route::post('/sims/request', [\App\Http\Controllers\smartsim\SimsController::class, 'requestSim'])->name('sims.request');
    Route::get('/sims/activate', [\App\Http\Controllers\smartsim\SimsController::class, 'activationPage'])->name('sims.activate.page');
    Route::post('/sims/activate', [\App\Http\Controllers\smartsim\SimsController::class, 'activateSim'])->name('sims.activate');
    Route::post('/sims/activate-for-user', [\App\Http\Controllers\smartsim\SimsController::class, 'activateForDownlineUser'])->name('sims.activate-for-user');
    Route::post('/sims/activated/{sim}/update-customer', [\App\Http\Controllers\smartsim\SimsController::class, 'updateCustomerDetails'])->name('sims.activated.update-customer');
    Route::post('/sims/activated/{sim}/push-email', [\App\Http\Controllers\smartsim\SimsController::class, 'pushActivationDetailsEmail'])->name('sims.activated.push-email');
    Route::post('/partner/sims/assign', [\App\Http\Controllers\smartsim\SimsController::class, 'partnerAssignSim'])->name('partner.sims.assign');
    Route::post('/sims/bulk-assign', [\App\Http\Controllers\smartsim\SimsController::class, 'bulkAssignSim'])->name('sims.bulk-assign');
    Route::get('/sims/bulk-assign/new', [\App\Http\Controllers\smartsim\SimsController::class, 'bulkAssignPage'])->name('sims.bulk-assign.page');
    Route::post('/sims/bulk-assign/resolve-numbers', [\App\Http\Controllers\smartsim\SimsController::class, 'resolveBulkAssignNumbers'])->name('sims.bulk-assign.resolve-numbers');
    Route::get('/sims/bulk-collect', [\App\Http\Controllers\smartsim\SimsController::class, 'bulkCollectPage'])->name('sims.bulk-collect');
    Route::post('/sims/bulk-collect/resolve-numbers', [\App\Http\Controllers\smartsim\SimsController::class, 'resolveBulkCollectNumbers'])->name('sims.bulk-collect.resolve-numbers');
    Route::post('/sims/bulk-collect', [\App\Http\Controllers\smartsim\SimsController::class, 'bulkCollect'])->name('sims.bulk-collect.execute');
    Route::get('/sims/downline-users/search', [\App\Http\Controllers\smartsim\SimsController::class, 'searchUsers'])->name('sims.downline-users.search');
    Route::post('/sims/request-swap', [\App\Http\Controllers\smartsim\SimsController::class, 'requestSwap'])->name('sims.request-swap');
    Route::get('/sims/downline-requests', [\App\Http\Controllers\smartsim\SimsController::class, 'downlineRequests'])->name('sims.downline-requests');
    Route::get('/sims/requests', [\App\Http\Controllers\smartsim\SimsController::class, 'downlineRequests'])->name('sims.requests');
    Route::get('/sims/downline-requests/available-sims', [\App\Http\Controllers\smartsim\SimsController::class, 'downlineRequestAvailableSims'])->name('sims.downline-requests.available-sims');
    Route::post('/sims/downline-requests/{simRequest}/fulfill', [\App\Http\Controllers\smartsim\SimsController::class, 'fulfillDownlineRequest'])->name('sims.downline-requests.fulfill');
    Route::post('/sims/downline-requests/{simRequest}/resolve-numbers', [\App\Http\Controllers\smartsim\SimsController::class, 'resolveDownlineRequestNumbers'])->name('sims.downline-requests.resolve-numbers');
    Route::get('/sims/business/commission-rates', [\App\Http\Controllers\smartsim\BusinessCommissionRateController::class, 'index'])->name('sims.business.commission-rates.index');
    Route::post('/sims/business/commission-rates', [\App\Http\Controllers\smartsim\BusinessCommissionRateController::class, 'store'])->name('sims.business.commission-rates.store');

    // Identity Verification Routes — personal/business/agent plus partner
    // (a narrower carve-out than Airtime/SME Data above).
    Route::prefix('verification')->middleware('verification_access')->group(function () {
        // BVN Verification
        Route::get('/bvn', [\App\Http\Controllers\Verification\BvnverificationController::class, 'index'])->name('bvn.verification.index');
        Route::post('/bvn', [\App\Http\Controllers\Verification\BvnverificationController::class, 'store'])->name('bvn.verification.store');
        Route::get('/bvn/standard/{bvn_no}', [\App\Http\Controllers\Verification\BvnverificationController::class, 'standardBVN'])->name('standardBVN');
        Route::get('/bvn/premium/{bvn_no}', [\App\Http\Controllers\Verification\BvnverificationController::class, 'premiumBVN'])->name('premiumBVN');
        Route::get('/bvn/plastic/{bvn_no}', [\App\Http\Controllers\Verification\BvnverificationController::class, 'plasticBVN'])->name('plasticBVN');

        // NIN Verification
        Route::get('/nin', [\App\Http\Controllers\Verification\NINverificationController::class, 'index'])->name('nin.verification.index');
        Route::post('/nin', [\App\Http\Controllers\Verification\NINverificationController::class, 'store'])->name('nin.verification.store');
        Route::get('/nin/regular/{nin_no}', [\App\Http\Controllers\Verification\NINverificationController::class, 'regularSlip'])->name('regularSlip');
        Route::get('/nin/standard/{nin_no}', [\App\Http\Controllers\Verification\NINverificationController::class, 'standardSlip'])->name('standardSlip');
        Route::get('/nin/premium/{nin_no}', [\App\Http\Controllers\Verification\NINverificationController::class, 'premiumSlip'])->name('premiumSlip');
        Route::get('/nin/vnin/{nin_no}', [\App\Http\Controllers\Verification\NINverificationController::class, 'vninSlip'])->name('vninSlip');

        // NIN Demo Verification
        Route::get('/nin-demo', [\App\Http\Controllers\Verification\NINDemoVerificationController::class, 'index'])->name('nin.demo.index');
        Route::post('/nin-demo', [\App\Http\Controllers\Verification\NINDemoVerificationController::class, 'store'])->name('nin.demo.store');
        Route::get('/nin-demo/regular/{nin_no}', [\App\Http\Controllers\Verification\NINDemoVerificationController::class, 'regularSlip'])->name('nin.demo.regularSlip');
        Route::get('/nin-demo/standard/{nin_no}', [\App\Http\Controllers\Verification\NINDemoVerificationController::class, 'standardSlip'])->name('nin.demo.standardSlip');
        Route::get('/nin-demo/premium/{nin_no}', [\App\Http\Controllers\Verification\NINDemoVerificationController::class, 'premiumSlip'])->name('nin.demo.premiumSlip');

        // NIN Phone Verification
        Route::get('/nin-phone', [\App\Http\Controllers\Verification\NINPhoneVerificationController::class, 'index'])->name('nin.phone.index');
        Route::post('/nin-phone', [\App\Http\Controllers\Verification\NINPhoneVerificationController::class, 'store'])->name('nin.phone.store');
        Route::get('/nin-phone/regular/{nin_no}', [\App\Http\Controllers\Verification\NINPhoneVerificationController::class, 'regularSlip'])->name('nin.phone.regularSlip');
        Route::get('/nin-phone/standard/{nin_no}', [\App\Http\Controllers\Verification\NINPhoneVerificationController::class, 'standardSlip'])->name('nin.phone.standardSlip');
        Route::get('/nin-phone/premium/{nin_no}', [\App\Http\Controllers\Verification\NINPhoneVerificationController::class, 'premiumSlip'])->name('nin.phone.premiumSlip');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/upgrade', [ProfileController::class, 'requestUpgrade'])->name('profile.upgrade.request');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Business Sales Manager (BSM) Console
Route::middleware(['auth', 'verified', 'business_sales_manager'])->prefix('bsm')->name('bsm.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/territories', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'territories'])->name('territories');
    Route::get('/territories/drilldown', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'territoryDrilldown'])->name('territories.drilldown');
    Route::get('/lifecycle', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'lifecycle'])->name('lifecycle');
    Route::get('/customers', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'customers'])->name('customers');
    Route::get('/acceleration', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'acceleration'])->name('acceleration');
    Route::get('/reports/jurisdictions/export', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'exportJurisdictionReport'])->name('reports.jurisdictions.export');
    Route::get('/reports/acceleration/export', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'exportAccelerationReport'])->name('reports.acceleration.export');
    Route::get('/reports/acceleration/managers', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'searchManagers'])->name('reports.acceleration.managers');
    Route::post('/lifecycle/assign', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'assignSim'])->name('lifecycle.assign');
    Route::post('/lifecycle/reassign', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'reassignSim'])->name('lifecycle.reassign');
    Route::post('/lifecycle/revert', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'revertSim'])->name('lifecycle.revert');
    Route::post('/lifecycle/{sim}/status', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'updateSimStatus'])->name('lifecycle.status');
    Route::post('/customers/quick-activate', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'quickActivate'])->name('customers.quick-activate');
    Route::post('/requests/{simRequest}/approve', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'approveRequest'])->name('requests.approve');
    Route::post('/failed-activations/{report}/retry', [\App\Http\Controllers\Bsm\BsmDashboardController::class, 'retryFailedActivation'])->name('failed-activations.retry');
});

Route::middleware(['auth', 'verified', 'support_staff'])->prefix('support-desk')->name('support-desk.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Support\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tickets', [\App\Http\Controllers\Support\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Support\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Support\TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [\App\Http\Controllers\Support\TicketController::class, 'updateStatus'])->name('tickets.status');

    Route::get('/transactions', [\App\Http\Controllers\Support\TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/sims', [\App\Http\Controllers\Support\SimController::class, 'index'])->name('sims.index');
    Route::get('/sims/{sim}', [\App\Http\Controllers\Support\SimController::class, 'show'])->name('sims.show');

    Route::get('/users', [\App\Http\Controllers\Support\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Support\UserController::class, 'show'])->name('users.show');

    Route::get('/sim-data-topup', [\App\Http\Controllers\Support\SimDataTopUpController::class, 'index'])->name('sim-data-topup.index');
    Route::get('/sim-data-topup/search-sims', [\App\Http\Controllers\Support\SimDataTopUpController::class, 'searchSims'])->name('sim-data-topup.search-sims');
    Route::get('/sim-data-topup/sims/{sim}/usage-review', [\App\Http\Controllers\Support\SimDataTopUpController::class, 'usageReview'])->name('sim-data-topup.usage-review');
    Route::get('/sim-data-topup/sims/{sim}/plans', [\App\Http\Controllers\Support\SimDataTopUpController::class, 'plans'])->name('sim-data-topup.plans');
    Route::post('/sim-data-topup/sims/{sim}/topup', [\App\Http\Controllers\Support\SimDataTopUpController::class, 'topup'])->name('sim-data-topup.topup');

    Route::prefix('sim-inventory')->name('sim-inventory.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Support\SimInventoryController::class, 'index'])->name('index');
        Route::get('/users/search', [\App\Http\Controllers\Support\SimInventoryController::class, 'searchUsers'])->name('users.search');

        // Declared before the '/{sim}' catch-all below — a route with a
        // literal segment declared after a '/{param}' route gets shadowed
        // (see the same note on the admin/sim-plan group).
        Route::get('/bulk-assign', [\App\Http\Controllers\Support\SimInventoryController::class, 'bulkAssignPage'])->name('bulk-assign');
        Route::post('/bulk-assign/resolve-numbers', [\App\Http\Controllers\Support\SimInventoryController::class, 'resolveBulkAssignNumbers'])->name('bulk-assign.resolve-numbers');
        Route::post('/bulk-assign', [\App\Http\Controllers\Support\SimInventoryController::class, 'bulkAssign'])->name('bulk-assign.execute');

        Route::get('/bulk-collect', [\App\Http\Controllers\Support\SimInventoryController::class, 'bulkCollectPage'])->name('bulk-collect');
        Route::post('/bulk-collect/resolve-numbers', [\App\Http\Controllers\Support\SimInventoryController::class, 'resolveBulkCollectNumbers'])->name('bulk-collect.resolve-numbers');
        Route::post('/bulk-collect', [\App\Http\Controllers\Support\SimInventoryController::class, 'bulkCollect'])->name('bulk-collect.execute');

        Route::get('/{sim}', [\App\Http\Controllers\Support\SimInventoryController::class, 'show'])->name('show');
        Route::post('/{sim}/assign', [\App\Http\Controllers\Support\SimInventoryController::class, 'assign'])->name('assign');
        Route::post('/{sim}/reassign', [\App\Http\Controllers\Support\SimInventoryController::class, 'reassign'])->name('reassign');
        Route::post('/{sim}/unassign', [\App\Http\Controllers\Support\SimInventoryController::class, 'unassign'])->name('unassign');
    });

    Route::get('/webhooks', [\App\Http\Controllers\Support\WebhookLogController::class, 'index'])->name('webhooks.index');

    Route::get('/failed-activations', [\App\Http\Controllers\Support\FailedActivationController::class, 'index'])->name('failed-activations.index');
    Route::post('/failed-activations/{report}/retry', [\App\Http\Controllers\Support\FailedActivationController::class, 'retry'])->name('failed-activations.retry');
});

Route::middleware(['auth', 'verified', 'business_sales_manager'])->prefix('admin/manage')->name('admin.manage.')->group(function () {
    // Users Management & Role Control
    Route::get('/users', [\App\Http\Controllers\Admin\ManageController::class, 'users'])->name('users');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\ManageController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\ManageController::class, 'updateUser'])->name('users.update');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\ManageController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/team', [\App\Http\Controllers\Admin\ManageController::class, 'userTeam'])->name('users.team');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\ManageController::class, 'destroyUser'])->name('users.destroy');

    // Manager replacement — hand an upline's seat to any platform user,
    // automatically upgrading their role and batching downlines to Platform Master Manager.
    Route::get('/replace-manager', [\App\Http\Controllers\Admin\ManagerReplacementController::class, 'index'])->name('replace-manager.index');
    Route::get('/users/{user}/replace', [\App\Http\Controllers\Admin\ManagerReplacementController::class, 'show'])->name('users.replace');
    Route::get('/users/{user}/replace/candidates', [\App\Http\Controllers\Admin\ManagerReplacementController::class, 'searchCandidates'])->name('users.replace.candidates');
    Route::post('/users/{user}/replace', [\App\Http\Controllers\Admin\ManagerReplacementController::class, 'store'])->name('users.replace.store');

    // Downline Batches & Bulk Transfer
    Route::get('/downline-batches', [\App\Http\Controllers\Admin\DownlineBatchController::class, 'index'])->name('downline-batches.index');
    Route::get('/downline-batches/{batch}', [\App\Http\Controllers\Admin\DownlineBatchController::class, 'show'])->name('downline-batches.show');
    Route::post('/downline-batches/{batch}/transfer', [\App\Http\Controllers\Admin\DownlineBatchController::class, 'transfer'])->name('downline-batches.transfer');

    Route::get('/upgrades', [\App\Http\Controllers\Admin\ManageController::class, 'upgrades'])->name('upgrades');
    Route::post('/upgrades/{user}/approve', [\App\Http\Controllers\Admin\ManageController::class, 'approveUpgrade'])->name('upgrades.approve');
    Route::post('/upgrades/{user}/reject', [\App\Http\Controllers\Admin\ManageController::class, 'rejectUpgrade'])->name('upgrades.reject');

    Route::get('/access', [\App\Http\Controllers\Admin\ManageController::class, 'access'])->name('access');
    Route::put('/access/{user}', [\App\Http\Controllers\Admin\ManageController::class, 'updateAccess'])->name('access.update');
});

Route::middleware(['auth', 'verified', 'super_admin'])->prefix('admin/manage')->name('admin.manage.')->group(function () {
    // Admin Support Tickets Routes
    Route::get('/support', [\App\Http\Controllers\Admin\SupportController::class, 'index'])->name('support.index');
    Route::get('/support/{ticket}', [\App\Http\Controllers\Admin\SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Admin\SupportController::class, 'reply'])->name('support.reply');
    Route::post('/support/{ticket}/status', [\App\Http\Controllers\Admin\SupportController::class, 'updateStatus'])->name('support.status');

    // Admin Wallet Routes
    Route::get('/adminwallet', [\App\Http\Controllers\Admin\AdminWalletController::class, 'index'])->name('adminwallet');
    Route::post('/adminwallet/verify-user', [\App\Http\Controllers\Admin\AdminWalletController::class, 'verifyUser'])->name('adminwallet.verify-user');
    Route::post('/adminwallet/single', [\App\Http\Controllers\Admin\AdminWalletController::class, 'adjustSingle'])->name('adminwallet.single');
    Route::post('/adminwallet/general', [\App\Http\Controllers\Admin\AdminWalletController::class, 'adjustGeneral'])->name('adminwallet.general');
});

Route::middleware(['auth', 'verified', 'super_admin'])->group(function () {
    Route::get('/admin/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('admin.transactions');

    // Push Notifications
    Route::prefix('admin/notifications')->name('admin.notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'show'])->name('show');
        Route::post('/{notification}/cancel', [\App\Http\Controllers\Admin\NotificationController::class, 'cancel'])->name('cancel');
    });
                                                                              
    // Platform Wallets (welfare + utilities revenue)
    Route::prefix('admin/platform-wallets')->name('admin.platform-wallets.')->group(function () {
        Route::get('/welfare-settings', [\App\Http\Controllers\Admin\PlatformWelfareSettingsController::class, 'edit'])->name('welfare-settings.edit');
        Route::put('/welfare-settings', [\App\Http\Controllers\Admin\PlatformWelfareSettingsController::class, 'update'])->name('welfare-settings.update');
        Route::get('/withdrawal-account', [\App\Http\Controllers\Admin\PlatformWithdrawalAccountController::class, 'edit'])->name('withdrawal-account.edit');
        Route::post('/withdrawal-account', [\App\Http\Controllers\Admin\PlatformWithdrawalAccountController::class, 'update'])->name('withdrawal-account.update');
        Route::get('/{walletType}/cashout', [\App\Http\Controllers\Admin\PlatformWalletCashOutController::class, 'show'])->name('cashout.show')->middleware('cashout_enabled');
        Route::post('/{walletType}/cashout', [\App\Http\Controllers\Admin\PlatformWalletCashOutController::class, 'store'])->name('cashout.store')->middleware('cashout_enabled');

        Route::prefix('cash-out-approvals')->name('approvals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PlatformCashOutApprovalController::class, 'index'])->name('index');
            Route::post('/{platformCashOutRequest}/approve', [\App\Http\Controllers\Admin\PlatformCashOutApprovalController::class, 'approve'])->name('approve')->middleware('cashout_enabled');
            Route::post('/{platformCashOutRequest}/reject', [\App\Http\Controllers\Admin\PlatformCashOutApprovalController::class, 'reject'])->name('reject');
        });
    });

    // Enterprise Partner referral settings — one-time bonus paid to whoever
    // invited a newly-activated Enterprise Partner.
    Route::prefix('admin/enterprise-partners')->name('admin.enterprise-partners.')->group(function () {
        Route::get('/referral-settings', [\App\Http\Controllers\Admin\EnterprisePartnerReferralSettingsController::class, 'edit'])->name('referral-settings.edit');
        Route::put('/referral-settings', [\App\Http\Controllers\Admin\EnterprisePartnerReferralSettingsController::class, 'update'])->name('referral-settings.update');

        // EP order fulfillment — a paid order is a request until an admin
        // reviews it here and decides how many of each item to actually grant.
        Route::prefix('fulfillment')->name('fulfillment.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EnterprisePartnerFulfillmentController::class, 'index'])->name('index');
            Route::post('/{application}/fulfill', [\App\Http\Controllers\Admin\EnterprisePartnerFulfillmentController::class, 'fulfill'])->name('fulfill');
            Route::post('/items/{item}/resolve-numbers', [\App\Http\Controllers\Admin\EnterprisePartnerFulfillmentController::class, 'resolveItemNumbers'])->name('items.resolve-numbers');
        });
    });

    // Agent upgrade settings — minimum wallet balance to self-upgrade to
    // Agent, and the one-time commission paid to a Partner upline.
    Route::prefix('admin/agent-upgrade')->name('admin.agent-upgrade-settings.')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\AgentUpgradeSettingsController::class, 'edit'])->name('edit');
        Route::put('/settings', [\App\Http\Controllers\Admin\AgentUpgradeSettingsController::class, 'update'])->name('update');
    });
  
    // Jurisdictions (Region -> State -> SenatorialDistrict admin-authored tree,
    // used to assign geographic coverage to Regional Manager / Coordinator).
    Route::prefix('admin/jurisdictions')->name('admin.jurisdictions.')->group(function () {
        Route::get('/regions', [\App\Http\Controllers\Admin\JurisdictionController::class, 'regions'])->name('regions.index');
        Route::post('/regions', [\App\Http\Controllers\Admin\JurisdictionController::class, 'storeRegion'])->name('regions.store');
        Route::put('/regions/{region}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'updateRegion'])->name('regions.update');
        Route::delete('/regions/{region}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'destroyRegion'])->name('regions.destroy');

        Route::get('/states', [\App\Http\Controllers\Admin\JurisdictionController::class, 'states'])->name('states.index');
        Route::post('/states', [\App\Http\Controllers\Admin\JurisdictionController::class, 'storeState'])->name('states.store');
        Route::put('/states/{state}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'updateState'])->name('states.update');
        Route::delete('/states/{state}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'destroyState'])->name('states.destroy');

        Route::get('/districts', [\App\Http\Controllers\Admin\JurisdictionController::class, 'districts'])->name('districts.index');
        Route::post('/districts', [\App\Http\Controllers\Admin\JurisdictionController::class, 'storeDistrict'])->name('districts.store');
        Route::put('/districts/{district}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'updateDistrict'])->name('districts.update');
        Route::delete('/districts/{district}', [\App\Http\Controllers\Admin\JurisdictionController::class, 'destroyDistrict'])->name('districts.destroy');
    });

    // Reconciliation (read-only detection across P2P, deposits, cashouts,
    // commissions, purchases, the SIM activation bonus, and wallet integrity)
    Route::prefix('admin/reconciliation')->name('admin.reconciliation.')->group(function () {
        Route::get('/{tab?}', [\App\Http\Controllers\Admin\ReconciliationController::class, 'index'])->name('index');
    });

    // CAC registrations (admin)
    Route::prefix('admin/cac')->name('admin.cac.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'index'])->name('index');
        Route::get('/{registration}', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'show'])->name('show');
        Route::post('/{registration}/status', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'updateStatus'])->name('status');
        Route::post('/{registration}/notes', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'addNote'])->name('notes');
        Route::post('/{registration}/upload', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'upload'])->name('upload');
        Route::get('/{registration}/documents/{document}', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'download'])->name('download');
        Route::delete('/{registration}/documents/{document}', [\App\Http\Controllers\Admin\CacRegistrationController::class, 'destroyDocument'])->name('documents.destroy');
    });

    // Advanced SIM Operations dashboard
    Route::prefix('admin/sims-operations')->name('admin.sims-operations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SimsOperationsDashboardController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\SimsOperationsDashboardController::class, 'exportCsv'])->name('export');
        Route::get('/activated-numbers/export', [\App\Http\Controllers\Admin\SimsOperationsDashboardController::class, 'exportActivatedNumbersCsv'])->name('activated-numbers.export');
        Route::get('/activation-bonus/export', [\App\Http\Controllers\Admin\SimsOperationsDashboardController::class, 'exportActivationBonusCsv'])->name('activation-bonus.export');
        Route::put('/export-settings', [\App\Http\Controllers\Admin\ActivatedSimsExportSettingsController::class, 'update'])->name('export-settings.update');
    });

    // Services Management
    Route::prefix('admin/services')->name('admin.services.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\ServiceController::class, 'store'])->name('store');
        Route::get('/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'show'])->name('show');
        Route::put('/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'update'])->name('update');
        Route::delete('/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroy'])->name('destroy');

        // Fields
        Route::post('/{service}/fields', [\App\Http\Controllers\Admin\ServiceController::class, 'storeField'])->name('fields.store');
        Route::put('/fields/{field}', [\App\Http\Controllers\Admin\ServiceController::class, 'updateField'])->name('fields.update');
        Route::delete('/fields/{field}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroyField'])->name('fields.destroy');

        // Prices
        Route::post('/{service}/prices', [\App\Http\Controllers\Admin\ServiceController::class, 'storePrice'])->name('prices.store');
        Route::put('/prices/{price}', [\App\Http\Controllers\Admin\ServiceController::class, 'updatePrice'])->name('prices.update');
        Route::delete('/prices/{price}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroyPrice'])->name('prices.destroy');
    });

    // SME Data Plans Management
    Route::prefix('admin/sme-plans')->name('admin.sme-plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SmePlanController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\SmePlanController::class, 'store'])->name('store');
        Route::put('/activation-bonus', [\App\Http\Controllers\Admin\SmePlanController::class, 'updateActivationBonus'])->name('activation-bonus.update');
        // Declared before the '/{plan}' catch-all below — a route with a
        // literal segment declared AFTER a '/{param}' route gets shadowed
        // (matched as the param instead), as happened once already on this
        // same page with 'activation-bonus'.
        Route::put('/provider-settings', [\App\Http\Controllers\Admin\SmePlanController::class, 'updateProviderSettings'])->name('provider-settings.update');
        Route::put('/provider-settings/activate', [\App\Http\Controllers\Admin\SmePlanController::class, 'activateProvider'])->name('provider-settings.activate');
        Route::put('/{plan}', [\App\Http\Controllers\Admin\SmePlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\App\Http\Controllers\Admin\SmePlanController::class, 'destroy'])->name('destroy');
    });
});

// SIM Cards & Plans Management (accessible to Super Admin and Business Sales Manager)
Route::middleware(['auth', 'verified', 'business_sales_manager'])->prefix('admin/sim-plan')->name('admin.sim-plan.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\SimPlanController::class, 'index'])->name('index');
    Route::post('/store', [\App\Http\Controllers\Admin\SimPlanController::class, 'store'])->name('store');
    Route::post('/assign', [\App\Http\Controllers\Admin\SimPlanController::class, 'assign'])->name('assign');
    Route::post('/direct-assign', [\App\Http\Controllers\Admin\SimPlanController::class, 'directAssign'])->name('direct-assign');
    Route::post('/unassign/{sim}', [\App\Http\Controllers\Admin\SimPlanController::class, 'unassign'])->name('unassign');
    Route::post('/activate/{sim}', [\App\Http\Controllers\Admin\SimPlanController::class, 'activate'])->name('activate');
    Route::post('/requests/{simRequest}/approve', [\App\Http\Controllers\Admin\SimPlanController::class, 'approveRequest'])->name('requests.approve');
    Route::post('/requests/{simRequest}/reject', [\App\Http\Controllers\Admin\SimPlanController::class, 'rejectRequest'])->name('requests.reject');
    Route::post('/requests/{simRequest}/resolve-numbers', [\App\Http\Controllers\Admin\SimPlanController::class, 'resolveRequestNumbers'])->name('requests.resolve-numbers');
    Route::post('/import-excel', [\App\Http\Controllers\Admin\SimPlanController::class, 'importExcel'])->name('import');
    Route::get('/download-sample', [\App\Http\Controllers\Admin\SimPlanController::class, 'downloadSample'])->name('download-sample');
    Route::get('/available-sims', [\App\Http\Controllers\Admin\SimPlanController::class, 'availableSims'])->name('available-sims');

    Route::get('/bulk-assign', [\App\Http\Controllers\Admin\SimPlanController::class, 'bulkAssignPage'])->name('bulk-assign');
    Route::post('/bulk-assign/resolve-numbers', [\App\Http\Controllers\Admin\SimPlanController::class, 'resolveBulkAssignNumbers'])->name('bulk-assign.resolve-numbers');

    Route::get('/bulk-collect', [\App\Http\Controllers\Admin\SimPlanController::class, 'bulkCollectPage'])->name('bulk-collect');
    Route::post('/bulk-collect/resolve-numbers', [\App\Http\Controllers\Admin\SimPlanController::class, 'resolveBulkCollectNumbers'])->name('bulk-collect.resolve-numbers');
    Route::post('/bulk-collect', [\App\Http\Controllers\Admin\SimPlanController::class, 'bulkCollect'])->name('bulk-collect.execute');

    Route::get('/users/search', [\App\Http\Controllers\Admin\SimPlanController::class, 'searchUsers'])->name('users.search');

    Route::prefix('swaps')->name('swaps.')->group(function () {
        Route::post('/{simSwapRequest}/approve', [\App\Http\Controllers\Admin\SimSwapController::class, 'approve'])->name('approve');
        Route::post('/{simSwapRequest}/reject', [\App\Http\Controllers\Admin\SimSwapController::class, 'reject'])->name('reject');
    });

    Route::post('/categories/{field}/toggle-activation', [\App\Http\Controllers\Admin\SimPlanController::class, 'toggleActivation'])->name('categories.toggle-activation');
    Route::post('/failed-activations/{report}/retry', [\App\Http\Controllers\Admin\SimPlanController::class, 'retryActivationBonus'])->name('failed-activations.retry');
});

Route::middleware(['auth', 'verified', 'super_admin'])->group(function () {

    // Admin SIM Data Top-Up — free, admin-granted top-ups via whichever
    // SME-data vendor is currently active, unrestricted by the
    // activation-bonus monthly cap. Only ACTIVATED SIMs with a current
    // holder are eligible.
    Route::prefix('admin/sim-data-topup')->name('admin.sim-data-topup.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SimDataTopUpController::class, 'index'])->name('index');
        Route::get('/search-sims', [\App\Http\Controllers\Admin\SimDataTopUpController::class, 'searchSims'])->name('search-sims');
        Route::get('/sims/{sim}/usage-review', [\App\Http\Controllers\Admin\SimDataTopUpController::class, 'usageReview'])->name('usage-review');
        Route::get('/sims/{sim}/plans', [\App\Http\Controllers\Admin\SimDataTopUpController::class, 'plans'])->name('plans');
        Route::post('/sims/{sim}/topup', [\App\Http\Controllers\Admin\SimDataTopUpController::class, 'topup'])->name('topup');
    });

    // Admin Cash Out Approvals
    Route::prefix('admin/cash-out')->name('admin.cash-out.')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\CashoutSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [\App\Http\Controllers\Admin\CashoutSettingsController::class, 'update'])->name('settings.update');
        Route::get('/', [\App\Http\Controllers\Admin\CashOutController::class, 'index'])->name('index');
        Route::post('/{cashOutRequest}/approve', [\App\Http\Controllers\Admin\CashOutController::class, 'approve'])->name('approve')->middleware('cashout_enabled');
        Route::post('/{cashOutRequest}/reject', [\App\Http\Controllers\Admin\CashOutController::class, 'reject'])->name('reject');
    });

    // Admin Leaderboard Settings (activation tiers, counting period, on/off)
    Route::prefix('admin/leaderboard')->name('admin.leaderboard.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LeaderboardSettingsController::class, 'index'])->name('index');
        Route::put('/settings', [\App\Http\Controllers\Admin\LeaderboardSettingsController::class, 'updateSettings'])->name('settings.update');
        Route::post('/tiers', [\App\Http\Controllers\Admin\LeaderboardSettingsController::class, 'storeTier'])->name('tiers.store');
        Route::put('/tiers/{tier}', [\App\Http\Controllers\Admin\LeaderboardSettingsController::class, 'updateTier'])->name('tiers.update');
        Route::delete('/tiers/{tier}', [\App\Http\Controllers\Admin\LeaderboardSettingsController::class, 'destroyTier'])->name('tiers.destroy');
    });

    // Webhook monitoring — every recorded webhook attempt, with retry on failures.
    Route::prefix('admin/webhooks')->name('admin.webhooks.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WebhookLogController::class, 'index'])->name('index');
        Route::post('/{webhookLog}/retry', [\App\Http\Controllers\Admin\WebhookLogController::class, 'retry'])->name('retry');
    });
});

// Onboarding invite acceptance — invitee is not authenticated yet, gated by the signed URL instead.
Route::middleware('signed')->group(function () {
    Route::get('/onboarding/accept/{user}', [\App\Http\Controllers\OnboardingController::class, 'acceptInviteForm'])->name('onboarding.accept');
    Route::post('/onboarding/accept/{user}', [\App\Http\Controllers\OnboardingController::class, 'completeInvite'])->name('onboarding.accept.complete');
});

// Removed temp-login backdoor route for production security.

Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return 'Cache cleared successfully!';
});

require __DIR__.'/auth.php';
