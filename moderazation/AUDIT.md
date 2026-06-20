# PAM — Phase 0 Codebase Audit

**Date:** June 2026
**Scope:** Full inventory of pages, database objects, dead code, and security findings prior to modernization.
**Method:** Static review of all `.php` files, `pma_db.sql` / `pam_dev.sql` schema dumps, and `.htaccess`.

---

## 1. Page Inventory

### Live application pages (reachable, in navigation or linked from it)

| File | Purpose | Auth guard | SQL ops |
|------|---------|:---------:|:------:|
| `login.php` | Login form + session creation | n/a (entry) | SELECT |
| `logout.php` | Destroys session | yes | — |
| `index.php` | FD list (main dashboard) | yes | SELECT/INSERT |
| `new_fd.php` | Create new FD | yes | SELECT/INSERT (heaviest, 23 stmts) |
| `edit_fd.php` | Edit FD | yes | SELECT/UPDATE |
| `renew_fd.php` | Renew matured FD | yes | SELECT/INSERT/UPDATE |
| `delete_fd.php` | Delete FD | yes | SELECT/DELETE |
| `on_date_fd_details.php` | FDs active on a given date | yes | SELECT |
| `namewise_list.php` | FDs grouped by holder | yes | SELECT |
| `monthwise_list.php` | FDs grouped by month | yes | SELECT |
| `fy_report.php` | Financial-year report | yes | SELECT |
| `monthly_investment.php` | Investment report (Highcharts) | yes | SELECT |
| `graphs.php` | Charts | yes | SELECT |

### Shared / infrastructure includes

| File | Role | Included by |
|------|------|-------------|
| `db_connect.php` | `db` class — connect/query/insert + file logging | 26 pages |
| `header.php` | Layout + nav menu | 24 pages |
| `footer.php` | Layout footer | 25 pages |
| `ajax.php` | Maturity-date & interest calculators | required by 18 pages; also a direct endpoint |
| `validation.php` | Form validation helpers | 6 pages |

### Dead / backup / test files (NOT part of the live app)

These are old copies, experiments, or dev utilities. They are still web-accessible and several lack auth guards, so they are an attack surface. **Recommend archiving/removing in Phase 1.**

| File | Type |
|------|------|
| `index222.php`, `index55.php`, `index6666.php` | Backup copies of `index.php` |
| `header666.php` | Backup copy of `header.php` |
| `fy_report666.php` | Backup copy |
| `monthwise_list666.php` | Backup copy |
| `namewise_list666.php` | Backup copy |
| `on_date_fd_details666.php` | Backup copy |
| `delete_fd222.php`, `delete_fd55.php` | Backup copies of `delete_fd.php` |
| `new_test.php`, `new_test66.php` | Work-in-progress copies of `new_fd.php` |
| `monthly_investment.php.orig` | Pre-edit `.orig` backup |
| `monthly_investment_1.php`, `monthly_investment_stock.php` | Variants |
| `sample.php` (26 bytes), `test.php` (48 bytes), `test_json.php` | Throwaway test scripts |
| `export_prod_data.php` | Data-export utility — **no auth guard** |

**Count:** ~19 dead/backup files out of 41 PHP files (~46% of the PHP in the web root is not live code).

---

## 2. Database Objects

The schema mixes **real source tables** with **derived SQL views**. The views are computed on demand — they are *not* data that needs migrating; in the new system they become ORM queries/aggregations.

### Source tables (7) — the real data, must be migrated

| Table | Role | Notes |
|-------|------|-------|
| `accounts` | Fixed deposits (core) | `name` is an `int` FK to `acc_users` but not enforced; money stored as `float` |
| `accounts_history` | Audit trail of closed/renewed FDs | `name` stored as `varchar` here — inconsistent with `accounts` |
| `acc_users` | FD account holders | `is_active` enum('y','n') |
| `auth_users` | App login users | SHA1 passwords; `is_super`/`is_active` enum('0','1') |
| `deposite_schemes` | FD/RD scheme types | |
| `interest_period` | Interest-rate slabs by scheme/term | |
| `monthly_investment` | Expected vs actual monthly investment | One of the few non-derived report tables |

### Views (15) — derived, do NOT migrate

`bank_fd_summary`, `check_this_month_details`, `current_amount_status`, `datewise_amount_details`, `datewise_fd_plan`, `interestwise_details`, `interestwise_details_new`, `monthly_interest_received`, `monthwise_amount_details`, `name_wise_details`, `schemewise_amount_details`, `scheme_wise_details`, `scheme_wise_details_active`, `scheme_wise_details_active_new`, `upcoming_bank_fd_maturities`.

Observations on the views:
- Several are **chained** (a view selecting from another view, e.g. `monthwise_amount_details` → `datewise_amount_details`), which is brittle and slow.
- Some contain **hardcoded dates** (e.g. `bank_fd_summary` filters `maturity_date BETWEEN '2026-04-01' AND '2028-03-31'`) and **hardcoded scheme IDs** (`NOT IN (22,24,25)`) baked into the definition — these silently go stale.
- All are defined `DEFINER=root@localhost SQL SECURITY DEFINER` — they execute with root privileges (privilege-escalation smell).

---

## 3. Security Findings (consolidated)

| # | Severity | Finding | Evidence |
|---|----------|---------|----------|
| S1 | **Critical** | SQL injection — queries built by string interpolation of user input | `login.php`: `... username='$username' and password=sha1('$password')`; same pattern across pages |
| S2 | **Critical** | Hardcoded DB credentials, using `root` | `db_connect.php`: `$username="root"; $password="root_password"` |
| S3 | **High** | Weak password hashing (SHA1, unsalted) | `auth_users.password`, `login.php` |
| S4 | **High** | Unauthenticated scripts in web root | `ajax.php`, `export_prod_data.php`, `test*.php`, `sample.php` have no `is_logged` guard |
| S5 | **Medium** | Sensitive logs world-readable in web root | `query_log.txt` (142 KB of executed SQL), `error_log.txt`, `search_query_log.txt` served by Apache |
| S6 | **Medium** | `display_errors on` in production | `.htaccess` `php_flag display_errors on` |
| S7 | **Medium** | No session hardening | No `HttpOnly`/`Secure`/`SameSite` cookie flags; no session regeneration on login |
| S8 | **Medium** | Stale/dead code publicly reachable | ~19 backup/test files (see §1) |
| S9 | **Medium** | Views run as `SQL SECURITY DEFINER` (root) | all 15 views |
| I1 | Integrity | Money stored as `float` (rounding errors) | `accounts.deposite_amount/total_interest/maturity_amount` |
| I2 | Integrity | MyISAM engine — no FKs, no transactions | all tables |
| I3 | Integrity | Inconsistent holder reference (`int` vs `varchar`) | `accounts.name` vs `accounts_history.name` |

S1–S4 should be addressed first in Phase 1; I1–I3 are resolved by the Phase 2 schema redesign.

---

## 4. Logging Behaviour (operational note)

`db_connect.php` writes every non-SELECT query to `query_log.txt` and every error to `error_log.txt` in the web root via `fopen(..., "a")`. This is the source of the 142 KB log and is both a disclosure risk (S5) and unbounded growth. The new system should use a proper logging framework writing outside the web root.

---

## 5. Migration Implications

- **Migrate:** the 7 source tables only.
- **Drop & re-derive:** all 15 views become Django ORM queries (and the hardcoded dates/scheme-IDs become configuration).
- **Re-implement:** `ajax.php` calculators as tested service functions; logging via framework.
- **Discard:** all dead/backup/test files.
