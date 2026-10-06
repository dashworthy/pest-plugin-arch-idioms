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
| `toAuthorizeWithGate($expectedAbility)` | `authorize()` checks `Gate::allows()` with an ability name — the one `$expectedAbility` derives from the class name, when given |
| `toUseApprovedDirectories($directories, $beneath)` | a class beneath a module sits in one of the module's approved directories, never in a new one or loose in the module, and is the type its directory names |
| `toMatchGateAbilities($directories, $alsoChecked)` | (on a list of permission names) every permission is checked by some `Gate::allows()`, and every checked ability is a permission |

`toMatchGateAbilities()` is the odd one out: it is an ordinary expectation, not an
arch one. Its subject is the list of permission names rather than a selection of
classes, so it reports every mismatch in one run.

## Usage

Each verb is an ordinary arch expectation. Point a selector at the Laravel base
class that defines the layer, and the verb checks every class that extends it —
no fixture classes to write.

### Notifications are queued and declare their channels

```php
use Illuminate\Notifications\Notification;

arch('every notification is queued and declares its channels')
    ->expect('App')
    ->classes()
    ->extending(Notification::class)
    ->toBeQueued()
    ->toDeclareNotificationChannels();
```

`toBeQueued()` passes for a notification that implements `ShouldQueue` (or
`ShouldQueueAfterCommit`, which extends it); `toDeclareNotificationChannels()`
passes when its `via()` returns a non-empty channel list.

### Mailables render from markdown

```php
use Illuminate\Mail\Mailable;

arch('every mailable uses a markdown template')
    ->expect('App')
    ->classes()
    ->extending(Mailable::class)
    ->toUseMarkdownMailTemplates();
```

The verb recognises only the named-argument form `new Content(markdown: '...')`.
A positional `new Content('mail.welcome')` is reported as a violation even when
it points at a markdown template.

### Models guard mass assignment and match their table

```php
use Illuminate\Database\Eloquent\Model;

arch('every model is safe and conventional')
    ->expect('App')
    ->classes()
    ->extending(Model::class)
    ->toGuardMassAssignment()
    ->toMatchTableName();
```

`toGuardMassAssignment()` passes for a model that declares `$fillable` or
`$guarded`, or relies on the fully guarded default; `toMatchTableName()` passes
when the class name resolves the table Laravel would derive from it.

### Form requests authorize through a gate

```php
use Illuminate\Foundation\Http\FormRequest;

arch('every form request authorizes through a gate')
    ->expect('App')
    ->classes()
    ->extending(FormRequest::class)
    ->toAuthorizeWithGate();
```

`toAuthorizeWithGate()` passes when `authorize()` calls `Gate::allows()` with a
string literal. Pass a closure to also pin *which* ability: it receives the
request's class name and returns the ability it should check, or `null` to
accept any.

```php
use Illuminate\Support\Str;

// StoreWidgetRequest must check 'widgets_store'.
$ability = function (string $request): ?string {
    if (! preg_match('/^(Index|Show|Store|Update|Destroy)(\w+)Request$/', class_basename($request), $parts)) {
        return null;
    }

    return Str::snake(Str::plural($parts[2])).'_'.strtolower($parts[1]);
};

arch('every form request checks the ability its name implies')
    ->expect('App\Http\Requests')
    ->classes()
    ->extending(FormRequest::class)
    ->toAuthorizeWithGate($ability);
```

### Every permission is checked, and every check names a permission

```php
use App\Models\Permission;

test('permissions and gate checks agree', function () {
    expect(Permission::pluck('name')->all())
        ->toMatchGateAbilities([app_path()], alsoChecked: ['viewPulse']);
});
```

`toMatchGateAbilities()` reads every `Gate::allows('...')` call in the PHP files
beneath the directories, outside any `vendor` directory. It fails for a
permission nothing checks (dead weight) and for a checked ability no permission
backs (it can never be granted), listing every mismatch at once. Pass abilities
the source cannot show — names built at runtime, or checked by a package — as
`$alsoChecked`.

### Modules keep to their approved directories

A modular application repeats the same few directories in every module:
`Actions`, `Models`, `Controllers` and so on. `toUseApprovedDirectories()`
holds every module to that list, so a new kind of class is a decision someone
approves rather than a directory that quietly appears.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

arch('every module keeps to the approved directories')
    ->expect('App\Domains')
    ->toUseApprovedDirectories(
        [
            'Actions',
            'Data',
            'Controllers' => Controller::class,
            'Enums' => UnitEnum::class,
            'Models' => Model::class,
            'Requests' => FormRequest::class,
            'Resources' => JsonResource::class,
        ],
        beneath: 'App\Domains\*\*',
    );
```

`$beneath` names the modules: a namespace in which `*` matches any one
segment, so `App\Domains\*\*` is every module two levels beneath
`App\Domains`. The verb checks the directory directly beneath each module and
nothing deeper, so `Actions\Drafts` is fine once `Actions` is approved. A
class sitting directly in a module fails too, and a class outside the modules
is left alone.

A directory given as a key names the class or interface everything in it must
extend or implement, however deeply nested: a `Requests` directory holds form
requests and nothing else. A directory given as a plain value holds any class.
Name a type wherever the directory has one, so the name of a directory says
what is in it.

The failure names the approved directories and asks for approval before a new
one is created or added to the list, so the list stays a decision. Exempt
existing exceptions with `->ignoring(...)` rather than approving their
directory for everyone.

#### Example

With the rule above, here is how each class in an invoicing module fares:

```text
app/Domains/Billing/Invoices/
├── Actions/
│   ├── IssueInvoice.php            ✓ Actions is approved, for any class
│   └── Drafts/
│       └── SaveDraft.php           ✓ anything may nest inside an approved directory
├── Models/
│   └── Invoice.php                 ✓ extends Model
├── Requests/
│   ├── StoreInvoiceRequest.php     ✓ extends FormRequest
│   └── InvoiceTotals.php           ✗ Requests holds form requests only
├── Gizmos/
│   └── InvoiceGizmo.php            ✗ Gizmos is not an approved directory
└── InvoiceHelper.php               ✗ sits directly in the module
```

`InvoiceGizmo` fails like this:

```text
Expecting the class to sit in an approved directory of App\Domains\Billing\Invoices,
but 'Gizmos' is not one. Use one of: Actions, Controllers, Data, Enums, Models,
Requests, Resources. If none fits, ask for approval before creating a new
directory or adding one to the approved list.

at app/Domains/Billing/Invoices/Gizmos/InvoiceGizmo.php:5
```

`InvoiceTotals` fails because of the type its directory names:

```text
Expecting every class in the Requests directory to be a
Illuminate\Foundation\Http\FormRequest, but this one is not. Extend or implement
Illuminate\Foundation\Http\FormRequest, or move the class to the approved
directory for what it is. If none fits, ask for approval before creating a new
directory or adding one to the approved list.

at app/Domains/Billing/Invoices/Requests/InvoiceTotals.php:5
```

Each failure has three fixes. If an approved directory fits, move the class
there: `InvoiceTotals` probably belongs in `Actions` or `Data`. If none fits,
get the new directory approved first, then add it in the same change that
first uses it:

```php
        [
            'Actions',
            'Data',
            'Gizmos', // approved for invoice gizmos: no other directory fits them
            // ...
        ],
```

If the class is a known exception, exempt it by name with
`->ignoring(InvoiceHelper::class)`, saying why, rather than approving its
directory for every module.

### Asserting a deliberate decision instead of ignoring it

A class caught by a selector that is *meant* to break the rule can be carved out
with `->ignoring(...)`:

```php
arch('notifications are queued')
    ->expect('App')
    ->classes()
    ->extending(Notification::class)
    ->toBeQueued()
    ->ignoring(App\Notifications\PaymentDeclined::class);
```

But an exclusion only records that `PaymentDeclined` was skipped, not what it was
opted into. When a class is deliberately synchronous, assert that in place with
`toBeSync()` instead — the next reader sees the decision without hunting for the
class:

```php
arch('the payment-declined alert is sent synchronously')
    ->expect(App\Notifications\PaymentDeclined::class)
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

### `toAuthorizeWithGate()` and `toMatchGateAbilities()`

- Both read source text, not the AST, and see only `Gate::allows()` called with
  a string literal. `Gate::denies()`, `Gate::authorize()`, `$user->can()`,
  policies, `can:` middleware and `@can` are not read, and an ability held in a
  variable or constant is invisible. Pass anything else that is checked through
  `$alsoChecked`.
- Text that merely mentions a `Gate::allows('...')` call with a quoted ability —
  a comment or docblock — is read as a check.
- `toAuthorizeWithGate()` reads `authorize()` from the file that declares it, so
  an inherited `authorize()` is read from the parent. Abstract classes pass
  without being checked.
- `toMatchGateAbilities()` scans every `.php` file beneath the directories,
  which includes Blade views; it skips any path containing a `vendor`
  directory.

### `toUseApprovedDirectories()`

- It reads the namespace, not the path on disk. Under PSR-4 they agree; a
  class whose namespace does not match its directory is checked by its
  namespace.
- A directory with no PHP class in it, such as one holding only views or
  JSON, is never seen.
- A directory names one type. A directory whose classes share no single
  parent or interface, such as `Actions` or `Data`, is listed without one.

## Editor and agent support

The package ships a Laravel Boost skill and guideline. Boost discovers them at
`resources/boost/skills/` and `resources/boost/guidelines/` for every installed
package, so a consuming project picks them up by running:

```bash
php artisan boost:update
```

The skill explains which verb covers which case and how to scope or exempt one;
the guideline is the short form injected into the project's AI guidelines.
