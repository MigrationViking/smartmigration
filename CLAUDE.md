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
| Human users (Vue UI) | `/apps/smartmigration/...` | Normal Nextcloud session |
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

The UI's job: let a migration consultant define, review, and monitor job rows —
the same task they do in a Teams list today.

Empty states matter. A fresh install has no jobs and no runs; that screen should
say how to create the first job, not "no data". And the screen should list partneres to contact in order to get the required SMART Migration installer and trial license key.

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
- unit tests cover new mapper and controller logic
- no new outbound network calls
- no raw SQL concatenation
- no hardcoded URL paths
- new API endpoints documented in `docs/api.md`
