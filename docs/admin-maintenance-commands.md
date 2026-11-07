# Admin Maintenance & CLI Management Commands

This document provides a comprehensive reference for the administrative Artisan commands in SimPhix. These commands enable Super Admins and DevOps operators to export platform analytics, audit balances, clear legacy or test data, and perform controlled platform resets.

---

## Table of Contents
1. [Core Safety Guarantees](#core-safety-guarantees)
2. [Data Export Commands](#data-export-commands)
   - [`enterprise:export-breakdown`](#1-enterpriseexport-breakdown)
   - [`users:export-balances`](#2-usersexport-balances)
3. [Financial Balance Clearing & Debits](#financial-balance-clearing--debits)
   - [`balances:clear`](#3-balancesclear)
4. [Platform Data Purge & Reset Commands](#platform-data-purge--reset-commands)
   - [`enterprise-partners:purge`](#4-enterprise-partnerspurge)
   - [`sims:remove-activated`](#5-simsremove-activated)
   - [`transactions:clear-history`](#6-transactionsclear-history)
   - [`sims:clear-activation-events`](#7-simsclear-activation-events)
   - [`requests:clear-pending`](#8-requestsclear-pending)
   - [`webhooks:clear-logs`](#9-webhooksclear-logs)
   - [`tickets:clear`](#10-ticketsclear)
   - [`accounts:clear`](#11-accountsclear)

---

## Core Safety Guarantees

All destructive commands enforce strict safety mechanisms:
- **Interactive Confirmations (`$this->confirm`)**: Require explicit `yes` confirmation before any record is modified or deleted. Destructive operations display an exact impact summary count beforehand.
- **Headless Bypass (`--force`)**: Skips confirmation prompts only when explicitly passed by an operator in automated CI/CD or scripted deployments.
- **Dry Run Mode (`--dry-run`)**: Supported on purge commands to preview matching records and impact without modifying database rows.
- **Foreign Key Check Handling (`Schema::disableForeignKeyConstraints`)**: Purge and reset commands disable foreign key checks during batch deletion to prevent MySQL Error 1451 constraint locks, automatically re-enabling them upon completion or error.
- **Database Transactions (`DB::transaction`)**: All multi-table updates and deletions run atomically within database transactions.

---

## Data Export Commands

### 1. `enterprise:export-breakdown`

Exports a comprehensive breakdown of Enterprise Business users (`role = business`), their downline business agents (`role = business_agent`), direct vs. downline activations, wallet balances, and SIM inventories to an Excel-compatible UTF-8 BOM CSV.

- **Signature**:
  ```bash
  php artisan enterprise:export-breakdown {business?} {--business=} {--output=} {--agents-output=} {--detailed} {--with-inventory-only}
  ```
- **Aliases**:
  - `enterprise-business:export`
  - `enterprise:export`
  - `simphix:export-enterprise-breakdown`

#### Options & Arguments
| Parameter | Type | Description |
|---|---|---|
| `business` | Argument | Optional ID, email, or business name of a specific Enterprise Business to export |
| `--business=` | Option | Target a specific Enterprise Business by ID, email, or name |
| `--output=` | Option | Custom path for the Enterprise summary CSV (defaults to `storage/app/exports/enterprise_breakdown_{timestamp}.csv`) |
| `--agents-output=` | Option | Custom path for the detailed Downline Agents CSV |
| `--detailed` | Option | Generates both the Enterprise Business summary CSV and a companion CSV containing agent-by-agent metrics |
| `--with-inventory-only` | Option | Excludes businesses with 0 SIMs and 0 downline agents |

#### Exported Metrics
- **Enterprise Profile**: ID, Business Name, Contact Person, Email, Phone, CAC Number, State, LGA, Account Tier, Status, Application status & snapshot amount.
- **Enterprise Balances**: Main Balance (`wallet.balance`), Commission Balance (`wallet.bonus`), Hold Balance (`wallet.hold_amount`), Lien Balance (`wallet.lien_amount`), Total Wallet Balance (`balance + bonus`).
- **Downline Agents Overview**: Total Agents count, Active Agents count, Agents Main Balances sum, Agents Commission Balances sum, Agents Combined Balance sum.
- **Combined Ecosystem Balance**: Grand total of Enterprise User balance + all Downline Agents balances.
- **Direct SIM Inventory (Theirs)**: Enterprise Own Direct SIM Stock (Allocated / Activated / Unactivated Stock) and Direct Conversion Rate (%).
- **Downline SIM Inventory**: Downline Agents SIM Inventory (Allocated / Activated / Unactivated Stock) and Downline Conversion Rate (%).
- **Total Ecosystem SIMs**: Grand Total SIMs Allocated, Total Activated, Total Stock, Overall Conversion Rate (%).
- **Provider Breakdown**: Formatted network distribution (Total / Activated / Stock) for MTN, Glo, Airtel, and 9mobile.

#### Usage Examples
```bash
# Export all Enterprise Businesses and display summary table in CLI
php artisan enterprise:export-breakdown

# Export with detailed agent-by-agent breakdown CSV
php artisan enterprise:export-breakdown --detailed

# Export only for a specific Enterprise Business
php artisan enterprise:export-breakdown 14
php artisan enterprise:export-breakdown "Mega Telecom LTD"

# Custom output file paths
php artisan enterprise:export-breakdown --output=storage/app/exports/ep_summary.csv --agents-output=storage/app/exports/ep_agents.csv
```

---

### 2. `users:export-balances`

Exports all user profiles and wallet balances (Main, Commission, Hold, Lien) across the platform into an Excel-compatible UTF-8 BOM CSV file, while outputting a real-time summary table of user funds and Platform Revenue/Operating Wallets.

- **Signature**:
  ```bash
  php artisan users:export-balances {--output=} {--role=} {--with-balance-only}
  ```
- **Aliases**:
  - `user:export-balances`
  - `balances:export`
  - `simphix:export-balances`

#### Options
| Option | Description |
|---|---|
| `--output=` | Custom destination path for CSV (defaults to `storage/app/exports/user_balances_{timestamp}.csv`) |
| `--role=` | Filter exported users by role (e.g. `partner`, `business`, `personal`, `coordinator`) |
| `--with-balance-only` | Only export users holding a positive balance (`balance > 0`, `bonus > 0`, or `hold_amount > 0`) |

#### Exported Columns
`User ID`, `Full Name`, `Email`, `Phone`, `Role`, `Wallet Number`, `Main Balance (NGN)`, `Commission Balance (NGN)`, `Hold Balance (NGN)`, `Lien Balance (NGN)`, `Total User Balance (NGN)`, `Total Credited (NGN)`, `Total Debited (NGN)`, `Wallet Status`, `Last Activity`.

#### Usage Examples
```bash
# Export all user balances across the platform
php artisan users:export-balances

# Export only partners with active balances
php artisan users:export-balances --role=partner --with-balance-only

# Export to a custom path
php artisan users:export-balances --output=storage/app/exports/active_balances.csv
```

---

## Financial Balance Clearing & Debits

### 3. `balances:clear`

Clears (debits to `0.00`) user wallet balances (`balance`, `bonus`, `hold_amount`, `lien_amount`) across the platform. Includes filters to target specific roles or balance types, with an option to reset Platform Revenue Wallets.

- **Signature**:
  ```bash
  php artisan balances:clear {--force} {--main-only} {--commission-only} {--role=} {--include-platform-wallets} {--record-transactions}
  ```
- **Aliases**:
  - `wallets:clear-balances`
  - `users:clear-balances`
  - `simphix:clear-balances`

#### Options
| Option | Description |
|---|---|
| `--force` | Bypass interactive confirmation prompt |
| `--main-only` | Clear only main balance (`wallet.balance`), leaving commission balances intact |
| `--commission-only` | Clear only commission/bonus balance (`wallet.bonus`), leaving main balances intact |
| `--role=` | Target balances only for users of a specific role (e.g. `--role=business`, `--role=partner`) |
| `--include-platform-wallets` | Also reset platform operating/revenue wallets (`platform_wallets.balance`) to `0.00` |
| `--record-transactions` | Generate an audit trail debit `Transaction` record for each cleared user balance |

#### Usage Examples
```bash
# Preview total balances to clear and ask confirmation
php artisan balances:clear

# Clear only commission balances for all partners
php artisan balances:clear --role=partner --commission-only

# Clear all user balances and platform wallets with debit transaction logging (unattended)
php artisan balances:clear --force --include-platform-wallets --record-transactions
```

---

## Platform Data Purge & Reset Commands

### 4. `enterprise-partners:purge`

Purges Enterprise Partners from the SimPhix database, including their downline Business Agents, associated SIMs (both activated and unactivated stock), applications, payments, and commission rate configurations.

Supports interactive menu selection so operators can choose a single Enterprise Business to remove without affecting other businesses.

- **Signature**:
  ```bash
  php artisan enterprise-partners:purge {business?} {--business=} {--select} {--all} {--force} {--dry-run}
  ```
- **Aliases**:
  - `enterprise:clear-business`
  - `enterprise:clear`
  - `enterprise:remove-all`
  - `simphix:remove-enterprise-partners`

#### Options & Arguments
| Parameter | Type | Description |
|---|---|---|
| `business` | Argument | Optional ID, email, or business name of a specific Enterprise Business to purge |
| `--business=` | Option | Target a specific Enterprise Business by ID, email, or name |
| `--select` | Option | Interactively select from existing Enterprise Businesses |
| `--all` | Option | Purge all Enterprise Businesses across the platform without interactive choice |
| `--force` | Option | Bypass confirmation prompt |
| `--dry-run` | Option | Preview matching businesses, agents, and SIMs without deleting |

#### Interactive Selection Menu
When executed without arguments, the command displays a table of all registered Enterprise Businesses and prompts the operator:
```text
Existing Enterprise Businesses on SimPhix:
+----+----------------------+-------------------+--------------+------+--------+
| ID | Business Name        | Email             | Phone        | SIMs | Agents |
+----+----------------------+-------------------+--------------+------+--------+
| 14 | Acme Global Telecom  | acme@example.com  | 08011112222  | 250  | 5      |
| 18 | Prime Logistics Ent  | prime@example.com | 08033334444  | 120  | 2      |
+----+----------------------+-------------------+--------------+------+--------+

 Select the Enterprise Business to clear (or select "all"):
  [all] ALL Enterprise Businesses (2 total)
  [14]  [#14] Acme Global Telecom (acme@example.com)
  [18]  [#18] Prime Logistics Ent (prime@example.com)
 > 14
```
Selecting `#14` purges only Acme Global Telecom and its assigned SIMs/agents; Prime Logistics Ent remains untouched.

#### Usage Examples
```bash
# Interactive selection menu
php artisan enterprise-partners:purge

# Purge specific business by ID
php artisan enterprise-partners:purge 14

# Purge specific business by name with force flag
php artisan enterprise-partners:purge "Acme Global Telecom" --force

# Purge all Enterprise Businesses on the platform
php artisan enterprise-partners:purge --all
```

---

### 5. `sims:remove-activated`

Permanently deletes all activated SIM cards (`status = 'activated'`), including linked customer registration records (`sim_customers`), activation history logs (`sim_histories`), and activation bonus ledgers (`sim_activation_bonus_grants`). Unactivated stock and unassigned SIMs remain untouched.

- **Signature**:
  ```bash
  php artisan sims:remove-activated {--force} {--keep-customers} {--keep-history} {--dry-run}
  ```
- **Aliases**:
  - `sims:clear-activated`
  - `simphix:remove-activated-sims`

#### Options
| Option | Description |
|---|---|
| `--force` | Delete without confirmation prompt |
| `--keep-customers` | Preserve customer details (`sim_customers`) linked to the activated SIMs |
| `--keep-history` | Preserve audit history logs (`sim_histories`) |
| `--dry-run` | Preview count of activated SIMs and linked records without deleting |

#### Usage Examples
```bash
# Preview activated SIMs to delete and prompt confirmation
php artisan sims:remove-activated

# Force delete all activated SIMs
php artisan sims:remove-activated --force

# Dry-run preview
php artisan sims:remove-activated --dry-run
```

---

### 6. `transactions:clear-history`

Permanently clears user transaction records (`transactions`), cascaded verification lookups (`verifications`), and optionally platform wallet transactions and commission earnings.

- **Signature**:
  ```bash
  php artisan transactions:clear-history {--force} {--include-platform} {--include-commissions} {--keep-verifications} {--all}
  ```
- **Aliases**:
  - `transactions:clear`
  - `simphix:clear-transactions`

#### Options
| Option | Description |
|---|---|
| `--force` | Clear without confirmation prompt |
| `--include-platform` | Also clear platform wallet transactions (`platform_wallet_transactions`) |
| `--include-commissions` | Also clear commission earnings records (`commission_earnings`) |
| `--keep-verifications` | Keep verification lookup records without deleting |
| `--all` | Clear all transaction tables including platform transactions and commissions |

#### Foreign Key Protection
Wraps execution in `Schema::disableForeignKeyConstraints()` to prevent MySQL Error 1451 foreign key violations when clearing parent transactions with dependent child verification rows.

#### Usage Examples
```bash
# Clear user transaction history with confirmation
php artisan transactions:clear-history

# Clear everything (user transactions, platform ledger, commission earnings)
php artisan transactions:clear-history --all --force
```

---

### 7. `sims:clear-activation-events`

Clears activation events, history logs, and failed/success activation bonus data reports from the platform:
- Failed/Retrying bonus reports (`reports` table where `type = 'activation_bonus_data'`)
- Successful/Completed bonus reports (`reports` table where `type = 'activation_bonus_data'`)
- SIM activation bonus grant tracking records (`sim_activation_bonus_grants`)
- Activation commission earnings (`commission_earnings`)
- SIM status transition history logs (`sim_histories` where `to_status = 'activated'`)

- **Signature**:
  ```bash
  php artisan sims:clear-activation-events {--force} {--dry-run} {--all-history} {--keep-histories} {--bonuses=} {--failed-bonuses} {--success-bonuses} {--all-bonuses} {--bonuses-only} {--keep-bonuses}
  ```
- **Aliases**:
  - `activation-events:clear`
  - `sim:clear-activation-events`
  - `sims:clear-events`
  - `simphix:clear-activation-events`

#### Options
| Option | Description |
|---|---|
| `--force` | Clear without confirmation prompt |
| `--dry-run` | Preview record counts without deleting |
| `--bonuses=all\|failed\|success` | Filter bonus reports to clear: `failed`, `success`, or `all` (default) |
| `--failed-bonuses` | Clear only failed/retrying activation bonus reports |
| `--success-bonuses` | Clear only successful/completed activation bonus reports and bonus grants |
| `--all-bonuses` | Clear all activation bonus reports (both failed and success) |
| `--bonuses-only` | Clear only activation bonus records/reports without deleting SIM history or commission earnings |
| `--keep-bonuses` | Preserve all activation bonus records and reports |
| `--keep-histories` | Preserve SIM history logs |
| `--all-history` | Clear all SIM history logs, not only activation-specific events |

#### Usage Examples
```bash
# Clear all activation events, history logs, and all failed & success activation bonuses
php artisan sims:clear-activation-events --force

# Clear ONLY failed activation bonus reports without touching SIM history or commissions
php artisan sims:clear-activation-events --failed-bonuses --bonuses-only --force

# Clear ONLY successful activation bonus reports and grants without touching SIM history
php artisan sims:clear-activation-events --success-bonuses --bonuses-only --force

# Clear all failed and success activation bonuses without touching SIM history
php artisan sims:clear-activation-events --bonuses-only --force

# Dry-run preview of all matching activation records to be cleared
php artisan sims:clear-activation-events --dry-run
```

---

### 8. `requests:clear-pending`

Clears all unfulfilled pending requests across the platform (SIM delivery requests, Loan requests, Moniepoint POS terminal requests, and Travel package requests). Approved and rejected requests remain intact.

- **Signature**:
  ```bash
  php artisan requests:clear-pending {--type=} {--force} {--dry-run}
  ```
- **Aliases**:
  - `requests:clear`
  - `simphix:clear-pending-requests`

#### Options
| Option | Description |
|---|---|
| `--type=` | Filter by request category: `sim`, `loan`, `pos`, or `travel` (defaults to all) |
| `--force` | Clear without confirmation prompt |
| `--dry-run` | Preview matching pending requests without deleting |

#### Usage Examples
```bash
# Clear all pending requests across all services
php artisan requests:clear-pending

# Clear only pending loan requests
php artisan requests:clear-pending --type=loan --force
```

---

### 9. `webhooks:clear-logs`

Clears external payment and vendor webhook payload logs (`webhook_logs`). Supports filtering by payment gateway or delivery status.

- **Signature**:
  ```bash
  php artisan webhooks:clear-logs {--provider=} {--status=} {--force} {--dry-run}
  ```
- **Aliases**:
  - `webhooks:clear`
  - `simphix:clear-webhook-logs`

#### Options
| Option | Description |
|---|---|
| `--provider=` | Target a specific gateway provider (e.g. `palmpay`, `paystack`, `moniepoint`) |
| `--status=` | Target a specific log status (e.g. `failed`, `processed`, `ignored`) |
| `--force` | Clear without confirmation prompt |
| `--dry-run` | Preview matching log count without deleting |

#### Usage Examples
```bash
# Clear all webhook logs
php artisan webhooks:clear-logs

# Clear only failed PalmPay webhooks
php artisan webhooks:clear-logs --provider=palmpay --status=failed --force
```

---

### 10. `tickets:clear`

Permanently clears support tickets (`tickets`) and their associated conversation messages (`ticket_messages`) from the database. Supports status and category filtering.

- **Signature**:
  ```bash
  php artisan tickets:clear {--status=} {--category=} {--force} {--dry-run}
  ```
- **Aliases**:
  - `support:clear-tickets`
  - `tickets:clear-all`
  - `simphix:clear-tickets`

#### Options
| Option | Description |
|---|---|
| `--status=` | Filter by ticket status: `open`, `responded`, or `closed` |
| `--category=` | Filter by ticket category: `technical`, `billing`, `general`, or `upgrade` |
| `--force` | Clear without confirmation prompt |
| `--dry-run` | Preview count of matching tickets and messages without deleting |

#### Usage Examples
```bash
# Preview all support tickets and messages, then prompt confirmation
php artisan tickets:clear

# Clear only closed support tickets
php artisan tickets:clear --status=closed --force

# Clear all technical tickets without prompt
php artisan tickets:clear --category=technical --force
```

---

### 11. `accounts:clear`

Permanently clears generated virtual bank accounts (`virtual_accounts`) across the platform or for a specific user or account number. **Critically, all user KYC records (BVN, NIN, Tier level, residential and shop addresses) remain 100% intact and preserved.**

- **Signature**:
  ```bash
  php artisan accounts:clear {target?} {--user=} {--account=} {--provider=} {--bank=} {--all} {--include-enterprise-payments} {--force} {--dry-run}
  ```
- **Aliases**:
  - `virtual-accounts:clear`
  - `bank-accounts:clear`
  - `accounts:clear-generated`
  - `simphix:clear-accounts`

#### Options & Arguments
| Parameter | Type | Description |
|---|---|---|
| `target` | Argument | Optional user ID, email address, phone number, or specific account number |
| `--user=` | Option | Target a specific user by ID, email, or phone |
| `--account=` | Option | Target a specific bank account number |
| `--provider=` | Option | Filter by provider (e.g. `palmpay`, `monnify`, `paystack`) |
| `--bank=` | Option | Filter by bank name (e.g. `PalmPay`, `Wema Bank`) |
| `--all` | Option | Clear all generated bank accounts across the entire platform |
| `--include-enterprise-payments` | Option | Also clear generated enterprise partner registration payment accounts (`enterprise_partner_payments`) |
| `--force` | Option | Clear without confirmation prompt |
| `--dry-run` | Option | Preview matching accounts and target users without deleting |

#### Usage Examples
```bash
# Interactive mode (prompts to choose between All, Specific User, or Specific Account)
php artisan accounts:clear

# Preview all generated bank accounts across the platform without deleting
php artisan accounts:clear --all --dry-run

# Clear all generated accounts across the platform with force
php artisan accounts:clear --all --force

# Clear bank account for a specific user by email or user ID
php artisan accounts:clear --user=musa@example.com --force
php artisan accounts:clear 844 --force

# Clear a specific bank account number directly
php artisan accounts:clear --account=9900112233 --force

# Clear only PalmPay accounts for a specific user
php artisan accounts:clear --user=musa@example.com --provider=palmpay --force
```
