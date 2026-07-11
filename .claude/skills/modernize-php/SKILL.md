---
name: modernize-php
description: Analyze a PHP file for opportunities to adopt the latest PHP 8.5 (and modern PHP 8.x) features, patterns, and best practices, then write a markdown report explaining each recommended change and why it matters — with extra context for junior developers. Use when the user asks to modernize, review, or "bring up to PHP 8.5" a specific PHP file, or asks which new PHP features a file could use.
disable-model-invocation: true
---

# modernize-php

Reviews one PHP file the user names and produces a markdown **report** of concrete, prioritized recommendations to modernize it toward PHP 8.5. It does **not** edit the source file — it only writes the report. The audience is a junior developer, so explanations teach the concept, not just the syntax.

## Inputs

- **Target file**: a path to a `.php` file, taken from the user's message / the skill argument. If none is given, or the path doesn't resolve to a readable `.php` file, ask the user for it before doing anything else.
- **Optional output path** for the report. Default: `<target-basename>.modernization.md` in the current working directory.

## Workflow

1. **Read the feature catalog** [references/php-features.md](references/php-features.md). It is the source of truth for which PHP version introduced each feature and for correct syntax — rely on it instead of recalling versions from memory. If a construct isn't in the catalog and you're unsure it exists, don't invent it; note the uncertainty in the report.
2. **Confirm the target PHP version.** Default to **8.5** (this project's target). If the file lives in a project with a `composer.json` `require.php` or a Dockerfile pinning a version, respect the lower of the two — never recommend a feature newer than what the project can run.
3. **Read the target file** fully.
4. **Analyze** it against the catalog checklist. For each spot, pick the *newest applicable* improvement. Look for, at minimum:
   - Headline 8.5 wins: pipe operator `|>`, `array_first/array_last`, `clone with`, `#[\NoDiscard]`, closures in constant expressions, the `Uri` extension, final promoted properties.
   - Modern baseline gaps: constructor property promotion, `match` vs `switch`, enums vs constant sets, `readonly`, first-class callables, property hooks / `private(set)`, `array_find/any/all`, typed constants, `#[\Override]`.
   - General signals: missing `declare(strict_types=1)`, doc-only types that could be native, SQL string interpolation (**security — always High priority**), `['status' => ..., 'message' => ...]` arrays that want a DTO/enum, magic numbers.
5. **Prioritize.** Security and correctness first, then readability/maintainability, then stylistic polish. Don't pad the report with trivial nits — a focused list of real improvements is more useful.
6. **Write the report** using [assets/report-template.md](assets/report-template.md) as the structure. For every recommendation include: the exact current snippet (with `File.php:line`), the suggested replacement, and a *why* written for a junior — explain the concept and any gotcha, not just "it's the new way." Fill the summary table so the reader can triage at a glance.
7. **Save** the report to the output path and tell the user where it is and the headline findings. Do not modify the analyzed source file.

## Principles

- **Teach, don't just prescribe.** The reader is learning. A recommendation without a clear reason is noise.
- **Behavior-preserving unless flagged.** If a change could alter behavior (e.g. `switch` loose vs `match` strict comparison), say so explicitly under "Risk of changing."
- **Accuracy over enthusiasm.** Never recommend a feature the target PHP version can't run, and never invent syntax. When unsure, say so.
- **Report only.** This skill writes a `.md` file; it never edits the source. If the user then wants the changes applied, that's a separate follow-up.
