# PHP Code Style & Conventions

Target runtime is **PHP 8.5**. Match the surrounding file — the codebase is internally consistent.

## Namespacing & layout

- PSR-4: `App\` → `www/src/`, `App\Bootstrap\` → `www/bootstrap/` (see `www/composer.json`).
- One class per file, class name matches file name. **Exception:** `App\Service\AuthMidleware` keeps that (mis)spelling — don't "fix" it without renaming everywhere.
- Domain sub-namespaces group related classes: `Service\User\Validator`, `Service\User\Normalizer`, `Service\Api`, `Repository\User`, `Model\User`.

## Naming

- Suffix classes by role: `*Controller`, `*Service`, `*Repo`, `*Model`, `*Validator`, `*Normalizer`, `*Exception`, `Entity` classes are bare nouns (`User`, `Role`).
- Abstract base classes are prefixed `Abstract*` (`AbstractController`, `AbstractModel`, `AbstractRepo`).
- Methods and properties are `camelCase`; class constants are `UPPER_SNAKE_CASE`.

## Types & PHP 8.5 idioms in use

- Typed properties and typed constructor params everywhere; nullable via `?Type`.
- **Typed class constants**: `const string RUNNING_STATE = ...`, `const array STOPPED_STATES = [...]`.
- `new Main()->run()` — calling a method directly on `new` without wrapping parens.
- `match ($x) { 204 => '...', default => '...' }` for mapping (e.g. Docker status code → message).
- Default constructor args can be `new` expressions (`Carbon $createdAt = new Carbon()`).
- `declare(strict_types=1)` appears in entrypoints/routes but is **not** applied uniformly across `src/`; add it to new files where it fits the neighbours.

## PHPDoc

Methods carry full docblocks with `@param`, `@return`, and `@throws` even when the signature is already typed. Properties often carry a redundant `/** @var Type $name */`. Keep this style — it's consistent across the code.

## Dependency injection

Prefer **constructor injection** with typed params; PHP-DI autowires them. Assign each dependency to a private typed property in the constructor.

⚠️ Some existing classes bypass DI and `new` their collaborators directly (e.g. `DashboardController::__construct` receives a `DashboardService` but ignores it and does `new DashboardService()`, which in turn does `new DockerClient()`). This is an inconsistency, not the intended pattern — prefer using the injected dependency in new code, and follow the existing file's style when editing it.

## Data access (repositories)

- Extend `AbstractRepo`; use the injected DBAL `Connection`. **Raw parameterized SQL only — no ORM/query builder.**
- Always use placeholders (`?` positional or `:named`) — never interpolate user input into SQL.
- Writes: wrap in `beginTransaction()` / `commit()`, `catch` → `rollback()`, `error_log($e)`, rethrow.
- Implement `hydrate(array $data): object` to turn a row into an `Entity`; timestamps become `Carbon` instances.

## Validation & normalization

- Normalize input first (`GeneralNormalizer::clean()` trims all values and lowercases `email`), then validate.
- Validators use `respect/validation` (imported as `use Respect\Validation\ValidatorBuilder as v;`).
- **Convention to follow: validators should `throw new ValidationException($errors)` on failure** — `LoginValidator` does this correctly. `RegisterValidator` instead *returns* an error array and its caller ignores it (a latent bug); prefer the throwing style for new/changed validators.

## Errors & result shapes

- Throw the typed `App\Exception\*` classes (constructed with a **message array** and, via the base, an HTTP status). `HttpErrorHandler` turns them into JSON.
- Service methods that wrap external calls return associative arrays shaped `['status' => int, 'message' => mixed]`; controllers pass these into `returnWithJson($response, [$msg], $status)`.
- Controllers return JSON via `AbstractController::returnWithJson()` / `returnWithSuccess()`, or render Twig.

## Language in strings

Code, identifiers, and user-facing strings are **English**. A few internal messages/log comments are Portuguese (e.g. the `DockerClient` runtime error). Keep new user-facing text English to match the templates and UI.
