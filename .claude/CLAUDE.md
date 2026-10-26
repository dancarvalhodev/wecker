# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Wecker is a Portainer-inspired web UI for managing Docker containers. A PHP (Slim 4) app talks to the Docker Engine HTTP API to list/start/stop containers and read logs, behind a session-based login. The whole stack (caddy, php-fpm, postgres, redis) runs via Docker Compose. Application code lives under `www/`.

## Commands

All commands run through the `justfile` ([just](https://just.systems), from repo root). `just` with no args lists them:

```bash
just install          # first run: setup + up + composer install + migrate + create admin
just setup            # copy env-example -> .env and www/env-example -> www/.env
just up               # docker compose up -d --build; app served at http://localhost
just stop | status | logs
just shell [service]  # sh inside a container (default: php)
just reset            # down -v + prune project containers/images
just nuke             # docker system prune -af --volumes (destroys ALL docker state; typed confirmation)
```

Database (schema lives in `www/database/migrations/NNN_description.sql`, applied by `www/bin/migrate.php` and tracked in `schema_migrations`):

```bash
just migrate          # apply pending migrations to the app db and to wecker_test
just db-reset         # drop + recreate both dbs from the migrations (asks for confirmation)
just admin-create     # interactive: name, email, password -> user with Role::ROLE_ADMIN
```

New migration = new numbered `.sql` file; never edit one that was already applied.

Composer / tests run **inside the php container** (php 8.5 on Ubuntu 24.04, code at `/var/www/html`); the recipes wrap `docker compose exec php composer ...`:

```bash
just composer <args>        # any composer command
just test                   # all tests (phpunit.xml: Unit + Integration suites)
just test testUnitIsWorking # single test (--filter)
just stan                   # PHPStan (level 6, phpstan.neon)
just cs                     # PHP-CS-Fixer check, PER-CS 2.0 (.php-cs-fixer.dist.php)
just cs-fix                 # apply PHP-CS-Fixer fixes
```

Tests: `www/tests/bootstrap.php` rebuilds the `wecker_test` database from the migrations on every run. Integration tests extend `Tests\Integration\IntegrationTestCase` (real Slim app, one rolled-back transaction per test, Docker faked with `fakeDocker([...responses])`, login with `actingAs($id)`). See `.claude/arch-explain/testing.md`.

## Detailed guidance

Read the topic files under `.claude/rules/` before working in the corresponding area:

- Backend architecture (request flow, layers, DI, Docker integration): @rules/architecture.md
- PHP code style & conventions (naming, patterns, error handling): @rules/code-style.md
- Frontend (Twig templates, jQuery page modules, DataTables/SweetAlert, JS↔API contracts): @rules/frontend.md

Developer-facing explanations (pt-BR, with analogies) live in `.claude/arch-explain/` — one file per component, starting at `request-flow.md`. Update the matching file when a change alters what it describes.

<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->
