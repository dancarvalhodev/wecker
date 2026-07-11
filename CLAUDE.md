# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Wecker is a Portainer-inspired web UI for managing Docker containers. A PHP (Slim 4) app talks to the Docker Engine HTTP API to list/start/stop containers and read logs, behind a session-based login. The whole stack (nginx, php-fpm, postgres, redis) runs via Docker Compose. The application code lives under `www/`.

## Commands

All commands run through Docker Compose via the `run.sh` wrapper (from repo root):

```bash
./run.sh setup   # copy env-example -> .env and www/env-example -> www/.env (run once)
./run.sh up      # docker compose up -d --build; app served at http://localhost
./run.sh stop    # docker compose down
./run.sh logs    # tail all container logs
./run.sh reset   # down -v + prune project containers/images
./run.sh nuke    # down -v + docker system prune -af --volumes (destroys ALL docker state)
```

First-run bootstrap also requires loading `docs/database.sql` into the postgres db (creates `roles`/`users`, seeds `admin@admin.com`). See README for the full sequence.

Composer / tests run **inside the php container** (php 8.5, Alpine):

```bash
docker compose exec php composer install               # deps land in www/vendor
docker compose exec php ./vendor/bin/phpunit           # run all tests
docker compose exec php ./vendor/bin/phpunit tests/Integration/TempTest.php   # single file
docker compose exec php ./vendor/bin/phpunit --filter testUnitIsWorking       # single test
```

Note: there is no committed `phpunit.xml` — the test suite is currently a placeholder (`www/tests/Integration/TempTest.php`). The IDE points PHPUnit at `www/tests`.

## Architecture

Request flow is a layered Slim app wired by PHP-DI autowiring:

**Bootstrap** — `www/public/index.php` starts the session and calls `App\Bootstrap\Main`, which loads `.env` (vlucas/phpdotenv), builds the DI container from `www/config/*.php`, creates the Slim app via `php-di/slim-bridge`, registers the error middleware, and loads routes. `App\Bootstrap\App` exposes `isProduction()/isTesting()/isDevelopment()` gates keyed off `APP_ENV`; production enables DI compilation and Twig cache under `www/storage/cache`.

**Routing** — `www/routes/web.php` returns a closure taking the Slim `App`. Routes map to `[Controller::class, 'method']`. Dashboard routes are guarded by `AuthMidleware` via `->add(...)`.

**Layers** (`www/src/`): `Controller` → `Service`/`Model` → `Repository` → Doctrine DBAL. Controllers extend `AbstractController` (holds Twig, provides `returnWithJson`/`returnWithSuccess`). Data access goes through repos extending `AbstractRepo` (each defines `hydrate()` to turn a DB row into an Entity); repos use **raw SQL via Doctrine DBAL `Connection`**, not the ORM.

**Auth** — session-based. `AuthService` reads/writes `$_SESSION['user_id']`; `AuthMidleware` (invokable) redirects unauthenticated requests to `/login`. Passwords use `password_hash`/`password_verify`.

**Docker integration** — `App\Service\Api\DockerClient` wraps a Guzzle client against Docker Engine API `v1.54`. It auto-detects the daemon by probing `_ping` across candidates in order: TCP `host.docker.internal:2375`, TCP `127.0.0.1:2375` (WSL2/Docker Desktop, requires "Expose daemon without TLS"), then the unix socket `/var/run/docker.sock` (Linux — mounted into the php container by docker-compose). The chosen client is cached in a static. `DashboardService` filters out the app's own containers by matching the `wecker` substring in the image name (`SYSTEM_IMAGE_SUFIX`).

**Errors** — `App\Handler\HttpErrorHandler` (extends Slim's `ErrorHandler`) renders all errors as JSON `{success, error}`. Custom exceptions in `App\Exception` (`ValidationException`, `UserException`, `DockerException`) carry a message array and an HTTP status code.

**Views** — Twig templates in `www/templates` (`layout/base.html.twig` is the shell; pages under `crud/`, `dashboard/`, `home/`). `config/twig.php` injects globals `auth.logged` and `global.year`. Front-end assets (jQuery, Bootstrap, DataTables, SweetAlert2) are vendored under `www/public/assets`; page-specific JS lives in `assets/js/pages/`.

## Conventions & gotchas

- **PSR-4**: `App\` → `www/src/`, `App\Bootstrap\` → `www/bootstrap/`.
- Namespaces sometimes diverge from filenames — e.g. `AuthMidleware` (note the spelling) is `App\Service\AuthMidleware`.
- Dashboard write endpoints (`start`/`stop`/`log`/`create`) expect **POST** with an `id` field in the parsed body; they return JSON.
- Some classes bypass DI and `new` their dependencies directly (e.g. `DashboardController` constructs `new DashboardService()`, which constructs `new DockerClient()`). Follow the existing style in the file you're editing rather than assuming everything is injected.
- Uses PHP 8.5 features: typed class constants (`const string`, `const array`), `new Main()->run()` without parens.
- Comments/log messages are a mix of English and Portuguese; keep new user-facing strings in English to match the templates.
