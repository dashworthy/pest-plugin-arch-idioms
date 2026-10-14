## Framework idiom expectations

This project installs `dashworthy/pest-plugin-arch-idioms`, which registers ten
Pest expectations: `toBeQueued`, `toBeQueuedAfterCommit`, `toBeSync`,
`toMatchTableName`, `toGuardMassAssignment`, `toDeclareNotificationChannels`,
`toUseMarkdownMailTemplates`, `toAuthorizeWithGate`, `toMatchGateAbilities`, and
`toUseApprovedDirectories`.

- Use them inside an ordinary `arch()` chain. There is no separate DSL. The one
  exception is `toMatchGateAbilities`, which takes a list of permission names.
- When you add a notification, mailable, job, model or form request, add or
  extend the `arch()` test that covers it rather than leaving the new class
  unasserted.
- Prefer asserting a deliberate choice (`toBeSync()`) over excluding a class
  with `->ignoring(...)`.
- Do not silence a failure with `@pest-arch-ignore-line`.
- Where a `toUseApprovedDirectories()` rule lists a module's approved
  directories, put every new class in one of them. If none fits, stop and ask
  the user to approve a new directory, saying why the existing ones do not
  fit. Do not create the directory, add it to the list or exempt the class
  with `->ignoring(...)` until they agree.
