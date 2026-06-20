# PAM Modernization Plan

**Project:** Personal Accounting Management (Fixed Deposit tracker)
**From:** Procedural PHP 5 + MySQLi (MyISAM) + jQuery
**To:** Python / Django 5 + Django REST Framework + PostgreSQL + responsive frontend
**Approach:** Incremental migration (strangler-fig), not a big-bang rewrite
**Date:** June 2026

---

## 1. Goals

1. Move to a modern, supported tech stack (Python/Django).
2. Re-design the database into a properly normalized schema.
3. Make the UI fully responsive.
4. Eliminate the known security concerns (SQL injection, weak hashing, hardcoded secrets).

## 2. A note on "incremental" + a stack change

You can't literally refactor PHP files into Django in place. The practical way to keep an app running while changing stacks is the **strangler-fig pattern**: stand up the new Django app beside the existing PHP app, move one feature (route) at a time behind a reverse proxy, and retire each PHP page as its Django equivalent goes live. This gives the safety of incremental delivery while still ending on a clean Django codebase. The plan below is structured that way.

```
        ┌──────────────┐
Users → │ Reverse proxy │ → /new_fd, /index … → legacy PHP   (shrinking)
        │  (Nginx)      │ → /api, /fds …      → new Django   (growing)
        └──────────────┘
```

---

## 3. Phase Overview

| Phase | Focus | Outcome |
|-------|-------|---------|
| 0 | Safety net & audit | Backups, version control, dependency/security audit |
| 1 | Security hardening (in legacy PHP) | Stop the bleeding: kill SQL injection, fix secrets/hashing |
| 2 | New schema design | Normalized PostgreSQL schema + Django models |
| 3 | Django foundation | Project scaffold, auth, ORM, data migration scripts |
| 4 | Feature-by-feature migration | Each PHP page reimplemented in Django, cut over via proxy |
| 5 | Responsive frontend | Modern templating + CSS framework, mobile-first |
| 6 | Hardening & cutover | Tests, CI/CD, security review, decommission PHP |

---

## 4. Phase 0 — Safety Net & Audit (Prep)

Before changing anything:

- Put the codebase under Git (if not already) and take a full DB dump.
- Stand up a staging environment that mirrors production.
- Inventory every page, every SQL query, and every report table.
- Identify which DB tables are **source data** (`accounts`, `acc_users`, `deposite_schemes`, `auth_users`, `accounts_history`, `interest_period`, `deposite_schemes`) vs. **derived/report tables** (`monthly_investment`, `name_wise_details`, `schemewise_amount_details`, `monthly_interest_received`, etc.). Derived tables should **not** be migrated — they become queries/views in the new system.

## 5. Phase 1 — Security Hardening (do this first, in the legacy app)

These fixes are urgent and can ship before the Django work, because they protect the live app during the months-long migration.

1. **SQL injection — highest priority.** Every query is built by string interpolation (e.g. `username='$username'` in `login.php`). Replace all queries with **parameterized prepared statements** (`mysqli_prepare` / bound params) or, faster, route DB access through a PDO wrapper. No user input should ever be concatenated into SQL.
2. **Password hashing.** SHA1 is broken. Migrate to `password_hash()` / `password_verify()` (bcrypt/argon2). Re-hash on next successful login (verify old SHA1, then upgrade the stored hash).
3. **Secrets management.** Remove the hardcoded `root` / `root_password` from `db_connect.php`. Load credentials from environment variables or a `.env` file kept out of version control. Create a least-privilege DB user (not `root`).
4. **Session security.** Set `session.cookie_httponly`, `cookie_secure`, `SameSite`, regenerate session ID on login, and enforce HTTPS.
5. **CSRF + output escaping.** Add CSRF tokens to all forms; escape all output with `htmlspecialchars()` to close XSS holes.
6. **Disable error display.** `.htaccess` currently has `display_errors on` — turn it off in production and log to file instead.
7. **Remove dead/backup files** from the web root (`index222.php`, `*666.php`, `delete_fd55.php`, `*.orig`, etc.) — they are unauthenticated, outdated, and an attack surface.

## 6. Phase 2 — Normalized Schema Design

### Problems with the current schema

- `accounts.name` is an `int` foreign key but **named like a string** and not declared as a real FK (MyISAM has no FK enforcement). `accounts_history.name` stores the name as `varchar` — inconsistent.
- **No foreign keys or referential integrity** anywhere (MyISAM engine).
- **`float` used for money** — causes rounding errors. Must be `NUMERIC/DECIMAL`.
- **Derived data stored as tables** (`monthly_investment`, `*_details` summaries) — violates normalization; these duplicate facts already in `accounts` and drift out of sync.
- **Repeating `(period, period_type)`, interest calc columns** stored redundantly per row.
- `enum` flags (`is_active`, `interest_type`) are stringly-typed and inconsistent (`'0'/'1'`, `'y'/'n'`, `int`).

### Proposed normalized model (Django / PostgreSQL)

```
User (Django auth)            # replaces auth_users; real password hashing built-in
  - first_name, last_name, is_staff/is_superuser, is_active

Account_Holder               # replaces acc_users
  - id, name, is_active

Scheme                       # replaces deposite_schemes
  - id, name, is_active

Interest_Slab                # replaces interest_period
  - id, scheme (FK), user_type, term_start, term_end,
    interest_rate (DECIMAL), comments

Fixed_Deposit                # replaces accounts (source of truth)
  - id, holder (FK → Account_Holder)
  - scheme (FK → Scheme)
  - ref_id
  - deposit_date, renewal_date, maturity_date
  - period, period_unit (enum: day/month/year)
  - rate_of_interest (DECIMAL)
  - interest_type (enum: simple/compound)
  - principal_amount (DECIMAL)        # was deposite_amount
  - total_interest (DECIMAL)
  - maturity_amount (DECIMAL)
  - status (enum: active/matured/renewed/closed)
  - created_at, updated_at

FD_History                   # replaces accounts_history (audit trail)
  - id, fixed_deposit (FK), action, snapshot fields, closed_date, changed_at
```

### Key normalization changes

- Every relationship becomes a real **foreign key** with `ON DELETE` rules (Django + PostgreSQL enforce them).
- All monetary and rate fields become **`DecimalField`** (no more `float`).
- Consistent **enum/choice** fields for status, period unit, interest type.
- **Derived report tables are dropped** and replaced by ORM queries / database **views** / aggregation, computed on demand (e.g. monthly investment, name-wise, scheme-wise, FY report).
- Reuse Django's built-in `auth_user` instead of the custom `auth_users` table.

## 7. Phase 3 — Django Foundation

- Scaffold a Django 5 project with apps: `accounts` (FD domain), `reports`, `users`.
- Configure PostgreSQL, environment-based settings (`django-environ`), `SECRET_KEY` from env.
- Define the models above; generate migrations.
- Build a **data migration script** that reads the old MySQL `accounts` / `acc_users` / `deposite_schemes` / `interest_period` / `accounts_history` and loads the new normalized tables, converting floats→decimals and string flags→enums, and mapping `name` ints to real holder FKs.
- Reimplement the interest/maturity calculators (currently in `ajax.php`) as tested Python service functions.
- Wire up Django's auth for login/logout/sessions (replaces `login.php` logic and gives secure hashing for free).

## 8. Phase 4 — Feature-by-Feature Migration

Migrate one page at a time, putting each new Django route live behind the proxy and retiring the PHP equivalent:

| Legacy page | New Django route | Notes |
|-------------|------------------|-------|
| `login.php` / `logout.php` | `accounts.auth` | Django auth, secure sessions |
| `index.php` | FD list view | Pagination, filtering, sorting |
| `new_fd.php` / `edit_fd.php` | FD create/update forms | Server-side validation |
| `renew_fd.php` / `delete_fd.php` | FD renew/close actions | Writes audit row to FD_History |
| `on_date_fd_details.php` | "As-of-date" report | ORM query |
| `namewise_list.php` | Holder-wise report | `GROUP BY` aggregation |
| `monthwise_list.php` | Month-wise report | Aggregation |
| `fy_report.php` | Financial-year report | Aggregation |
| `monthly_investment.php` / `graphs.php` | Charts | Chart.js / Recharts from a clean JSON API |
| `ajax.php` | DRF API endpoints | Calculators as REST |

Start with the lowest-risk read-only page (e.g. `on_date_fd_details.php`) to prove the pattern, then move to write pages.

## 9. Phase 5 — Responsive Frontend

The current UI uses fixed-width HTML 4.0 tables with no viewport handling. Options:

- **Server-rendered (recommended for this app's size):** Django templates + **Tailwind CSS** (or Bootstrap 5), mobile-first, with a `<meta viewport>` tag, responsive tables (card layout on small screens), and accessible forms.
- **SPA (if richer interactivity is wanted later):** React + Tailwind consuming the DRF API.

Replace Highcharts 7.1.1 with a current charting library (Chart.js or ECharts). Keep the datepicker but use a native `<input type="date">` or a modern, dependency-light picker.

## 10. Phase 6 — Hardening, Testing & Cutover

- **Tests:** unit tests for interest/maturity calculators, model constraints, and the data-migration script; integration tests per migrated route.
- **Security review:** run a dependency scan, Django's `manage.py check --deploy`, and a focused review of auth, permissions, and any remaining raw SQL.
- **CI/CD:** automated lint/test/build pipeline; containerize with Docker; deploy behind Nginx + Gunicorn over HTTPS.
- **Cutover:** once every route is migrated and validated against production data, route 100% of traffic to Django, run a final data sync, and decommission the PHP app and MyISAM database.

---

## 11. Security Checklist (consolidated)

- [ ] All queries parameterized (no string interpolation) — Django ORM gives this by default
- [ ] Passwords hashed with bcrypt/argon2 (Django default)
- [ ] Secrets in environment, not source; `SECRET_KEY` rotated
- [ ] Least-privilege database user (not `root`)
- [ ] HTTPS enforced; secure/HttpOnly/SameSite cookies
- [ ] CSRF protection on all forms (Django built-in)
- [ ] Output escaping / XSS protection (Django templates auto-escape)
- [ ] `DEBUG = False` and error display off in production
- [ ] Dead/backup files removed from web root
- [ ] Dependency and deployment security scans in CI

## 12. Suggested Sequencing & Risk

Phase 1 (security in legacy PHP) should ship **immediately and independently** — it removes the most dangerous risks within days, regardless of how fast the Django migration goes. Phases 2–6 then proceed at a measured pace. Because each Django route goes live only after validation, the app stays usable throughout and there is no single high-risk cutover day.
