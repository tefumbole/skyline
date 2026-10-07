#!/usr/bin/env bash
# Run ON THE VPS — copies WasenderAPI settings from Manukeza → AlphaBridge API .env
set -euo pipefail

ABT_ROOT="${ABT_ROOT:-/var/www/alphabridge}"
ABT_ENV="$ABT_ROOT/apps/api/.env"

find_manukeza_env() {
  local candidates=(
    /var/www/manukeza/apps/api/.env
    /var/www/Manukeza/apps/api/.env
    /home/*/manukeza/apps/api/.env
  )
  local path
  for path in "${candidates[@]}"; do
    [[ -f "$path" ]] && { echo "$path"; return 0; }
  done
  find /var/www -maxdepth 5 -type f -path '*/apps/api/.env' 2>/dev/null \
    | grep -i manukeza | head -1
}

MANUKEZA_ENV="${MANUKEZA_ENV:-$(find_manukeza_env || true)}"

if [[ -z "$MANUKEZA_ENV" || ! -f "$MANUKEZA_ENV" ]]; then
  echo "ERROR: Manukeza apps/api/.env not found."
  echo "Set MANUKEZA_ENV=/path/to/manukeza/apps/api/.env and re-run."
  exit 1
fi

if [[ ! -f "$ABT_ENV" ]]; then
  echo "ERROR: AlphaBridge API .env missing: $ABT_ENV"
  exit 1
fi

echo "Source: $MANUKEZA_ENV"
echo "Target: $ABT_ENV"

KEY=$(grep -E '^WASENDER_API_KEY=' "$MANUKEZA_ENV" | head -1 | cut -d= -f2- || true)
BASE=$(grep -E '^WASENDER_BASE_URL=' "$MANUKEZA_ENV" | head -1 | cut -d= -f2- || true)
SESSION=$(grep -E '^WASENDER_SESSION_ID=' "$MANUKEZA_ENV" | head -1 | cut -d= -f2- || true)

if [[ -z "$KEY" ]]; then
  echo "ERROR: WASENDER_API_KEY not found in $MANUKEZA_ENV"
  exit 1
fi

BASE="${BASE:-https://wasenderapi.com/api}"

cp "$ABT_ENV" "${ABT_ENV}.bak.$(date +%Y%m%d%H%M%S)"

# Remove old Wasender lines, append fresh ones
grep -v -E '^(WASENDER_|# Wasender)' "$ABT_ENV" > "${ABT_ENV}.tmp" || true
mv "${ABT_ENV}.tmp" "$ABT_ENV"

cat >> "$ABT_ENV" << EOF

# WasenderAPI — copied from Manukeza ($(date -Iseconds))
WASENDER_API_KEY=${KEY}
WASENDER_BASE_URL=${BASE}
WASENDER_DEFAULT_COUNTRY=CM
EOF

if [[ -n "$SESSION" ]]; then
  echo "WASENDER_SESSION_ID=${SESSION}" >> "$ABT_ENV"
fi

# Optional: mirror to frontend .env for announcement compose (not required for login OTP)
ROOT_ENV="$ABT_ROOT/.env"
if [[ -f "$ROOT_ENV" ]]; then
  cp "$ROOT_ENV" "${ROOT_ENV}.bak.$(date +%Y%m%d%H%M%S)"
  grep -v -E '^(VITE_WASENDER_|# Wasender)' "$ROOT_ENV" > "${ROOT_ENV}.tmp" || true
  mv "${ROOT_ENV}.tmp" "$ROOT_ENV"
  cat >> "$ROOT_ENV" << EOF

# WasenderAPI — copied from Manukeza (frontend announcements)
VITE_WASENDER_API_KEY=${KEY}
VITE_WASENDER_BASE_URL=${BASE}
VITE_DEFAULT_PHONE_COUNTRY=CM
EOF
  echo "Updated frontend .env (rebuild needed for VITE_* changes)"
fi

echo ""
echo "Wasender configured. Key prefix: ${KEY:0:4}***"
echo "Restarting API..."
pm2 restart alphabridge-api --update-env
pm2 save

echo ""
echo "Test OTP send:"
echo '  TOKEN=$(curl -s -X POST https://alpha-bridge.net/api/auth/login -H "Content-Type: application/json" -d '"'"'{"email":"admin@alpha-bridge.net","password":"system"}'"'"' | python3 -c "import sys,json; print(json.load(sys.stdin)[\"session\"][\"access_token\"])")'
echo '  curl -s -X POST https://alpha-bridge.net/api/auth/otp/send -H "Authorization: Bearer $TOKEN"'
