import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export interface License {
	/** Name the remote SMART Migration server calls itself. */
	smServerName: string | null
	licenseKey: string | null
	/** Unix seconds, or null when the licence does not expire. */
	expirationDate: number | null
	/** Version the remote SMART Migration server reports about itself. */
	currentSmVersion: string | null
	/** Minimum version this app is built against, from Application::REQUIRED_SMART_VERSION. */
	requiredSmartVersion: string
}

function url(path = ''): string {
	return generateUrl(`/apps/smartmigration/settings${path}`)
}

export async function fetchLicense(): Promise<License> {
	const { data } = await axios.get<License>(url('/license'))
	return data
}

export interface SupportContact {
	supportName: string | null
	supportEmail: string | null
	supportCompany: string | null
}

export async function fetchSupportContact(): Promise<SupportContact> {
	const { data } = await axios.get<SupportContact>(url('/support'))
	return data
}
