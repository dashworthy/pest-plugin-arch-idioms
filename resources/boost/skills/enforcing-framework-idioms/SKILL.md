---
name: enforcing-framework-idioms
description: Use when adding or changing a queued job, notification, mailable, or Eloquent model in a project that has dashworthy/pest-plugin-arch-idioms installed — the package ships seven arch expectations that enforce dispatch, table-naming, mass-assignment, channel and mail-template conventions, and this skill explains which verb covers which case and how to scope or exempt one.
---

# Enforcing framework idioms with arch expectations

This project has `dashworthy/pest-plugin-arch-idioms` installed. It registers
seven expectations onto Pest's architecture plugin. They compose with any
`arch()` chain — there is no separate DSL.

## Pick the verb

| You are writing | Assert |
|---|---|
| a job, listener, notification or mailable that should not run inline | `toBeQueued()` |
| something dispatched inside a database transaction | `toBeQueuedAfterCommit()` |
| something deliberately kept synchronous | `toBeSync()` |
| an Eloquent model | `toMatchTableName()` and `toGuardMassAssignment()` |
| a notification | `toDeclareNotificationChannels()` |
| a mailable | `toUseMarkdownMailTemplates()` |

`toBeSync()` exists so a synchronous choice is asserted rather than merely
absent. Prefer it over silently excluding a class from `toBeQueued()`.

## Write the test

```php
arch('notifications are queued')
    ->expect('App\Domains')
    ->classes()
    ->toBeQueued();
```

Scope with `->classes()`, `->ignoring('Fully\Qualified\Name')`, or by
targeting a narrower namespace. Target a single class by passing its FQCN to
`expect()`.

## Before you exempt something

An `->ignoring(...)` records only that a class was skipped, never what it was
opted into. Where a positive verb exists — `toBeSync()` for a deliberately
synchronous class — assert that in a second convention instead, so the decision
is checked rather than merely tolerated.

## Three behaviours to know

- **One violation per run.** These are arch expectations; `Blueprint` throws on
  the first failing class. Fix, rerun, repeat.
- **An empty layer passes.** A typo'd namespace turns a rule green. If that
  matters, assert the layer is non-empty in a separate test.
- **`@pest-arch-ignore-line` suppresses any of them**, and cannot be disabled.
  Police its use with an arch test of your own if that matters.

## Where the AST verbs are blind

`toDeclareNotificationChannels()` and `toUseMarkdownMailTemplates()` read the
class's own file, so a `via()` or `content()` inherited from a **trait** is
invisible and reported as missing. They also scan the whole file rather than
one class body, so a second class in the same file can satisfy the check for
its neighbour. Keep one class per file and declare these methods on the class.

## Where `toGuardMassAssignment()` is blind

It reads `getFillable()`/`getGuarded()` from a model built with
`newInstanceWithoutConstructor()`, so a model declaring `#[Fillable([...])]`
or `#[Guarded([...])]` as a PHP attribute is wrongly reported as unguarded —
Eloquent only resolves those attributes during construction. Exclude any such
model with `->ignoring(...)`.
