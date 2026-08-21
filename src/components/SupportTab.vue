<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { type License, type SupportContact, fetchLicense, fetchSupportContact } from '../api/settings'
import { versionStatus } from '../versionStatus'
import { isTodayOrPast, toDate } from '../utils/datetime'
import { SUPPORT_URL } from '../branding'

const contact = ref<SupportContact | null>(null)
const license = ref<License | null>(null)
const loading = ref(true)

const status = computed(() => versionStatus(license.value))
const requiredSmartVersion = computed(() => license.value?.requiredSmartVersion ?? '')
const reportedSmVersion = computed(() => license.value?.currentSmVersion ?? '')

/** A licence that runs out today is already expired, not expiring. */
const licenseExpired = computed(() => isTodayOrPast(license.value?.expirationDate ?? null))

const expirationDate = computed(() => {
	const date = toDate(license.value?.expirationDate ?? null)
	return date ? date.toLocaleDateString() : ''
})

const notReported = t('smartmigration', 'Not reported')

const supportName = computed(() => contact.value?.supportName || notReported)
const supportEmail = computed(() => contact.value?.supportEmail || notReported)
const supportCompany = computed(() => contact.value?.supportCompany || notReported)

async function load() {
	loading.value = true
	try {
		[contact.value, license.value] = await Promise.all([
			fetchSupportContact(),
			fetchLicense(),
		])
	} catch (error) {
		console.error('Failed to load support information', error)
		showError(t('smartmigration', 'Could not load the support information.'))
	} finally {
		loading.value = false
	}
}

onMounted(load)
</script>

<template>
	<div class="support-tab">
		<div>
			<NcButton :href="SUPPORT_URL"
				target="_blank"
				:title="t('smartmigration', 'Opens the MigrateDMS AI Support page in a new tab')">
				{{ t('smartmigration', 'AI Support') }}
			</NcButton>
		</div>

		<template v-if="!loading && (licenseExpired || status === 'mismatch')">
			<h3>{{ t('smartmigration', 'Setup status') }}</h3>

			<NcNoteCard v-if="licenseExpired" type="error">
				{{ t('smartmigration', 'The SMART Migration licence expired on {date}. While it is expired this app is not being serviced by the server. Contact the support contact below to renew the licence.', { date: expirationDate }) }}
			</NcNoteCard>

			<NcNoteCard v-if="status === 'mismatch'" type="error">
				{{ t('smartmigration', 'The SMART Migration server reported version {reported}, but this app requires version {required}. Jobs will not run until the two match. Either update the SMART Migration server to version {required}, or install the version of this app that was built for version {reported}. If you are not sure which of the two to change, contact the support contact below before making a change.', { reported: reportedSmVersion, required: requiredSmartVersion }) }}
			</NcNoteCard>
		</template>

		<h3>{{ t('smartmigration', 'Support contact') }}</h3>

		<NcLoadingIcon v-if="loading" :size="32" />

		<template v-else>
			<div class="support-tab__field">
				<label>{{ t('smartmigration', 'Name') }}</label>
				<p class="support-tab__value">
					{{ supportName }}
				</p>
			</div>

			<div class="support-tab__field">
				<label>{{ t('smartmigration', 'Email') }}</label>
				<p class="support-tab__value">
					{{ supportEmail }}
				</p>
			</div>

			<div class="support-tab__field">
				<label>{{ t('smartmigration', 'Company') }}</label>
				<p class="support-tab__value">
					{{ supportCompany }}
				</p>
			</div>

			<p class="support-tab__hint">
				{{ t('smartmigration', 'The support contact is set by SMART Migration through the API and cannot be edited here.') }}
			</p>
		</template>
	</div>
</template>

<style scoped>
.support-tab {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

/* Matches the section headings in the job edit dialog. */
.support-tab h3 {
	color: var(--color-element-success);
	border-top: 1px solid var(--color-border);
	margin: 12px 0 0;
	padding-top: 16px;
}

.support-tab__field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.support-tab__field label {
	font-weight: bold;
}

.support-tab__value {
	margin: 0;
}

.support-tab__hint {
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
	margin: 0;
}
</style>
