<?php

namespace App\Http\Controllers;

use App\Models\TravelPackage;
use App\Models\TravelRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TravelTourController extends Controller
{
    /**
     * Display travel packages catalog & booking portal.
     */
    public function index(Request $request): View
    {
        TravelPackage::seedDefaults();

        $activeCategory = $request->query('category', 'all');

        $packagesQuery = TravelPackage::active()->orderBy('sort_order')->orderBy('id');

        if (in_array($activeCategory, ['umrah', 'hajj', 'international'], true)) {
            $packagesQuery->where('category', $activeCategory);
        }

        $packages = $packagesQuery->get();

        $user = Auth::user();
        $userRequests = $user ? TravelRequest::where('user_id', $user->id)->latest()->take(5)->get() : collect();

        $selectedPackageId = $request->query('package');
        $selectedPackage = $selectedPackageId ? TravelPackage::find($selectedPackageId) : null;

        return view('travel.index', [
            'packages'          => $packages,
            'activeCategory'    => $activeCategory,
            'userRequests'      => $userRequests,
            'selectedPackage'   => $selectedPackage,
        ]);
    }

    /**
     * Store a new travel request from user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'phone'               => ['required', 'string', 'max:30'],
            'email'               => ['nullable', 'email', 'max:255'],
            'travel_package_id'   => ['nullable', 'exists:travel_packages,id'],
            'package_name'        => ['nullable', 'string', 'max:255'],
            'package_type'        => ['nullable', 'string', 'in:umrah,hajj,international,custom'],
            'number_of_travelers' => ['nullable', 'integer', 'min:1', 'max:100'],
            'departure_date'      => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:3000'],
            'referral_name'       => ['nullable', 'string', 'max:255'],
            'referral_phone'      => ['nullable', 'string', 'max:30'],
        ]);

        $travelPackage = null;
        if (!empty($validated['travel_package_id'])) {
            $travelPackage = TravelPackage::find($validated['travel_package_id']);
        }

        $packageName = $travelPackage ? $travelPackage->name : ($validated['package_name'] ?? 'Custom Travel Itinerary');
        $packageType = $travelPackage ? $travelPackage->category : ($validated['package_type'] ?? 'custom');

        $travelRequest = TravelRequest::create([
            'reference'           => TravelRequest::generateReference(),
            'user_id'             => Auth::id(),
            'travel_package_id'   => $travelPackage?->id,
            'name'                => trim($validated['name']),
            'phone'               => trim($validated['phone']),
            'email'               => !empty($validated['email']) ? trim($validated['email']) : null,
            'package_name'        => $packageName,
            'package_type'        => $packageType,
            'number_of_travelers' => (int) ($validated['number_of_travelers'] ?? 1),
            'departure_date'      => $validated['departure_date'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'referral_name'       => !empty($validated['referral_name']) ? trim($validated['referral_name']) : null,
            'referral_phone'      => !empty($validated['referral_phone']) ? trim($validated['referral_phone']) : null,
            'status'              => TravelRequest::STATUS_PENDING,
        ]);

        return redirect()->route('travel.tracking', ['ref' => $travelRequest->reference])
            ->with('success', "Your travel request has been submitted successfully! Your tracking reference code is {$travelRequest->reference}. Our specialized travel officer will contact you shortly.");
    }

    /**
     * Show tracking page where user can track requests by reference code,
     * or view all applications belonging to their account.
     */
    public function tracking(Request $request): View
    {
        $user = Auth::user();
        $queryRef = trim((string) $request->query('ref'));
        $searchPhone = trim((string) $request->query('phone'));

        $matchedRequest = null;
        if ($queryRef !== '') {
            $matchedRequest = TravelRequest::with('travelPackage')
                ->where('reference', $queryRef)
                ->first();
        } elseif ($searchPhone !== '') {
            $matchedRequest = TravelRequest::with('travelPackage')
                ->where('phone', 'like', "%{$searchPhone}%")
                ->latest()
                ->first();
        }

        $myRequests = collect();
        if ($user) {
            $myRequests = TravelRequest::with('travelPackage')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('travel.tracking', [
            'user'            => $user,
            'queryRef'        => $queryRef,
            'matchedRequest'  => $matchedRequest,
            'myRequests'      => $myRequests,
        ]);
    }

    /**
     * Directly view a single request tracking by reference.
     */
    public function showTracking(string $reference): View
    {
        $travelRequest = TravelRequest::with(['travelPackage', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('travel.tracking_detail', [
            'travelRequest' => $travelRequest,
        ]);
    }
}
