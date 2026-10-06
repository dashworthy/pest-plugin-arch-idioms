## Framework idiom expectations

This project installs `dashworthy/pest-plugin-arch-idioms`, which registers nine
Pest expectations: `toBeQueued`, `toBeQueuedAfterCommit`, `toBeSync`,
`toMatchTableName`, `toGuardMassAssignment`, `toDeclareNotificationChannels`,
`toUseMarkdownMailTemplates`, `toAuthorizeWithGate`, and `toMatchGateAbilities`.

- Use them inside an ordinary `arch()` chain. There is no separate DSL. The one
  exception is `toMatchGateAbilities`, which takes a list of permission names.
- When you add a notification, mailable, job, model or form request, add or
  extend the `arch()` test that covers it rather than leaving the new class
  unasserted.
- Prefer asserting a deliberate choice (`toBeSync()`) over excluding a class
  with `->ignoring(...)`.
- Do not silence a failure with `@pest-arch-ignore-line`.
