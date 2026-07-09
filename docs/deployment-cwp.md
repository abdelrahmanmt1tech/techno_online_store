# CWP Production Deployment

GitHub Actions workflow: `.github/workflows/deploy.yml`

Triggers on every push to `main`. Deploys via SSH to:

```
/home/technomasrsystem/online-store.technomasrsystems.com
```

PHP binary on CWP:

```
/opt/alt/php83/usr/bin/php
```

## Deploy sequence

1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force` (central DB)
4. `php artisan tenants:sync-permissions --migrate --no-interaction` (tenant DBs + permissions)
5. `npm ci && npm run build` (only if `npm` is available on the server)
6. `php artisan filament:assets`
7. `php artisan optimize:clear`
8. `php artisan optimize`
9. `php artisan queue:restart`

## Intentionally excluded from deploy

| Step | Reason |
|---|---|
| `db:seed` | Would reset admin password on every deploy via `AdminSeeder` |
| `composer install` without `--no-dev` | Dev dependencies must not run in production |

## Required GitHub secrets

| Secret | Purpose |
|---|---|
| `SERVER_HOST` | CWP server hostname |
| `SERVER_USER` | SSH user (e.g. `technomasrsystem`) |
| `SSH_PRIVATE_KEY` | Private key for deploy |
| `SERVER_PORT` | SSH port (typically `22`) |

## Required server `.env` (manual)

- Database credentials
- `APP_ENV=production`
- `APP_DEBUG=false`
- `BYPASS_PERMISSIONS=true` while features are still under active development; set `false` before launch
- `WHATSAPP_WEBHOOK_VERIFY_TOKEN`, `META_APP_SECRET`
- `WHATSAPP_WEBHOOK_LOG_CHANNEL=whatsapp-webhook`
- `QUEUE_CONNECTION=database` + queue worker running

## Post-deploy checks

```bash
php artisan config:clear
php artisan route:list --path=webhooks
tail -f storage/logs/whatsapp-webhook.log
```

Verify Meta webhook GET challenge returns the challenge string, not `Forbidden`.
