<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import JobsTab from './components/JobsTab.vue'
import HomeTab from './components/HomeTab.vue'
import SettingsTab from './components/SettingsTab.vue'
import SupportTab from './components/SupportTab.vue'
import { type License, fetchLicense } from './api/settings'
import { type Partner, partners } from './data/partners'

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

const selectedPartner = ref<Partner | null>(null)

onMounted(async () => {
	try {
		license.value = await fetchLicense()
	} catch (error) {
		// The Settings tab surfaces a toast for this; a second one on every page
		// load would just be noise.
		console.error('Failed to load version information', error)
	}
})

function selectPartner(partner: Partner) {
	selectedPartner.value = partner
}
</script>

<template>
	<div id="smartmigration-admin">
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
			<HomeTab v-if="activeTab === 'home'"
				:license="license"
				@show-partners="activeTab = 'partners'"
				@show-jobs="activeTab = 'jobs'" />

			<JobsTab v-else-if="activeTab === 'jobs'" />

			<NcEmptyContent v-else-if="activeTab === 'runs'"
				:name="t('smartmigration', 'Run History')"
				:description="t('smartmigration', 'The history of job runs will live here.')" />

			<SettingsTab v-else-if="activeTab === 'settings'"
				@show-partners="activeTab = 'partners'"
				@show-support="activeTab = 'support'" />

			<div v-else-if="activeTab === 'partners'" class="smartmigration-partners-tab">
				<section class="smartmigration-partner-detail" aria-live="polite">
					<div v-if="selectedPartner" class="smartmigration-partner-detail__content">
						<NcAvatar :display-name="selectedPartner.name"
							:avatar="selectedPartner.logo"
							:size="96"
							disable-menu
							disable-tooltip />
						<div class="smartmigration-partner-detail__main">
							<h2>{{ selectedPartner.name }}</h2>
							<div class="smartmigration-partner-detail__facts">
								<span>
									<strong>{{ t('smartmigration', 'Country') }}:</strong> {{ selectedPartner.country }}
								</span>
								<span>
									<strong>{{ t('smartmigration', 'E-mail') }}:</strong> <a :href="`mailto:${selectedPartner.email}`">{{ selectedPartner.email }}</a>
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Phone') }}:</strong> <a :href="`tel:${selectedPartner.phone.replaceAll(' ', '')}`">{{ selectedPartner.phone }}</a>
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Address') }}:</strong> {{ selectedPartner.address }}
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Services') }}:</strong> {{ selectedPartner.services.join(', ') }}
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Website') }}:</strong> <a :href="selectedPartner.website" target="_blank" rel="noopener noreferrer">{{ selectedPartner.website.replace('https://', '') }}</a>
								</span>
							</div>
						</div>
						<p class="smartmigration-partner-detail__description">
							{{ selectedPartner.description }}
						</p>
					</div>
					<p v-else class="smartmigration-partner-detail__empty">
						{{ t('smartmigration', 'Select a partner') }}
					</p>
				</section>

				<div class="smartmigration-partners-scroll">
					<table class="smartmigration-partners">
						<thead>
							<tr>
								<th class="smartmigration-partners__logo-col">
									{{ t('smartmigration', 'Logo') }}
								</th>
								<th>{{ t('smartmigration', 'Company') }}</th>
								<th>{{ t('smartmigration', 'Country') }}</th>
								<th>{{ t('smartmigration', 'Services') }}</th>
								<th>{{ t('smartmigration', 'Website') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="partner in partners"
								:key="partner.id"
								:aria-selected="selectedPartner?.id === partner.id"
								:class="{ 'smartmigration-partners__row--selected': selectedPartner?.id === partner.id }"
								:tabindex="0"
								@click="selectPartner(partner)"
								@keydown.enter="selectPartner(partner)"
								@keydown.space.prevent="selectPartner(partner)">
								<td class="smartmigration-partners__logo-col">
									<img class="smartmigration-partners__logo" :src="partner.logo" :alt="partner.name">
								</td>
								<td>{{ partner.name }}</td>
								<td>{{ partner.country }}</td>
								<td>{{ partner.services.join(', ') }}</td>
								<td>
									<a :href="partner.website"
										target="_blank"
										rel="noopener noreferrer"
										@click.stop>
										{{ partner.website.replace('https://', '') }}
									</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
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
	min-width: 720px;
	border-collapse: collapse;
}

.smartmigration-partners-scroll {
	max-height: 50vh;
	overflow: auto;
}

.smartmigration-partner-detail {
	min-height: 240px;
	display: grid;
	place-items: center;
	border: 1px solid var(--color-border);
	border-radius: 8px;
}

.smartmigration-partner-detail__content {
	display: grid;
	grid-template-columns: 96px minmax(0, 1fr);
	align-items: start;
	gap: 24px;
	width: 100%;
}

.smartmigration-partner-detail__main h2 {
	margin: 0 0 16px;
}

.smartmigration-partner-detail__facts {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 8px 24px;
}

.smartmigration-partner-detail__facts span {
	min-width: 0;
	overflow-wrap: anywhere;
}

.smartmigration-partner-detail__facts a {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.smartmigration-partner-detail__description {
	grid-column: 1 / -1;
	min-height: 192px;
	margin: 24px 0 0;
	padding: 16px;
	background: var(--color-background-hover);
	border-left: 3px solid var(--color-primary-element);
	line-height: 1.6;
}

.smartmigration-partner-detail__empty {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 1.1rem;
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

.smartmigration-partners__logo {
	display: block;
	width: 32px;
	height: 32px;
	padding: 6px;
	background: var(--color-primary-element);
	border-radius: 50%;
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

.smartmigration-partners tbody tr {
	cursor: pointer;
}

.smartmigration-partners tbody tr:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: -2px;
}

.smartmigration-partners tbody tr.smartmigration-partners__row--selected {
	background-color: var(--color-primary-element-light);
}

@media (max-width: 700px) {
	.smartmigration-partner-detail {
		padding: 16px;
	}

	.smartmigration-partner-detail__content {
		grid-template-columns: 1fr;
	}

	.smartmigration-partner-detail__facts {
		grid-template-columns: 1fr;
	}

}
</style>
