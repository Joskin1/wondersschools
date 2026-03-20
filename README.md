# Wonders

Laravel 12 + Filament application with a Dockerized local test stack and a Render deployment blueprint.

## Local Docker setup

This repo now supports a two-container local stack:

- `app`: Laravel + PHP-FPM + Nginx
- `db`: MySQL 8.4 for local testing only

Start everything:

```bash
docker compose up --build -d
```

Open the app at `http://localhost:8080`.

Local MySQL is exposed on `127.0.0.1:33060` with:

- database: `school_saas`
- user: `laravel`
- password: `secret`
- root password: `root`

Useful commands:

```bash
docker compose logs -f app
docker compose exec app php artisan migrate:status
docker compose down
docker compose down -v
```

Notes:

- The app container runs migrations automatically on startup.
- An `APP_KEY` is generated automatically for local Docker if one is not provided.
- `docker compose down -v` deletes the MySQL test data volume.

## Render deployment

As of March 19, 2026, Render's free tier supports free web services and free Postgres databases. MySQL on Render requires running your own database service with a persistent disk, which is not the free path.

This repo's `render.yaml` is therefore set up for:

- free Render web service
- free Render Postgres database
- Docker-based deploy using this repository's `Dockerfile`

### Deploy steps

1. Push this repo to GitHub or GitLab.
2. In Render, create a new Blueprint and point it at the repo.
3. When prompted for `APP_KEY`, generate one locally:

```bash
php artisan key:generate --show
```

4. Complete the Blueprint creation and deploy.

The app container is already configured to:

- listen on Render's dynamic `PORT`
- use `RENDER_EXTERNAL_URL` automatically when `APP_URL` is not set
- fail fast if `APP_KEY` is missing in Render

### Render environment model

The free Render blueprint uses:

- `DB_CONNECTION=pgsql`
- `DATABASE_URL` from the managed Render Postgres instance
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=sync`

If you later upgrade to a paid Render setup and want MySQL there too, run MySQL as a separate Docker/private service with a persistent disk. Keep local Docker MySQL for testing either way.

## Files added for deployment

- `Dockerfile`
- `docker-compose.yml`
- `docker/start.sh`
- `docker/nginx/default.conf.template`
- `render.yaml`

