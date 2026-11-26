<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\SimSubscription;
use App\Models\User;
use App\Services\SimSubscriptionService;
use App\Support\SimStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SimSubscriptionController extends Controller
{
    public function __construct(
        protected SimSubscriptionService $subscriptionService
    ) {}

    /**
     * Display a comprehensive page of SIM subscriptions across the platform.
     */
    public function index(Request $request): View
    {
        $baseQuery = Sim::query()
            ->with(['partner.wallet', 'user', 'customer', 'latestSubscription'])
            ->where(function ($q) {
                $q->where('status', SimStatus::ACTIVATED)
                    ->orWhereNotNull('subscription_expires_at')
                    ->orWhereHas('subscriptions');
            });

        // Top KPI counts across all SIM subscriptions
        $now = now();
        $totalSubscriptions = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)
            ->where('subscription_expires_at', '>', $now->copy()->addDays(30))
            ->count();
        $expiringSoonCount = (clone $baseQuery)
            ->whereBetween('subscription_expires_at', [$now, $now->copy()->addDays(30)])
            ->count();
        $expiredCount = (clone $baseQuery)
            ->where('subscription_expires_at', '<=', $now)
            ->count();

        // Filters
        $query = clone $baseQuery;

        if ($request->filled('status')) {
            match ($request->status) {
                'active'        => $query->where('subscription_expires_at', '>', $now->copy()->addDays(30)),
                'expiring_soon' => $query->whereBetween('subscription_expires_at', [$now, $now->copy()->addDays(30)]),
                'expired'       => $query->where('subscription_expires_at', '<=', $now),
                default         => null,
            };
        }

        if ($request->filled('provider')) {
            $query->where('provider', strtolower($request->provider));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sims = $query->orderByRaw('CASE WHEN subscription_expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('subscription_expires_at', 'asc')
            ->paginate(20)
            ->withQueryString();

        $categories = ServiceField::whereHas('service', fn ($q) => $q->where('name', 'simcard'))
            ->pluck('field_name');

        return view('admin.sim-subscriptions.index', compact(
            'sims',
            'totalSubscriptions',
            'activeCount',
            'expiringSoonCount',
            'expiredCount',
            'categories'
        ));
    }

    /**
     * Admin reactivation handler:
     * - Option A: Charge partner wallet (main or bonus)
     * - Option B: Admin manual approval
     */
    public function reactivate(Request $request, Sim $sim): RedirectResponse
    {
        $validated = $request->validate([
            'payment_option' => 'required|in:partner_wallet,admin_manual',
            'wallet_source'  => 'nullable|required_if:payment_option,partner_wallet|in:main,bonus',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $admin = Auth::user();

            if ($validated['payment_option'] === 'partner_wallet') {
                $partner = $sim->partner ?? $sim->user;
                if (! $partner) {
                    return back()->with('error', 'No partner or user is associated with this SIM to charge.');
                }

                $this->subscriptionService->reactivateViaWallet($sim, $partner, $validated['wallet_source'], $admin);

                return back()->with('success', "SIM {$sim->number} subscription reactivated successfully for 360 days by charging {$partner->name}'s " . ($validated['wallet_source'] === 'bonus' ? 'Commission' : 'Main') . " balance.");
            }

            // Admin manual override
            $now = now();
            $newExpiresAt = ($sim->subscription_expires_at && $sim->subscription_expires_at->isFuture())
                ? $sim->subscription_expires_at->copy()->addDays(Sim::SUBSCRIPTION_LIFETIME_DAYS)
                : $now->copy()->addDays(Sim::SUBSCRIPTION_LIFETIME_DAYS);

            $sim->update([
                'status'                  => SimStatus::ACTIVATED,
                'subscription_expires_at' => $newExpiresAt,
            ]);

            SimSubscription::create([
                'sim_id'            => $sim->id,
                'partner_id'        => $sim->partner_id,
                'starts_at'         => $now,
                'expires_at'        => $newExpiresAt,
                'fee'               => 0.00,
                'payment_method'    => 'admin_manual',
                'status'            => 'active',
                'paid_at'           => $now,
                'payment_reference' => 'ADMIN-MANUAL-' . time(),
                'notes'             => $validated['notes'] ?: "Reactivated manually by Admin {$admin->name}",
            ]);

            \App\Models\SimHistory::create([
                'sim_id'      => $sim->id,
                'from_status' => $sim->status,
                'to_status'   => SimStatus::ACTIVATED,
                'user_id'     => $admin->id,
                'notes'       => "Admin manual reactivation for 360 days until " . $newExpiresAt->format('M d, Y') . ($validated['notes'] ? " ({$validated['notes']})" : ''),
            ]);

            return back()->with('success', "SIM {$sim->number} subscription reactivated manually for 360 days until " . $newExpiresAt->format('M d, Y') . '.');
        } catch (\Exception $e) {
            Log::error('Admin SIM reactivation failed.', [
                'sim_id'    => $sim->id,
                'admin_id'  => Auth::id(),
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Option B for Admin: Generate a dedicated one-time funding account for reactivation.
     */
    public function generateFundingAccount(Request $request, Sim $sim): JsonResponse|RedirectResponse
    {
        try {
            $partner = $sim->partner ?? $sim->user;
            $subscription = $this->subscriptionService->generateFundingAccount($sim, $partner, Auth::user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success'        => true,
                    'account_number' => $subscription->account_number,
                    'account_name'   => $subscription->account_name,
                    'bank_name'      => $subscription->bank_name,
                    'amount'         => (float) $subscription->fee,
                    'formatted_fee'  => number_format((float) $subscription->fee, 2),
                    'reference'      => $subscription->payment_reference,
                    'subscription_id'=> $subscription->id,
                    'message'        => 'One-time funding account generated successfully.',
                ]);
            }

            return back()->with('generated_funding_account', [
                'sim_number'     => $sim->number,
                'account_number' => $subscription->account_number,
                'account_name'   => $subscription->account_name,
                'bank_name'      => $subscription->bank_name,
                'amount'         => (float) $subscription->fee,
                'formatted_fee'  => number_format((float) $subscription->fee, 2),
                'reference'      => $subscription->payment_reference,
                'subscription_id'=> $subscription->id,
            ])->with('success', "One-time reactivation account {$subscription->account_number} ({$subscription->bank_name}) generated for SIM {$sim->number}.");
        } catch (\Exception $e) {
            Log::error('Failed to generate reactivation funding account.', [
                'sim_id'    => $sim->id,
                'exception' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin manually confirms payment received for a generated one-time account.
     */
    public function confirmFundingPayment(Request $request, SimSubscription $subscription): RedirectResponse
    {
        try {
            $this->subscriptionService->confirmReactivationPayment($subscription, null, null, Auth::user());

            return back()->with('success', "Payment confirmed! SIM {$subscription->sim->number} subscription reactivated for 360 days.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export SIM subscriptions to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $now = now();
        $query = Sim::query()
            ->with(['partner', 'user', 'customer'])
            ->where(function ($q) {
                $q->where('status', SimStatus::ACTIVATED)
                    ->orWhereNotNull('subscription_expires_at');
            });

        if ($request->filled('status')) {
            match ($request->status) {
                'active'        => $query->where('subscription_expires_at', '>', $now->copy()->addDays(30)),
                'expiring_soon' => $query->whereBetween('subscription_expires_at', [$now, $now->copy()->addDays(30)]),
                'expired'       => $query->where('subscription_expires_at', '<=', $now),
                default         => null,
            };
        }

        if ($request->filled('provider')) {
            $query->where('provider', strtolower($request->provider));
        }

        $filename = 'sim_subscriptions_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'SIM Number',
                'Network',
                'Category',
                'Partner Name',
                'Partner Email',
                'Customer Name',
                'Customer Phone',
                'Activated At',
                'Subscription Expires At',
                'Days Remaining',
                'Status',
            ]);

            $query->chunk(500, function ($sims) use ($handle) {
                foreach ($sims as $sim) {
                    $partnerName = $sim->partner ? trim($sim->partner->first_name . ' ' . $sim->partner->last_name) : '—';
                    $customerName = $sim->customer?->full_name ?? '—';
                    $customerPhone = $sim->customer?->phone_number ?? '—';
                    $daysRemaining = $sim->daysRemaining();

                    fputcsv($handle, [
                        $sim->number,
                        strtoupper($sim->provider),
                        $sim->category,
                        $partnerName,
                        $sim->partner?->email ?? '—',
                        $customerName,
                        $customerPhone,
                        $sim->activated_at?->format('Y-m-d H:i') ?? '—',
                        $sim->subscription_expires_at?->format('Y-m-d H:i') ?? '—',
                        $daysRemaining !== null ? $daysRemaining : '—',
                        $sim->subscriptionStatusLabel(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
