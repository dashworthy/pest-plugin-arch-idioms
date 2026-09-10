# pest-plugin-arch-idioms

Framework-idiom architecture expectations for Pest, registered directly onto
`pest-plugin-arch`. No DSL, no selector grammar, no reporting layer of its own —
every verb composes with `arch()` chains you already write.

## Install

```bash
composer require --dev dashworthy/pest-plugin-arch-idioms
```

## Verbs

| Verb | Passes when |
|---|---|
| `toBeQueued()` | the class implements `ShouldQueue` (or `ShouldQueueAfterCommit`, which extends it) |
| `toBeQueuedAfterCommit()` | the class implements `ShouldQueueAfterCommit` specifically |
| `toBeSync()` | the class implements neither — a positive assertion that dispatch is deliberately synchronous |
| `toMatchTableName()` | an Eloquent model resolves the table its class name implies |
| `toGuardMassAssignment()` | a model declares `$fillable` or `$guarded`, or relies on the fully guarded default |
| `toDeclareNotificationChannels()` | `via()` exists and returns a non-empty channel list |
| `toUseMarkdownMailTemplates()` | `content()` names a markdown template rather than a plain view |

## Usage

Each verb is an ordinary arch expectation, so it chains onto an `expect(...)`
you already write — one class, or a whole layer with `->classes()`.

### Queued notifications that declare their channels

```php
namespace App\Domains\Billing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->markdown('mail.billing.invoice-paid');
    }
}
```

```php
arch('billing notifications are queued and declare their channels')
    ->expect('App\Domains\Billing\Notifications')
    ->classes()
    ->toBeQueued()
    ->toDeclareNotificationChannels();
```

`InvoicePaid implements ShouldQueue`, so `toBeQueued()` passes; its `via()`
returns a non-empty list, so `toDeclareNotificationChannels()` passes. Drop the
`implements ShouldQueue` and the first verb fails; return `[]` from `via()` and
the second does.

### Mailables rendered from markdown

```php
namespace App\Domains\Onboarding\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

final class WelcomeEmail extends Mailable
{
    public function content(): Content
    {
        return new Content(markdown: 'mail.onboarding.welcome');
    }
}
```

```php
arch('onboarding mailables use markdown templates')
    ->expect('App\Domains\Onboarding\Mail')
    ->classes()
    ->toUseMarkdownMailTemplates();
```

The verb only recognises the named-argument form `new Content(markdown: '...')`.
A positional `new Content('mail.welcome')` is reported as a violation even when
it points at a markdown template.

### Models that guard mass assignment and match their table

```php
namespace App\Domains\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class Invoice extends Model
{
    /** @var list<string> */
    protected $fillable = ['team_id', 'amount_cents', 'status'];
}
```

```php
arch('billing models are safe and conventional')
    ->expect('App\Domains\Billing\Models')
    ->classes()
    ->toGuardMassAssignment()
    ->toMatchTableName();
```

`Invoice` declares `$fillable`, so mass assignment is guarded; its class name
resolves the table `invoices`, which is what `getTable()` returns, so the table
name matches.

### Asserting a deliberate decision instead of ignoring it

A class caught by a layer selector that is *meant* to break the rule can be
carved out with `->ignoring(...)`:

```php
arch('billing notifications are queued')
    ->expect('App\Domains\Billing\Notifications')
    ->classes()
    ->toBeQueued()
    ->ignoring(App\Domains\Billing\Notifications\PaymentDeclined::class);
```

But an exclusion only records that `PaymentDeclined` was skipped, not what it was
opted into. When a class is deliberately synchronous, assert that in place with
`toBeSync()` instead — the next reader sees the decision without hunting for the
class:

```php
arch('the payment-declined alert is sent synchronously')
    ->expect(App\Domains\Billing\Notifications\PaymentDeclined::class)
    ->toBeSync();
```

## What you are accepting

These are arch expectations, so they inherit arch's behaviour:

- **One violation per run.** A rule failing across forty classes reports the
  first and stops.
- **`@pest-arch-ignore-line` suppresses any of them.** Police its use with an
  arch test of your own if that matters to you.
- **An empty layer passes.** A typo'd namespace turns a rule green. Assert the
  layer is non-empty separately if you need that guard.

## Known limitations

Every verb that inspects a model instantiates it via
`newInstanceWithoutConstructor()`; no database connection or booted container
is needed, but anything Eloquent resolves during construction is invisible to
the check. That is the general form of the `#[Fillable]` caveat below.

### `toGuardMassAssignment()`

- **The verb produces a false failure on correct code that declares
  `#[Fillable([...])]` or `#[Guarded([...])]` as a PHP attribute.** It reads
  `getFillable()`/`getGuarded()` from an instance created with
  `newInstanceWithoutConstructor()`, but Eloquent only resolves the attribute
  forms inside `initializeGuardsAttributes()`, which runs during construction.
  With the constructor skipped, an attribute-declared list is invisible and
  the model is reported as unguarded. This repository's
  `app/Domains/Shared/Teams/Models/Membership.php` declares
  `#[Fillable(['team_id', 'user_id', 'role'])]` and is wrongly flagged by this
  verb. Exclude any model using the attribute form with `->ignoring(...)`.
- A model that `extends Pivot` inherits `protected $guarded = []` from
  `Illuminate\Database\Eloquent\Relations\Pivot`, so pivots are reported as
  unguarded unless they declare `$fillable` in property form.
- A non-model class caught by the selector is reported as a violation, not
  skipped. Scope the selector or use `->ignoring(...)`.
- Abstract classes pass without being checked.

### `toMatchTableName()`

- A model that does not set `$table` passes **by construction** —
  `getTable()` derives exactly the name the verb expects, so the rule cannot
  fail for it. It only guards against a future `$table` that breaks
  convention; it is not a check on current code.
- A legitimately custom table name (a pivot such as `team_members`, or a
  legacy table) is a violation by design; exclude it with `->ignoring(...)`.
- Same non-model and abstract-class behaviour as `toGuardMassAssignment()`.

### `toDeclareNotificationChannels()` and `toUseMarkdownMailTemplates()`

- Both read the class's own file. A `via()` or `content()` provided by a
  **trait** is not seen — the AST inspected is the class's own file only —
  and is reported as missing.
- Both read `$object->stmts`, which is the AST for the *whole file*, not one
  class body. A second class (or an anonymous class) in the same file can
  satisfy the check on its neighbour's behalf — a false pass, not a false
  failure. Keep one class per file.
- `toUseMarkdownMailTemplates()` additionally scans every argument node
  inside `content()`, so a nested, unrelated call that happens to use a
  `markdown:` named argument also produces a false pass. It also only
  recognises the named-argument form — `new Content(markdown: '...')`. A
  positional `new Content('mail.welcome')` is reported as violating even when
  it does point at a markdown template.

### `toBeQueued()`, `toBeQueuedAfterCommit()`, `toBeSync()`

- No type guard at all: a trait, interface or enum caught by the selector is
  reported as not implementing `ShouldQueue`. Scope the selector.

## Editor and agent support

The package ships a Laravel Boost skill and guideline. Boost discovers them at
`resources/boost/skills/` and `resources/boost/guidelines/` for every installed
package, so a consuming project picks them up by running:

```bash
php artisan boost:update
```

The skill explains which verb covers which case and how to scope or exempt one;
the guideline is the short form injected into the project's AI guidelines.
