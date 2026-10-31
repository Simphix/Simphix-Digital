<?php

namespace App\Support;

use App\Models\User;

/**
 * Role gating for the SIM Services pages. Two tiers:
 * - Catalog roles (Regional Manager, Coordinator, Partner, Business
 *   [Enterprise Partner], Business Agent) browse the POS SIM / CCTV SIM
 *   device pages, the raw Inventory, and self-activate a SIM they hold.
 * - Everyone else (Personal, Agent, ...) only requests a SIM — activation is
 *   done for them by their partner/business agent/admin — and uses "My SIM"
 *   to track what they've requested/own. Agent lost catalog/distribution
 *   access entirely when it became a discount-only tier (see
 *   AgentUpgradeController) — it was never in MINE_ROLES either, so an
 *   agent effectively has no SIM Services access at all now.
 */
class SimAccess
{
    public const CATALOG_ROLES = ['regional_manager', 'coordinator', 'partner', 'business', 'business_agent', 'business_sales_manager'];

    public const MINE_ROLES = ['personal'];

    public static function canBrowseCatalog(User $user): bool
    {
        return in_array($user->role, self::CATALOG_ROLES, true);
    }

    public static function canViewMine(User $user): bool
    {
        return in_array($user->role, self::MINE_ROLES, true);
    }
}
