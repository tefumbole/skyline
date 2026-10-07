#!/usr/bin/env bash
# Copy full Alpha Bridge Hostinger MySQL database → Beyond Enterprise database.
# Run ON the VPS. Overwrites all tables in the target database.
set -euo pipefail

STAMP="$(date +%Y%m%d-%H%M%S)"
DUMP="/tmp/alphabridge-to-beyond-${STAMP}.sql"
BACKUP_DIR="/var/www/beyondtechworld/backups"

SRC_HOST="${SRC_DB_HOST:-193.203.168.163}"
SRC_USER="${SRC_DB_USER:-u152889834_alphabridge}"
SRC_PASS="${SRC_DB_PASSWORD:-U152889834_alphabridge}"
SRC_NAME="${SRC_DB_NAME:-u152889834_alphabridge}"

TGT_HOST="${TGT_DB_HOST:-193.203.168.163}"
TGT_USER="${TGT_DB_USER:-u152889834_beyondworld}"
TGT_PASS="${TGT_DB_PASSWORD:-U152889834_beyondworld}"
TGT_NAME="${TGT_DB_NAME:-u152889834_beyondworld}"

mkdir -p "$BACKUP_DIR"

dump_db() {
  local host="$1" user="$2" pass="$3" name="$4" out="$5"
  mysqldump -h "$host" -u "$user" -p"$pass" "$name" \
    --single-transaction --routines --triggers \
    > "$out" 2>/dev/null \
    || mysqldump -h "$host" -u "$user" -p"$pass" "$name" \
    --single-transaction \
    > "$out"
}

echo "==> 1. Backup current Beyond database (if any tables exist)..."
if mysql -h "$TGT_HOST" -u "$TGT_USER" -p"$TGT_PASS" "$TGT_NAME" -e "SHOW TABLES" 2>/dev/null | grep -q .; then
  dump_db "$TGT_HOST" "$TGT_USER" "$TGT_PASS" "$TGT_NAME" "$BACKUP_DIR/beyondworld-before-import-${STAMP}.sql"
  echo "    Saved backup to $BACKUP_DIR/beyondworld-before-import-${STAMP}.sql"
fi

echo "==> 2. Dump Alpha Bridge database ($SRC_NAME)..."
dump_db "$SRC_HOST" "$SRC_USER" "$SRC_PASS" "$SRC_NAME" "$DUMP"

BYTES=$(wc -c < "$DUMP" | tr -d ' ')
echo "    Dump size: $(du -h "$DUMP" | cut -f1) ($BYTES bytes)"

echo "==> 3. Import into Beyond database ($TGT_NAME)..."
mysql -h "$TGT_HOST" -u "$TGT_USER" -p"$TGT_PASS" "$TGT_NAME" < "$DUMP"

echo "==> 4. Copy uploads from Alpha Bridge (if present)..."
if [[ -d /var/www/alphabridge/apps/api/uploads ]]; then
  mkdir -p /var/www/beyondtechworld/apps/api/uploads
  rsync -a /var/www/alphabridge/apps/api/uploads/ /var/www/beyondtechworld/apps/api/uploads/
  echo "    Uploads synced"
fi

echo "==> 5. Seed default admin (username: admin, password: system)..."
cd /var/www/beyondtechworld
export SEED_ADMIN_USERNAME=admin
export SEED_ADMIN_EMAIL=admin@beyondtechworld.com
export SEED_ADMIN_PASSWORD=system
export SEED_ADMIN_PHONE=+237675321739
export SEED_ADMIN_NAME=Administrator
npm run db:seed-admin --prefix apps/api

echo ""
echo "Import complete."
echo "  Login: admin / system"
echo "  Phone: +237675321739"
echo "  Dump:  $DUMP"
