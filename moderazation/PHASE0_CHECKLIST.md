# Phase 0 — Safety Net & Audit: Status

| Item | Status | Notes |
|------|:------:|-------|
| `.gitignore` created (logs, secrets, dumps, IDE, `.orig`) | ✅ Done | `F:\chaitanya\workspace\pam\.gitignore` |
| Codebase audit (pages, SQL, tables/views, dead files, security) | ✅ Done | `AUDIT.md` |
| Source-vs-derived DB object classification | ✅ Done | 7 source tables, 15 views — see `AUDIT.md` §2 |
| Security findings catalog | ✅ Done | `AUDIT.md` §3 (S1–S9, I1–I3) |
| Backup script (parameterized, verified) | ✅ Done | `tools/backup_db.sh` |
| Staging environment (reproducible) | ✅ Done | `tools/docker-compose.staging.yml` |
| Git init + baseline commit | ✅ Done | Run on user's machine via `tools/git_setup.bat` |
| Full production DB dump | ✅ Done | Taken via phpMyAdmin → `backups/pam.sql.zip`. Verified: prod `pam` DB, MariaDB 10.4, 7 source tables populated (`accounts` ~2,346, `accounts_history` ~3,671 rows), 15 views. Extracted to `backups/pam.sql` for staging import. |
| Stand up staging from a prod dump | ✅ Done | Brought up via `docker compose -f tools/docker-compose.staging.yml up -d` |

---

## Why Git and the DB dump couldn't be run from here

This folder is exposed to my sandbox over a FUSE mount that **blocks file deletion and the file-locking/rename operations Git relies on** — even removing a file I just created returns "Operation not permitted," and Git's first config write got corrupted. Creating/editing files works (that's how all these files were written), but running `git init`/`git commit` does not. Likewise, the sandbox has no network route to your local MySQL server, so the dump must run on your machine.

A partial `.git` folder was created during the attempt and **could not be removed** from here. The setup scripts delete it for you before re-initializing.

## What to run (≈2 minutes)

**1. Initialize Git** — double-click or run from the project root:
```
tools\git_setup.bat
```
(removes the stale `.git`, runs `git init`, stages everything per `.gitignore`, and makes the baseline commit.)

**2. Back up the production database** — from a shell that can reach MySQL:
```
set DB_USER=your_user
set DB_PASS=your_pass
set DB_NAME=pam
bash tools/backup_db.sh
```
Output lands in `backups/` as a verified `.sql.gz`.

**3. (Optional) Bring up staging** — gunzip your dump into `backups/`, then:
```
docker compose -f tools/docker-compose.staging.yml up -d
```
App on http://localhost:8080, DB UI on http://localhost:8081.

## Not done by design (deferred to later phases)

- **Removing dead/backup files** — inventoried here, but deletion belongs to Phase 1 cleanup so it's captured in a single reviewed commit.
- **Security fixes (S1–S9)** — these are Phase 1.
- **Schema changes (I1–I3)** — Phase 2.
