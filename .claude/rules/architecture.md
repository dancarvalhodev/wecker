# Backend Architecture

Layered Slim 4 app wired by PHP-DI autowiring. Request path:

```
public/index.php → Bootstrap\Main → routes/web.php → Controller → Service / Model → Repository → Doctrine DBAL → Postgres
                                                                 ↘ Service\Api\DockerClient → Docker Engine API
```

## Bootstrap

- `www/public/index.php` — entrypoint. Starts the PHP session (`session_start()`), requires the Composer autoloader, and runs `new Main()->run()`.
- `App\Bootstrap\Main` (`www/bootstrap/Main.php`) — the constructor runs `loadConfigs()` (vlucas/phpdotenv `safeLoad` of `www/.env`) and builds the app with `AppFactory::create(ContainerFactory::create())`.
- `App\Bootstrap\ContainerFactory` — builds the PHP-DI container; shared by the web entrypoint, the CLI scripts and the tests.
- `App\Bootstrap\AppFactory` — builds the Slim app on a given container (`php-di/slim-bridge` `Bridge::create`, error middleware + `HttpErrorHandler`, `routes/web.php`); shared by `Main` and `IntegrationTestCase`.
- `www/bin/*.php` — CLI entrypoints (`migrate.php`, `admin-create.php`). `bin/bootstrap.php` loads the autoloader and `.env` and returns the container; the scripts only parse arguments, call a class from `src/` and print through `App\Console\Terminal`. Keep logic out of `bin/`.
- `App\Bootstrap\App` (`www/bootstrap/App.php`) — static env gates `isProduction()/isTesting()/isDevelopment()` keyed off `getenv('APP_ENV')`. Production enables DI compilation and proxy caching under `www/storage/cache`; Twig cache is likewise only enabled in production.

## Dependency injection

- Container built by `DI\ContainerBuilder` with **attributes and autowiring enabled** — controllers/services/repos get their constructor dependencies resolved automatically; you rarely need to register a class explicitly.
- Explicit definitions live in `www/config/*.php`, each returning a `[Class => factory]` array and loaded via `addDefinitions()`:
  - `config/database.php` — the app's Doctrine DBAL `Connection`, created by `App\Database\ConnectionFactory` from the `DB_*` env vars (`pdo_pgsql`).
  - `config/twig.php` — builds `Slim\Views\Twig` pointed at `www/templates`; registers globals `auth.logged` and `global.year`.
  - `config/handler.php` — binds `ResponseFactoryInterface` and autowires `HttpErrorHandler`.
  - `config/docker.php` — `DockerClient` built from `DockerClientFactory::create()` (see Docker integration).

## Routing

`www/routes/web.php` returns `function (App $app)`. Routes use the `[Controller::class, 'method']` array form. Dashboard routes are guarded with `->add(AuthMidleware::class)`. Public: `/`, `/register`, `/login`, `/logout`. Auth-guarded: `/dashboard` (GET) and `/dashboard/{start,stop,list,log,create}` (POST).

## Layers (`www/src/`)

- **Controller** — extend `AbstractController` (holds `Twig`; provides `returnWithJson()` and `returnWithSuccess()`). Controllers either render a Twig template or return JSON.
- **Service** — business logic. `DashboardService` orchestrates Docker calls and shapes `{status, message}` results; `AuthService` manages the session; `User/` holds `Validator/` and `Normalizer/` helpers.
- **Model** — `UserModel` sits between controller and repo (hashing passwords, building entities). `AbstractModel` is currently empty.
- **Repository** — extend `AbstractRepo` (holds the DBAL `Connection`, declares `abstract hydrate()`). **Data access is raw SQL through DBAL `Connection` — there is no ORM.** Writes wrap `beginTransaction()/commit()/rollback()`; reads use `fetchAssociative`/`fetchOne`; `hydrate()` maps a row to an `Entity`.
- **Database** — `ConnectionFactory` (a DBAL `Connection` for any database name) and `Migrator` (applies `www/database/migrations/*.sql`, tracked in `schema_migrations`; see `.claude/arch-explain/migrations.md`).
- **Console** — `Terminal`: CLI input/output helpers (`ask()` with hidden input, `success()`, `fail()`).
- **Entity** — plain immutable-ish PHP objects (`final class`, private props, getters). `User::setId()` throws if the id is already set. `Role` exposes `ROLE_ADMIN = 1` / `ROLE_USER = 2` constants matching the seeded `roles` table.

## Auth

Session-based. `AuthService` reads/writes `$_SESSION['user_id']` (`login()` calls `session_regenerate_id(true)`). `AuthMidleware` (invokable middleware — note the spelling) redirects unauthenticated requests to `/login` with a 302. Passwords use `password_hash`/`password_verify` (bcrypt default).

## Docker integration

`App\Service\Api\DockerClient` receives a `GuzzleHttp\ClientInterface` in its constructor and calls the Docker Engine API **`v1.54`** with `request()` (the interface has no `get()`/`post()`). `DashboardService` receives the `DockerClient` by constructor injection; tests swap it via `IntegrationTestCase::fakeDocker()`.

`App\Service\Api\DockerClientFactory::create()` (wired in `config/docker.php`) auto-detects the daemon by probing `_ping` across candidates **in order**, once per request that resolves a `DockerClient`:

1. TCP `http://host.docker.internal:2375` (Docker Desktop from inside a container)
2. TCP `http://127.0.0.1:2375` (WSL2 / Docker Desktop with "Expose daemon without TLS")
3. Unix socket `/var/run/docker.sock` (Linux — mounted into the php container by docker-compose)

If none respond it throws `RuntimeException('Docker não acessível')`. `DashboardService` hides the app's own containers by skipping any whose image name contains `wecker` (`SYSTEM_IMAGE_SUFIX`), and classifies state via `RUNNING_STATE`/`STOPPED_STATES` constants.

## Error handling

`App\Handler\HttpErrorHandler` (extends Slim's `ErrorHandler`) renders every error as JSON `{success: false, error: <message>}`. Custom exceptions in `App\Exception` carry a **message array** plus an HTTP status code: `ValidationException` (422, base class exposing `getMessages()`), `DockerException` (extends `ValidationException`), `UserException`. The error middleware shows details only when not in production.

## Infra topology (docker-compose)

- `caddy` — HTTP only (`:80`, published on `PORT_HTTP`), config in `config/caddy/Caddyfile`. Mounts only `www/public` (read-only): serves static files and sends everything else to `php:9000` via `php_fastcgi` (front controller `index.php`), setting `APP_ENV=development`. Access logs go to the container output.
- `php` — Ubuntu 24.04 + `ppa:ondrej/php`, php-fpm 8.5 on port 9000 (`bin/php/Dockerfile`, pool overrides in `bin/php/fpm-pool.conf`), Composer included. Code mounted at **`/var/www/html`**; `config/php/php.ini` is mounted as `conf.d/99-wecker.ini` for both fpm and cli. Also mounts **`/var/run/docker.sock`**; `entrypoint.sh` adds `www-data` to the docker-socket group so PHP can reach the daemon.
- `postgres` — `DB_*` env, data in `data/postgres`.
- `redis` — PHP sessions (`session.save_handler = redis` in `php.ini`); exposed on 6379.
