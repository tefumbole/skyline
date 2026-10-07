#!/usr/bin/env bash
# Apply latest AlphaBridge code to local MySQL + build frontend.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
  cp .env.local.example .env
  echo "Created .env from .env.local.example"
fi

if [[ ! -f apps/api/.env ]]; then
  cp apps/api/.env.local.example apps/api/.env
  echo "Created apps/api/.env from apps/api/.env.local.example"
fi

mysql_ready() {
  local port="$1"
  mysql -u abt -palphabridge_local -h 127.0.0.1 -P "$port" -e "SELECT 1" >/dev/null 2>&1
}

DB_PORT="$(grep '^DB_PORT=' apps/api/.env | cut -d= -f2 || echo 3306)"
if ! mysql_ready "$DB_PORT"; then
  if command -v docker >/dev/null 2>&1; then
    echo "Starting Docker MySQL..."
    docker compose up -d
    for _ in {1..30}; do
      if mysql_ready 3307; then
        if grep -q '^DB_PORT=' apps/api/.env; then
          sed -i '' 's/^DB_PORT=.*/DB_PORT=3307/' apps/api/.env
        else
          echo 'DB_PORT=3307' >> apps/api/.env
        fi
        DB_PORT=3307
        break
      fi
      sleep 1
    done
  fi
fi

DB_PORT="$(grep '^DB_PORT=' apps/api/.env | cut -d= -f2 || echo 3306)"
if ! mysql_ready "$DB_PORT"; then
  echo "ERROR: Local MySQL not reachable on port ${DB_PORT}."
  echo "Run: bash tools/setup-local-mysql.sh   OR   docker compose up -d"
  exit 1
fi

VERSION="$(node -e "import('./src/constants/appVersion.js').then(m=>process.stdout.write(m.APP_VERSION))")"

echo "==> 1. Install dependencies"
npm install
cd apps/api && npm install && cd ../..

echo "==> 2. Migrate local database"
npm run db:migrate

echo "==> 3. Build frontend (${VERSION})"
npm run build

echo ""
echo "============================================"
echo "  Local deploy complete — ${VERSION}"
echo "  Start dev:  npm run dev:local"
echo "  Or:         npm run dev:api & npm run dev"
echo "  Frontend:   http://localhost:3000"
echo "  API:        http://localhost:3003"
echo "  Login shows version on screen"
echo "============================================"
