# Nightly activated-SIMs export cron job

Covers scheduling `php artisan sims:export-activated`
(`app/Console/Commands/ExportActivatedSims.php`) so a CSV of activated
SIM numbers goes out by email automatically every night.

## What the command actually does

Reads the single settings row an admin controls from **Admin → SIMs
Operations** (`sim_export_settings`, via `App\Models\SimExportSettings`):

- If the export is turned **off**, the command exits immediately and sends
  nothing.
- If it's **on** but no recipient email is set, it logs a warning and exits.
- By default, it exports all SIMs activated during the **previous full calendar day**
  (`00:00:00` to `23:59:59` in `Africa/Lagos` / WAT). This means whether the cron
  runs at midnight, 1:00 AM, or 3:00 AM overnight, it consistently reports on the
  entire completed day without missing or duplicating hours.
- It uses **`SimHistory` activation events** (`to_status = activated`) as the authoritative
  source of truth, ensuring that older SIMs updated recently (e.g. via data top-ups,
  plan edits, or reassignments) are never falsely included.
- It builds the CSV (`App\Services\ActivatedSimsExportService`) and **sends the email synchronously**,
  right there in the command — unlike the bulk-bonus/notification commands documented
  elsewhere in this directory, there is no queue involved here, so no
  separate queue-worker setup is needed for this one.

### Command Flags

```bash
# Default: previous calendar day (00:00:00 to 23:59:59 WAT)
php artisan sims:export-activated

# Specific calendar day
php artisan sims:export-activated --date=2026-10-26

# Rolling past N hours (e.g. past 24 hours from right now)
php artisan sims:export-activated --hours=24

# All-time: export every activated SIM currently in the system
php artisan sims:export-activated --all
```

This follows the same **direct cron entry per command** convention already
established in `docs/cron-jobs.md` and `docs/push-notification-scheduling.md`,
not Laravel's `schedule:run`/scheduler mechanism — that mechanism isn't used
anywhere else in this app (`bootstrap/app.php` has no `->withSchedule()`
call, and `routes/console.php` only defines the `inspire` command), so a
second, different scheduling mechanism isn't introduced here.

Because the on/off check happens inside the command itself, it is always
safe to have this cron entry running every night regardless of whether the
export is currently turned on — a run while it's off just logs one line and
exits.

---

## The cron entry

Recommended: once a night, low-traffic — e.g. midnight server time:

```
0 0 * * *
```

### On cPanel

1. Find your PHP binary path the same way `docs/cron-jobs.md` Part 1 step 1
   describes (MultiPHP Manager → your app's PHP version → matching
   `ea-phpXX` CLI binary).
2. **cPanel → Advanced → Cron Jobs → Add New Cron Job**, pick **Once Per
   Day** (or "Common Settings" → *Once a day*, adjusting the hour to `0` for
   midnight), or fill the fields manually to match `0 0 * * *`.
3. Command (adjust the PHP path and app path — same two values as every
   other cron entry in this app, `CPANEL_PHP_BIN`/`CPANEL_DEPLOY_PATH` in
   `.github/workflows/deploy-cpanel.yml` if you want to double-check them):
   ```bash
   /opt/cpanel/ea-php82/root/usr/bin/php /home/yourcpaneluser/public_html/artisan sims:export-activated >> /home/yourcpaneluser/public_html/storage/logs/sim-export.log 2>&1
   ```
4. Save.

### On a VPS (Ubuntu/Debian-style)

```bash
crontab -e
```

Add:
```
0 0 * * * cd /var/www/smartsim && php artisan sims:export-activated >> storage/logs/sim-export.log 2>&1
```

---

## Turning it on / changing the recipient

No file or `.env` edit needed — both are set from **Admin → SIMs
Operations**, in the "Nightly Export Email" card above the activation
stats: toggle it on/off and set the recipient email, then **Save**. The next
cron run picks up the change immediately, nothing to restart.

---

## Verifying it worked

```bash
# Confirm the command ran and what it decided (skipped / sent / failed)
tail -20 storage/logs/sim-export.log

# Confirm the current settings
php artisan tinker --execute="print_r(App\Models\SimExportSettings::current()->toArray());"

# Force a run right now regardless of the cron schedule
php artisan sims:export-activated
```

A healthy "on" run's log looks like:
```
Sent 1234 activated SIM number(s) to ops@example.com.
```
A healthy "off" run's log looks like:
```
Nightly activated-SIMs export is turned off (Admin > SIMs Operations) — skipping.
```

If it's on but nothing arrives, check `storage/logs/laravel.log` for a
`sims:export-activated failed to send:` line — that's the mail
transport/`.env` `MAIL_*` config to check first.
