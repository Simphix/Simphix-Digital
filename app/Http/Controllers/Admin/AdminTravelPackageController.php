<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTravelPackageController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'super_admin', 403, 'Unauthorized access to Travel & Tours administrative packages.');
    }

    /**
     * Display listing of all travel packages with category filters.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        TravelPackage::seedDefaults();

        $query = TravelPackage::query()->withCount('requests');

        if ($request->filled('category') && $request->query('category') !== 'all') {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tagline', 'like', "%{$search}%")
                  ->orWhere('price_range', 'like', "%{$search}%")
                  ->orWhere('duration', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('sort_order')->orderBy('id')->get();

        $stats = [
            'total'         => TravelPackage::count(),
            'active'        => TravelPackage::where('is_active', true)->count(),
            'umrah'         => TravelPackage::where('category', 'umrah')->count(),
            'hajj'          => TravelPackage::where('category', 'hajj')->count(),
            'international' => TravelPackage::where('category', 'international')->count(),
        ];

        return view('admin.travel-requests.packages.index', [
            'packages' => $packages,
            'stats'    => $stats,
        ]);
    }

    /**
     * Show form to create a new travel package.
     */
    public function create(): View
    {
        $this->authorizeAdmin();

        $package = new TravelPackage([
            'category'    => 'umrah',
            'is_active'   => true,
            'is_featured' => true,
            'sort_order'  => (TravelPackage::max('sort_order') ?? 0) + 1,
        ]);

        return view('admin.travel-requests.packages.form', [
            'package' => $package,
            'isEdit'  => false,
        ]);
    }

    /**
     * Store a newly created travel package.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatePackage($request);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (TravelPackage::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;
        $validated['highlights'] = $this->parseHighlights($request->input('highlights'));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $package = TravelPackage::create($validated);

        return redirect()->route('admin.travel-requests.packages.index')
            ->with('success', "Travel package \"{$package->name}\" created successfully.");
    }

    /**
     * Show form to edit an existing travel package.
     */
    public function edit(TravelPackage $travelPackage): View
    {
        $this->authorizeAdmin();

        return view('admin.travel-requests.packages.form', [
            'package' => $travelPackage,
            'isEdit'  => true,
        ]);
    }

    /**
     * Update the specified travel package.
     */
    public function update(Request $request, TravelPackage $travelPackage): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatePackage($request);

        if ($travelPackage->name !== $validated['name']) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (TravelPackage::where('slug', $slug)->where('id', '!=', $travelPackage->id)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['highlights'] = $this->parseHighlights($request->input('highlights'));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $travelPackage->update($validated);

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))
                ->with('success', "Travel package \"{$travelPackage->name}\" updated successfully.");
        }

        return redirect()->route('admin.travel-requests.packages.index')
            ->with('success', "Travel package \"{$travelPackage->name}\" updated successfully.");
    }

    /**
     * Toggle active status of a travel package.
     */
    public function toggleStatus(TravelPackage $travelPackage): RedirectResponse
    {
        $this->authorizeAdmin();

        $travelPackage->update([
            'is_active' => !$travelPackage->is_active,
        ]);

        $statusLabel = $travelPackage->is_active ? 'activated and is visible to customers' : 'deactivated and hidden from customers';

        return back()->with('success', "Package \"{$travelPackage->name}\" has been {$statusLabel}.");
    }

    /**
     * Delete a travel package.
     */
    public function destroy(TravelPackage $travelPackage): RedirectResponse
    {
        $this->authorizeAdmin();

        $name = $travelPackage->name;
        $travelPackage->delete();

        return redirect()->route('admin.travel-requests.packages.index')
            ->with('success', "Package \"{$name}\" has been deleted.");
    }

    /**
     * Validate request payload for package creation/update.
     */
    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'in:' . implode(',', array_keys(TravelPackage::CATEGORIES))],
            'tagline'     => ['nullable', 'string', 'max:255'],
            'price_range' => ['nullable', 'string', 'max:255'],
            'duration'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'highlights'  => ['nullable'],
            'is_active'   => ['nullable'],
            'is_featured' => ['nullable'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    /**
     * Parse highlights/inclusions from textarea or array into array of clean strings.
     */
    private function parseHighlights(mixed $input): array
    {
        if (empty($input)) {
            return [];
        }

        if (is_array($input)) {
            return array_values(array_filter(array_map('trim', $input)));
        }

        if (is_string($input)) {
            $lines = preg_split('/[\r\n]+/', $input);
            return array_values(array_filter(array_map('trim', $lines)));
        }

        return [];
    }
}
