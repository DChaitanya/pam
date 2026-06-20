# Phase 3 — Django Foundation

Builds the runnable foundation on top of the Phase 2 schema: the calculators as
tested Python services, Django authentication (replacing `login.php`), a
login-required dashboard, and a legacy-user import. Everything was validated by
running it (14 tests pass; full login→dashboard flow exercised).

## What's new in `pam_django/`

```
fds/
├── services/calculations.py   # FD math ported from ajax.php + new_fd.php (pure, tested)
├── hashers.py                 # transitional SHA1 hasher (auto-upgrades on login)
├── views.py                   # login-required dashboard
├── templates/
│   ├── base.html              # responsive layout
│   ├── registration/login.html
│   └── fds/dashboard.html     # summary stats + upcoming maturities
├── management/commands/
│   └── import_legacy_users.py # auth_users -> Django auth_user
└── tests.py                   # 14 tests (calculations + auth)
config/
├── settings.py                # + auth, password hashers, security headers
└── urls.py                    # login / logout / dashboard / admin
```

## Calculators (ports of the legacy logic)

`fds/services/calculations.py` provides framework-free functions:

- `maturity_date(start, period, unit)` — mirrors the legacy `mktime()` date math, including month-day overflow (31 Jan + 1 month → 3 Mar).
- `total_interest(principal, rate, period, unit, interest_type=...)` — exact port of `get_total_interest()`.
- `maturity_amount(...)` — principal + interest as a `Decimal`.
- `monthly_payout()` / `quarterly_payout()` — ports of the `new_fd.php` payout helpers.

These are covered by unit tests with hand-computed expected values (simple and compound interest, date overflow, payouts).

### Correction to the `interest_type` field (from Phase 2's open question)

The legacy formulas reveal `interest_type` is **the number of compounding periods per year**, not a simple/compound flag:

- `0` → simple interest
- `n` → compound, `principal × (1 + rate/n/100) ^ (n × years)` (1 = annual, 2 = half-yearly, 4 = quarterly, 12 = monthly)

The model was corrected accordingly (`FixedDeposit.interest_type` is now a `PositiveSmallIntegerField` with this meaning, migration `0002`), and the calculators implement it exactly.

## Authentication (replaces `login.php`)

- Uses Django's built-in `LoginView` / `LogoutView`, sessions, CSRF, and password validators — secure hashing (PBKDF2) for free.
- **Transitional login:** `fds/hashers.py` verifies the legacy unsalted SHA1 hashes (stored as `legacy_sha1$$…`). On the first successful login Django re-hashes the password to PBKDF2 automatically, so legacy hashes disappear over time — the same upgrade-on-login strategy used in the Phase 1 PHP fix.
- Security settings added: HttpOnly/SameSite cookies, `X_FRAME_OPTIONS=DENY`, content-type nosniff, and SSL redirect/secure cookies gated behind `DJANGO_SECURE_SSL` for production.

## Dashboard

A `login_required` dashboard at `/dashboard/` showing active-FD count, holders, schemes, total active principal/maturity, and the next 10 upcoming maturities — proving the stack works end-to-end against the normalized models.

## Importing legacy users

```bash
LEGACY_DB_NAME=pam LEGACY_DB_USER=... LEGACY_DB_PASSWORD=... \
    python manage.py import_legacy_users
```
Idempotent; maps `is_super` → `is_staff`/`is_superuser`, and stores the SHA1 hash in transitional format so existing passwords keep working.

## Run it

```bash
cd pam_django
pip install -r requirements.txt
cp .env.example .env          # set DJANGO_SECRET_KEY, DB_*, LEGACY_DB_*
python manage.py migrate
python manage.py import_legacy_users     # optional: bring over logins
python manage.py migrate_legacy          # optional: bring over FD data (Phase 2)
python manage.py runserver
# visit http://127.0.0.1:8000/  -> login -> dashboard
```

For a quick local check without PostgreSQL: `DB_ENGINE=sqlite python manage.py migrate && ... runserver`.

## Verification done

- `manage.py check` clean.
- **14 tests pass**: calculator correctness (dates, simple/compound interest, payouts) and auth (legacy SHA1 login, auto-upgrade to PBKDF2, wrong-password rejection, dashboard login-required + renders).
- Full HTTP flow exercised via the test client: `GET /login/` (200) → `POST` credentials → redirect to `/dashboard/` (200) → hash upgraded to `pbkdf2_sha256`.

## Next (Phase 4)

Port the remaining pages route-by-route (FD list, create/edit/renew/delete, the reports) as Django views/DRF endpoints, reusing these services, and cut each one over from PHP behind the reverse proxy.
