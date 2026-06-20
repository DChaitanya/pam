#!/usr/bin/env bash
#
# PAM — full database backup (schema + data)
# Phase 0 safety net. Run on a host that can reach the MySQL server.
#
# Usage:
#   DB_HOST=127.0.0.1 DB_USER=pam_ro DB_PASS=secret DB_NAME=pam ./backup_db.sh
#
# Produces a timestamped, gzipped dump in ./backups/ and verifies it is non-empty.

set -euo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:?set DB_USER}"
DB_PASS="${DB_PASS:?set DB_PASS}"
DB_NAME="${DB_NAME:-pam}"

OUT_DIR="$(dirname "$0")/../backups"
mkdir -p "$OUT_DIR"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="$OUT_DIR/${DB_NAME}_${STAMP}.sql.gz"

echo "Dumping ${DB_NAME} from ${DB_HOST}:${DB_PORT} ..."

# --single-transaction is for InnoDB; this DB is MyISAM, so we add --lock-tables
# to get a consistent snapshot. --routines/--triggers/--events capture everything.
mysqldump \
  --host="$DB_HOST" --port="$DB_PORT" \
  --user="$DB_USER" --password="$DB_PASS" \
  --lock-tables \
  --routines --triggers --events \
  --add-drop-table --complete-insert --hex-blob \
  "$DB_NAME" | gzip -9 > "$OUT_FILE"

# Verify the dump is non-trivially sized and contains a CREATE statement.
SIZE=$(stat -c%s "$OUT_FILE" 2>/dev/null || stat -f%z "$OUT_FILE")
if [ "$SIZE" -lt 1024 ]; then
  echo "ERROR: backup looks too small (${SIZE} bytes). Aborting." >&2
  exit 1
fi
if ! gunzip -c "$OUT_FILE" | head -n 50 | grep -qi "CREATE TABLE"; then
  echo "ERROR: backup does not contain CREATE TABLE. Aborting." >&2
  exit 1
fi

echo "OK -> $OUT_FILE (${SIZE} bytes)"
echo "Restore with:  gunzip -c '$OUT_FILE' | mysql -u USER -p ${DB_NAME}"
