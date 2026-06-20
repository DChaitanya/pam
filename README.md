# PAM — Personal Accounting Management

A PHP web application for tracking bank **Fixed Deposits (FDs)** — recording deposits, calculating maturity dates and interest, and generating financial reports. Built for Indian rupee fixed deposits (timezone `Asia/Kolkata`, `en_IN` currency formatting, financial-year reporting).

## Tech Stack

- **Backend:** PHP (procedural), MySQLi
- **Database:** MySQL / MariaDB (MyISAM tables)
- **Frontend:** HTML 4.0, jQuery, Highcharts 7.1.1, jQuery UI Datepicker
- **Server:** Apache (`mod_php`)
- **IDE:** Eclipse PDT (`.project` / `.buildpath`)

There is no framework — each page is a standalone PHP file that shares a common header/footer and talks to MySQL through a single `db` class.

## Architecture

Authentication is session-based: `login.php` validates credentials against the `auth_users` table, sets `$_SESSION` values, and every page guards on the logged-in session. All database access goes through the `db` class in `db_connect.php`, which handles connect/query/insert and logs SQL and errors to flat files.

```
pam/
├── db_connect.php          # db class — connect/query/insert, file-based query + error logging
├── login.php / logout.php  # Session auth against auth_users
├── header.php / footer.php # Shared layout and navigation menu
│
├── index.php               # FD List (main dashboard)
├── new_fd.php              # Create a new FD entry
├── edit_fd.php             # Edit an FD
├── renew_fd.php            # Renew a matured FD
├── delete_fd.php           # Delete an FD
├── on_date_fd_details.php  # FDs active on a given date
├── namewise_list.php       # FDs grouped by account holder
├── monthwise_list.php      # FDs grouped by month
├── fy_report.php           # Financial-year report
├── monthly_investment.php  # Investment report (Highcharts with drilldown)
├── graphs.php              # Charts
├── ajax.php                # AJAX endpoint: maturity-date + interest calculators
│
├── pma_db.sql / pam_dev.sql # MySQL schema dumps (production + development)
├── media/
│   ├── css/                # main.css, datepicker styles
│   ├── js/                 # jquery, highcharts 7.1.1, fd_cal.js, datepicker
│   └── images/             # logos, calendar and sort icons
└── query_log.txt / error_log.txt  # Runtime SQL and error logs
```

## Pages

| Page | Purpose |
|------|---------|
| `index.php` | Main dashboard listing all fixed deposits |
| `new_fd.php` | Create a new FD record |
| `edit_fd.php` | Edit an existing FD |
| `renew_fd.php` | Renew a matured FD |
| `delete_fd.php` | Delete an FD |
| `on_date_fd_details.php` | FDs active on a specific date |
| `namewise_list.php` | FDs grouped by account holder |
| `monthwise_list.php` | FDs grouped by month |
| `fy_report.php` | Financial-year report |
| `monthly_investment.php` | Investment report with interactive charts |
| `graphs.php` | Graphical charts |
| `ajax.php` | Server-side maturity-date and interest calculations |

## Data Model

The core table is **`accounts`** — one row per fixed deposit:

| Column | Description |
|--------|-------------|
| `name` | Account holder (references `acc_users`) |
| `deposite_scheme` | Scheme type (references `deposite_schemes`) |
| `deposite_date` / `renewal_date` / `maturity_date` | Key FD dates |
| `period` / `period_type` | Term length and unit (day / month / year) |
| `rate_of_interest` | Interest rate (%) |
| `interest_type` | Simple vs. compound interest |
| `deposite_amount` | Principal |
| `total_interest` / `maturity_amount` | Calculated returns |
| `is_active` | Active flag |

Supporting tables:

- `acc_users` — FD account holders
- `deposite_schemes` — FD/RD scheme types
- `auth_users` — application login users

Several additional tables (`monthly_investment`, `name_wise_details`, `schemewise_amount_details`, and others) hold pre-computed report and summary data.

## Setup

1. Create a MySQL database named `pam` (or `pam_dev` for development).
2. Import the schema:
   ```bash
   mysql -u root -p pam < pma_db.sql
   ```
3. Configure the database connection in `db_connect.php` (host, username, password, database name).
4. Deploy the project under an Apache document root with PHP enabled.
5. Access the app via `login.php` / `index.php`.

## Known Issues & Technical Debt

- **SQL injection risk:** Queries are built with raw string interpolation (e.g. `username='$username'` in `login.php`). Migrating to prepared statements is strongly recommended.
- **Weak password hashing:** Passwords use SHA1. Consider `password_hash()` / `password_verify()`.
- **Hardcoded credentials:** Database credentials are stored directly in `db_connect.php`. Move them to an environment-based config that is excluded from version control.
- **Aging stack:** Uses procedural `mysqli`, a `mod_php5` directive in `.htaccess`, and an HTML 4.0 doctype — targets old PHP/Apache versions.
- **Duplicate / backup files:** The repository root contains numerous old copies that are not part of the live app and should be archived or removed, including:
  - `index222.php`, `index55.php`, `index6666.php`
  - `*666.php` variants (`header666.php`, `fy_report666.php`, etc.)
  - `delete_fd222.php`, `delete_fd55.php`, `new_test66.php`
  - `monthly_investment.php.orig`

## License

Internal / proprietary. No license specified.
