#!/usr/bin/env bash
# Verify Beyond Enterprise Hostinger MySQL database exists, then migrate.
# Create the database in hPanel first if this script reports access denied.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

ENV_FILE="${1:-apps/api/.env}"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE — copy apps/api/.env.beyondtechworld.example and set DB_PASSWORD"
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

echo "==> Testing MySQL connection to ${DB_NAME}@${DB_HOST}..."
if ! mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "SELECT 1" >/dev/null 2>&1; then
  cat <<EOF

ERROR: Cannot connect to database "$DB_NAME".

Create a NEW database in Hostinger hPanel (separate from alpha-bridge):
  1. hPanel → Databases → MySQL Databases → Create database
  2. Database name: beyondworld (becomes u152889834_beyondworld)
  3. Create user u152889834_beyondworld with ALL privileges on that database
  4. Add VPS IP (187.124.2.238) to Remote MySQL allowlist
  5. Update apps/api/.env with DB_USER, DB_NAME, DB_PASSWORD
  6. Re-run: bash tools/setup-beyondtechworld-db.sh

EOF
  exit 1
fi

echo "    Database OK — running migrations..."
npm run db:migrate
echo "    Beyond Enterprise database ready."
