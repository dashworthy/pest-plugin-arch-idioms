## Framework idiom expectations

This project installs `dashworthy/pest-plugin-arch-idioms`, which registers seven
Pest architecture expectations: `toBeQueued`, `toBeQueuedAfterCommit`, `toBeSync`,
`toMatchTableName`, `toGuardMassAssignment`, `toDeclareNotificationChannels`, and
`toUseMarkdownMailTemplates`.

- Use them inside an ordinary `arch()` chain. There is no separate DSL.
- When you add a notification, mailable, job or model, add or extend the
  `arch()` test that covers it rather than leaving the new class unasserted.
- Prefer asserting a deliberate choice (`toBeSync()`) over excluding a class
  with `->ignoring(...)`.
- Do not silence a failure with `@pest-arch-ignore-line`.
