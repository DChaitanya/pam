# Phase 4 — Feature Migration (PHP pages → Django)

Ports the legacy pages into Django views that reuse the Phase 2 models and Phase 3
services. Validated by running it: **22 tests pass** and every page renders.

## Page-by-page status

| Legacy page | New route | View | Status |
|-------------|-----------|------|--------|
| `login.php` / `logout.php` | `/login/`, `/logout/` | Django auth | ✅ (Phase 3) |
| `index.php` | `/fds/` | `fd_list` — filter (holder/scheme/month), sort, paginate | ✅ |
| `new_fd.php` | `/fds/new/` | `fd_create` | ✅ |
| `edit_fd.php` | `/fds/<id>/edit/` | `fd_edit` | ✅ |
| `renew_fd.php` | `/fds/<id>/renew/` | `fd_renew` (writes history) | ✅ |
| `delete_fd.php` | `/fds/<id>/close/` | `fd_close` (soft close + history) | ✅ |
| `namewise_list.php` | `/reports/namewise/` | `report_namewise` | ✅ |
| `monthwise_list.php` | `/reports/monthwise/` | `report_monthwise` | ✅ |
| `fy_report.php` | `/reports/fy/` | `report_fy` | ✅ |
| `on_date_fd_details.php` | `/reports/on-date/` | `report_on_date` | ✅ |
| `ajax.php` | `/api/calc/maturity-date/`, `/api/calc/interest/` | JSON endpoints | ✅ |
| `monthly_investment.php` / `graphs.php` | — | charts | ⏳ deferred |

## Key design choices

**Server-side calculation.** `FixedDepositForm` collects only the inputs (holder,
scheme, dates, period, rate, interest_type, principal) and computes
`maturity_date`, `total_interest`, and `maturity_amount` on save via the Phase 3
services. The browser never supplies derived money values, so they can't be
tampered with — a correctness/security improvement over the legacy forms, which
trusted hidden fields populated by `ajax.php`.

**Close instead of hard delete.** `delete_fd.php` physically deleted the row.
`fd_close` instead writes an `FDHistory` snapshot and sets `status = CLOSED`,
preserving the audit trail (the data is retained, not destroyed).

**Renew** snapshots the current FD into `FDHistory`, then rolls the term forward
from the maturity date and recomputes the figures — matching the legacy intent
with referential integrity.

**Calculator endpoints** replace `ajax.php` and reuse the exact same service
functions the forms use, so the live preview and the saved values can never drift.

## Tests (22 total)

- Phase 3 (14): calculators + auth.
- Phase 4 (8): create computes derived fields; zero-period rejected; close writes
  history + sets status; renew snapshots and rolls forward +12 months; list filters
  by holder; both calculator endpoints return correct JSON; views require login.

All pages also smoke-tested for HTTP 200 (dashboard, FD list, create form, all four reports).

## Run it

```bash
cd pam_django
DB_ENGINE=sqlite python manage.py migrate      # or PostgreSQL via .env
DB_ENGINE=sqlite python manage.py test fds     # 22 tests
DB_ENGINE=sqlite python manage.py runserver
# log in, then: /fds/ (list), /fds/new/ (create), /reports/* (reports)
```

## Still open

- **Charts** (`monthly_investment.php` / `graphs.php`) — the data aggregations exist
  (see the month/holder reports); only the Highcharts→Chart.js rendering remains.
- The list/report filters mirror the legacy ones; the legacy "joint account" name
  groupings in `index.php` (hardcoded holder-ID unions) were intentionally dropped —
  reintroduce as data (a holder group) if still needed.
- Production hardening, CI, and the reverse-proxy cutover are Phase 6.
