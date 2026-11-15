# Super Admin Account Management

Super Admins manage internal admin accounts from **Manage Users** in the web dashboard. Account creation, role changes, and removal are restricted to authenticated, verified Super Admins.

## Create an Admin

From **Manage Users**, choose **Create Admin** and provide the account holder's name, email, phone number, role, and a confirmed password of at least 12 characters. The account is created active and email-verified.

Supported internal roles are:

| Role | Access |
|---|---|
| `super_admin` | Full platform administration |
| `business_sales_manager` | Business sales management tools |
| `support` | Support tools |

## Change or Remove an Admin

Use the user list's **Edit** action to update an admin's profile, status, or role. A Super Admin may demote another Super Admin to an available platform role or change another account to `super_admin`.

Use **Delete** to remove another admin account. Deleting your own account is blocked. Deleting another Super Admin is permitted only for a Super Admin. User deletion removes the user's wallet and virtual-account records as handled by the existing user-management workflow.

## Authorization Safeguards

- Business Sales Managers cannot create admin accounts, modify another Business Sales Manager, edit a Super Admin, or assign the `super_admin` role.
- Support and other non-super-admin users cannot access the admin-management routes.
- Self-deletion is rejected.
- Upgrade requests cannot grant `super_admin`; role elevation is done directly by a Super Admin.

## CLI Status

The following legacy commands are temporarily disabled:

```text
php artisan make:admin
php artisan admin:make
php artisan user:make-admin
```

They return a failure message and do not modify any account, including when called with `--revoke`. Use the web dashboard while CLI-based role management is paused. The command implementation is retained for a future reactivation.
