<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Commission Engine: App\Listeners\AwardCommissions is auto-discovered
        // for the App\Events\SimActivated event by Laravel's listener discovery
        // (its handle() method type-hints the event) — no explicit Event::listen needed.

        // Outbound vendor API rate limits (queued jobs only — see
        // laravel-api-request-standards.md Rule 4). Works against whatever
        // CACHE_STORE is configured (this app uses 'database'); no Redis
        // dependency required.
        RateLimiter::for('sme-data-vendor', fn () => Limit::perMinute(30));
        RateLimiter::for('palmpay-vendor', fn () => Limit::perMinute(30));

        // Named (not bare numeric) so each of the three device-challenge
        // routes gets its own bucket — Laravel's bare `throttle:N,M` keys
        // purely on (route domain, IP) for guests, so two different bare-
        // throttled routes hit back-to-back by the same guest would
        // otherwise silently share one counter and block each other.
        RateLimiter::for('device-challenge-send', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        // Slightly above DeviceVerificationChallenge's own 5-wrong-attempt
        // lockout so that lockout (not this route throttle) is what a
        // brute-force attempt actually hits first — the throttle is a
        // secondary safety net against hammering many different challenges
        // from one IP, not meant to collide with legitimate lockout flow.
        RateLimiter::for('device-challenge-verify', fn ($request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('device-challenge-resend', fn ($request) => Limit::perMinute(3)->by($request->ip()));

        // Enforce strong password rules globally
        \Illuminate\Validation\Rules\Password::defaults(function () {
            return \Illuminate\Validation\Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Force HTTPS in production — protects session cookies and data in transit
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Set default pagination views
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        // Share recent transactions with header layout as notifications
        view()->composer('layouts.partials.header', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // If staff, get last 5 system transactions, else get user's last 5 transactions
                if ($user->isStaff()) {
                    $transactions = \App\Models\Transaction::latest()
                        ->with('user')
                        ->limit(5)
                        ->get();
                } else {
                    $transactions = \App\Models\Transaction::where('user_id', $user->id)
                        ->latest()
                        ->limit(5)
                        ->get();
                }
                
                $notifications = $transactions->map(function ($tx) use ($user) {
                    $title = $tx->description;
                    if ($user->isStaff() && $tx->user) {
                        $title = "{$tx->user->email}: {$tx->description}";
                    }
                    
                    return [
                        'id' => $tx->id,
                        'title' => $title,
                        'desc' => "Ref: {$tx->transaction_ref} • Amount: ₦" . number_format($tx->amount, 2) . " • Status: " . strtoupper($tx->status),
                        'time' => $tx->created_at->diffForHumans(),
                        'read' => false, // Start unread so user can clear them
                    ];
                });
                
                $view->with('headerNotifications', $notifications);
            } else {
                $view->with('headerNotifications', collect([]));
            }
        });

        // Sidebar nav badges: open support tickets for admins, pending
        // downline SIM requests for partners. Cheap counts, computed once
        // per request regardless of how many pages include the sidebar.
        view()->composer('layouts.partials.sidebar', function ($view) {
            $openTicketsBadge = 0;
            $pendingDownlineRequestsBadge = 0;
            $failedWebhooksBadge = 0;
            $sidebarUpline = null;

            if (auth()->check()) {
                $user = auth()->user();

                if ($user->role === 'super_admin') {
                    $openTicketsBadge = \App\Models\Ticket::where('status', 'open')->count();
                    $failedWebhooksBadge = \App\Models\WebhookLog::where('status', 'failed')->count();
                } elseif (in_array($user->role, ['partner', 'coordinator', 'regional_manager', 'business', 'business_agent'], true)) {
                    $pendingDownlineRequestsBadge = \App\Models\SimRequest::where('upline_id', $user->id)
                        ->where('status', 'pending')
                        ->count();
                }

                $sidebarUpline = $user->referrer;
            }

            $view->with('openTicketsBadge', $openTicketsBadge);
            $view->with('pendingDownlineRequestsBadge', $pendingDownlineRequestsBadge);
            $view->with('failedWebhooksBadge', $failedWebhooksBadge);
            $view->with('sidebarUpline', $sidebarUpline);
        });
    }
}
