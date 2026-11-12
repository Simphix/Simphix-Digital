# Downline Batches, Hierarchy Management & Safe Architecture

This document details the architecture, workflows, and operational procedures for managing detached downlines, enforcing referral hierarchy boundaries, executing live-search destination transfers, and maintaining secure cryptographic resilience across SimPhix.

---

## Table of Contents
1. [Referral Hierarchy Architecture](#1-referral-hierarchy-architecture)
2. [Platform Level Master Manager](#2-platform-level-master-manager)
3. [Downline Batches Lifecycle](#3-downline-batches-lifecycle)
4. [Role-Eligible Destination Manager Search & Transfers](#4-role-eligible-destination-manager-search--transfers)
5. [CLI Downline Clearing & Re-alignment](#5-cli-downline-clearing--re-alignment)
6. [Universal Non-Blocking Export Downloads](#6-universal-non-blocking-export-downloads)
7. [Cryptographic Resilience (`SafeEncrypted` Cast)](#7-cryptographic-resilience-safeencrypted-cast)

---

## 1. Referral Hierarchy Architecture

SimPhix implements a strict hierarchical multi-tier distribution network (`App\Support\RoleHierarchy`):

```
Retail Distribution Chain:
  Regional Manager (regional_manager)
        │
        ▼
   Coordinator (coordinator)
        │
        ▼
     Partner (partner)
        │
        ▼
  Personal User / Customer (personal, agent)

Enterprise Distribution Chain:
  Enterprise Partner (business)
        │
        ▼
  Business Agent (business_agent)
        │
        ▼
  Personal User / Customer (personal)
```

### Hierarchy Constraints
- **Direct Tier Subordination**: Each manager tier may only recruit or manage the role directly beneath it in its own chain.
- **Independence of Chains**: The retail chain (`regional_manager -> coordinator -> partner`) and enterprise chain (`business -> business_agent`) operate independently.
- **Internal Staff Isolation**: Internal staff and back-office roles (`super_admin`, `business_sales_manager`, `support`) are **not** customer accounts and **never carry a referral upline** (`referred_by = null`).

---

## 2. Platform Level Master Manager

When an upline manager is retired or replaced (via Manager Replacement at `/admin/manage/replace-manager`), their downlines cannot simply be orphaned without an upline, nor can they be prematurely transferred to an incompatible role.

The **Platform Level Master Manager** (`User::platformMasterManager()`) serves as the central platform holding seat:
- **Account Identity**: `master.manager@platform.internal`
- **Holding Function**: Holds all detached downlines safely during transitions without breaking commission tracking or cascade queries.
- **Protection Guard**: The Master Manager account cannot have its downlines purged or cleared via maintenance commands; it is the platform's trusted holding anchor.

---

## 3. Downline Batches Lifecycle

Detached downlines are grouped into immutable **Downline Batches** (`App\Models\DownlineBatch`):

| Status | Description |
|---|---|
| `held` | All members in the batch are currently parked under the Platform Level Master Manager pending reassignment. |
| `partially_transferred` | A subset of downlines has been transferred to one or more active managers; remaining members remain held. |
| `transferred` | All initial downlines have been successfully transferred to destination managers. |

### Web Management Routes
- **Batches Index**: `GET /admin/manage/downline-batches`
- **Batch Detail & Transfer UI**: `GET /admin/manage/downline-batches/{batch}`
- **Destination Manager Live Search**: `GET /admin/manage/downline-batches/{batch}/search-destinations?q={query}`
- **Execute Bulk Transfer**: `POST /admin/manage/downline-batches/{batch}/transfer`

---

## 4. Role-Eligible Destination Manager Search & Transfers

To prevent hierarchy corruption (e.g. transferring a Coordinator to a Partner, or a Partner to a Regional Manager), the transfer service strictly enforces **Eligible Destination Roles**:

### Eligibility Mapping Table

| Batch Source Role | Batch Downlines Role | Eligible Destination Upline Roles |
|---|---|---|
| `regional_manager` | `coordinator` | `regional_manager` **only** |
| `coordinator` | `partner` | `coordinator` **only** |
| `business` | `business_agent` | `business` (Enterprise Partner) **only** |
| `partner` | `personal`, `agent` | `partner` **only** |
| `business_agent` | `personal` | `business_agent` **only** |

### Live Interactive Search & Selection
The batch show interface (`resources/views/admin/manage/downline_batches/show.blade.php`) provides:
1. **Required Role Indicator**: Prominently displays the required destination role (e.g., `Eligible Destination Role: COORDINATOR`).
2. **Debounced Live Search**: Typing in the search field dynamically queries `/admin/manage/downline-batches/{batch}/search-destinations?q=...` with instant results by name, email, or phone number.
3. **Manager Picker Cards**: Shows candidate name, verified eligible upline badge, email, phone, and current active downline count.
4. **Safety Confirmation Modal**: SweetAlert2 dialog requires explicit confirmation before executing reassignments.
5. **Partial or Complete Transfer**: Admins can select specific users via checkboxes or transfer all held users in bulk.

---

## 5. CLI Downline Clearing & Re-alignment

The administrative command `downlines:clear` allows Super Admins to clear downlines across upline managers and enforce platform rules:

```bash
# Preview changes without modifying database
php artisan downlines:clear --dry-run

# Execute full hierarchy realignment across all managers
php artisan downlines:clear --force

# Target a specific manager ID
php artisan downlines:clear --manager-id=19 --force
```

### Action Rules Executed by CLI
1. **Catalog Role Members** (`regional_manager`, `coordinator`, `partner`, `business`, `business_agent`):
   - Detached from previous upline managers and assigned to or retained under the Platform Level Master Manager (`referred_by = $masterManager->id`).
2. **Admin & Staff Accounts** (`super_admin`, `business_sales_manager`, `support`):
   - Stripped of any referral upline (`referred_by = null`).
3. **Customer & Retail Accounts** (`personal`, `agent`):
   - Detached from upline managers (`referred_by = null`), or optionally reassigned to Master Manager via `--customers=master`.

---

## 6. Universal Non-Blocking Export Downloads

### Problem Addressed
Previously, data export buttons across the application (e.g., Users export, SME Plans export, Financial exports) would enter an infinite spinning/processing state on various browsers (especially Mobile Safari, Chrome on Android, and Chromium) because file downloads do not trigger traditional AJAX `load` or `readystatechange` completion events.

### Solution Architecture
The universal export component (`resources/views/components/export-download.blade.php`):
1. **Hidden Download Iframe / Dynamic Blob Trigger**: Submits the export request within an isolated hidden target to avoid page unloading.
2. **Cookie-Based Handshake**: When the Laravel server finishes generating the CSV/Excel stream, it attaches an `app_export_download=complete` cookie.
3. **Reactive Poller with Fallback Timeout**: The client-side script polls for this cookie every 500ms and restores the button to its active, clickable state immediately upon file receipt, backed by an automatic 8-second safety timeout.

---

## 7. Cryptographic Resilience (`SafeEncrypted` Cast)

### Problem Addressed
When credentials, vendor tokens, or provider API keys (such as `sme_data_plans.api_key` or payment gateways) were stored with Laravel's standard `'encrypted'` Eloquent cast, rotating or modifying the application key (`APP_KEY`) in `.env` resulted in catastrophic `Illuminate\Contracts\Encryption\DecryptException: The MAC is invalid` HTTP 500 errors across admin views.

### Solution Architecture
- **`App\Casts\SafeEncrypted`**:
  - Implements a resilient `get()` method wrapped in `try/catch (DecryptException $e)`.
  - If MAC validation fails or the key was encrypted under a legacy/different key, it safely catches the exception, logs a warning, and returns the raw string or a fallback empty value rather than crashing the view engine.
  - Automatically re-encrypts the value under the current active `APP_KEY` on the next model save.
