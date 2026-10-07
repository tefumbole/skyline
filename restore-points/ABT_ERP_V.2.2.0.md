# Restore Point: ABT_ERP_V.2.2.0

**Date:** 2026-07-14  
**Git tag:** `ABT_ERP_V.2.2.0`  
**Login version label:** `2.2.0`

## Snapshot includes

- Full Laravel Events module (phases 1–8)
- Operational events (`btw_events`), public publication, countdown
- Event workforce, labour budgets, worker categories
- Contract templates, worker signing, admin countersign, signed PDFs
- Scheduled event reminders (WhatsApp) via `events:process-reminders`
- Event timesheets (staff portal + admin approve/reject)
- Labour payments with PDF receipts and WhatsApp delivery
- Gallery page + contact section on About (prior CMS work)

## Restore code to this point

```bash
git fetch --tags
git checkout ABT_ERP_V.2.2.0
# or on a branch:
git checkout -b restore-abt-v2.2.0 ABT_ERP_V.2.2.0
```

## Redeploy production

```bash
ssh myvps "cd /var/www/beyondtechworld && git pull && cd laravel-app && php artisan migrate --force && php artisan view:clear && php artisan config:clear"
```
