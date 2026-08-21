<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { type License, fetchLicense } from '../api/settings'
import { isTodayOrPast, toDate } from '../utils/datetime'
import { versionStatus } from '../versionStatus'

/** Tab switching lives in App.vue, so the link asks the parent to move. */
const emit = defineEmits<{ showPartners: [], showSupport: [] }>()

const license = ref<License | null>(null)
const loading = ref(true)

const notReported = t('smartmigration', 'Not reported')

const smServerName = computed(() => license.value?.smServerName || notReported)

const licenseKey = computed(() => license.value?.licenseKey || notReported)

const expirationDate = computed(() => {
	const date = toDate(license.value?.expirationDate ?? null)
	return date ? date.toLocaleDateString() : notReported
})

/** A licence that runs out today is already expired, not expiring. */
const licenseExpired = computed(() => isTodayOrPast(license.value?.expirationDate ?? null))

const currentSmVersion = computed(() => license.value?.currentSmVersion || notReported)
const requiredSmartVersion = computed(() => license.value?.requiredSmartVersion || '')

const status = computed(() => versionStatus(license.value))

/** Blank means no SMART Migration server has reported in yet — normal on a fresh install. */
const versionUnreported = computed(() => status.value === 'unreported')

const versionsMatch = computed(() => status.value === 'match')

async function load() {
	loading.value = true
	try {
		license.value = await fetchLicense()
	} catch (error) {
		console.error('Failed to load license', error)
		showError(t('smartmigration', 'Could not load the license information.'))
	} finally {
		loading.value = false
	}
}

onMounted(load)
</script>

<template>
	<div class="settings-tab">
		<h3>{{ t('smartmigration', 'Remote SMART Migration Server') }}</h3>

		<NcLoadingIcon v-if="loading" :size="32" />

		<template v-else>
			<div class="settings-tab__field">
				<label>{{ t('smartmigration', 'Server Name') }}</label>
				<p class="settings-tab__value">
					{{ smServerName }}
				</p>
			</div>

			<div class="settings-tab__field">
				<label>{{ t('smartmigration', 'Version') }}</label>
				<p class="settings-tab__value">
					{{ currentSmVersion }}
				</p>
			</div>

			<div class="settings-tab__field">
				<label>{{ t('smartmigration', 'Version required by this app') }}</label>
				<p class="settings-tab__value">
					{{ requiredSmartVersion }}
				</p>
			</div>

			<NcNoteCard v-if="versionUnreported" type="info">
				{{ t('smartmigration', 'This app needs a remote SMART Migration server to do the actual work. Install SMART Migration version {version} and point it at this Nextcloud to get started.', { version: requiredSmartVersion }) }}
				<button class="settings-tab__link" @click="emit('showPartners')">
					{{ t('smartmigration', 'Learn more') }}
				</button>
			</NcNoteCard>
			<NcNoteCard v-else-if="versionsMatch" type="success">
				{{ t('smartmigration', 'The remote SMART Migration server is running the required version. The setup is ready to use.') }}
			</NcNoteCard>
			<NcNoteCard v-else type="error">
				{{ t('smartmigration', 'The remote SMART Migration server is not running the required version. The two versions must match for the setup to function. Please update the SMART Migration server to version {version}.', { version: requiredSmartVersion }) }}
			</NcNoteCard>

			<hr class="settings-tab__separator">

			<div class="settings-tab__field">
				<label>{{ t('smartmigration', 'License Key') }}</label>
				<p class="settings-tab__value">
					{{ licenseKey }}
				</p>
			</div>

			<div class="settings-tab__field">
				<label>{{ t('smartmigration', 'Expiration Date') }}</label>
				<p class="settings-tab__value">
					{{ expirationDate }}
				</p>
			</div>

			<NcNoteCard v-if="licenseExpired" type="error">
				{{ t('smartmigration', 'The SMART Migration licence has expired, so this app is no longer being serviced.') }}
				<button class="settings-tab__link" @click="emit('showSupport')">
					{{ t('smartmigration', 'Learn more') }}
				</button>
			</NcNoteCard>
		</template>
	</div>
</template>

<style scoped>
.settings-tab {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

/* Matches the section headings in the job edit dialog. */
.settings-tab h3 {
	color: var(--color-element-success);
	border-top: 1px solid var(--color-border);
	margin: 12px 0 0;
	padding-top: 16px;
}

.settings-tab__separator {
	border: 0;
	border-top: 1px solid var(--color-border);
	margin: 4px 0;
	width: 100%;
}

.settings-tab__field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.settings-tab__field label {
	font-weight: bold;
}

.settings-tab__value {
	margin: 0;
}

/* A real button, so it is keyboard reachable, painted to read as an inline link. */
.settings-tab__link {
	background: none;
	border: none;
	padding: 0;
	font: inherit;
	font-weight: bold;
	color: inherit;
	text-decoration: underline;
	cursor: pointer;
}
</style>
