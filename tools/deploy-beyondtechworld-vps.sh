#!/usr/bin/env bash
# Deploy Beyond Enterprise to beyondtechworld.com — port 3004 (Alpha Bridge uses 3003)
# Usage: bash tools/deploy-beyondtechworld-vps.sh
set -euo pipefail

ROOT="${1:-/var/www/beyondtechworld}"
cd "$ROOT"

echo "==> 1. Ensure nginx owns port 80"
if systemctl is-active --quiet apache2 2>/dev/null; then
  systemctl stop apache2
  systemctl disable apache2
fi
systemctl start nginx || systemctl restart nginx

echo "==> 2. Pull latest code"
git pull || { echo "git pull failed — fix conflicts first"; exit 1; }

echo "==> 3. API env (separate DB from alpha-bridge)"
if [[ ! -f apps/api/.env ]]; then
  cp apps/api/.env.beyondtechworld.example apps/api/.env
  echo "    Created apps/api/.env — create Hostinger DB and set DB_PASSWORD, then re-run"
  exit 1
fi
# Ensure port 3004
grep -q '^PORT=' apps/api/.env && sed -i 's/^PORT=.*/PORT=3004/' apps/api/.env || echo 'PORT=3004' >> apps/api/.env

echo "==> 4. Install & migrate (separate database)"
cd apps/api && npm install && cd ../..
bash tools/setup-beyondtechworld-db.sh apps/api/.env || {
  echo "    Database not ready — create u152889834_beyondtechworld in Hostinger hPanel first"
  exit 1
}

echo "==> 5. Frontend env + build (Beyond Enterprise branding)"
cp tools/env/beyondtechworld.production.env .env
npm install
npm run build

echo "==> 6. Start API on port 3004 (PM2) — does NOT stop alphabridge-api"
npm install -g pm2 2>/dev/null || true
pm2 delete beyondtechworld-api 2>/dev/null || true
pm2 delete ecosystem.beyondtechworld 2>/dev/null || true
PORT=3004 pm2 start src/main.js --name beyondtechworld-api --cwd "$ROOT/apps/api"
pm2 save

sleep 2
curl -sf http://127.0.0.1:3004/health || { echo "API health check failed on :3004"; pm2 logs beyondtechworld-api --lines 20 --nostream; exit 1; }
echo "    Beyond Enterprise API OK on :3004"

echo "==> 7. SSL certificate (if missing)"
if [[ ! -f /etc/letsencrypt/live/beyondtechworld.com/fullchain.pem ]]; then
  install -m 644 "$ROOT/tools/nginx/beyondtechworld-http.conf" /etc/nginx/sites-available/beyondtechworld
  ln -sf /etc/nginx/sites-available/beyondtechworld /etc/nginx/sites-enabled/beyondtechworld
  nginx -t && systemctl reload nginx
  certbot certonly --webroot -w /var/www/letsencrypt \
    -d beyondtechworld.com -d www.beyondtechworld.com \
    --non-interactive --agree-tos -m admin@beyondtechworld.com || true
fi

echo "==> 8. Nginx — apply all VPS vhost configs"
bash "$ROOT/tools/vps-apply-nginx.sh" "$ROOT"

echo ""
echo "Beyond Enterprise deploy complete."
echo "  https://beyondtechworld.com"
echo "  API: http://127.0.0.1:3004/health"
