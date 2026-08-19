<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import JobsTab from './components/JobsTab.vue'

type TabId = 'home' | 'jobs' | 'runs' | 'settings' | 'support'

const tabs: { id: TabId, label: string }[] = [
	{ id: 'home', label: t('smartmigration', 'Home') },
	{ id: 'jobs', label: t('smartmigration', 'Jobs') },
	{ id: 'runs', label: t('smartmigration', 'Run History') },
	{ id: 'settings', label: t('smartmigration', 'Settings') },
	{ id: 'support', label: t('smartmigration', 'Support') },
]

const activeTab = ref<TabId>('home')

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

			<NcEmptyContent v-else-if="activeTab === 'settings'"
				:name="t('smartmigration', 'Settings')"
				:description="t('smartmigration', 'License information and general settings will live here.')" />

			<div v-else class="smartmigration-support">
				<NcButton href="https://migratedms.com/pages/nextcloud"
					target="_blank"
					:title="t('smartmigration', 'Opens the MigrateDMS AI Support page in a new tab')">
					{{ t('smartmigration', 'AI Support') }}
				</NcButton>

				<h3>{{ t('smartmigration', 'Partners') }}</h3>
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
		</div>
	</div>
</template>

<style scoped>
#smartmigration-admin {
	padding: 20px;
	max-width: 960px;
}

.smartmigration-tabs {
	display: flex;
	gap: 4px;
	margin: 16px 0 8px;
	border-bottom: 1px solid var(--color-border);
	padding-bottom: 8px;
}

.smartmigration-panel {
	min-height: 300px;
}

.smartmigration-support h3 {
	margin-top: 24px;
}

.smartmigration-partners {
	width: 100%;
	border-collapse: collapse;
	margin-top: 8px;
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
