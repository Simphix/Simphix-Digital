<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::withCount(['fields', 'prices']);

        // Filter by Status
        if ($request->has('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        if ($request->sort == 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $services = $query->paginate(10);

        // Stats
        $totalServicesCount = Service::count();
        $activeServicesCount = Service::where('is_active', true)->count();
        $inactiveServicesCount = Service::where('is_active', false)->count();

        return view('admin.services.index', compact('services', 'totalServicesCount', 'activeServicesCount', 'inactiveServicesCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Store new image
            $path = $request->file('image')->store('profile_photos', 'public');
            $validated['image'] = config('app.url') . '/storage/' . $path;
        }

        Service::create($validated);

        return back()->with('success', 'Service created successfully.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                $oldPath = str_replace(config('app.url') . '/storage/', '', $service->image);
                Storage::disk('public')->delete($oldPath);
            }

            // Store new image
            $path = $request->file('image')->store('services', 'public');

            // Save full URL
            $validated['image'] = config('app.url') . '/storage/' . $path;
        }

        $service->update($validated);

        return back()->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        // Delete image from storage if exists
        if ($service->image) {
            $oldPath = str_replace(config('app.url') . '/storage/', '', $service->image);
            Storage::disk('public')->delete($oldPath);
        }

        $service->delete();
        return back()->with('success', 'Service deleted successfully.');
    }

    public function show(Service $service)
    {
        $fields = $service->fields()->paginate(10, ['*'], 'fields_page')->appends(request()->query());
        $prices = $service->prices()->with(['serviceField', 'user'])->paginate(10, ['*'], 'prices_page')->appends(request()->query());

        return view('admin.services.show', compact('service', 'fields', 'prices'));
    }

    // Field Management
    public function storeField(Request $request, Service $service)
    {
        // Custom Check for Unique Field Code
        if (ServiceField::where('field_code', $request->field_code)->exists()) {
            $lastField = ServiceField::latest('id')->first();
            $lastCode = $lastField ? $lastField->field_code : 'None';
            return back()->with('error', "Field code already exists. The last field code used was: {$lastCode}")->withInput();
        }

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_code' => 'required|string|max:255', // Removed unique check from database level to handle manually? Actually better to double check but the prompt asked for "if exists return error message..."
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $service->fields()->create($validated);

        return back()->with('success', 'Service field added successfully.');
    }

    public function updateField(Request $request, ServiceField $field)
    {
        // Check uniqueness if changed
        if ($request->field_code !== $field->field_code && ServiceField::where('field_code', $request->field_code)->exists()) {
             $lastField = ServiceField::latest('id')->first();
             $lastCode = $lastField ? $lastField->field_code : 'None';
             return back()->with('error', "Field code already exists. The last field code used was: {$lastCode}")->withInput();
        }

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_code' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $field->update($validated);

        return back()->with('success', 'Service field updated successfully.');
    }

    public function destroyField(ServiceField $field)
    {
        try {
            $field->delete();
            return back()->with('success', 'Service field deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Check for integrity constraint violation (SQLState 23000)
            if ($e->getCode() === "23000") {
                return back()->with('error', 'Cannot delete this field because it is linked to existing user records or transactions. Please deactivate it instead to preserve data integrity.');
            }
            // Rethrow other errors or handle generic failure
            return back()->with('error', 'An error occurred while attempting to delete the field: ' . $e->getMessage());
        }
    }

    // Price Management
    public function storePrice(Request $request, Service $service)
    {
        $priceTarget = $request->input('price_target'); // 'role' or 'user'

        $rules = [
            'service_fields_id' => 'nullable|exists:service_fields,id',
            'price' => 'required|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'margin_percent' => 'nullable|numeric|min:0',
        ];

        if ($priceTarget === 'user') {
            $rules['user_email'] = 'required|email|exists:users,email';
        } else {
            $rules['user_type'] = 'required|in:personal,agent,partner,business,staff,checker,super_admin,regional_manager,coordinator';
        }

        $validated = $request->validate($rules);

        $userId = null;
        $userType = null;

        if ($priceTarget === 'user') {
            $user = User::where('email', $validated['user_email'])->first();
            if (!$user) {
                return back()->with('error', 'User with the specified email does not exist.')->withInput();
            }
            $userId = $user->id;
        } else {
            $userType = $validated['user_type'];
        }

        $serviceFieldsId = $validated['service_fields_id'] ?? null;

        if ($serviceFieldsId) {
            $fieldBelongs = ServiceField::where('id', $serviceFieldsId)->where('service_id', $service->id)->exists();
            if (!$fieldBelongs) {
                return back()->with('error', 'The selected variant is invalid for this service.')->withInput();
            }
        }

        // Check for duplicate pricing configuration
        $exists = ServicePrice::where('service_id', $service->id)
            ->where('service_fields_id', $serviceFieldsId)
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A price configuration already exists for this target and field combination.')->withInput();
        }

        $service->prices()->create([
            'service_fields_id' => $serviceFieldsId,
            'user_id' => $userId,
            'user_type' => $userType,
            'price' => $validated['price'],
            'commission' => $validated['commission'] ?? 0.00,
            'margin_percent' => $validated['margin_percent'] ?? 0.00,
        ]);

        return back()->with('success', 'Price configuration added successfully.');
    }

    public function updatePrice(Request $request, ServicePrice $price)
    {
        $priceTarget = $request->input('price_target'); // 'role' or 'user'

        $rules = [
            'price' => 'required|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'margin_percent' => 'nullable|numeric|min:0',
        ];

        if ($priceTarget === 'user') {
            $rules['user_email'] = 'required|email|exists:users,email';
        } else {
            $rules['user_type'] = 'required|in:personal,agent,partner,business,staff,checker,super_admin,regional_manager,coordinator';
        }

        $validated = $request->validate($rules);

        $userId = null;
        $userType = null;

        if ($priceTarget === 'user') {
            $user = User::where('email', $validated['user_email'])->first();
            if (!$user) {
                return back()->with('error', 'User with the specified email does not exist.')->withInput();
            }
            $userId = $user->id;
        } else {
            $userType = $validated['user_type'];
        }

        // Check for duplicate pricing configuration (excluding the current price item)
        $exists = ServicePrice::where('service_id', $price->service_id)
            ->where('service_fields_id', $price->service_fields_id)
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('id', '!=', $price->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A price configuration already exists for this target and field combination.')->withInput();
        }

        $price->update([
            'user_id' => $userId,
            'user_type' => $userType,
            'price' => $validated['price'],
            'commission' => $validated['commission'] ?? 0.00,
            'margin_percent' => $validated['margin_percent'] ?? 0.00,
        ]);

        return back()->with('success', 'Price configuration updated successfully.');
    }

    public function destroyPrice(ServicePrice $price)
    {
        $price->delete();
        return back()->with('success', 'Price configuration deleted successfully.');
    }
}
