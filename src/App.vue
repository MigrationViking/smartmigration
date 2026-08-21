<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import JobsTab from './components/JobsTab.vue'
import SettingsTab from './components/SettingsTab.vue'
import SupportTab from './components/SupportTab.vue'
import { type License, fetchLicense } from './api/settings'
import { versionStatus } from './versionStatus'

type TabId = 'home' | 'jobs' | 'runs' | 'settings' | 'partners' | 'support'

const tabs: { id: TabId, label: string }[] = [
	{ id: 'home', label: t('smartmigration', 'Home') },
	{ id: 'jobs', label: t('smartmigration', 'Jobs') },
	{ id: 'runs', label: t('smartmigration', 'Run History') },
	{ id: 'settings', label: t('smartmigration', 'Settings') },
	{ id: 'support', label: t('smartmigration', 'Support') },
	{ id: 'partners', label: t('smartmigration', 'Partners') },
]

const activeTab = ref<TabId>('home')

const license = ref<License | null>(null)

/** Material Design "close" glyph, so the banner needs no icon dependency. */
const mdiClose = 'M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z'

/** Dismissal lasts for this page view only: a reload re-checks and warns again. */
const versionAlertDismissed = ref(false)

const requiredSmartVersion = computed(() => license.value?.requiredSmartVersion ?? '')

/**
 * An unreported version is the normal state of a fresh install, not a fault, so
 * it gets an informational banner rather than the error one.
 */
const status = computed(() => versionStatus(license.value))

const versionMismatch = computed(() => status.value === 'mismatch')

const showVersionAlert = computed(() =>
	(status.value === 'unreported' || status.value === 'mismatch')
	&& !versionAlertDismissed.value,
)

/**
 * Following the link lands on the fuller explanation, so the banner has done its
 * job and would only repeat itself. Where that explanation lives depends on the
 * problem: no server yet means you need an installer and a licence, which is a
 * partner conversation; a version mismatch is a support conversation.
 */
function openHelpTab() {
	activeTab.value = status.value === 'mismatch' ? 'support' : 'partners'
	versionAlertDismissed.value = true
}

const versionAlertMessage = computed(() => (versionMismatch.value
	? t('smartmigration', 'SMART Migration is not functional. The remote SMART Migration server is running a different version than this app requires, and jobs will not run until the two versions match. The SMART Migration server must be running version {version}.', { version: requiredSmartVersion.value })
	: t('smartmigration', 'This app needs a remote SMART Migration server to do the actual work. Install SMART Migration version {version} and point it at this Nextcloud to get started.', { version: requiredSmartVersion.value })))

onMounted(async () => {
	try {
		license.value = await fetchLicense()
	} catch (error) {
		// The Settings tab surfaces a toast for this; a second one on every page
		// load would just be noise.
		console.error('Failed to load version information', error)
	}
})

interface Partner {
	name: string
	address: string
	email: string
	website: string
}

const partners: Partner[] = [
	{ name: 'Nordlys Consulting ApS', address: 'Vesterbrogade 12, 1620 Copenhagen, Denmark', email: 'partners@nordlysconsulting.example', website: 'https://www.nordlysconsulting.example' },
	{ name: 'Rheinland Digital GmbH', address: 'Domstraße 4, 50668 Cologne, Germany', email: 'kontakt@rheinlanddigital.example', website: 'https://www.rheinlanddigital.example' },
	{ name: 'Atelier Cloud Solutions', address: '18 Rue de la République, 69002 Lyon, France', email: 'contact@ateliercloud.example', website: 'https://www.ateliercloud.example' },
	{ name: 'Harborview IT Services', address: '200 Seaport Blvd, Boston, MA 02210, USA', email: 'sales@harborviewit.example', website: 'https://www.harborviewit.example' },
	{ name: 'Nova Terra Systems', address: 'Prinsengracht 45, 1015 Amsterdam, Netherlands', email: 'info@novaterra.example', website: 'https://www.novaterra.example' },
	{ name: 'BrightPath Migrations Ltd', address: '22 Deansgate, Manchester M3 2BW, United Kingdom', email: 'hello@brightpathmigrations.example', website: 'https://www.brightpathmigrations.example' },
	{ name: 'Alpine Data Partners AG', address: 'Bahnhofstrasse 8, 8001 Zürich, Switzerland', email: 'office@alpinedatapartners.example', website: 'https://www.alpinedatapartners.example' },
	{ name: 'Cedar & Stone Consulting', address: '150 King Street W, Toronto, ON M5H 1J9, Canada', email: 'support@cedarstone.example', website: 'https://www.cedarstone.example' },
	{ name: 'Fjord Technologies AS', address: 'Karl Johans gate 10, 0154 Oslo, Norway', email: 'team@fjordtech.example', website: 'https://www.fjordtech.example' },
	{ name: 'Sunrise Cloud Advisors', address: '55 Market Street, Sydney NSW 2000, Australia', email: 'contact@sunrisecloud.example', website: 'https://www.sunrisecloud.example' },
]

function mailtoLink(email: string): string {
	const subject = t('smartmigration', 'I would like to get in touch about SMART Migration for Nextcloud')
	const body = t('smartmigration', 'Dear sirs,\n\nPlease contact me with regards to SMART Migration for Nextcloud.\n\nBest regards,')
	return `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
}
</script>

<template>
	<div id="smartmigration-admin">
		<NcNoteCard v-if="showVersionAlert"
			:type="versionMismatch ? 'error' : 'info'"
			class="smartmigration-version-alert"
			:show-alert="versionMismatch">
			<div class="smartmigration-version-alert__row">
				<span>
					{{ versionAlertMessage }}
					<button class="smartmigration-version-alert__link"
						@click="openHelpTab">
						{{ t('smartmigration', 'Learn more') }}
					</button>
				</span>
				<NcButton variant="tertiary"
					class="smartmigration-version-alert__close"
					:aria-label="t('smartmigration', 'Dismiss until the page is reloaded')"
					:title="t('smartmigration', 'Dismiss until the page is reloaded')"
					@click="versionAlertDismissed = true">
					<template #icon>
						<NcIconSvgWrapper :path="mdiClose" />
					</template>
				</NcButton>
			</div>
		</NcNoteCard>

		<h2>{{ t('smartmigration', 'SMART Migration') }}</h2>

		<div class="smartmigration-tabs" role="tablist">
			<NcButton v-for="tab in tabs"
				:key="tab.id"
				role="tab"
				variant="tertiary"
				:aria-selected="activeTab === tab.id"
				:pressed="activeTab === tab.id"
				@click="activeTab = tab.id">
				{{ tab.label }}
			</NcButton>
		</div>

		<div class="smartmigration-panel" role="tabpanel">
			<NcEmptyContent v-if="activeTab === 'home'"
				:name="t('smartmigration', 'Dashboard')"
				:description="t('smartmigration', 'An overview of running jobs and documentation will live here.')" />

			<JobsTab v-else-if="activeTab === 'jobs'" />

			<NcEmptyContent v-else-if="activeTab === 'runs'"
				:name="t('smartmigration', 'Run History')"
				:description="t('smartmigration', 'The history of job runs will live here.')" />

			<SettingsTab v-else-if="activeTab === 'settings'"
				@show-partners="activeTab = 'partners'"
				@show-support="activeTab = 'support'" />

			<div v-else-if="activeTab === 'partners'" class="smartmigration-partners-tab">
				<NcNoteCard v-if="status === 'unreported'" type="info">
					{{ t('smartmigration', 'No SMART Migration server has reported in, which is normal on a fresh install of this app. Install the SMART Migration server on the machine that will run the BI data discoveries and migrations and connect it to this Nextcloud; it writes its version here on first contact, and this app starts working once it does. The server must be running version {version}. If you do not have the installer or licence key, contact one of the partners listed.', { version: requiredSmartVersion }) }}
				</NcNoteCard>

				<table class="smartmigration-partners">
					<thead>
						<tr>
							<th class="smartmigration-partners__logo-col">
								{{ t('smartmigration', 'Logo') }}
							</th>
							<th>{{ t('smartmigration', 'Company name') }}</th>
							<th>{{ t('smartmigration', 'Address') }}</th>
							<th>{{ t('smartmigration', 'Contact e-mail') }}</th>
							<th>{{ t('smartmigration', 'Website') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="partner in partners" :key="partner.name">
							<td class="smartmigration-partners__logo-col">
								<NcAvatar :display-name="partner.name"
									:size="32"
									disable-menu
									disable-tooltip />
							</td>
							<td>{{ partner.name }}</td>
							<td>{{ partner.address }}</td>
							<td><a :href="mailtoLink(partner.email)">{{ partner.email }}</a></td>
							<td><a :href="partner.website" target="_blank" rel="noopener noreferrer">{{ partner.website.replace('https://', '') }}</a></td>
						</tr>
					</tbody>
				</table>
			</div>

			<SupportTab v-else />
		</div>
	</div>
</template>

<style scoped>
#smartmigration-admin {
	padding: 20px;
	/* Wide enough for the jobs table's ~1000px minimum, so the tab bar, the panel
	   and the table all share one width instead of the table overflowing. */
	max-width: 1200px;
}

.smartmigration-version-alert {
	margin-block-end: 16px;
}

.smartmigration-version-alert__close {
	flex-shrink: 0;
}

/* Keeps the dismiss button centred against the text however many lines it wraps to. */
.smartmigration-version-alert__row {
	display: flex;
	align-items: center;
	gap: 12px;
}

/* A real button, so it is keyboard reachable, painted to read as an inline link. */
.smartmigration-version-alert__link {
	background: none;
	border: none;
	padding: 0;
	font: inherit;
	font-weight: bold;
	color: inherit;
	text-decoration: underline;
	cursor: pointer;
}

.smartmigration-tabs {
	display: flex;
	gap: 4px;
	margin: 16px 0 8px;
	border-bottom: 2px solid var(--color-primary-element);
	padding-bottom: 8px;
}

.smartmigration-panel {
	min-height: 300px;
}

.smartmigration-partners {
	width: 100%;
	border-collapse: collapse;
}

.smartmigration-partners th,
.smartmigration-partners td {
	padding: 8px 12px;
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}

.smartmigration-partners__logo-col {
	width: 48px;
}

.smartmigration-partners th {
	color: var(--color-text-maxcontrast);
	font-weight: bold;
}

.smartmigration-partners td a {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.smartmigration-partners tbody tr:hover {
	background-color: var(--color-background-hover);
}
</style>
