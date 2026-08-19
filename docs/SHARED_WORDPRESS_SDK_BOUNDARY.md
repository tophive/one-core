# One Core — Shared SDK Boundary

One Core does not own a licensing client or updater. Licensing remains theme-owned.

One Core consumes the stable read-only entitlement filters exposed by the One theme. The obsolete dormant EDD updater source under `t/inc/updater/` is removed so future releases cannot accidentally reintroduce a second updater or license runtime.
