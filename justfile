test_db := "wecker_test"

[private]
default:
    @just --list --unsorted

# First run: setup + up + composer install + migrate + create admin
[group('environment')]
install: setup up (composer "install --no-interaction") migrate _admin-if-missing
    @echo "✔ Install complete: http://localhost"

# Create .env and www/.env from the examples
[group('environment')]
setup:
    #!/usr/bin/env bash
    set -euo pipefail
    [ -f .env ] || { cp env-example .env; echo "✔ .env created"; }
    [ -f www/.env ] || { cp www/env-example www/.env; echo "✔ www/.env created"; }
    echo "✔ Setup complete"

# Start the containers (with build)
[group('environment')]
up: _docker _env
    docker compose up -d --build
    @echo "🌐 http://localhost"

# Stop the containers
[group('environment')]
stop: _docker _env
    docker compose down

# Show the containers' status
[group('environment')]
status: _docker _env
    docker compose ps

# Follow the logs of all containers
[group('environment')]
logs: _docker _env
    docker compose logs -f

# Open a shell in a container (default: php)
[group('environment')]
shell service="php": (_running service)
    docker compose exec {{ service }} sh

# Apply pending migrations to the app and test databases
[group('database')]
migrate: (_running "php") _wait-db
    docker compose exec php php bin/migrate.php
    docker compose exec php php bin/migrate.php {{ test_db }}

# Drop and recreate the app and test databases from the migrations
[group('database')]
[confirm("This deletes ALL data in the app and test databases. Continue? [y/N]")]
db-reset: (_running "php") _wait-db
    docker compose exec php php bin/migrate.php --fresh
    docker compose exec php php bin/migrate.php --fresh {{ test_db }}

# Create an admin user (asks for name, email and password)
[group('database')]
admin-create: (_running "php")
    docker compose exec php php bin/admin-create.php

# Run Composer in the php container
[group('php')]
composer *args: (_running "php")
    docker compose exec php composer {{ args }}

# Run the tests (optional filter by test name)
[group('php')]
test filter="": (_running "php")
    docker compose exec php composer test {{ if filter == "" { "" } else { "-- --filter " + quote(filter) } }}

# Static analysis (PHPStan)
[group('php')]
stan: (_running "php")
    docker compose exec php composer stan

# Check code style (PHP-CS-Fixer)
[group('php')]
cs: (_running "php")
    docker compose exec php composer cs

# Fix code style
[group('php')]
cs-fix: (_running "php")
    docker compose exec php composer cs:fix

# Tear down the project and prune stopped containers and images
[group('cleanup')]
reset: _docker _env
    docker compose down -v --remove-orphans
    docker container prune -f
    docker image prune -f

# Wipe ALL of Docker on this machine, not just Wecker (asks for confirmation)
[group('cleanup')]
nuke: _docker
    #!/usr/bin/env bash
    set -euo pipefail
    echo "⚠️  This deletes ALL Docker containers, images, volumes and networks on this machine."
    read -r -p "Type 'nuke' to confirm: " answer
    [ "$answer" = "nuke" ] || { echo "Cancelled."; exit 1; }
    docker compose down -v --remove-orphans
    docker system prune -af --volumes

_docker:
    @command -v docker > /dev/null || { echo "✖ docker not found in PATH." >&2; exit 1; }
    @docker compose version > /dev/null 2>&1 || { echo "✖ docker compose v2 not found." >&2; exit 1; }
    @docker info > /dev/null 2>&1 || { echo "✖ The Docker daemon did not respond. Is it running?" >&2; exit 1; }

_env:
    @[ -f .env ] || { echo "✖ .env not found. Run: just setup" >&2; exit 1; }

_admin-if-missing: (_running "php")
    docker compose exec php php bin/admin-create.php --if-missing

_wait-db: _docker _env
    #!/usr/bin/env bash
    set -euo pipefail
    # 127.0.0.1 because on first boot postgres runs a temporary socket-only server, then restarts
    for _ in $(seq 30); do
        docker compose exec -T postgres pg_isready -q -h 127.0.0.1 -d postgres && exit 0
        sleep 1
    done
    echo "✖ Postgres did not become ready within 30 seconds." >&2
    exit 1

_running service: _docker _env
    @[ -n "$(docker compose ps --status running --quiet {{ service }})" ] || { echo "✖ The '{{ service }}' container is not running. Run: just up" >&2; exit 1; }
