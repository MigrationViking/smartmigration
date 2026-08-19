# SMART Migration OCS API

Base URL: `/ocs/v2.php/apps/smartmigration`

Auth: HTTP Basic with a Nextcloud app password. Every request must send the
`OCS-APIRequest: true` header.

## GET /api/v1/version

Returns this app's version and the Nextcloud server version, so SMART
Migration can check compatibility before it starts polling for jobs.

**Request**

```
GET /ocs/v2.php/apps/smartmigration/api/v1/version
OCS-APIRequest: true
Authorization: Basic <base64(user:app-password)>
```

**Response** `200 OK`

```json
{
  "ocs": {
    "meta": { "status": "ok", "statuscode": 200, "message": "OK" },
    "data": {
      "appId": "smartmigration",
      "appVersion": "0.1.0",
      "apiVersion": "v1",
      "nextcloudVersion": "34.0.3.1",
      "nextcloudVersionMajor": 34
    }
  }
}
```

| Field | Type | Meaning |
|---|---|---|
| `appId` | string | Always `smartmigration`. |
| `appVersion` | string | This app's version, from `appinfo/info.xml`. |
| `apiVersion` | string | The API contract version this response was shaped for (`v1`). |
| `nextcloudVersion` | string | Full Nextcloud server version string, e.g. `34.0.3.1`. |
| `nextcloudVersionMajor` | int | Nextcloud major version, for quick compatibility checks. |

No group membership or admin rights are required — any authenticated user
with a valid app password can call this endpoint.
