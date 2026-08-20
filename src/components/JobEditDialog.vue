<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import type { Job, JobInput } from '../api/jobs'
import { fromDatePicker, toDate } from '../utils/datetime'

const props = defineProps<{
	open: boolean
	job: Job | null
	saving?: boolean
}>()

const emit = defineEmits<{
	'update:open': [value: boolean]
	save: [value: JobInput]
}>()

/**
 * NcTextField renders `modelValue.toString()`, which throws on null and makes the field
 * silently disappear; NcTextArea tolerates null at runtime but still types `modelValue`
 * as a plain string. So every field bound to either holds a string here and is converted
 * back to null (or a number) in `save()`.
 */
type FormState = Omit<JobInput, 'description' | 'group' | 'sourceUrl' | 'sourceUnc' | 'sourceUpn' | 'sizeFrom' | 'sizeTo' | 'sourceFileType'> & {
	description: string
	group: string
	sourceUrl: string
	sourceUnc: string
	sourceUpn: string
	sizeFrom: string
	sizeTo: string
	sourceFileType: string
}

function defaultForm(): FormState {
	return {
		title: '',
		description: '',
		group: 'Group 1',
		advancedMode: 'No',
		status: 'Hold',
		scheduledDate: Math.floor(Date.now() / 1000),
		recurrence: 'None',
		mode: 'Discovery',
		sourceType: 'SharePoint Library',
		sourceUrl: '',
		sourceUnc: '',
		sourceUpn: '',
		includeSubfolders: 'Yes',
		includeVersionHistory: 'Yes',
		fromDate: null,
		toDate: null,
		sizeFrom: '',
		sizeTo: '',
		sourceFileType: '',
		versionHistoryScope: '*:*:*',
	}
}

const form = reactive<FormState>(defaultForm())

watch(() => [props.open, props.job], () => {
	if (!props.open) {
		return
	}
	if (props.job) {
		Object.assign(form, {
			title: props.job.title,
			description: props.job.description ?? '',
			group: props.job.group ?? '',
			advancedMode: props.job.advancedMode,
			status: props.job.status,
			scheduledDate: props.job.scheduledDate,
			recurrence: props.job.recurrence,
			mode: props.job.mode,
			sourceType: props.job.sourceType,
			sourceUrl: props.job.sourceUrl ?? '',
			sourceUnc: props.job.sourceUnc ?? '',
			sourceUpn: props.job.sourceUpn ?? '',
			includeSubfolders: props.job.includeSubfolders,
			includeVersionHistory: props.job.includeVersionHistory,
			fromDate: props.job.fromDate,
			toDate: props.job.toDate,
			sizeFrom: props.job.sizeFrom?.toString() ?? '',
			sizeTo: props.job.sizeTo?.toString() ?? '',
			sourceFileType: props.job.sourceFileType ?? '',
			versionHistoryScope: props.job.versionHistoryScope,
		})
	} else {
		Object.assign(form, defaultForm())
	}
}, { immediate: true })

const dialogName = computed(() => props.job ? t('smartmigration', 'Edit job') : t('smartmigration', 'Create new job'))

/** Advanced settings stay in effect when hidden; the toggle only reduces visual clutter. */
const showAdvanced = computed(() => form.advancedMode === 'Yes')

/** File shares have no version history, so the whole version-history group is irrelevant there. */
const supportsVersionHistory = computed(() => form.sourceType !== 'FileShare')

function close() {
	emit('update:open', false)
}

/**
 * Empty text becomes null so the API keeps its nullable column types.
 *
 * @param value - the raw field value
 */
function trimmedOrNull(value: string): string | null {
	return value.trim() === '' ? null : value.trim()
}

/**
 * Numeric text becomes a number; anything empty or unparseable becomes null.
 *
 * @param value - the raw field value
 */
function numberOrNull(value: string): number | null {
	const parsed = Number(value)
	return value.trim() === '' || Number.isNaN(parsed) ? null : parsed
}

function save() {
	emit('save', {
		...form,
		description: trimmedOrNull(form.description),
		sourceFileType: trimmedOrNull(form.sourceFileType),
		group: trimmedOrNull(form.group),
		sourceUrl: trimmedOrNull(form.sourceUrl),
		sourceUnc: trimmedOrNull(form.sourceUnc),
		sourceUpn: trimmedOrNull(form.sourceUpn),
		sizeFrom: numberOrNull(form.sizeFrom),
		sizeTo: numberOrNull(form.sizeTo),
	})
}
</script>

<template>
	<NcDialog :open="open"
		:name="dialogName"
		size="large"
		:can-close="!saving"
		@update:open="emit('update:open', $event)">
		<div class="job-edit-form">
			<div class="job-edit-form__field">
				<NcCheckboxRadioSwitch :model-value="form.advancedMode === 'Yes'"
					@update:model-value="form.advancedMode = $event ? 'Yes' : 'No'">
					{{ t('smartmigration', 'Advanced Mode') }}
				</NcCheckboxRadioSwitch>
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'When set to "Yes," all advanced settings will be visible in the interface. These settings are always active and in use, regardless of this option. Setting this to "No" only hides the settings to simplify the view and reduce visual clutter.') }}
				</p>
			</div>

			<h3>{{ t('smartmigration', 'Job') }}</h3>
			<div class="job-edit-form__grid">
				<NcTextField v-model="form.title"
					:label="t('smartmigration', 'Title')"
					:placeholder="t('smartmigration', 'Enter value here')" />

				<div class="job-edit-form__field">
					<label>{{ t('smartmigration', 'Job Description') }}</label>
					<NcTextArea v-model="form.description" :placeholder="t('smartmigration', 'Enter value here')" />
				</div>

				<NcTextField v-model="form.group"
					:label="t('smartmigration', 'Job Group')"
					:helper-text="t('smartmigration', 'Utility column you can use to group jobs together and to view or edit them in the \'Job Group\' view.')" />
			</div>

			<div class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Job Status') }}</label>
				<NcCheckboxRadioSwitch v-for="value in ['Hold', 'Ready', 'Finished']"
					:key="value"
					:model-value="form.status"
					:value="value"
					name="status"
					type="radio"
					@update:model-value="form.status = $event">
					{{ value }}
				</NcCheckboxRadioSwitch>
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Indicates the job\'s state: Hold (editable, not processed), Ready (queued for processing), Running (in progress), Reporting (final reporting in progress), or Finished (completed). If you change the status to Hold while the job is Running, the job will be cancelled.') }}
				</p>
			</div>

			<div v-if="showAdvanced" class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Scheduled Date') }}</label>
				<NcDateTimePickerNative :model-value="toDate(form.scheduledDate)"
					type="datetime-local"
					@update:model-value="form.scheduledDate = fromDatePicker($event) ?? form.scheduledDate" />
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Date and time when the job is scheduled to start. Actual start time may vary due to workload delays.') }}
				</p>
			</div>

			<div v-if="showAdvanced" class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Recurrence') }}</label>
				<NcCheckboxRadioSwitch v-for="value in ['None', 'Daily', 'Weekly', 'Monthly']"
					:key="value"
					:model-value="form.recurrence"
					:value="value"
					name="recurrence"
					type="radio"
					@update:model-value="form.recurrence = $event">
					{{ value }}
				</NcCheckboxRadioSwitch>
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Specifies the job\'s repetition schedule.') }}
				</p>
			</div>

			<h3>{{ t('smartmigration', 'Action') }}</h3>
			<div class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Mode') }}</label>
				<NcCheckboxRadioSwitch v-for="value in ['Discovery', 'Test Migration', 'Migration']"
					:key="value"
					:model-value="form.mode"
					:value="value"
					name="mode"
					type="radio"
					@update:model-value="form.mode = $event">
					{{ value }}
				</NcCheckboxRadioSwitch>
			</div>

			<h3>{{ t('smartmigration', 'Source') }}</h3>
			<div class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Source Type') }}</label>
				<NcCheckboxRadioSwitch v-for="value in ['SharePoint Library', 'OneDrive', 'FileShare']"
					:key="value"
					:model-value="form.sourceType"
					:value="value"
					name="sourceType"
					type="radio"
					@update:model-value="form.sourceType = $event">
					{{ value }}
				</NcCheckboxRadioSwitch>
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Where the files to be discovered are stored') }}
				</p>
			</div>

			<NcTextField v-if="form.sourceType === 'SharePoint Library'"
				v-model="form.sourceUrl"
				:label="t('smartmigration', 'Source URL')"
				:helper-text="t('smartmigration', 'The URL to the SharePoint source Library https://mytenant.sharepoint.com/sites/T1/LibB. Note that you can specify a subfolder after the document library. https://mytenant.sharepoint.com/sites/S1/LibA/MyFolder')" />

			<NcTextField v-if="form.sourceType === 'FileShare'"
				v-model="form.sourceUnc"
				:label="t('smartmigration', 'Source UNC')"
				:helper-text="t('smartmigration', 'The UNC or drive specification for the source files. \\\\MyServer\\MyShare\\MyFolder')" />

			<NcTextField v-if="form.sourceType === 'OneDrive'"
				v-model="form.sourceUpn"
				:label="t('smartmigration', 'Source UPN')"
				:helper-text="t('smartmigration', 'The User Principal Name (UPN) of the source user\'s OneDrive. Example: user1@domain.com')" />

			<div class="job-edit-form__field">
				<NcCheckboxRadioSwitch :model-value="form.includeSubfolders === 'Yes'"
					@update:model-value="form.includeSubfolders = $event ? 'Yes' : 'No'">
					{{ t('smartmigration', 'Include Subfolders') }}
				</NcCheckboxRadioSwitch>
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Include subfolders in the file scan or not.') }}
				</p>
			</div>

			<div v-if="supportsVersionHistory" class="job-edit-form__field">
				<NcCheckboxRadioSwitch :model-value="form.includeVersionHistory === 'Yes'"
					@update:model-value="form.includeVersionHistory = $event ? 'Yes' : 'No'">
					{{ t('smartmigration', 'Include Version History') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcTextField v-if="supportsVersionHistory && form.includeVersionHistory === 'Yes'"
				v-model="form.versionHistoryScope"
				:label="t('smartmigration', 'Version History Scope')"
				:helper-text="t('smartmigration', 'How much SharePoint version history to extract, written as major:minor:minor — major versions, minor versions on the latest major, and minor versions on all other majors. * means all, and trailing parts may be left out; they default to 1 minor on the latest major and 0 on the others. The default *:*:* extracts everything.')" />

			<div v-if="showAdvanced" class="job-edit-form__grid">
				<div class="job-edit-form__field">
					<label>{{ t('smartmigration', 'From Date') }}</label>
					<NcDateTimePickerNative :model-value="toDate(form.fromDate)"
						type="date"
						@update:model-value="form.fromDate = fromDatePicker($event)" />
					<p class="job-edit-form__hint">
						{{ t('smartmigration', 'File selection from date.') }}
					</p>
				</div>

				<div class="job-edit-form__field">
					<label>{{ t('smartmigration', 'To Date') }}</label>
					<NcDateTimePickerNative :model-value="toDate(form.toDate)"
						type="date"
						@update:model-value="form.toDate = fromDatePicker($event)" />
					<p class="job-edit-form__hint">
						{{ t('smartmigration', 'File selection to date.') }}
					</p>
				</div>
			</div>

			<div v-if="showAdvanced" class="job-edit-form__grid">
				<NcTextField v-model="form.sizeFrom"
					type="number"
					:label="t('smartmigration', 'Size From')"
					:helper-text="t('smartmigration', 'Include files of [Size] &gt;= NN (MB)')" />

				<NcTextField v-model="form.sizeTo"
					type="number"
					:label="t('smartmigration', 'Size To')"
					:helper-text="t('smartmigration', 'Include files of size &lt;= xx MB')" />
			</div>

			<div v-if="showAdvanced" class="job-edit-form__field">
				<label>{{ t('smartmigration', 'Source File Type') }}</label>
				<NcTextArea v-model="form.sourceFileType" :placeholder="t('smartmigration', 'Enter value here')" />
				<p class="job-edit-form__hint">
					{{ t('smartmigration', 'Enter one or more file extensions to filter by type. Extensions can be specified without the leading dot — for example, csv, txt, docx. Separate multiple extensions with a semicolon (;) on a single line, such as csv;txt;xlsx, or enter each extension on its own line. You can mix both styles freely. Filtering is always case-insensitive, so PDF and pdf are treated the same.') }}
				</p>
			</div>
		</div>

		<template #actions>
			<NcButton :disabled="saving" @click="close">
				{{ t('smartmigration', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" :disabled="!form.title || saving" @click="save">
				{{ saving ? t('smartmigration', 'Saving …') : t('smartmigration', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.job-edit-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 4px 20px 20px;
	max-height: 70vh;
	overflow-y: auto;
}

/*
 * Each h3 opens a section, so the rule above it separates it from what precedes:
 * the Advanced Mode toggle for the first one, the previous section for the rest.
 */
.job-edit-form h3 {
	/* The saturated element green; --color-success is a pale fill and --color-success-text is near-white on dark. */
	color: var(--color-element-success);
	border-top: 1px solid var(--color-border);
	margin: 12px 0 0;
	padding-top: 16px;
}

.job-edit-form__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 12px;
}

.job-edit-form__field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.job-edit-form__field label {
	font-weight: bold;
}

.job-edit-form__hint {
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
	margin: 0;
}
</style>
