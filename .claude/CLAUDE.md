# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Wecker is a Portainer-inspired web UI for managing Docker containers. A PHP (Slim 4) app talks to the Docker Engine HTTP API to list/start/stop containers and read logs, behind a session-based login. The whole stack (nginx, php-fpm, postgres, redis) runs via Docker Compose. Application code lives under `www/`.

## Commands

All infra commands run through the `run.sh` wrapper (from repo root):

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

## Detailed guidance

Read the topic files under `.claude/rules/` before working in the corresponding area:

- Backend architecture (request flow, layers, DI, Docker integration): @rules/architecture.md
- PHP code style & conventions (naming, patterns, error handling): @rules/code-style.md
- Frontend (Twig templates, jQuery page modules, DataTables/SweetAlert, JS↔API contracts): @rules/frontend.md
