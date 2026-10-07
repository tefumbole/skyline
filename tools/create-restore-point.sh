#!/usr/bin/env bash
# Tag the current commit as an ERP restore point (reads version from appVersion.js).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

VERSION="$(node -e "import('./src/constants/appVersion.js').then(m=>process.stdout.write(m.APP_VERSION))")"
COMMIT="$(git rev-parse --short HEAD)"
DATE="$(date +%Y-%m-%d)"

if git rev-parse "$VERSION" >/dev/null 2>&1; then
  echo "Tag $VERSION already exists. Delete it first or bump APP_VERSION."
  exit 1
fi

git tag -a "$VERSION" -m "Restore point $VERSION ($DATE) at $COMMIT"

echo "Created git tag: $VERSION -> $COMMIT"
echo "Push with: git push origin $VERSION"
