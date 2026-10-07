#!/usr/bin/env bash
# Backup local AlphaBridge database + uploads for the current ERP version.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

VERSION="$(node -e "import('./src/constants/appVersion.js').then(m=>process.stdout.write(m.APP_VERSION))")"
STAMP="$(date +%Y%m%d-%H%M%S)"
COMMIT="$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"

DB_HOST="${LOCAL_DB_HOST:-127.0.0.1}"
DB_PORT="${LOCAL_DB_PORT:-$(grep '^DB_PORT=' apps/api/.env 2>/dev/null | cut -d= -f2 || echo 3306)}"
DB_USER="${LOCAL_DB_USER:-abt}"
DB_PASSWORD="${LOCAL_DB_PASSWORD:-alphabridge_local}"
DB_NAME="${LOCAL_DB_NAME:-alphabridge}"

mkdir -p backups

SQL_FILE="backups/local-${VERSION}-${STAMP}.sql"
MANIFEST="backups/local-${VERSION}-${STAMP}.manifest.txt"
UPLOADS_TAR="backups/local-uploads-${VERSION}-${STAMP}.tar.gz"

echo "==> Dumping local database (${DB_NAME} on :${DB_PORT})..."
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
  --single-transaction --routines --triggers --set-gtid-purged=OFF --no-tablespaces \
  > "$SQL_FILE"

BYTES=$(wc -c < "$SQL_FILE" | tr -d ' ')
if [[ "$BYTES" -lt 1000 ]]; then
  echo "ERROR: Backup dump too small (${BYTES} bytes)."
  exit 1
fi

if [[ -d apps/api/uploads ]]; then
  echo "==> Archiving uploads..."
  tar -czf "$UPLOADS_TAR" -C apps/api uploads
else
  UPLOADS_TAR=""
fi

cat > "$MANIFEST" <<EOF
Alpha Bridge local system backup
Version: ${VERSION}
Git commit: ${COMMIT}
Created: ${STAMP}
Database dump: $(basename "$SQL_FILE")
Uploads archive: $(basename "${UPLOADS_TAR:-none}")
Restore DB:
  mysql -h ${DB_HOST} -P ${DB_PORT} -u ${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < ${SQL_FILE}
Restore uploads:
  tar -xzf ${UPLOADS_TAR:-local-uploads-*.tar.gz} -C apps/api
Git restore point tag: ${VERSION}
EOF

echo ""
echo "============================================"
echo "  Local backup complete — ${VERSION}"
echo "  SQL:       ${SQL_FILE} ($(du -h "$SQL_FILE" | cut -f1))"
if [[ -n "$UPLOADS_TAR" ]]; then
  echo "  Uploads:   ${UPLOADS_TAR} ($(du -h "$UPLOADS_TAR" | cut -f1))"
fi
echo "  Manifest:  ${MANIFEST}"
echo "============================================"
