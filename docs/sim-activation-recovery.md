# Recovering SIMs stuck by the activation-transaction-rollback bug

Covers `php artisan sim:revert-stuck-to-partner`
(`app/Console/Commands/RevertStuckSimsToPartner.php`) — the recovery
procedure for SIMs left stuck mid-handoff by a bug fixed alongside this
command. If you're seeing SIMs that a partner delegated to a downline
customer, that never activate, that customer's wallet was never debited
for, and there's no `SimHistory` entry showing an activation attempt —
this is that bug, and this is how to clean it up.

## What the bug was

`SimsController::activateForDownlineUser()` ran three things inside one
database transaction: debit the customer's wallet, flip the SIM to
`ACTIVATED`, and fire the entire commission/welfare payout chain (four
`SimActivated` listeners — `AwardCommissions`, `AwardBusinessAgentCommissions`,
`CreditPlatformWelfareWallet`, `GrantActivationBonus`). Any exception
anywhere in that chain — a misconfigured `ServicePrice`, a locked row, a
`PlatformWallet` row-creation race — rolled back *everything*: the
debit, the `ACTIVATED` status, and the `SimHistory` row all vanished.
Every `catch` on that path swallowed the exception into a one-time flash
message with no logging, so the failure left no trace anywhere.

Critically, the **delegation** step that puts a SIM in this state
(`SimAssignmentService::assignDown()`, invoked when a partner hands a SIM
to a specific downline customer) is its own, already-committed
transaction — separate from the activation attempt. So when activation
later fails and rolls back, the SIM is left sitting delegated to that
customer (`user_id` set to them, `status` still
`ASSIGNED_TO_PARTNER`/`ASSIGNED_TO_BUSINESS_AGENT`), with **no debit
ever having happened**, and no way back to the partner's own inventory
through the normal UI.

The root cause (non-atomic side effects + a missing row lock) is fixed —
see the `SimActivated` event now being dispatched via `DB::afterCommit()`
in `SimAssignmentService::activate()`, and the row-locking added to
`activateForDownlineUser()`. This doc is only about cleaning up SIMs that
got stuck **before** that fix was deployed.

## Identifying stuck SIMs

A SIM matches this bug if:
- `status` is `ASSIGNED_TO_PARTNER` or `ASSIGNED_TO_BUSINESS_AGENT`, **and**
- `user_id` is set to a downline customer, not the holder themselves
  (i.e. `user_id !== partner_id` / `user_id !== business_agent_id`), **and**
- no `Transaction` row exists recording a debit for that customer
  referencing the SIM's number.

The command below finds these automatically — you don't need to query
for them by hand, but the shape above is what it's looking for if you
want to sanity-check its output.

## Running the recovery

Always dry-run first:

```bash
php artisan sim:revert-stuck-to-partner --dry-run
```

This prints a table of every matching SIM and what it would do to it —
**revert** (safe to proceed) or **skip** (a debit referencing this
number already exists; needs manual review, not this command). Nothing
is written to the database in dry-run mode.

Once you've reviewed the list, run it for real:

```bash
php artisan sim:revert-stuck-to-partner
```

It asks for a yes/no confirmation before writing anything. In a
non-interactive shell (e.g. scripted from a deploy step), skip the
prompt with `--force`:

```bash
php artisan sim:revert-stuck-to-partner --force
```

To limit it to specific SIMs you've already identified (e.g. from a
support ticket) instead of every match in the database:

```bash
php artisan sim:revert-stuck-to-partner --sim=52 --sim=61 --dry-run
php artisan sim:revert-stuck-to-partner --sim=52 --sim=61 --force
```

## What it actually does

For each SIM it reverts, `SimAssignmentService::revertToHolder()` sets
`user_id` back to the holder's own id (`partner_id` for
`ASSIGNED_TO_PARTNER`, `business_agent_id` for
`ASSIGNED_TO_BUSINESS_AGENT`) — this codebase's existing "still
self-held, not yet delegated to anyone" marker (the same one
`SimAssignmentService::assignDown()` and `adminAssign()` use). `status`
is left untouched. A `SimHistory` row is written recording the reversal,
with `actor_id` set to the first `super_admin` user found, so there's a
permanent audit trail distinguishing this from a normal delegation.

The SIM then shows back up in the partner's/agent's own inventory,
exactly as if it had never been delegated to that customer — they can
re-delegate and activate it again normally, now through the fixed code
path.

## What it deliberately does *not* do

- **It never touches a SIM where a matching debit exists.** If the
  customer genuinely was charged (a legitimate activation, or a partial
  failure that happened to still leave a debit row behind), that SIM is
  skipped and printed separately so a human decides what's fair — refund,
  manually activate, or something else — rather than the command
  guessing.
- **It doesn't refund anyone.** There's nothing to refund for a SIM this
  command actually reverts, by definition (that's exactly what it checks
  before reverting). If a skipped SIM turns out to need a refund, handle
  that through the normal wallet-adjustment path, not this command.
- **It doesn't activate anything.** Reverted SIMs go back to the
  partner's inventory in an unactivated state — the partner still needs
  to re-delegate and activate them normally afterward.

## Verifying it worked

```bash
php artisan tinker --execute="
\$sim = App\Models\Sim::find(SIM_ID);
echo 'status='.\$sim->status.' user_id='.\$sim->user_id.' partner_id='.\$sim->partner_id.PHP_EOL;
App\Models\SimHistory::where('sim_id', SIM_ID)->latest('id')->first()?->note;
"
```

`user_id` should now equal `partner_id` (or `business_agent_id`), status
unchanged, and the latest `SimHistory` note should reference this
command. Confirm the customer's wallet balance is unchanged from before
the run — it should be, since this command never touches `wallets`.

## Re-running safely

The command is idempotent: once a SIM is reverted, `user_id` equals its
holder's id, so it no longer matches the "mid-handoff" filter and won't
show up again on a subsequent `--dry-run`/run. Safe to re-run on a
schedule or after every deploy if you want an ongoing safety net,
though after the underlying fix is deployed there should be no new
matches going forward.
