# SMART Migration OCS API

The API SMART Migration uses to talk to this app.

- **Base URL:** `/ocs/v2.php/apps/smartmigration`
- **Auth:** HTTP Basic with a Nextcloud **app password** (not the account password)
- **Required header:** `OCS-APIRequest: true` — without it Nextcloud rejects the request
- **Recommended header:** `Accept: application/json` — otherwise responses come back as XML

This app is **passive**. It never calls out to SMART Migration, never contacts
Microsoft 365, and has no background jobs. SMART Migration polls it.

## Authentication

Create an app password in Nextcloud under **Settings -> Security -> Devices & sessions**,
or from the command line:

```
nextcloud.occ user:add-app-password <user> --name="SMART Migration"
```

Use a dedicated service account. The credential can be revoked at any time from the
same Security page, which immediately cuts off API access without touching the data.

```
curl -u 'smartservice:APP-PASSWORD' \
     -H 'OCS-APIRequest: true' \
     -H 'Accept: application/json' \
     https://cloud.example.com/ocs/v2.php/apps/smartmigration/api/v1/version
```

Any authenticated Nextcloud user with a valid app password may call these endpoints.
Admin rights are **not** required.

## Response envelope

Every response is wrapped in the standard OCS envelope. The payload is under
`ocs.data`, and the real status code is `ocs.meta.statuscode`:

```json
{
  "ocs": {
    "meta": { "status": "ok", "statuscode": 200, "message": "OK" },
    "data": { }
  }
}
```

On failure, `ocs.meta.status` is `failure`, `ocs.meta.statuscode` carries the code,
and `ocs.data.message` explains what went wrong:

```json
{
  "ocs": {
    "meta": { "status": "failure", "statuscode": 400, "message": "" },
    "data": { "message": "licenseKey must not exceed 40 characters" }
  }
}
```

**Dates are unix seconds** (integers), never formatted strings — everywhere in this API.

---

## GET /api/v1/version

Reports this app's version and the Nextcloud server version, so SMART Migration can
check compatibility before it starts polling.

**Request**

```
GET /ocs/v2.php/apps/smartmigration/api/v1/version
OCS-APIRequest: true
Authorization: Basic <base64(user:app-password)>
```

**Response** `200 OK`

```json
{
  "appId": "smartmigration",
  "appVersion": "0.1.2",
  "apiVersion": "v1",
  "requiredSmartVersion": "1.0.0",
  "nextcloudVersion": "34.0.3.1",
  "nextcloudVersionMajor": 34
}
```

| Field | Type | Meaning |
|---|---|---|
| `appId` | string | Always `smartmigration`. |
| `appVersion` | string | This app's version, from `appinfo/info.xml`. |
| `apiVersion` | string | The API contract version this response was shaped for (`v1`). |
| `requiredSmartVersion` | string | The minimum SMART Migration version this app's contract works with, max 10 characters. Maintained by hand in `lib/AppInfo/Application.php`; compare it against your own version before polling. |
| `nextcloudVersion` | string | Full Nextcloud server version string, e.g. `34.0.3.1`. |
| `nextcloudVersionMajor` | int | Nextcloud major version, for quick compatibility checks. |

---

## GET /api/v1/settings/license

Reads the stored licence and the name and version the remote SMART Migration server
last reported. Every field is `null` until a licence has been written, which is the normal
state of a fresh install.

**Response** `200 OK`

```json
{
  "smServerName": "SMART-PROD-01",
  "licenseKey": "SMART-TEST-0001",
  "expirationDate": 1800000000,
  "currentSmVersion": "2.4.1"
}
```

| Field | Type | Meaning |
|---|---|---|
| `smServerName` | string\|null | Name the SMART Migration server calls itself, max 64 characters. `null` if never reported or cleared. |
| `licenseKey` | string\|null | The licence key, max 40 characters. `null` if never set or cleared. |
| `expirationDate` | int\|null | Expiry as unix seconds. `null` if never set, or if the licence does not expire. |
| `currentSmVersion` | string\|null | Version the SMART Migration server reported about itself, max 20 characters. `null` if never reported. Compare against `requiredSmartVersion` from the version endpoint. |

---

## PUT /api/v1/settings/license

Stores the licence key and its expiry. SMART Migration writes this after the customer
activates a licence; the Settings tab in the Nextcloud UI only ever displays it.

**All four fields are replaced on every call** — this is a PUT, not a partial update.
Omitting any field sets it to `null`, so always send the set together unless you
deliberately want to clear one.

### Clearing a field

Every field can be cleared by sending it empty, which is useful for testing the app's
unlicensed and "no server has reported in" states:

> **No field is required.** Because this is a full replace, a call that omits a field
> clears it — `PUT` with only `licenseKey` wipes both `expirationDate` and
> `currentSmVersion`. Always send the whole set.

| Field | Empty value | Result |
|---|---|---|
| `smServerName` | `smServerName=` | Stored as `null`. Whitespace-only clears it too. |
| `licenseKey` | `licenseKey=` | Stored as `null`, which puts the UI back into its unlicensed state. Whitespace-only clears it too. |
| `expirationDate` | `expirationDate=` | Stored as `null`. Nextcloud casts an empty value to `0` for an int parameter, and this app treats `0` as "no expiry". |
| `currentSmVersion` | `currentSmVersion=` | Stored as `null`. Whitespace-only is trimmed to empty and also stored as `null`. |

The same holds for all three fields of `PUT /api/v1/settings/support`: an empty or
whitespace-only value stores `null`.

Clearing `currentSmVersion` puts the UI back into its fresh-install state, where it
reports that no SMART Migration server has connected yet.

**Request**

```
PUT /ocs/v2.php/apps/smartmigration/api/v1/settings/license
OCS-APIRequest: true
Content-Type: application/x-www-form-urlencoded

smServerName=SMART-PROD-01&licenseKey=SMART-TEST-0001&expirationDate=1800000000&currentSmVersion=2.4.1
```

| Parameter | Type | Required | Meaning |
|---|---|---|---|
| `smServerName` | string | no | Name the SMART Migration server calls itself. Trimmed, max 64 characters. Empty or omitted stores `null`. |
| `licenseKey` | string | no | The licence key. Trimmed, max 40 characters. Empty or omitted stores `null`. |
| `expirationDate` | int | no | Expiry as unix seconds. Defaults to `null`. `0` and an empty value both mean "no expiry" and are stored as `null`. |
| `currentSmVersion` | string | no | Version the SMART Migration server reports about itself. Trimmed, max 20 characters. Defaults to `null`. |

**Response** `200 OK` — echoes back what was stored:

```json
{
  "smServerName": "SMART-PROD-01",
  "licenseKey": "SMART-TEST-0001",
  "expirationDate": 1800000000,
  "currentSmVersion": "2.4.1"
}
```

**Errors**

| Code | `data.message` | Cause |
|---|---|---|
| `400` | `smServerName must not exceed 64 characters` | Name longer than the 64-character column. Rejected rather than silently truncated. |
| `400` | `licenseKey must not exceed 40 characters` | Key longer than the 40-character column. Rejected rather than silently truncated. |
| `400` | `currentSmVersion must not exceed 20 characters` | Version longer than the 20-character column. Rejected rather than silently truncated. |

The licence lives in a single row of `oc_smartmig_settings`, created on first write.
Calling this endpoint repeatedly updates that row; it never accumulates rows.

**Example**

```
curl -u 'smartservice:APP-PASSWORD' -X PUT \
     -H 'OCS-APIRequest: true' -H 'Accept: application/json' \
     -d 'licenseKey=SMART-TEST-0001' \
     -d 'expirationDate=1800000000' \
     https://cloud.example.com/ocs/v2.php/apps/smartmigration/api/v1/settings/license
```

---

## GET /api/v1/settings/support

Reads the stored support contact — who the customer calls when a migration goes wrong.
All three fields are `null` until a contact has been written.

**Response** `200 OK`

```json
{
  "supportName": "Ida Berg",
  "supportEmail": "support@migratedms.com",
  "supportCompany": "MigrateDMS"
}
```

| Field | Type | Meaning |
|---|---|---|
| `supportName` | string\|null | Contact person, max 50 characters. `null` if never set. |
| `supportEmail` | string\|null | Contact email address, max 50 characters. `null` if never set. |
| `supportCompany` | string\|null | Contact company, max 50 characters. `null` if never set. |

---

## PUT /api/v1/settings/support

Stores the support contact. SMART Migration writes this; the Support tab in the
Nextcloud UI only ever displays it.

**All three fields are replaced on every call** — omitting one sets it to `null`, so
send the whole contact together. Blank and whitespace-only values are stored as `null`.

This is deliberately a separate endpoint from the licence: the two are written at
different times, and a shared full-replace PUT would let one wipe the other.

**Request**

```
PUT /ocs/v2.php/apps/smartmigration/api/v1/settings/support
OCS-APIRequest: true
Content-Type: application/x-www-form-urlencoded

supportName=Ida+Berg&supportEmail=support@migratedms.com&supportCompany=MigrateDMS
```

| Parameter | Type | Required | Meaning |
|---|---|---|---|
| `supportName` | string | no | Contact person. Trimmed, max 50 characters. Defaults to `null`. |
| `supportEmail` | string | no | Contact email address. Trimmed, max 50 characters. Defaults to `null`. |
| `supportCompany` | string | no | Contact company. Trimmed, max 50 characters. Defaults to `null`. |

**Response** `200 OK` — echoes back what was stored.

**Errors**

| Code | `data.message` | Cause |
|---|---|---|
| `400` | `<field> must not exceed 50 characters` | The named field is longer than its 50-character column. Rejected rather than silently truncated. |

The contact shares the single `oc_smartmig_settings` row with the licence, created on
first write by either endpoint.

**Example**

```
curl -u 'smartservice:APP-PASSWORD' -X PUT \
     -H 'OCS-APIRequest: true' -H 'Accept: application/json' \
     -d 'supportName=Ida Berg' \
     -d 'supportEmail=support@migratedms.com' \
     -d 'supportCompany=MigrateDMS' \
     https://cloud.example.com/ocs/v2.php/apps/smartmigration/api/v1/settings/support
```

---

## Not yet implemented

The job and run-history endpoints described in the project brief are **not built yet**.
When they land they will be documented here:

```
GET    /api/v1/jobs?status=pending&type=migration&limit=50
PATCH  /api/v1/jobs/{id}                -> status, result summary, counts
POST   /api/v1/runs                     -> create run-history row
PATCH  /api/v1/runs/{id}                -> progress during a run
```

## Internal admin UI endpoints (not part of this contract)

`lib/Controller/JobsController.php` and `lib/Controller/SettingsController.php` expose
routes under `/apps/smartmigration/settings/...` that back the Vue admin UI:

| Route | Purpose |
|---|---|
| `GET /settings/jobs` | List all jobs for the Jobs tab |
| `POST /settings/jobs` | Create a job |
| `GET /settings/jobs/{id}` | Read one job |
| `PUT /settings/jobs/{id}` | Full update from the edit dialog |
| `PATCH /settings/jobs/{id}` | Partial update from inline table editing |
| `POST /settings/jobs/{id}/copy` | Duplicate a job from the row menu |
| `DELETE /settings/jobs/{id}` | Delete a job |
| `GET /settings/license` | The Settings tab, read-only |
| `GET /settings/support` | The Support tab, read-only |

These use the normal Nextcloud session plus CSRF token, require an admin user, and are
**not** reachable via app password or the OCS layer. SMART Migration must not call them —
the `/api/v1/...` endpoints above are the only supported integration surface.

Note the asymmetry for the licence and the support contact: the UI can only **read** them
(`GET /settings/license`, `GET /settings/support`), because writing is SMART Migration's
job via the matching `PUT /api/v1/settings/...` endpoints. There is deliberately no UI
write path for either.
