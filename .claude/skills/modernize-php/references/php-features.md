# PHP feature & idiom catalog (for the modernize-php skill)

Use this as the checklist when reviewing a file. Each entry has: the feature, the PHP version that introduced it, what the *old* way looks like, the *new* way, and the reason it's better. When you write the report, prefer the newest applicable feature but only recommend it when the file's minimum PHP version supports it (this project targets **PHP 8.5** — see `www/composer.json` / the PHP Dockerfile).

> Accuracy note: this catalog is the source of truth for version attribution. Do not "remember" a different version for a feature — cite what's written here. If you're unsure whether a construct exists, say so in the report rather than inventing syntax.

---

## Headline PHP 8.5 features (the "latest news")

### Pipe operator `|>` (8.5)
Chains calls left-to-right; the left value is passed as the single argument to the callable on the right (use first-class callable syntax `fn(...)`).
```php
// Before — nested, read inside-out:
$result = array_filter(array_map('trim', explode(',', $csv)));

// After — reads top-to-bottom:
$result = $csv
    |> fn($s) => explode(',', $s)
    |> fn($a) => array_map(trim(...), $a)
    |> array_filter(...);
```
Why: removes deep nesting and temporary variables; the data flow reads in execution order.

### `array_first()` / `array_last()` (8.5)
Return the first/last element of an array without touching the internal pointer or needing `reset()`/`end()` or `array_key_first()`.
```php
// Before:
$first = $items[array_key_first($items)] ?? null;
// After:
$first = array_first($items);
```
Why: clear intent, no pointer side effects, safe on empty arrays (returns `null`).

### `clone with` (8.5)
`clone` accepts a second argument: an array of property => value overrides applied during cloning (can even update `readonly` properties).
```php
// Before — a "wither" that hand-copies every field:
public function withStatus(string $s): self {
    $c = clone $this; $c->status = $s; return $c;
}
// After:
$updated = clone($order, ['status' => 'paid']);
```
Why: immutable value objects become trivial; no boilerplate wither methods.

### `#[\NoDiscard]` attribute (8.5)
Marks a function/method whose return value must be used; ignoring it raises a warning. Silence intentionally with `(void)`.
```php
#[\NoDiscard("the sanitized value is the whole point")]
function sanitize(string $in): string { /* ... */ }
```
Why: catches bugs where a pure function's result is accidentally thrown away.

### Closures in constant expressions (8.5)
Closures may now appear in constant expressions: class constants, static property defaults, attribute arguments, default parameter values.
```php
class Report {
    const FORMATTER = fn(int $n) => number_format($n, 2);
}
```
Why: lets you attach behavior to constants/attributes without a runtime init step.

### First-class URL/URI parsing — `Uri\Rfc3986\Uri`, `Uri\WhatWg\Url` (8.5)
A native, spec-compliant URI extension replacing fragile `parse_url()` handling.
```php
$uri = new Uri\Rfc3986\Uri('http://host:2375/v1.54/containers/json');
$uri->getHost();  // 'host'
$uri->getPath();  // '/v1.54/containers/json'
```
Why: `parse_url()` is lossy and non-compliant; this validates and round-trips correctly.

### `get_error_handler()` / `get_exception_handler()` (8.5)
Retrieve the currently registered handler (previously you could only set them).
Why: libraries can inspect/wrap existing handlers instead of clobbering them.

### Final promoted properties + attributes on constants (8.5)
`final` is allowed on promoted constructor properties; attributes (e.g. `#[\Deprecated]`) can annotate class constants.
```php
public function __construct(public final string $id) {}
```

---

## Modern baseline (8.0–8.4) — still worth recommending if the file predates them

### Constructor property promotion (8.0)
```php
// Before:
private string $name;
public function __construct(string $name) { $this->name = $name; }
// After:
public function __construct(private string $name) {}
```
Why: removes the declare-then-assign duplication.

### `match` expression (8.0)
Strict (`===`) comparison, returns a value, no fallthrough, throws on unhandled.
Why: safer and more concise than `switch` for value mapping.

### Named arguments (8.0) & nullsafe operator `?->` (8.0)
`?->` short-circuits a whole chain to `null` instead of erroring on a null in the middle.

### `str_contains` / `str_starts_with` / `str_ends_with` (8.0)
Replace `strpos(...) !== false` idioms.

### Enums (8.1)
Replace class constants used as a fixed set of values with a backed enum; gives type safety and methods.
```php
enum Role: int { case Admin = 1; case User = 2; }
```
Why: invalid values become impossible; you get autocompletion and `->name`/`->value`.

### `readonly` properties (8.1) and `readonly` classes (8.2)
Enforce immutability at the language level instead of by convention (private + getter only).

### First-class callable syntax `strlen(...)` (8.1)
```php
$fn = trim(...);   // instead of 'trim' or Closure::fromCallable('trim')
```
Why: refactor-safe, IDE-navigable references to callables. Required by the pipe operator.

### `new` in initializers (8.1)
Default argument / property values can be `new Foo()`.

### Readonly, DNF types, `never` return type (8.1/8.2)
Use `never` for functions that always throw/exit.

### Typed class constants (8.3) and `#[\Override]` (8.3)
```php
const string VERSION = 'v1.54';
```
`#[\Override]` documents (and enforces) that a method overrides a parent — catches typos in method names.

### Property hooks & asymmetric visibility (8.4)
```php
public string $slug { get => strtolower($this->title); }
public private(set) int $id;   // public read, private write
```
Why: computed/guarded properties without full getter/setter pairs; `private(set)` replaces the "public getter, private setter" dance (relevant to entities with a guarded `setId`).

### `array_find` / `array_any` / `array_all` / `array_find_key` (8.4)
Replace manual `foreach`-with-flag search loops.

### `new Foo()->method()` without parentheses (8.4)
Already used in this codebase (`new Main()->run()`).

### `#[\Deprecated]` attribute (8.4)
Mark methods/constants as deprecated so callers get a warning.

---

## General modernization signals to flag (any version)

- `declare(strict_types=1);` missing from a file — recommend adding it (the project applies it inconsistently).
- Raw SQL string interpolation of variables — must use DBAL placeholders (security, not just style).
- Doc-only type hints (`@param string`) where a native type could be added to the signature.
- `array` used as a de-facto struct with `['status' => ..., 'message' => ...]` — candidate for a small `readonly` class / DTO or enum.
- `switch` chains, `strpos !== false`, `count($x) > 0` for emptiness (`$x !== []`), manual search loops.
- Magic numbers (e.g. Docker HTTP status codes `204/304/404`) — candidate for an enum or named constants.
