<?php

namespace App\Support;

use App\Models\LeaderboardSettings;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;

/**
 * Personal/team SIM inventory, downline-onboarding, and activation-count
 * metrics for an arbitrary target user — shared by DownlineDetailController
 * (upline viewing their own downline) and Admin\ManageController (admin
 * viewing any user).
 */
class DownlineMetrics
{
    private const HOLDER_FK = [
        'regional_manager' => 'regional_manager_id',
        'coordinator'      => 'coordinator_id',
        'partner'          => 'partner_id',
        'business'         => 'business_id',
        'business_agent'   => 'business_agent_id',
    ];

    private const ASSIGNED_STATUS = [
        'regional_manager' => SimStatus::ASSIGNED_TO_RM,
        'coordinator'      => SimStatus::ASSIGNED_TO_COORDINATOR,
        'partner'          => SimStatus::ASSIGNED_TO_PARTNER,
        'business'         => SimStatus::ASSIGNED_TO_BUSINESS,
        'business_agent'   => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
    ];

    public static function forUser(User $user): array
    {
        $isCatalogRole = array_key_exists($user->role, self::HOLDER_FK);
        $fk = self::HOLDER_FK[$user->role] ?? 'user_id';

        $personalQuery = match (true) {
            $user->role === 'partner' => Sim::where('partner_id', $user->id)->whereColumn('user_id', 'partner_id'),
            $user->role === 'business_agent' => Sim::where('business_agent_id', $user->id)->whereColumn('user_id', 'business_agent_id'),
            $user->role === 'business' => Sim::where('business_id', $user->id)->whereNull('business_agent_id'),
            $isCatalogRole => Sim::where($fk, $user->id)->where('status', self::ASSIGNED_STATUS[$user->role]),
            default => Sim::where('user_id', $user->id),
        };
        $personalTotal = (clone $personalQuery)->count();
        $personalActivated = (clone $personalQuery)->where('status', SimStatus::ACTIVATED)->count();
        $personalConversion = $personalTotal > 0 ? round($personalActivated / $personalTotal * 100) : 0;

        $teamTotal = $teamActivated = $teamConversion = null;
        if ($isCatalogRole) {
            $teamQuery = Sim::where($fk, $user->id);
            $teamTotal = (clone $teamQuery)->count();
            $teamActivated = (clone $teamQuery)->where('status', SimStatus::ACTIVATED)->count();
            $teamConversion = $teamTotal > 0 ? round($teamActivated / $teamTotal * 100) : 0;
        }

        $nextRole = RoleHierarchy::nextRole($user->role);
        $totalDownline = $user->referrals()->count();
        $roleBreakdown = $user->referrals()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        $activationScopeColumn = $isCatalogRole ? "sims.$fk" : 'sims.user_id';
        $totalActivations = SimHistory::query()
            ->join('sims', 'sim_histories.sim_id', '=', 'sims.id')
            ->where('sim_histories.to_status', SimStatus::ACTIVATED)
            ->where($activationScopeColumn, $user->id)
            ->count();

        // Ensure totalActivations reflects at least the currently activated SIMs in scope
        $totalActivations = max($totalActivations, $teamActivated ?? $personalActivated);

        $settings = LeaderboardSettings::current();
        $period = $settings->periodBounds();
        $periodActivations = SimHistory::query()
            ->join('sims', 'sim_histories.sim_id', '=', 'sims.id')
            ->where('sim_histories.to_status', SimStatus::ACTIVATED)
            ->where($activationScopeColumn, $user->id)
            ->whereBetween('sim_histories.created_at', [$period['start'], $period['end']])
            ->count();

        return compact(
            'isCatalogRole',
            'personalTotal',
            'personalActivated',
            'personalConversion',
            'teamTotal',
            'teamActivated',
            'teamConversion',
            'nextRole',
            'totalDownline',
            'roleBreakdown',
            'totalActivations',
            'periodActivations'
        );
    }
}
