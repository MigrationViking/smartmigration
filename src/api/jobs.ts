import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export type JobMode = 'Discovery' | 'Test Migration' | 'Migration'
export type JobStatus = 'Hold' | 'Ready' | 'Finished'
export type JobRecurrence = 'None' | 'Daily' | 'Weekly' | 'Monthly'
export type JobSourceType = 'SharePoint Library' | 'OneDrive' | 'FileShare'
export type YesNo = 'Yes' | 'No'

export interface Job {
	id: number
	title: string
	description: string | null
	group: string | null
	advancedMode: YesNo
	status: JobStatus
	scheduledDate: number
	recurrence: JobRecurrence
	mode: JobMode
	sourceType: JobSourceType
	sourceUrl: string | null
	sourceUnc: string | null
	sourceUpn: string | null
	includeSubfolders: YesNo
	includeVersionHistory: YesNo
	fromDate: number | null
	toDate: number | null
	sizeFrom: number | null
	sizeTo: number | null
	sourceFileType: string | null
	versionHistoryScope: string
	result: string | null
	createdAt: number
	updatedAt: number
	createdBy: string
}

export type JobInput = Omit<Job, 'id' | 'result' | 'createdAt' | 'updatedAt' | 'createdBy'>

function url(path = ''): string {
	return generateUrl(`/apps/smartmigration/settings/jobs${path}`)
}

export async function fetchJobs(): Promise<Job[]> {
	const { data } = await axios.get<{ jobs: Job[] }>(url())
	return data.jobs
}

export async function createJob(job: JobInput): Promise<Job> {
	const { data } = await axios.post<Job>(url(), job)
	return data
}

export async function updateJob(id: number, job: JobInput): Promise<Job> {
	const { data } = await axios.put<Job>(url(`/${id}`), job)
	return data
}

export async function patchJob(id: number, patch: Partial<Pick<Job, 'title' | 'status' | 'group' | 'scheduledDate'>>): Promise<Job> {
	const { data } = await axios.patch<Job>(url(`/${id}`), patch)
	return data
}

export async function copyJob(id: number): Promise<Job> {
	const { data } = await axios.post<Job>(url(`/${id}/copy`))
	return data
}

export async function deleteJob(id: number): Promise<void> {
	await axios.delete(url(`/${id}`))
}
