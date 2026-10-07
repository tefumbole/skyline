# Restore Point: ABT_ERP_V.1.1.2

**Date:** 2026-06-07  
**Git tag:** `ABT_ERP_V.1.1.2`  
**Login version label:** `ABT_ERP_V.1.1.2`

## Snapshot includes

- MySQL production backend with VPS deploy tooling
- Custom RBAC roles (e.g. Country Director) with MySQL `role_permissions`
- Permission-aware admin sidebar and route access for staff roles
- Signed shareholder agreements (approved + pending with signatures)
- Super admin OTP login fix and session role sync
- Auto version bump on git push (`.githooks/pre-push`)

## Restore code to this point

```bash
git fetch --tags
git checkout ABT_ERP_V.1.1.2
# or on a branch:
git checkout -b restore-abt-v1.1.2 ABT_ERP_V.1.1.2
```

## Redeploy production

```bash
cd /var/www/alphabridge
bash tools/deploy-alphabridge-vps.sh
```

## Create the next restore point

1. Bump `APP_VERSION` in `src/constants/appVersion.js` (or let pre-push hook increment)
2. Add a new file under `restore-points/`
3. Run: `bash tools/create-restore-point.sh`
