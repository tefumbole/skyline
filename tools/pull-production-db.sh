#!/usr/bin/env bash
# Pull production database from VPS (Hostinger MySQL) into local MySQL.
# Requires: SSH access to VPS (default host: myvps), local MySQL on port 3306.
#
# Usage:
#   bash tools/pull-production-db.sh
#   SSH_HOST=root@187.124.2.238 bash tools/pull-production-db.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SSH_HOST="${SSH_HOST:-myvps}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/alphabridge}"
LOCAL_DB_HOST="${LOCAL_DB_HOST:-127.0.0.1}"
LOCAL_DB_PORT="${LOCAL_DB_PORT:-3306}"
LOCAL_DB_USER="${LOCAL_DB_USER:-abt}"
LOCAL_DB_PASSWORD="${LOCAL_DB_PASSWORD:-alphabridge_local}"
LOCAL_DB_NAME="${LOCAL_DB_NAME:-alphabridge}"

mkdir -p data/import
STAMP="$(date +%Y%m%d-%H%M%S)"
DUMP_FILE="data/import/production-${STAMP}.sql"

echo "==> Dumping production database via ${SSH_HOST}..."
# --column-statistics=0: Hostinger MySQL lacks information_schema.COLUMN_STATISTICS (MySQL 8 client vs older server)
ssh "$SSH_HOST" "set -a && source ${REMOTE_DIR}/apps/api/.env && set +a && mysqldump \
  -h \"\$DB_HOST\" -P \"\${DB_PORT:-3306}\" -u \"\$DB_USER\" -p\"\$DB_PASSWORD\" \"\$DB_NAME\" \
  --column-statistics=0 \
  --single-transaction --routines --triggers --set-gtid-purged=OFF" > "$DUMP_FILE"

BYTES=$(wc -c < "$DUMP_FILE" | tr -d ' ')
if [ "$BYTES" -lt 1000 ]; then
  echo "ERROR: Dump looks too small (${BYTES} bytes). Check SSH and DB credentials on VPS."
  exit 1
fi

echo "    Saved $(du -h "$DUMP_FILE" | cut -f1) to $DUMP_FILE"

# Hostinger/MariaDB dumps use DEFAULT uuid(); Homebrew MySQL 8+ needs DEFAULT (UUID())
if grep -q 'DEFAULT uuid()' "$DUMP_FILE"; then
  echo "==> Normalizing UUID defaults for local MySQL..."
  TMP="${DUMP_FILE}.tmp"
  sed 's/DEFAULT uuid()/DEFAULT (UUID())/g' "$DUMP_FILE" > "$TMP" && mv "$TMP" "$DUMP_FILE"
fi

echo ""
echo "==> Importing into local MySQL (${LOCAL_DB_NAME} on :${LOCAL_DB_PORT})..."
mysql -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASSWORD" \
  -e "DROP DATABASE IF EXISTS \`${LOCAL_DB_NAME}\`; CREATE DATABASE \`${LOCAL_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASSWORD" \
  "$LOCAL_DB_NAME" < "$DUMP_FILE"

echo ""
echo "==> Applying schema patches + seed defaults..."
npm run db:migrate

echo ""
echo "==> Resetting local admin password (so you can log in locally)..."
npm run db:reset-admin

echo ""
echo "============================================"
echo "  Production data imported to local DB"
echo "  Dump file: $DUMP_FILE"
echo "  Local login after import:"
echo "    admin@alpha-bridge.net / ChangeMe@123456"
echo "  (Production password is NOT copied for security)"
echo "============================================"
