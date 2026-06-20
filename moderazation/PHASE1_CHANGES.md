# Phase 1 — Security Hardening: Changes

Legacy PHP app hardened in place (no stack change yet). Targets PHP 8.x + MariaDB.
All findings refer to the catalog in `AUDIT.md` §3.

## What changed

### New files
- **`config.php`** — central config; loads DB credentials from `.env`/environment (removes hardcoded secrets, S2).
- **`.env`** — local, gitignored; holds the actual DB credentials (currently the existing ones so nothing breaks).
- **`.env.example`** — committed template.
- **`auth_guard.php`** — hardened session bootstrap + login check that **calls `exit`** (closes the auth-bypass where pages redirected but kept rendering, S7).
- **`db/migrations/000_create_app_user.sql`** — least-privilege DB user (replace `root`, S2).
- **`db/migrations/001_security.sql`** — widen `auth_users.password` to `VARCHAR(255)` for bcrypt (S3).

### `db_connect.php`
- Credentials now come from `config.php` (no hardcoded `root`/password).
- Added prepared-statement helpers **`select()`** and **`execute()`** (bound parameters → no SQL injection, S1).
- Legacy `query()`/`insert_query()` kept only for constant or integer-validated statements.
- Logging is debug-gated and never stores bound values (S5).

### `login.php`
- Auth query parameterized (S1).
- **SHA1 → bcrypt**: verifies the legacy SHA1 hash, then transparently re-hashes to bcrypt on successful login (S3).
- Session-fixation protection (`session_regenerate_id`), open-redirect fixed, hardened session cookies, `exit` after redirects.

### Live pages — SQL injection removed (S1)
- **Writes parameterized** with bound params: `new_fd.php` (all 8 INSERTs via an `add_account()` helper), `edit_fd.php` (UPDATE + lookup), `delete_fd.php` (history INSERT + DELETE + lookup), `renew_fd.php` (UPDATE + history INSERT + lookup), and the holder sub-query in `monthly_investment.php`.
- **Read filters integer-validated** (cast to `int`, which cannot carry injection) on `index.php`, `on_date_fd_details.php`, `namewise_list.php`, `monthwise_list.php`, `fy_report.php`: the `name`, `deposite_scheme`, `deposited_on`, `matured_on`, `ord`, `ot`, `fy` parameters. `interest_on_date` is restricted to date characters.
- Record-id parameters (`fdid`) cast to `int` with `exit` on invalid input.

### Unauthenticated scripts (S4)
- `ajax.php` — direct `?action` calls now require a logged-in session (the function-include path is unaffected).
- `export_prod_data.php` — **disabled** (it used the removed `mysql_*` API, ran as root, no auth). Returns HTTP 410.

### `.htaccess`
- `display_errors off` + `log_errors on` (S6).
- Hardened session cookie flags (S7).
- Denies web access to `.env`, `*.sql`, log files, dotfiles, and the `backups/`, `db/`, `tools/` directories (S5).

## ⚠️ Required before deploying (run on staging first)

1. **Run the password-column migration** — bcrypt needs 60 chars; the column was `VARCHAR(50)` and would corrupt hashes:
   ```sql
   -- db/migrations/001_security.sql
   ALTER TABLE auth_users MODIFY password VARCHAR(255) NOT NULL;
   ```
   This MUST run before the first login on the new code, or password upgrades will break accounts.

2. **(Recommended) Create the least-privilege DB user** (`db/migrations/000_create_app_user.sql`), then set `DB_USER`/`DB_PASS` in `.env` to that user instead of `root`.

3. **Confirm PHP 8.x** on the target (staging is `php:8.3-apache`). The prepared-statement helpers use `mysqli_stmt_get_result()` (mysqlnd) and array-form session cookie params.

## Verification done

- Parameterized-statement placeholder/type/argument counts checked programmatically (all match).
- Brace/paren balance verified on every changed file (via the authoritative file view).
- Injection scan: no remaining superglobals interpolated into SQL, no `insert_query` with user input; the only `sha1()` left is the intentional legacy-verification path in `login.php`.
- Note: `php -l` could not be run (no PHP in the sandbox, installs blocked). Please run `php -l` on each changed file and exercise login + FD create/edit/renew/delete on **staging** before promoting to production.
