<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Sim;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SIM lookup for the support desk — by unique identifier only (exact SIM
 * number, or a holder's exact email/phone), never a general browsable
 * list of every SIM and its status. No search means no query at all. No
 * swap, reassignment, or activation actions here either (those stay in
 * Admin\SimSwapController / smartsim\SimsController, which carry real
 * ownership/holder-chain consequences).
 */
class SimController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $sims = collect();

        if ($search !== '') {
            $sims = Sim::with('user')
                ->where(function ($q) use ($search) {
                    $q->where('number', $search)
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('email', $search)->orWhere('phone', $search);
                      });
                })
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get();
        }

        return view('support-desk.sims.index', compact('search', 'sims'));
    }

    public function show(Sim $sim): View
    {
        $sim->load([
            'user', 'partner', 'coordinator', 'regionalManager', 'business', 'businessAgent',
            'history' => fn ($q) => $q->with('actor')->latest(),
        ]);

        return view('support-desk.sims.show', compact('sim'));
    }
}
