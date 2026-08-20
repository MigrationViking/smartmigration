# SMART Migration for Nextcloud

Read this before making changes. It is the working brief for this repo.

## What this app is

A Nextcloud app that is a paralel to the Microsoft Teams / SharePoint list layer of
MigrateDMS **Content Governance**.

Today, Content Governance stores migration job definitions as items in SharePoint
lists inside a dedicated Teams team. SMART Migration reads those list items,
executes the actions they describe, writes status and results back onto the item,
and appends a row to a run-history list describing the job session.

This app reproduces that contract inside Nextcloud. Nothing else changes about how
SMART Migration works.

Sources in scope: SharePoint / SharePoint Online, OneDrive for Business, Teams
document libraries, and file shares. Files only — no email, no calendar. However, we are in process of integrating external service for emil and calendar migration.

The product story is discovery and interactive business intelligence before,
during, and after migration — not just moving bytes. Discovery-only jobs are a
complete, valid outcome, not an unfinished migration.

The Business intelligence data is maintained by SMART Migration and is using MS Power BI.

## Hard architectural rules

These are decided. Do not propose alternatives.

1. **The app is passive.** It makes no outbound HTTP requests, ever. No HTTP
   client, no cron job that calls out, no webhook delivery. SMART Migration polls
   this app.
2. **The app owns its data in the Nextcloud database**, created through the
   standard Nextcloud migration API. No external database connection.
3. **SMART Migration never touches the database directly.** All access is through
   the app's OCS API.
4. **The app never talks to Microsoft 365.** No Graph, no CSOM, no SharePoint
   knowledge. It stores job definitions and results.

Two consumers:

| Consumer | Path | Auth |
|---|---|---|
| Human users (Vue UI) | Nextcloud Administration settings (`Settings\Admin`/`Settings\Section`), not a top-nav app | Normal Nextcloud session, admin |
| SMART Migration | `/ocs/v2.php/apps/smartmigration/api/...` | App password over Basic |

## Authentication

**Write no custom auth code.**

SMART Migration already authenticates to Nextcloud with a Nextcloud user app
password (already documented in the Content Governance getting-started guide,
used for file upload). The same credential authenticates the OCS API.

Nextcloud's OCS layer accepts app passwords over HTTP Basic natively. Controllers
extend `OCSController` and carry `#[NoAdminRequired]`. Requests must send the
`OCS-APIRequest: true` header.

- Revocation is Settings -> Security in Nextcloud. Nothing to build.
- Authorization is Nextcloud group membership via `IGroupManager`. Do not invent
  a permission model.
- The service account is a normal Nextcloud user. Recommend a dedicated one in
  docs; do not enforce it in code.

## Data model

Four tables, mirroring the Content Governance list structure. Prefix
`smartmig_` (Nextcloud prepends `oc_`). Keep identifiers short — Oracle support
caps them at 30 characters.

Content Governance job types today: Discovery ,Migration, Import Jobs

> **TODO — not yet decided.** The required tables and their columns are not pinned
> down. Derive them from the existing SharePoint list schemas rather than
> inventing them. Rename columns more intitively since the MS Teams lists have evolved over time.


Every table gets `id`, `created_at`, `updated_at` (bigint unix), `created_by`
(uid, string 64). Job tables also get `status` (string 32) — the field SMART
writes back.

A job's source a library URL ora file share is a UNC.

Run-history rows are a retention artifact — people will read them years later to
answer "where did this file come from". Treat them as immutable once written and
do not prune them on a schedule.
There must be a link from the job to all runhistory rows.

## API contract

Versioned under `/api/v1`. Shape it around what SMART does, not around raw rows:

```
GET    /api/v1/jobs?status=pending&type=migration&limit=50
PATCH  /api/v1/jobs/{id}                -> status, result summary, counts
POST   /api/v1/runs                     -> create run-history row
PATCH  /api/v1/runs/{id}                -> progress during a run
```

- **Multiple SMART instancejobs polls this app, **
  job claiming required, 
  If that assumption changes, revisit this section first.
- **Whitelist filter and sort columns.** Never build SQL from client-supplied
  column names.
- Return OCS-shaped envelopes via `DataResponse` with proper HTTP status codes.

## Environment

Development happens directly on a Hostinger VPS — there is no local environment.

- Nextcloud **34.0.3, installed as a snap**, tracking `latest/beta`, auto-refresh
  enabled. The server version can change under you; check `snap changes` before
  assuming a new failure is your code.
- `occ` is invoked as **`nextcloud.occ`**, not `php occ`.
- App path: `/var/snap/nextcloud/current/nextcloud/extra-apps/smartmigration`,
  owned `root:root`, mode 755.
- **The app is served at URL prefix `/extra-apps`, not `/apps`.** Always build
  URLs with `@nextcloud/router` (`generateUrl`, `generateOcsUrl`, `imagePath`).
  Never string-concatenate a path — it will work on most installs and break on
  this one.
- Host toolchain: PHP 8.3 CLI (also default `php`), Composer 2.10, Node 24,
  npm 11. The template requires Node ^24 — Node 22 warns and eventually breaks.
- Logs: `/var/snap/nextcloud/current/nextcloud/data/nextcloud.log`. Xdebug is not
  practical under snap confinement, so `tail -f` on that log is the debugger.
- **The snap ships no `tests/` directory** — it's a packaged production build, not
  a git clone of `nextcloud/server`. Don't write app tests that assume a core
  `tests/bootstrap.php` exists three levels up; it never will on this box. See
  the unit-testing note under Codebase conventions.


## Codebase conventions

- PHP 8.3, namespace `OCA\SmartMigration\`.
- **There is no `appinfo/routes.php`.** Routing is via PHP 8 attributes
  (`#[ApiRoute]`, `#[FrontpageRoute]`) on controller methods. Add routes as
  annotated methods.
- Entities extend `OCP\AppFramework\Db\Entity`; data access via `QBMapper`. No raw
  SQL strings.
- Migrations in `lib/Migration/`, extending `SimpleMigrationStep`, using
  `ISchemaWrapper`. **Migrations are append-only — never edit a shipped one.**
- Constructor-based dependency injection. Never `\OC::$server`.
- All user-facing strings through `t()` / `IL10N`.
- **Unit tests are isolated, not integration tests.** `tests/bootstrap.php` only
  requires the app's own Composer autoloader — no core bootstrap, no
  `\OC_App::loadApp()`. `OCP\*`/`NCU\*` classes resolve at test time via an
  `autoload-dev` PSR-4 mapping onto the `nextcloud/ocp` stub package (already a
  `require-dev` dependency), which is dropped from any `--no-dev` build. If a
  class needs a live server to test, that's a signal it's reaching for
  `\OC::$server` or similar — fix the class, don't reintroduce a core bootstrap.
- Frontend: Vue 3 + TypeScript, built with Vite. `npm run build` outputs to `js/`
  and `css/`, both gitignored.

  // STEEN dev. info -------------------
  When changesa are don in github - make pull on vps terminal
    cd /var/snap/nextcloud/current/nextcloud/extra-apps/smartmigration
    git pull

  If changes made on vps and commioted from there server
    git config --global pull.rebase true
    git pull

  Gow to build:
    npm run build     # production  ctrrl.f5 til reload i browser
    Or leave npm run watch running in a spare terminal and it rebuilds on every save — then you only need the hard reload.

    appinfo/info.xml — needs the app reloaded.
    nextcloud.occ app:disable smartmigration && nextcloud.occ app:enable smartmigration

    Always npm run build before a release tag.
 -------------------------------------

## Reference implementations on this box

Other apps are installed alongside this one in `extra-apps/`. Read them for house
style rather than working from generic tutorials:

- `tables/` — closest analogue to what we're building
- `metavox/` — MigrateDMS-adjacent, from a known partner
- `groupfolders/`, `files_accesscontrol/` — mature first-party patterns

## Frontend design

## SMART Migration Dependency
SMART Migration is required to be installed in the customeres infrastructure with MS App Registration using a pfx or installed cert fro access to the MS tenant. For SharePoint on-prem NTLM auth is used.

**Look native, not distinctive.** This app should be indistinguishable from a
first-party Nextcloud app. Use `NcAppContent`, `NcAppNavigation`, `NcButton`,
`NcModal`, `NcLoadingIcon`, `NcEmptyContent` from `@nextcloud/vue`. No custom
design system, no CSS framework, no custom color tokens — server CSS variables
(`--color-primary`, `--color-main-background`) give theming and dark mode free.

- UI Must support English, German,French,Danish and make room for other languages.

-UI The UI controls must be the latest Nextcloud native controls - not something else. The app must be complinet to Nextcloud and have the best possible look a like other native nextcloud apps and MetaVox.

The UI's job: let a migration consultant define, review, and monitor job rows —
the same task they do in a Teams list today.

Empty states matter. A fresh install has no jobs and no runs; that screen should
say how to create the first job, not "no data". And the screen should list partneres to contact in order to get the required SMART Migration installer and trial license key.

## Tab and dialog pattern — Jobs is the reference

The Jobs tab is the built, agreed shape for every other tab (Run History, Settings,
Partners). Copy these patterns rather than inventing new ones.

| File | Role |
|---|---|
| `src/components/JobsTab.vue` | Toolbar, table, inline editing, row menu, delete confirm |
| `src/components/JobEditDialog.vue` | The create/edit dialog for one row |
| `src/api/jobs.ts` | Typed axios client plus the `Job` / `JobInput` types |

### The `NcTextField` null trap — read this first

`NcInputField` renders `value: modelValue.value.toString()`. **A `null` model value
throws, Vue swallows the render error, and the field silently renders nothing.** No
console-visible layout gap, no error in the UI — the control just isn't there. This
cost a full debugging session; do not rediscover it.

- Never bind `NcTextField` to a nullable value. `NcTextArea` survives null at runtime
  (it renders `modelValue` raw), which is why textareas stayed visible where text fields
  vanished — but it still **types** `modelValue` as `string`, so a nullable binding is a
  type error and relies on undeclared behaviour. Don't lean on it; hold strings for both.
- In dialogs, hold every text-field-bound value as a **string** in the form state and
  convert on save (see below).
- For read-only or inline bindings, guard at the call site: `:model-value="job.group ?? ''"`.
- **If a control is mysteriously missing from a form, suspect a null binding before
  suspecting the `v-if`.**

### Dialog form state

`JobEditDialog` keeps a `FormState` type — `JobInput` with every nullable field bound to
a text control (`description`, `group`, `sourceUrl`, `sourceUnc`, `sourceUpn`, `sizeFrom`,
`sizeTo`, `sourceFileType`) widened to `string`. Loading an existing row coerces with
`?? ''`; every widened field must be converted back in `save()` via
`trimmedOrNull()` / `numberOrNull()` so the API still receives the `string | null` and
`number | null` its contract declares. Numeric fields are plain strings with
`type="number"` — **do not use `v-model.number`**, it renders a literal `NaN` when the
user clears the field.

### Conditional visibility

Two independent mechanisms, both plain `v-if`:

1. **Advanced Mode** — a `showAdvanced` computed (`form.advancedMode === 'Yes'`) gates
   Scheduled Date, Recurrence, From/To Date, Size From/To, and Source File Type.
2. **Field-driven** — Source URL / UNC / UPN each appear for exactly one Source Type
   (SharePoint Library / FileShare / OneDrive); Version History Scope appears only when
   Include Version History is checked.

**Hiding is cosmetic only.** Hidden values stay in the form state and round-trip
through `save()` untouched; the settings remain in force for the job. Never clear a
value just because its field is hidden.

### Dialog layout

`NcDialog size="large"` wrapping a scrollable `.job-edit-form` (`max-height: 70vh`).
Inside it: the Advanced Mode toggle first, then `<h3>` section headings — Job, Action,
Source. Each field is a `.job-edit-form__field` (bold `<label>`, control, then a
`.job-edit-form__hint` `<p>`); pairs of fields share a `.job-edit-form__grid`.

### Color scheme — use the right half of each variable pair

Server CSS variables only, as the Frontend design section requires. The trap is that
Nextcloud ships several variants per semantic color and **most of them are fills, not
text colors**:

| Variable | Light | Dark | Use for |
|---|---|---|---|
| `--color-success` | `#D8F3DA` | `#11321A` | Backgrounds only — invisible as text in both themes |
| `--color-success-text` | `#005416` | `#D5F2DC` | Text on a success *fill*; near-white on a dark page |
| `--color-element-success` | `#099f05` | `#40A330` | **Saturated accent — section headings** |

Section `<h3>`s use `var(--color-element-success)`, which stays clearly green and AA-
contrast in both themes. Section separators are `border-top: 1px solid var(--color-border)`
on each `<h3>`, so adding a section adds its rule automatically. Hint text is
`var(--color-text-maxcontrast)` — don't reuse that for headings, they would match.
Before picking any color variable, check its actual value in
`/snap/nextcloud/current/htdocs/apps/theming/lib/Themes/{DefaultTheme,DarkTheme}.php`
rather than guessing from the name.

### Table row interactions

- **Inline editing** for a few high-traffic columns (title, status, scheduled date,
  group) via `patchJob`; everything else is edited in the dialog. Rows are never
  editable in place the way Nextcloud Tables allows.
- **Optimistic updates with rollback**: mutate the row, remember the previous value in
  a `Map`, `await` the PATCH, and restore from the map plus `showError()` on failure.
- **Multi-row replication**: `bulkTargets(job)` returns the whole selection when the
  edited row is part of a multi-row selection, otherwise just that row. Inline edits to
  status, group, and scheduled date replicate across the selection.
- **Row menu order**: row-scoped actions first (Edit, Copy, Delete), then an
  `NcActionSeparator`, then New last — New is the one action that doesn't act on the
  clicked row, so it stays visually separated.
- **Double-click a row opens the edit dialog**, but `onRowDoubleClick` ignores clicks
  landing on `input, textarea, select, button, a, label, .jobs-tab__inline-field` —
  otherwise double-clicking a word in an inline field to select it would yank the user
  into a dialog.

### Checks before calling frontend work done

`npm run lint`, `npm run stylelint`, `npm run typecheck`, and `npm run build` must all
pass, then hard-reload (Ctrl+F5). The UI is a single-page Vue app: clicking around will
not pick up a rebuild, only a full reload will.

**`npm run typecheck` (`vue-tsc --noEmit`) is the one that catches binding bugs.** Vite
transpiles without checking and eslint does no type analysis, so a wrong prop type — the
nullable-binding bug above — builds perfectly green. Run the typecheck after touching any
`.vue` file; a clean `npm run build` is not evidence the types are sound.

Two prop-type gotchas it enforces, both from `@nextcloud/vue`:

- `NcTextField` and `NcTextArea` type `modelValue` as non-nullable, so bind strings.
- `NcTextField` **emits** `string | number` (it supports `type="number"`), so a handler
  taking a `string` needs `String($event)` at the call site.

Run `vue-tsc` through the script, never `npx vue-tsc` — npx resolves its own TypeScript
copy and dies with `ERR_PACKAGE_PATH_NOT_EXPORTED`.

## Distribution

- Free and open source, AGPL-3.0-or-later, published on the Nextcloud App Store.
- App ID `smartmigration`. Signing certificate comes from Nextcloud's own CA
  (CSR submitted as PR #1161) — **not** the Authenticode certificate MigrateDMS
  uses for `.exe` files.
- `.github/workflows/release.yml` builds and packages on a `v*` tag. Signing gets
  added there once the certificate arrives.
- Hosting partners may fork or rebrand; MigrateDMS maintains the core. Keep
  branding in few, overridable places.

## Definition of done

- psalm and php-cs-fixer clean
- `npm run build` succeeds
- `npm run typecheck` clean (Vite does not typecheck — see the Jobs tab pattern section)
- unit tests cover new mapper and controller logic
- no new outbound network calls
- no raw SQL concatenation
- no hardcoded URL paths
- new API endpoints documented in `docs/api.md`
