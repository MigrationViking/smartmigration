<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t, n } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionRadio from '@nextcloud/vue/components/NcActionRadio'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import JobEditDialog from './JobEditDialog.vue'
import { type Job, type JobInput, copyJob, createJob, deleteJob, fetchJobs, patchJob, updateJob } from '../api/jobs'
import { fromDatePicker, toDate } from '../utils/datetime'

const jobs = ref<Job[]>([])
const loading = ref(true)
const filter = ref('')

const dialogOpen = ref(false)
const editingJob = ref<Job | null>(null)
const dialogSaving = ref(false)

const selectedIds = ref<Set<number>>(new Set())
const deleteConfirmOpen = ref(false)
const deleteTarget = ref<Job | null>(null)
const deleting = ref(false)
const lastSelectedIndex = ref<number | null>(null)
let pendingShiftKey = false

const allSelected = computed(() => filteredJobs().length > 0 && filteredJobs().every((job) => selectedIds.value.has(job.id)))
const bulkEditActive = computed(() => selectedIds.value.size > 1)

const deleteConfirmMessage = computed(() => deleteTarget.value
	? t('smartmigration', 'Are you sure you want to delete "{title}"?', { title: deleteTarget.value.title })
	: n('smartmigration', 'Are you sure you want to delete %n selected job?', 'Are you sure you want to delete %n selected jobs?', selectedIds.value.size))

function errorMessage(error: unknown, fallback: string): string {
	const response = (error as { response?: { data?: { message?: string } } })?.response
	return response?.data?.message ?? fallback
}

async function load() {
	loading.value = true
	try {
		jobs.value = await fetchJobs()
		const validIds = new Set(jobs.value.map((job) => job.id))
		selectedIds.value = new Set([...selectedIds.value].filter((id) => validIds.has(id)))
		lastSelectedIndex.value = null
	} catch (error) {
		console.error('Failed to load jobs', error)
		showError(errorMessage(error, t('smartmigration', 'Could not load the job list.')))
	} finally {
		loading.value = false
	}
}

onMounted(load)

function filteredJobs(): Job[] {
	const needle = filter.value.trim().toLowerCase()
	if (!needle) {
		return jobs.value
	}
	return jobs.value.filter((job) => [job.title, job.mode, job.status, job.group]
		.some((value) => value?.toLowerCase().includes(needle)))
}

function isSelected(job: Job): boolean {
	return selectedIds.value.has(job.id)
}

function bulkTargets(job: Job): Job[] {
	return selectedIds.value.size > 1 && selectedIds.value.has(job.id)
		? jobs.value.filter((candidate) => selectedIds.value.has(candidate.id))
		: [job]
}

/** Drives the per-status colour on the inline status control. */
function statusClass(status: string): string {
	return `jobs-tab__status--${status.toLowerCase().replace(/\s+/g, '-')}`
}

function rememberShiftKey(event: MouseEvent) {
	pendingShiftKey = event.shiftKey
}

function toggleSelected(job: Job, selected: boolean) {
	const rows = filteredJobs()
	const index = rows.findIndex((row) => row.id === job.id)
	const shiftKey = pendingShiftKey
	pendingShiftKey = false

	const next = new Set(selectedIds.value)

	if (shiftKey && lastSelectedIndex.value !== null && index !== -1) {
		const [start, end] = [lastSelectedIndex.value, index].sort((a, b) => a - b)
		for (let i = start; i <= end; i++) {
			if (selected) {
				next.add(rows[i].id)
			} else {
				next.delete(rows[i].id)
			}
		}
	} else if (selected) {
		next.add(job.id)
	} else {
		next.delete(job.id)
	}

	selectedIds.value = next
	if (index !== -1) {
		lastSelectedIndex.value = index
	}
}

function toggleSelectAll(selected: boolean) {
	if (selected) {
		selectedIds.value = new Set(filteredJobs().map((job) => job.id))
	} else {
		selectedIds.value = new Set()
	}
	lastSelectedIndex.value = null
}

function openCreateDialog() {
	editingJob.value = null
	dialogOpen.value = true
}

function openEditDialog(job: Job) {
	editingJob.value = job
	dialogOpen.value = true
}

/**
 * Open the edit dialog on double-click, but leave the inline controls alone: double-clicking
 * a word in the Title or Group field has to keep selecting that word rather than open a dialog.
 *
 * @param job - the row that was double-clicked
 * @param event - the originating double-click
 */
function onRowDoubleClick(job: Job, event: MouseEvent) {
	const target = event.target as HTMLElement | null
	if (target?.closest('input, textarea, select, button, a, label, .jobs-tab__inline-field')) {
		return
	}
	openEditDialog(job)
}

async function copyRow(job: Job) {
	try {
		await copyJob(job.id)
		await load()
		showSuccess(t('smartmigration', 'Job copied.'))
	} catch (error) {
		console.error('Failed to copy job', error)
		showError(errorMessage(error, t('smartmigration', 'Could not copy the job.')))
	}
}

function requestDeleteRow(job: Job) {
	deleteTarget.value = job
	deleteConfirmOpen.value = true
}

function requestDeleteSelected() {
	if (selectedIds.value.size === 0) {
		return
	}
	deleteTarget.value = null
	deleteConfirmOpen.value = true
}

async function confirmDelete() {
	deleting.value = true
	try {
		const ids = deleteTarget.value ? [deleteTarget.value.id] : [...selectedIds.value]
		await Promise.all(ids.map((id) => deleteJob(id)))
		deleteConfirmOpen.value = false
		deleteTarget.value = null
		selectedIds.value = new Set()
		await load()
		showSuccess(n('smartmigration', '%n job deleted.', '%n jobs deleted.', ids.length))
	} catch (error) {
		console.error('Failed to delete jobs', error)
		showError(errorMessage(error, t('smartmigration', 'Could not delete the job(s).')))
		await load()
	} finally {
		deleting.value = false
	}
}

async function saveDialog(input: JobInput) {
	dialogSaving.value = true
	try {
		if (editingJob.value) {
			await updateJob(editingJob.value.id, input)
		} else {
			await createJob(input)
		}
		dialogOpen.value = false
		await load()
		showSuccess(t('smartmigration', 'Job saved.'))
	} catch (error) {
		console.error('Failed to save job', error)
		showError(errorMessage(error, t('smartmigration', 'Could not save the job.')))
	} finally {
		dialogSaving.value = false
	}
}

async function updateTitle(job: Job, title: string) {
	if (title === job.title) {
		return
	}
	const previous = job.title
	job.title = title
	try {
		await patchJob(job.id, { title })
	} catch (error) {
		job.title = previous
		console.error('Failed to update job title', error)
		showError(errorMessage(error, t('smartmigration', 'Could not update the title.')))
	}
}

async function updateStatus(job: Job, status: Job['status']) {
	if (status === job.status) {
		return
	}
	const targets = bulkTargets(job)
	const previous = new Map(targets.map((target) => [target.id, target.status]))
	targets.forEach((target) => {
		target.status = status
	})

	try {
		await Promise.all(targets.map((target) => patchJob(target.id, { status })))
		if (targets.length > 1) {
			showSuccess(n('smartmigration', 'Updated status for %n job.', 'Updated status for %n jobs.', targets.length))
		}
	} catch (error) {
		targets.forEach((target) => {
			target.status = previous.get(target.id) ?? target.status
		})
		console.error('Failed to update job status', error)
		showError(errorMessage(error, t('smartmigration', 'Could not update the status.')))
	}
}

async function updateGroup(job: Job, group: string) {
	const normalized = group || null
	if (normalized === job.group) {
		return
	}
	const targets = bulkTargets(job)
	const previous = new Map(targets.map((target) => [target.id, target.group]))
	targets.forEach((target) => {
		target.group = normalized
	})

	try {
		await Promise.all(targets.map((target) => patchJob(target.id, { group: normalized })))
		if (targets.length > 1) {
			showSuccess(n('smartmigration', 'Updated group for %n job.', 'Updated group for %n jobs.', targets.length))
		}
	} catch (error) {
		targets.forEach((target) => {
			target.group = previous.get(target.id) ?? target.group
		})
		console.error('Failed to update job group', error)
		showError(errorMessage(error, t('smartmigration', 'Could not update the group.')))
	}
}

async function updateScheduledDate(job: Job, date: Date | null) {
	const scheduledDate = fromDatePicker(date)
	if (scheduledDate === null || scheduledDate === job.scheduledDate) {
		return
	}
	const targets = bulkTargets(job)
	const previous = new Map(targets.map((target) => [target.id, target.scheduledDate]))
	targets.forEach((target) => {
		target.scheduledDate = scheduledDate
	})

	try {
		await Promise.all(targets.map((target) => patchJob(target.id, { scheduledDate })))
		if (targets.length > 1) {
			showSuccess(n('smartmigration', 'Updated scheduled date for %n job.', 'Updated scheduled date for %n jobs.', targets.length))
		}
	} catch (error) {
		targets.forEach((target) => {
			target.scheduledDate = previous.get(target.id) ?? target.scheduledDate
		})
		console.error('Failed to update job scheduled date', error)
		showError(errorMessage(error, t('smartmigration', 'Could not update the scheduled date.')))
	}
}
</script>

<template>
	<div class="jobs-tab">
		<div class="jobs-tab__toolbar">
			<NcTextField :model-value="filter"
				class="jobs-tab__filter"
				:label="t('smartmigration', 'Filter jobs')"
				:placeholder="t('smartmigration', 'Filter jobs')"
				@update:model-value="filter = String($event)" />

			<div class="jobs-tab__toolbar-actions">
				<NcButton v-if="selectedIds.size > 0" variant="error" @click="requestDeleteSelected">
					{{ n('smartmigration', 'Delete %n selected job', 'Delete %n selected jobs', selectedIds.size) }}
				</NcButton>

				<NcButton variant="primary" @click="openCreateDialog">
					{{ t('smartmigration', 'Create new') }}
				</NcButton>

				<NcActions :force-menu="true" :aria-label="t('smartmigration', 'Job table actions')">
					<NcActionButton>
						{{ t('smartmigration', 'Import') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>

		<NcLoadingIcon v-if="loading" :size="32" />

		<NcEmptyContent v-else-if="jobs.length === 0"
			:name="t('smartmigration', 'No jobs yet')"
			:description="t('smartmigration', 'Create your first discovery job to see what\'s in a SharePoint library, OneDrive account or file share before you migrate anything.')">
			<template #action>
				<NcButton variant="primary" @click="openCreateDialog">
					{{ t('smartmigration', 'Create new') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<table v-else class="jobs-tab__table">
			<thead>
				<tr>
					<th class="jobs-tab__select-col">
						<NcCheckboxRadioSwitch :model-value="allSelected"
							:aria-label="t('smartmigration', 'Select all jobs')"
							@update:model-value="toggleSelectAll" />
					</th>
					<th class="jobs-tab__title-col">
						{{ t('smartmigration', 'Title') }}
					</th>
					<th>{{ t('smartmigration', 'Mode') }}</th>
					<th :class="{ 'jobs-tab__bulk-column': bulkEditActive }">
						{{ t('smartmigration', 'Status') }}
					</th>
					<th :class="{ 'jobs-tab__bulk-column': bulkEditActive }">
						{{ t('smartmigration', 'Scheduled Date') }}
					</th>
					<th>{{ t('smartmigration', 'Result') }}</th>
					<th :class="{ 'jobs-tab__bulk-column': bulkEditActive }">
						{{ t('smartmigration', 'Group') }}
					</th>
					<th class="jobs-tab__menu-col" />
				</tr>
			</thead>
			<tbody>
				<tr v-for="job in filteredJobs()"
					:key="job.id"
					class="jobs-tab__row"
					@dblclick="onRowDoubleClick(job, $event)">
					<td class="jobs-tab__select-col" @click.capture="rememberShiftKey($event)">
						<NcCheckboxRadioSwitch :model-value="isSelected(job)"
							:aria-label="t('smartmigration', 'Select job {title}', { title: job.title })"
							@update:model-value="toggleSelected(job, $event)" />
					</td>
					<td>
						<NcTextField class="jobs-tab__inline-field jobs-tab__title-col"
							label-outside
							:model-value="job.title"
							:placeholder="t('smartmigration', 'Title')"
							@update:model-value="updateTitle(job, String($event))" />
					</td>
					<td>{{ job.mode }}</td>
					<td>
						<NcActions :class="['jobs-tab__inline-field', statusClass(job.status)]" :menu-name="job.status">
							<NcActionRadio v-for="value in ['Hold', 'Ready', 'Finished']"
								:key="value"
								:model-value="job.status"
								:value="value"
								:name="`status-${job.id}`"
								@update:model-value="updateStatus(job, value)">
								{{ value }}
							</NcActionRadio>
						</NcActions>
					</td>
					<td>
						<NcDateTimePickerNative class="jobs-tab__inline-field"
							:model-value="toDate(job.scheduledDate)"
							type="datetime-local"
							:label="t('smartmigration', 'Scheduled Date')"
							label-outside
							@update:model-value="updateScheduledDate(job, $event)" />
					</td>
					<td>{{ job.result }}</td>
					<td>
						<NcTextField class="jobs-tab__inline-field"
							label-outside
							:model-value="job.group ?? ''"
							:placeholder="t('smartmigration', 'Group')"
							@update:model-value="updateGroup(job, String($event))" />
					</td>
					<td class="jobs-tab__menu-col">
						<NcActions :aria-label="t('smartmigration', 'Job row actions')">
							<NcActionButton @click="openEditDialog(job)">
								{{ t('smartmigration', 'Edit') }}
							</NcActionButton>
							<NcActionButton @click="copyRow(job)">
								{{ t('smartmigration', 'Copy') }}
							</NcActionButton>
							<NcActionButton @click="requestDeleteRow(job)">
								{{ t('smartmigration', 'Delete') }}
							</NcActionButton>
							<NcActionSeparator />
							<NcActionButton @click="openCreateDialog">
								{{ t('smartmigration', 'New') }}
							</NcActionButton>
						</NcActions>
					</td>
				</tr>
			</tbody>
		</table>

		<p v-if="selectedIds.size === 1" class="jobs-tab__selection-hint">
			{{ t('smartmigration', 'Tip: hold Shift and select another row to select everything in between.') }}
		</p>
		<p v-else-if="selectedIds.size > 1" class="jobs-tab__selection-hint">
			{{ t('smartmigration', 'Changing Status, Scheduled Date or Group on one selected row applies that change to all selected rows.') }}
		</p>

		<JobEditDialog :open="dialogOpen"
			:job="editingJob"
			:saving="dialogSaving"
			@update:open="dialogOpen = $event"
			@save="saveDialog" />

		<NcDialog :open="deleteConfirmOpen"
			:name="t('smartmigration', 'Delete jobs')"
			:message="deleteConfirmMessage"
			:can-close="!deleting"
			@update:open="deleteConfirmOpen = $event; if (!$event) deleteTarget = null">
			<template #actions>
				<NcButton :disabled="deleting" @click="deleteConfirmOpen = false; deleteTarget = null">
					{{ t('smartmigration', 'Cancel') }}
				</NcButton>
				<NcButton variant="error" :disabled="deleting" @click="confirmDelete">
					{{ deleting ? t('smartmigration', 'Deleting …') : t('smartmigration', 'Delete') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<style scoped>
.jobs-tab {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.jobs-tab__toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.jobs-tab__toolbar-actions {
	display: flex;
	align-items: center;
	gap: 8px;
}

.jobs-tab__filter {
	max-width: 320px;
}

.jobs-tab__table {
	width: 100%;
	border-collapse: collapse;
}

.jobs-tab__table th,
.jobs-tab__table td {
	padding: 8px 12px;
	text-align: start;
	border-bottom: 1px solid var(--color-border);
	vertical-align: middle;
}

.jobs-tab__table th {
	color: var(--color-text-maxcontrast);
	font-weight: bold;
}

.jobs-tab__menu-col {
	width: 44px;
}

.jobs-tab__table th.jobs-tab__bulk-column {
	color: var(--color-error-text, var(--color-error));
}

.jobs-tab__select-col {
	width: 44px;
	padding-inline-end: 0;
}

.jobs-tab__row:hover {
	background-color: var(--color-background-hover);
}

.jobs-tab__inline-field {
	min-width: 140px;
}

/*
 * Colour-code the status control's label. Reaching into NcButton's text element is
 * the only way to colour an NcActions menu-name.
 *
 * One distinct hue per state — green / orange / blue / violet — plus Hold, which
 * keeps the default text colour. Bold makes the colour read at a glance down the
 * column.
 *
 * These are local tokens rather than the server's --color-element-* family, which
 * is too muted for the brightness wanted here. That means dark mode is not free:
 * the values below are declared per theme, matching how the server does it —
 * data-themes on <html> for an explicit choice, prefers-color-scheme otherwise.
 */
.jobs-tab {
	--smartmigration-status-ready: #0fbf0f;
	--smartmigration-status-running: #ff8a00;
	--smartmigration-status-reporting: #1a9bff;
	--smartmigration-status-finished: #a259ff;
}

@media (prefers-color-scheme: dark) {
	html:not([data-themes~="light"]) .jobs-tab {
		--smartmigration-status-ready: #3ee83e;
		--smartmigration-status-running: #ffab2e;
		--smartmigration-status-reporting: #5cbcff;
		--smartmigration-status-finished: #c08cff;
	}
}

html[data-themes~="dark"] .jobs-tab {
	--smartmigration-status-ready: #3ee83e;
	--smartmigration-status-running: #ffab2e;
	--smartmigration-status-reporting: #5cbcff;
	--smartmigration-status-finished: #c08cff;
}

.jobs-tab__status--ready :deep(.button-vue__text),
.jobs-tab__status--running :deep(.button-vue__text),
.jobs-tab__status--reporting :deep(.button-vue__text),
.jobs-tab__status--finished :deep(.button-vue__text) {
	font-weight: bold;
}

.jobs-tab__status--ready :deep(.button-vue__text) {
	color: var(--smartmigration-status-ready);
}

.jobs-tab__status--running :deep(.button-vue__text) {
	color: var(--smartmigration-status-running);
}

.jobs-tab__status--reporting :deep(.button-vue__text) {
	color: var(--smartmigration-status-reporting);
}

.jobs-tab__status--finished :deep(.button-vue__text) {
	color: var(--smartmigration-status-finished);
}

.jobs-tab__title-col {
	min-width: 350px;
}

.jobs-tab__selection-hint {
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
	margin: 0;
}
</style>
