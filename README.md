# Deal Hay Backend

API-only Laravel 13 backend for Deal Hay. It serves the Next.js frontend and includes Laravel Reverb, database queues, scheduled tasks, Resend mail, and Cloudflare R2 media storage.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
composer run dev
```

## Local defaults

- SQLite database: `database/database.sqlite`
- Sanctum bearer tokens only
- Mail: `log`
- Media disks: local `storage/app/r2` and `storage/app/r2-private`
- Frontend URL: `http://localhost:3000`

The local `.env.example` intentionally contains only values that differ from application defaults. Add provider credentials only when switching a local service away from its local/log driver.

## Docker

The same image is used by the HTTP/Reverb service and the queue/scheduler worker:

```bash
docker compose build
docker compose run --rm web php artisan migrate --force
docker compose up
```

Compose is isolated from the host `.env`: it uses a dedicated SQLite/storage volume and local mail/media drivers. The API is available at `http://localhost:8000`; Reverb is also exposed directly on `http://localhost:8081` for the local frontend configuration.

## Render production deployment

`render.yaml` provisions two paid services in Singapore:

- `deal-hay-api`: FrankenPHP/Caddy, Laravel HTTP, and Reverb; health check at `/up`.
- `deal-hay-worker`: all database queues plus `schedule:work`.

The Blueprint assumes `deal-hay-backend` is the Git repository root. If it is later placed in a monorepo, update `dockerfilePath` and `dockerContext` relative to that repository root before syncing the Blueprint.

The API service runs `php artisan migrate --force` as Render's pre-deploy command. The runtime entrypoint then runs `php artisan optimize`, after production environment variables are available. No persistent disk is required because the database, cache/session data, queues, and R2 media are external.

FrankenPHP/Caddy is the public listener on Render's `PORT` (default `10000`). Reverb listens on `127.0.0.1:8081`, and Caddy forwards `/app/*` and `/apps/*` to it. Keep `REVERB_SERVER_HOST=127.0.0.1` in production. If `/up` returns `{"health":"OK"}` while `/api` returns plain-text `Not found.`, traffic is reaching Reverb directly; redeploy with only the Caddy port exposed. Local Compose overrides the Reverb host to `0.0.0.0` for its direct `8081` mapping.

Create a Render Blueprint from this repository and fill every blank value in the `deal-hay-production` environment group before the first successful deploy. In particular:

- Generate `APP_KEY` once with `php artisan key:generate --show`; never regenerate it during startup.
- Set `APP_URL` to the public Render API URL and `FRONTEND_URL` to the production Vercel URL.
- Set `DB_URL` to the external MySQL connection URL.
- Set the Resend, administrator, and R2 values declared in `render.yaml`.
- Reverb derives its public hostname from `APP_URL`; production defaults to port `443` and scheme `https`.
- Do not add `PORT`; Render provides it automatically.

If an environment variable is removed from `render.yaml`, remove the stale value from the Render Dashboard as well. Render preserves dashboard variables that a later Blueprint revision omits.

The configured pre-deploy command and background worker require paid Render compute. For a free web-service experiment, migrations must be run manually and realtime/background work will not be production-ready.

## Vercel frontend

Configure one server-only variable for each Vercel environment and redeploy after changing it:

```env
BACKEND_API_URL=https://your-api-host.onrender.com/api
```

No backend credential and no `NEXT_PUBLIC_*` backend URL is required by the current frontend.

## Useful commands

```bash
php artisan route:list
php artisan test
vendor/bin/pint
```

See `docs/api-contract.md` for the endpoint contract used by the frontend.
