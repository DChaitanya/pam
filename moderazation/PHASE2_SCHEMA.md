# Phase 2 — Normalized Schema (Django + PostgreSQL)

The new normalized schema lives in `pam_django/` (a Django 5.1 project) beside the
legacy PHP app, per the strangler-fig plan. Phase 2 delivers the data model,
migrations, and a one-off legacy→normalized data-migration command.

## What's here

```
pam_django/
├── manage.py
├── requirements.txt          # Django, psycopg (PostgreSQL), mysqlclient (legacy read)
├── .env.example              # all configuration via environment
├── config/                   # project (settings, urls, wsgi/asgi)
│   └── settings.py           #   env-driven; PostgreSQL primary + optional legacy MySQL
└── fds/                      # the fixed-deposit domain app
    ├── models.py             # the normalized models
    ├── admin.py              # admin screens for every model
    ├── migrations/0001_initial.py
    └── management/commands/migrate_legacy.py   # data migration
```

## The normalized model

```
AccountHolder (acc_users)        Scheme (deposite_schemes)
   id, name, is_active              id, name, is_active
        │                                │
        │            ┌───────────────────┤
        │            │                   │
   FixedDeposit (accounts)          InterestSlab (interest_period)
   id, holder→, scheme→,             id, scheme→, holder_type,
   ref_id, deposit/renewal/          term_start, term_end,
   maturity_date, period,            interest_rate(DECIMAL)
   period_unit(d/m/y),
   rate_of_interest(DECIMAL),
   interest_type, principal_amount,
   total_interest, maturity_amount   (all DECIMAL),
   status(active/matured/renewed/closed),
   created_at, updated_at
        │
   FDHistory (accounts_history)
   id, fixed_deposit→, holder→, scheme→,
   holder_name_snapshot, legacy_account_id,
   <same value columns>, action, closed_date, changed_at
```

Every table also carries a `legacy_id` so the migration is idempotent and the
original integer keys can be rebuilt into real foreign keys.

## How this fixes the audit findings

| Finding | Legacy problem | Normalized fix |
|---------|----------------|----------------|
| I1 | money stored as `float` | every amount/rate is a `DecimalField` |
| I2 | MyISAM, no FKs/transactions | real FKs (`PROTECT`/`SET_NULL`) on PostgreSQL; migration runs in a transaction |
| I3 | holder was `int` in `accounts` but `varchar` in `accounts_history` | one `AccountHolder` FK everywhere; the old text name is preserved in `holder_name_snapshot` |
| — | stringly-typed flags (`'y'/'n'`, `'0'/'1'`) | `BooleanField` and `TextChoices`/`IntegerChoices` enums |
| — | 15 derived report **views/tables** duplicated data | dropped; they become ORM queries in later phases |
| — | hardcoded dates / scheme IDs baked into views | gone; filters become query parameters |

## Running it

1. **Install + configure**
   ```bash
   cd pam_django
   python -m venv .venv && source .venv/bin/activate
   pip install -r requirements.txt
   cp .env.example .env          # then edit DB_* and LEGACY_DB_* values
   ```

2. **Create the normalized PostgreSQL schema**
   ```bash
   python manage.py migrate
   ```

3. **Import the legacy data** (reads MySQL, writes PostgreSQL; idempotent)
   ```bash
   python manage.py migrate_legacy --dry-run   # preview, rolls back
   python manage.py migrate_legacy             # commit
   ```
   Expected volumes from the current backup: ~26 schemes, 17 holders, ~32 interest
   slabs, ~2,346 fixed deposits, ~3,671 history rows.

## Verification done in this phase

- `manage.py check` clean; `makemigrations`/`migrate` apply cleanly.
- Real inserts exercised on the models: FK integrity, `PROTECT` on holder delete,
  Decimal money round-tripping, and an `FDHistory` row — all pass.
- The `migrate_legacy` command loads and is discoverable; its `dec()`/`unit()`
  transforms are unit-checked. (The full legacy read needs a live MySQL + the
  `mysqlclient` driver, so run `--dry-run` against staging first.)

## Open question to confirm

`accounts.interest_type` was an unlabelled integer. The model keeps the raw
values with provisional labels `0=Simple`, `1=Compound` (`InterestType`). Please
confirm the intended meaning so the labels are correct before building reports on it.
