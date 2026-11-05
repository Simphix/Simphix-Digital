# Super Admin Management (CLI)

Covers managing the `super_admin` role using the Artisan CLI commands:
- `php artisan make:admin`
- `php artisan admin:make`
- `php artisan user:make-admin`

---

## Security Architecture & UI Isolation

For security and to prevent privilege escalation vulnerabilities, **the `super_admin` role cannot be assigned or granted through the web interface**:

1. **User Edit Form** (`admin.manage.users.edit`):
   - The role selection dropdown excludes `super_admin`.
   - Existing Super Admin accounts display a read-only badge indicating their role is managed via CLI only.
   - Non-super-admin users cannot be updated to `super_admin` via web requests.

2. **Role Access Management** (`admin.manage.access`):
   - The inline role update dropdown excludes `super_admin`.
   - Existing Super Admin accounts are rendered with a read-only `Super Admin (CLI only)` badge with no modification form.
   - Submitting `role=super_admin` returns a validation error.

3. **Account Upgrade Requests** (`admin.manage.upgrades`):
   - Account upgrades are strictly limited to customer roles (`partner`, `business`).
   - If an upgrade request attempts to grant `super_admin`, the approval handler rejects it.

---

## Artisan Commands

### 1. Promote a User to Super Admin

To make a user a Super Admin by their email address:

```bash
php artisan make:admin user@example.com
```

Or using the `admin:make` alias:

```bash
php artisan admin:make user@example.com
```

#### Interactive Mode

If called without arguments, the command prompts for the user email interactively:

```bash
php artisan make:admin
# Enter the user email address: user@example.com
```

#### What Happens During Promotion:
- Verifies that a user exists with the provided email.
- If the user is already a Super Admin, notifies you without making redundant changes.
- If the user previously held a jurisdiction-tied role (`regional_manager` or `coordinator`), automatically detaches any managed region or senatorial district to prevent stale jurisdiction mappings.
- Sets the user's role to `super_admin` and ensures status is `active`.

---

### 2. Revoke Super Admin Status

To revoke Super Admin permissions and revert the account back to a standard `personal` user role, use the `--revoke` flag:

```bash
php artisan make:admin user@example.com --revoke
```

Or with the alias:

```bash
php artisan admin:make user@example.com --revoke
```

#### What Happens During Revocation:
- Verifies the user currently holds the `super_admin` role.
- Updates the user's role to `personal`.
- The user immediately loses access to the administrative dashboard, system configurations, and back-office tools.

---

## Reference Table

| Action | Command | Description |
|---|---|---|
| **Promote User** | `php artisan make:admin <email>` | Grants `super_admin` role to the specified user email. |
| **Promote (Alias)** | `php artisan admin:make <email>` | Identical alias for convenience. |
| **Promote (Alias)** | `php artisan user:make-admin <email>` | Alternative alias for user management. |
| **Revoke Super Admin** | `php artisan make:admin <email> --revoke` | Reverts `super_admin` back to `personal` role. |
| **Interactive** | `php artisan make:admin` | Prompts for the email in terminal. |

---

## Verification via Tinker

To quickly inspect a user's role in the database:

```bash
php artisan tinker --execute="echo App\Models\User::where('email', 'user@example.com')->value('role');"
```
