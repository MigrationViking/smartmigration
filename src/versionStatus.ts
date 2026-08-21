import type { License } from './api/settings'

/**
 * Where the remote SMART Migration server stands relative to the version this
 * app is built against.
 *
 * - `unreported` — no server has written its version yet, normal on a fresh install
 * - `mismatch` — a server reported a version, and it is not the required one
 * - `match` — the reported version is the required one
 */
export type VersionStatus = 'unreported' | 'mismatch' | 'match'

/**
 * Null until the licence has loaded, so callers do not flash a state while the
 * request is still in flight.
 *
 * @param license - The licence payload, or null while it is still loading
 */
export function versionStatus(license: License | null): VersionStatus | null {
	if (license === null) {
		return null
	}
	if (!license.currentSmVersion) {
		return 'unreported'
	}

	return license.currentSmVersion === license.requiredSmartVersion ? 'match' : 'mismatch'
}
