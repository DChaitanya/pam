# Phase 5 — Responsive Frontend & Charts

Makes the Django UI mobile-first and replaces the legacy Highcharts 7.1.1 with
Chart.js. Validated by running it: **23 tests pass** and all pages render.

## What changed (all in `pam_django/fds/templates/`)

**Mobile-first base layout (`base.html`).**
- `<meta viewport>` (already present) plus a fluid, mobile-first stylesheet.
- **CSS-only hamburger nav** — collapses to a toggle under 760px with no JavaScript (a checkbox + label), so navigation works even if JS is disabled.
- Accessible forms: every control has a `<label>`, visible focus outlines, and 44px minimum touch targets.

**Responsive tables.**
- Every data table (`fd_list`, all four reports, dashboard) uses `class="responsive"` with `data-label` on each cell.
- On phones the table header is hidden and each row reflows into a labelled card (label on the left, value on the right) — no horizontal scrolling. On desktop it stays a normal table.

**Charts page (`/reports/charts/`) — replaces `monthly_investment.php` + `graphs.php`.**
- `report_charts` view aggregates active-FD maturity value by month and principal by holder (top 10) via the ORM, and emits the data with Django's `json_script` (safe, no inline-data injection).
- The template renders a bar chart (maturity by month) and a doughnut (principal by holder) using **Chart.js 4.4.1** from cdnjs, with ₹ Indian-format tooltips.
- Graceful degradation: a `<noscript>` block links to the equivalent By Month / By Holder tables.

**Native date pickers.** Date fields use `<input type="date">` (browser-native), retiring the legacy jQuery UI datepicker.

## Why this is an improvement

- No jQuery / jQuery-UI / Highcharts dependencies — a single small CDN script (Chart.js) replaces ~640 KB of bundled legacy JS.
- Works on phones without horizontal scrolling; navigation and charts both degrade gracefully without JS.
- Chart data comes from the same normalized ORM queries as the table reports, so the charts can't drift from the numbers.

## Tests / validation

- 23 tests pass (the new one asserts the charts page renders with both JSON data blocks and the Chart.js script).
- Smoke-tested HTTP 200 on dashboard, FD list, create form, charts, and all four reports.
- Chart **rendering** is client-side (Chart.js in the browser), so verify visually on staging; the server-side data and page were validated here.

## Status against the plan

Phase 5 (responsive frontend) is complete, including the chart migration that was
deferred from Phase 4. Remaining: **Phase 6** — test/CI pipeline, `manage.py check
--deploy` hardening, containerization, and the reverse-proxy cutover that retires
the PHP app.
