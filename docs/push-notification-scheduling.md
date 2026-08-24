# Scheduled push notification cron job

Covers scheduling `php artisan notifications:dispatch-due`
(`app/Console/Commands/DispatchScheduledNotifications.php`) so notifications
an admin schedules for later actually go out at the right time.

## What the command actually does

Finds every `admin_notifications` row with `status = scheduled` and
`scheduled_at <= now()`, marks it `sending`, and **queues** one
`SendAdminNotificationJob` per notification. It does not send anything
itself — the job lands on the `jobs` table (`QUEUE_CONNECTION=database` in
`.env`) and only runs once a **queue worker** picks it up, same as every
other queued job in this app (see `docs/cron-jobs.md` for the full
worker-setup walkthrough — reuse whatever's already running there; you do
not need a second worker just for this).

"Send Now" (as opposed to a scheduled notification) skips this command
entirely — it dispatches `SendAdminNotificationJob` immediately from the
`admin.notifications.store` request, so it's only genuinely-scheduled
notifications that depend on this cron entry.

This follows the same **direct cron entry per command** convention already
established in `docs/cron-jobs.md`, not Laravel's `schedule:run`/scheduler
mechanism — that mechanism isn't used anywhere else in this app
(`bootstrap/app.php` has no `->withSchedule()` call), so a second, different
scheduling mechanism isn't introduced here.

---

## The cron entry

Recommended: every minute, so a scheduled notification fires close to its
chosen time rather than being batched with the next slow interval.

```
* * * * *
```

### On cPanel

1. Find your PHP binary path the same way `docs/cron-jobs.md` Part 1 step 1
   describes (MultiPHP Manager → your app's PHP version → matching
   `ea-phpXX` CLI binary).
2. **cPanel → Advanced → Cron Jobs → Add New Cron Job**, set to run every
   minute (`* * * * *`).
3. Command:
   ```bash
   /opt/cpanel/ea-php82/root/usr/bin/php /home/yourcpaneluser/public_html/artisan notifications:dispatch-due >> /home/yourcpaneluser/public_html/storage/logs/notifications.log 2>&1
   ```
   (adjust the PHP path and app path to match your deployment, same as the
   bulk-bonus cron entry).

### On a VPS (Ubuntu/Debian-style)

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/smartsim && php artisan notifications:dispatch-due >> storage/logs/notifications.log 2>&1
```

---

## VAPID keys (one-time setup, before any of this works)

Push notifications need a VAPID key pair configured before anything can
send — without it, `WebPushService` will fail every send attempt.

```bash
php artisan notifications:generate-vapid-keys
```

Copy the printed `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` into `.env`.
`VAPID_SUBJECT` defaults to `APP_URL` if left unset. Generate these **once**
per environment (dev, staging, production each need their own) — rotating
them invalidates every existing browser subscription, which would silently
stop delivery to everyone until they re-enable notifications.

---

## Verifying it worked

```bash
# Confirm the command found and dispatched due notifications
tail -50 storage/logs/notifications.log

# Confirm the job actually ran (should trend toward 0 if the worker's keeping up)
php artisan tinker --execute="echo DB::table('jobs')->count();"

# Confirm no jobs died
php artisan tinker --execute="echo DB::table('failed_jobs')->count();"

# Confirm delivery outcomes were recorded
php artisan tinker --execute="echo App\Models\AdminNotificationDelivery::count();"
```
