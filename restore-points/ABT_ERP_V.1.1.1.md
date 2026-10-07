# Restore Point: ABT_ERP_V.1.1.1

**Date:** 2026-06-07  
**Git tag:** `ABT_ERP_V.1.1.1`  
**Login version label:** `ABT_ERP_V.1.1.1`

## Snapshot includes

- MySQL backend with local/production sync tooling
- Signed shareholder agreements (3 approved on production after last data push)
- WhatsApp agreement send: text first, 6s delay, PDF via APP_URL fallback
- Global WhatsApp send queue (6s minimum between messages)
- French/English i18n with EN default and header/admin language switcher
- RBAC roles & permissions admin UI
- Training programs unified with courses (7 programs)

## Restore code to this point

```bash
git fetch --tags
git checkout ABT_ERP_V.1.1.1
# or on a branch:
git checkout -b restore-abt-v1.1.1 ABT_ERP_V.1.1.1
```

## Restore production database (if needed)

Production backups created by `tools/push-local-to-vps.sh` live on the VPS under:

`/var/www/alphabridge/backups/production-before-local-*.sql`

Redeploy after checkout:

```bash
cd /var/www/alphabridge
bash tools/deploy-alphabridge-vps.sh
```

## Create the next restore point

1. Bump `APP_VERSION` in `src/constants/appVersion.js`
2. Add a new file under `restore-points/`
3. Run: `bash tools/create-restore-point.sh`
