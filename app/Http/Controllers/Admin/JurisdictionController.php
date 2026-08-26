<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin-authored Region -> State -> Senatorial District tree, used to
 * assign geographic coverage to Regional Manager (one Region each) and
 * Coordinator (one Senatorial District each) accounts. Entirely independent
 * of the referral hierarchy (RoleHierarchy/referred_by) — moving a State
 * between Regions is the "remap" mechanism: since a Coordinator's effective
 * jurisdiction is always resolved live through district->state->region
 * (see User::jurisdictionLabel()), a single State update cascades
 * automatically with no writes to any District/Coordinator record.
 */
class JurisdictionController extends Controller
{
    // -------------------------------------------------------------------
    // Regions
    // -------------------------------------------------------------------

    public function regions(): View
    {
        $regions = Region::withCount('states')->with('regionalManager')->orderBy('name')->paginate(15);

        $allManagers = User::where('role', 'regional_manager')
            ->with('regionAsManager:id,name,regional_manager_id')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->first_name . ' ' . $u->last_name),
                'region_id' => $u->regionAsManager?->id,
            ]);

        return view('admin.jurisdictions.regions', compact('regions', 'allManagers'));
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        $validated = $this->validateRegion($request);

        Region::create($validated);

        return back()->with('success', 'Region created successfully.');
    }

    public function updateRegion(Request $request, Region $region): RedirectResponse
    {
        $validated = $this->validateRegion($request, $region);

        $region->update($validated);

        return back()->with('success', 'Region updated successfully.');
    }

    public function destroyRegion(Region $region): RedirectResponse
    {
        if ($region->states()->exists()) {
            return back()->with('error', 'Cannot delete this region while it still has states assigned to it. Reassign or delete those states first.');
        }

        $region->delete();

        return back()->with('success', 'Region deleted successfully.');
    }

    private function validateRegion(Request $request, ?Region $region = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('regions', 'name')->ignore($region?->id)],
            'regional_manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'regional_manager'),
                Rule::unique('regions', 'regional_manager_id')->ignore($region?->id),
            ],
        ], [
            'regional_manager_id.exists' => 'Please select a valid user currently holding the Regional Manager role.',
            'regional_manager_id.unique' => 'This user is already assigned as Regional Manager for another region. Unassign them there first.',
        ]);
    }

    // -------------------------------------------------------------------
    // States
    // -------------------------------------------------------------------

    public function states(): View
    {
        $states = State::withCount('senatorialDistricts')->with('region')->orderBy('name')->paginate(15);
        $regions = Region::orderBy('name')->get(['id', 'name']);

        return view('admin.jurisdictions.states', compact('states', 'regions'));
    }

    public function storeState(Request $request): RedirectResponse
    {
        $validated = $this->validateState($request);

        State::create($validated);

        return back()->with('success', 'State created successfully.');
    }

    public function updateState(Request $request, State $state): RedirectResponse
    {
        $validated = $this->validateState($request, $state);

        $state->update($validated);

        return back()->with('success', 'State updated successfully.');
    }

    public function destroyState(State $state): RedirectResponse
    {
        if ($state->senatorialDistricts()->exists()) {
            return back()->with('error', 'Cannot delete this state while it still has senatorial districts assigned to it. Reassign or delete those districts first.');
        }

        $state->delete();

        return back()->with('success', 'State deleted successfully.');
    }

    private function validateState(Request $request, ?State $state = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('states', 'name')->ignore($state?->id)],
            'region_id' => ['nullable', 'exists:regions,id'],
        ]);
    }

    // -------------------------------------------------------------------
    // Senatorial Districts
    // -------------------------------------------------------------------

    public function districts(): View
    {
        $districts = SenatorialDistrict::with(['state.region', 'coordinator'])->orderBy('name')->paginate(15);
        $states = State::orderBy('name')->get(['id', 'name']);

        $allCoordinators = User::where('role', 'coordinator')
            ->with('districtAsCoordinator:id,name,coordinator_id')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->first_name . ' ' . $u->last_name),
                'district_id' => $u->districtAsCoordinator?->id,
            ]);

        return view('admin.jurisdictions.districts', compact('districts', 'states', 'allCoordinators'));
    }

    public function storeDistrict(Request $request): RedirectResponse
    {
        $validated = $this->validateDistrict($request);

        SenatorialDistrict::create($validated);

        return back()->with('success', 'Senatorial district created successfully.');
    }

    public function updateDistrict(Request $request, SenatorialDistrict $district): RedirectResponse
    {
        $validated = $this->validateDistrict($request, $district);

        $district->update($validated);

        return back()->with('success', 'Senatorial district updated successfully.');
    }

    public function destroyDistrict(SenatorialDistrict $district): RedirectResponse
    {
        $district->delete();

        return back()->with('success', 'Senatorial district deleted successfully.');
    }

    private function validateDistrict(Request $request, ?SenatorialDistrict $district = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('senatorial_districts', 'name')->ignore($district?->id)],
            'state_id' => ['required', 'exists:states,id'],
            'coordinator_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'coordinator'),
                Rule::unique('senatorial_districts', 'coordinator_id')->ignore($district?->id),
            ],
        ], [
            'coordinator_id.exists' => 'Please select a valid user currently holding the Coordinator role.',
            'coordinator_id.unique' => 'This user is already assigned as Coordinator for another senatorial district. Unassign them there first.',
        ]);
    }
}
