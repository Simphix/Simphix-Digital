# Platform wallets

Covers the `platform_wallets`/`platform_wallet_transactions` ledger — the
platform's own revenue pools, separate from any user's personal `wallets`
row — and how to hook a new revenue-generating service into it.

## Why a separate ledger from `wallets`/`transactions`

Every `wallets` row belongs to exactly one `user_id`, and both
`transactions.user_id` and `reports.user_id` are `NOT NULL` foreign keys.
A platform-level credit (the welfare wallet's per-activation top-up, a
service's profit margin) has no owning user by definition, so it
architecturally cannot be recorded in those tables. `platform_wallets` +
`platform_wallet_transactions` exist to hold exactly that: money the
platform itself earns, not any individual account.

`platform_wallets` has one row per `type` (a plain string slug, e.g.
`welfare`, `utilities_revenue`) — a new wallet kind needs no migration,
just a new type slug and a call into the ledger below.

## The two wallets today

- **`welfare`** (`PlatformWallet::TYPE_WELFARE`) — credited a fixed,
  admin-configurable amount (`/admin/platform-wallets/welfare-settings`,
  backed by `PlatformWelfareSettings`) every time a SIM activates. See
  `App\Listeners\CreditPlatformWelfareWallet`, which listens on the same
  `App\Events\SimActivated` event `App\Listeners\AwardCommissions` does.
- **`utilities_revenue`** (`PlatformWallet::TYPE_UTILITIES_REVENUE`) —
  credited a configurable margin % of the amount charged, per service call.
  The margin is set per service/role price via the existing
  `/admin/services` screen (`ServicePrice.margin_percent`, right next to
  the existing `commission` field) — it defaults to `0`, so a service earns
  the platform nothing until an admin explicitly sets a margin on it.

## Adding a new revenue-generating service — the whole checklist

1. Make sure the service already has a `ServicePrice` row (it needs one for
   its own price/commission anyway).
2. Set `margin_percent` on that `ServicePrice` via `/admin/services` (no
   code change — the field already exists on every service price).
3. In the controller, right after the service's own success point (after
   its `DB::commit()`, or right after its existing commission-credit block
   if it has one), add:

   ```php
   $priceRecord = \App\Models\ServicePrice::where('service_fields_id', $serviceField->id)
       ->where('user_type', $user->role)
       ->whereNull('user_id')
       ->first();

   if ($priceRecord && $priceRecord->margin_percent > 0) {
       $marginAmount = ($amountCharged * (float) $priceRecord->margin_percent) / 100;
       if ($marginAmount > 0) {
           app(\App\Services\PlatformWalletLedger::class)->credit(
               \App\Models\PlatformWallet::TYPE_UTILITIES_REVENUE,
               'Utilities Revenue Wallet',
               $marginAmount,
               '<new_source_slug>',
               "Platform margin on <service name> ({$transactionRef})",
               $transaction
           );
       }
   }
   ```

That's it — no migration, no new table, no new admin page. `PlatformWalletLedger::credit()`
handles the row-locking, the balance/total_credited update, and the ledger
row in one call (see `app/Services/PlatformWalletLedger.php`).

## `source` slug convention

Free-form string identifying what generated the credit, so the ledger is
readable without parsing `metadata`. Current values:

| Source                    | Wallet              | Where                                                    |
|----------------------------|----------------------|-----------------------------------------------------------|
| `sim_activation`           | `welfare`            | `App\Listeners\CreditPlatformWelfareWallet`               |
| `airtime_topup`             | `utilities_revenue`  | `App\Http\Controllers\Action\AirtimeController`            |
| `data_topup`                 | `utilities_revenue`  | `App\Http\Controllers\Action\SmeDataController`             |
| `verification_bvn`         | `utilities_revenue`  | `App\Http\Controllers\Verification\BvnverificationController` |
| `verification_nin`         | `utilities_revenue`  | `App\Http\Controllers\Verification\NINverificationController` |
| `verification_nin_phone`   | `utilities_revenue`  | `App\Http\Controllers\Verification\NINPhoneVerificationController` |
| `verification_nin_demo`    | `utilities_revenue`  | `App\Http\Controllers\Verification\NINDemoVerificationController` |
| `cashout`                    | any                    | a platform cash-out request debit (`PlatformWalletLedger::debit()`) |
| `cashout_refund`           | any                    | a rejected/declined cash-out request refunded back        |

Pick a new, descriptive slug for service #6+ — nothing else needs updating.

## Reading the ledger

`PlatformWallet::forType($type, $name)` gets-or-creates a wallet row.
`$wallet->transactions()` is the full ledger for that wallet, newest first
by default ordering; each row's `related_type`/`related_id` morph points
back to the SIM/Transaction that caused it, and `metadata` carries anything
else worth keeping.
