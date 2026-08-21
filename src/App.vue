<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { t } from '@nextcloud/l10n'
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

const partnerList = ref<Partner[]>([...partners])
const selectedPartner = ref<Partner | null>(null)
type PartnerSortKey = 'order' | 'name' | 'country' | 'services' | 'website'
/** The list opens in the order the partner files are numbered, not alphabetically. */
const sortKey = ref<PartnerSortKey>('order')
const sortAscending = ref(true)
const partnersScroll = ref<HTMLElement | null>(null)
const partnersScrollHeight = ref('50vh')

const sortedPartners = computed(() => [...partnerList.value].sort((left, right) => {
	if (sortKey.value === 'order') {
		const comparison = left.sortOrder - right.sortOrder
		return sortAscending.value ? comparison : -comparison
	}

	const leftValue = sortKey.value === 'services' ? left.services.join(', ') : left[sortKey.value]
	const rightValue = sortKey.value === 'services' ? right.services.join(', ') : right[sortKey.value]
	const comparison = leftValue.localeCompare(rightValue)
	return sortAscending.value ? comparison : -comparison
}))

onMounted(() => {
	window.addEventListener('resize', updatePartnersScrollHeight)
	nextTick(updatePartnersScrollHeight)
})

onBeforeUnmount(() => {
	window.removeEventListener('resize', updatePartnersScrollHeight)
})

onMounted(async () => {
	try {
		license.value = await fetchLicense()
	} catch (error) {
		// The Settings tab surfaces a toast for this; a second one on every page
		// load would just be noise.
		console.error('Failed to load version information', error)
	}
})

/**
 * Size the partner table's scroll area to the space actually left below it, so its
 * scrollbar ends at the bottom of the window. A fixed vh value cannot do this: the
 * detail panel above changes height with each partner's description.
 */
function updatePartnersScrollHeight() {
	const element = partnersScroll.value
	if (element === null) {
		return
	}

	const top = element.getBoundingClientRect().top
	// Leave room for the scrollbar end controls and keep the last row off the edge.
	partnersScrollHeight.value = `${Math.max(160, Math.round(window.innerHeight - top - 40))}px`
}

watch([activeTab, selectedPartner, partnerList], () => {
	nextTick(updatePartnersScrollHeight)
})

function selectPartner(partner: Partner) {
	selectedPartner.value = partner
}

function sortPartners(key: PartnerSortKey) {
	if (sortKey.value === key) {
		sortAscending.value = !sortAscending.value
		return
	}

	sortKey.value = key
	sortAscending.value = true
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
				<section class="smartmigration-partner-detail"
					:class="{ 'smartmigration-partner-detail--empty': !selectedPartner }"
					aria-live="polite">
					<div v-if="selectedPartner" class="smartmigration-partner-detail__content">
						<img class="smartmigration-partner-detail__logo"
							:src="selectedPartner.logo"
							:alt="selectedPartner.name"
							width="128"
							height="128">
						<div class="smartmigration-partner-detail__main">
							<h2>{{ selectedPartner.name }}</h2>
							<div class="smartmigration-partner-detail__facts">
								<span class="smartmigration-partner-detail__fact--offset">
									<strong>{{ t('smartmigration', 'Country') }}:</strong> {{ selectedPartner.country }}
								</span>
								<span>
									<strong>{{ t('smartmigration', 'E-mail') }}:</strong> <a :href="`mailto:${selectedPartner.email}`">{{ selectedPartner.email }}</a>
								</span>
								<span class="smartmigration-partner-detail__fact--offset">
									<strong>{{ t('smartmigration', 'Phone') }}:</strong> <a :href="`tel:${selectedPartner.phone.replaceAll(' ', '')}`">{{ selectedPartner.phone }}</a>
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Address') }}:</strong> {{ selectedPartner.address }}
								</span>
								<span class="smartmigration-partner-detail__fact--offset">
									<strong>{{ t('smartmigration', 'Services') }}:</strong> {{ selectedPartner.services.join(', ') }}
								</span>
								<span>
									<strong>{{ t('smartmigration', 'Website') }}:</strong> <a :href="selectedPartner.website" target="_blank" rel="noopener noreferrer">{{ selectedPartner.website.replace('https://', '') }}</a>
								</span>
							</div>
						</div>
						<div class="smartmigration-partner-detail__description"
							:class="`smartmigration-partner-detail__description--${selectedPartner.descriptionLayout ?? 'sections'}`">
							<!-- The importer sanitizes HTML before it reaches this trusted display slot. -->
							<!-- eslint-disable-next-line vue/no-v-html -->
							<div v-if="selectedPartner.descriptionHtml" class="smartmigration-partner-detail__description-html" v-html="selectedPartner.descriptionHtml" />
							<template v-else-if="selectedPartner.descriptionSections">
								<section v-for="section in selectedPartner.descriptionSections"
									:key="section.heading"
									:class="`smartmigration-partner-detail__description-section smartmigration-partner-detail__description-section--${section.tone}`">
									<h4>{{ section.heading }}</h4>
									<p>{{ section.text }}</p>
								</section>
							</template>
							<p v-else>
								{{ selectedPartner.description }}
							</p>
						</div>
					</div>
					<p v-else class="smartmigration-partner-detail__empty">
						{{ t('smartmigration', 'Select a partner') }}
					</p>
				</section>

				<div ref="partnersScroll"
					class="smartmigration-partners-scroll"
					:style="{ maxHeight: partnersScrollHeight }">
					<table class="smartmigration-partners">
						<thead>
							<tr>
								<th class="smartmigration-partners__logo-col"
									:aria-sort="sortKey === 'order' ? (sortAscending ? 'ascending' : 'descending') : 'none'">
									<button type="button" class="smartmigration-partners__sort-button" @click="sortPartners('order')">
										{{ t('smartmigration', 'Logo') }}
										<span v-if="sortKey === 'order'" aria-hidden="true">{{ sortAscending ? ' ^' : ' v' }}</span>
									</button>
								</th>
								<th :aria-sort="sortKey === 'name' ? (sortAscending ? 'ascending' : 'descending') : 'none'">
									<button type="button" class="smartmigration-partners__sort-button" @click="sortPartners('name')">
										{{ t('smartmigration', 'Company') }}
										<span v-if="sortKey === 'name'" aria-hidden="true">{{ sortAscending ? ' ^' : ' v' }}</span>
									</button>
								</th>
								<th :aria-sort="sortKey === 'country' ? (sortAscending ? 'ascending' : 'descending') : 'none'">
									<button type="button" class="smartmigration-partners__sort-button" @click="sortPartners('country')">
										{{ t('smartmigration', 'Country') }}
										<span v-if="sortKey === 'country'" aria-hidden="true">{{ sortAscending ? ' ^' : ' v' }}</span>
									</button>
								</th>
								<th :aria-sort="sortKey === 'services' ? (sortAscending ? 'ascending' : 'descending') : 'none'">
									<button type="button" class="smartmigration-partners__sort-button" @click="sortPartners('services')">
										{{ t('smartmigration', 'Services') }}
										<span v-if="sortKey === 'services'" aria-hidden="true">{{ sortAscending ? ' ^' : ' v' }}</span>
									</button>
								</th>
								<th :aria-sort="sortKey === 'website' ? (sortAscending ? 'ascending' : 'descending') : 'none'">
									<button type="button" class="smartmigration-partners__sort-button" @click="sortPartners('website')">
										{{ t('smartmigration', 'Website') }}
										<span v-if="sortKey === 'website'" aria-hidden="true">{{ sortAscending ? ' ^' : ' v' }}</span>
									</button>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="partner in sortedPartners"
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

			<SupportTab v-else @show-partners="activeTab = 'partners'" />
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

/* max-height is set from script; this is only the value before the first measure. */
.smartmigration-partners-scroll {
	max-height: 50vh;
	overflow: auto;
}

/*
 * Starts at 60% of the window height and grows with it on larger screens, but never
 * past twice the floor — a partner presentation should not push the table off-screen.
 */
.smartmigration-partner-detail {
	height: clamp(320px, 60vh, 640px);
	/* The presentation scrolls inside itself, so the box never scrolls as a whole. */
	overflow: hidden;
	display: grid;
	place-items: stretch;
	border: 1px solid var(--color-border);
	border-radius: 8px;
}

.smartmigration-partner-detail--empty {
	height: 2cm;
	min-height: 2cm;
}

.smartmigration-partner-detail__content {
	display: grid;
	grid-template-columns: 128px minmax(0, 1fr);
	/* Facts take what they need; the presentation takes everything that is left. */
	grid-template-rows: auto minmax(0, 1fr);
	align-items: start;
	gap: 24px;
	width: 100%;
	height: 100%;
	min-height: 0;
	box-sizing: border-box;
	padding: 16px;
}

.smartmigration-partner-detail__logo {
	align-self: center;
	justify-self: center;
	width: 128px;
	height: 128px;
	border-radius: 18px;
}

.smartmigration-partner-detail__main h2 {
	margin: 0 0 16px;
	transform: translateY(4px);
}

.smartmigration-partner-detail__main {
	transform: translateY(-8px);
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

.smartmigration-partner-detail__fact--offset {
	margin-inline-start: 12px;
}

.smartmigration-partner-detail__facts a {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.smartmigration-partner-detail__description {
	grid-column: 1 / -1;
	box-sizing: border-box;
	min-width: 0;
	width: 100%;
	justify-self: center;
	align-self: stretch;
	min-height: 0;
	overflow-y: auto;
	scrollbar-gutter: stable;
	overscroll-behavior: contain;
	margin: 0 auto;
	padding: 8px 16px;
	background: var(--color-background-hover);
	border-inline-start: 3px solid var(--color-primary-element);
	line-height: 1.6;
}

.smartmigration-partner-detail__description-section {
	padding-inline-start: 12px;
	border-inline-start: 3px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description-section + .smartmigration-partner-detail__description-section {
	margin-block-start: 16px;
}

.smartmigration-partner-detail__description-section--success {
	border-color: var(--color-element-success);
}

.smartmigration-partner-detail__description-section--warning {
	border-color: var(--color-element-warning);
}

.smartmigration-partner-detail__description-section h4 {
	margin: 0 0 4px;
	font-size: 1rem;
}

.smartmigration-partner-detail__description-section p,
.smartmigration-partner-detail__description > p {
	margin: 0;
}

.smartmigration-partner-detail__description--spotlight {
	border-inline-start: 0;
	border-block-start: 4px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description--spotlight .smartmigration-partner-detail__description-section:first-child {
	padding: 16px;
	background: var(--color-main-background);
	border-inline-start: 4px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description--spotlight .smartmigration-partner-detail__description-section:first-child h4 {
	font-size: 1.2rem;
}

.smartmigration-partner-detail__description--spotlight .smartmigration-partner-detail__description-section:not(:first-child) {
	display: inline-block;
	width: calc(50% - 12px);
	box-sizing: border-box;
	vertical-align: top;
}

.smartmigration-partner-detail__description--sections {
	counter-reset: profile-section;
}

.smartmigration-partner-detail__description--sections .smartmigration-partner-detail__description-section {
	position: relative;
	padding-inline-start: 44px;
}

.smartmigration-partner-detail__description--sections .smartmigration-partner-detail__description-section::before {
	position: absolute;
	top: 0;
	inset-inline-start: 0;
	display: grid;
	width: 28px;
	height: 28px;
	place-items: center;
	background: var(--color-primary-element);
	border-radius: 50%;
	color: var(--color-primary-text);
	content: counter(profile-section);
	counter-increment: profile-section;
	font-weight: bold;
}

.smartmigration-partner-detail__description--columns {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 20px;
	align-content: start;
}

.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section + .smartmigration-partner-detail__description-section {
	margin-block-start: 0;
}

.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section:last-child {
	grid-column: 1 / -1;
}

.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section {
	padding: 16px;
	background: var(--color-main-background);
	border-inline-start: 0;
	border-block-start: 4px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section--success {
	border-block-start-color: var(--color-element-success);
}

.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section--warning {
	border-block-start-color: var(--color-element-warning);
}

.smartmigration-partner-detail__description--banner {
	padding: 0;
	border-inline-start: 0;
	border-block-start: 4px solid var(--color-element-success);
}

.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section {
	display: grid;
	grid-template-columns: minmax(150px, 0.45fr) minmax(0, 1fr);
	gap: 16px;
	padding: 16px;
	border-inline-start: 0;
	border-block-end: 1px solid var(--color-border);
}

.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section h4 {
	color: var(--color-element-success);
	font-size: 1.1rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
}

.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section p {
	margin: 0;
}

.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section:nth-child(even) {
	background: var(--color-main-background);
}

.smartmigration-partner-detail__description--timeline {
	border-inline-start: 0;
}

.smartmigration-partner-detail__description--timeline .smartmigration-partner-detail__description-section {
	position: relative;
	padding: 0 0 20px;
	padding-inline-start: 28px;
	border-inline-start: 2px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description--timeline .smartmigration-partner-detail__description-section::before {
	position: absolute;
	top: 0;
	inset-inline-start: -7px;
	width: 12px;
	height: 12px;
	background: var(--color-primary-element);
	border-radius: 50%;
	content: '';
}

.smartmigration-partner-detail__description--timeline .smartmigration-partner-detail__description-section h4 {
	font-size: 1.15rem;
}

.smartmigration-partner-detail__description--timeline .smartmigration-partner-detail__description-section:last-child {
	padding-block-end: 0;
	border-inline-start-color: transparent;
}

.smartmigration-partner-detail__description--governance-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px;
	background: var(--color-main-background);
	border-inline-start: 0;
}

.smartmigration-partner-detail__description--governance-grid :deep(h2) {
	grid-column: 1 / -1;
	margin: 0;
	padding-block-end: 8px;
	border-block-end: 2px solid var(--color-primary-element);
}

.smartmigration-partner-detail__description--governance-grid :deep(h3) {
	margin: 0;
	padding: 12px;
	background: var(--color-background-hover);
	font-size: 1rem;
}

.smartmigration-partner-detail__description--governance-grid :deep(h3) + :deep(p) {
	margin: 0;
	padding: 12px;
	background: var(--color-background-hover);
	border-block-end: 3px solid var(--color-element-success);
}

.smartmigration-partner-detail__description--assurance-matrix {
	display: grid;
	grid-template-columns: minmax(140px, 0.55fr) minmax(0, 1fr);
	align-content: start;
	padding: 0;
	background: var(--color-main-background);
	border-inline-start: 0;
}

.smartmigration-partner-detail__description--assurance-matrix :deep(h2) {
	grid-column: 1 / -1;
	margin: 0;
	padding: 16px;
	background: var(--color-element-error);
	color: var(--color-main-text);
}

.smartmigration-partner-detail__description--assurance-matrix :deep(h3) {
	margin: 0;
	padding: 12px;
	border-block-end: 1px solid var(--color-border);
	font-size: 0.95rem;
}

.smartmigration-partner-detail__description--assurance-matrix :deep(h3) + :deep(p) {
	margin: 0;
	padding: 12px;
	border-block-end: 1px solid var(--color-border);
}

.smartmigration-partner-detail__description-html :deep(h2),
.smartmigration-partner-detail__description-html :deep(h3),
.smartmigration-partner-detail__description-html :deep(h4) {
	margin: 0 0 8px;
	color: var(--color-primary-element);
}

.smartmigration-partner-detail__description-html :deep(p) {
	margin: 0 0 12px;
}

.smartmigration-partner-detail__description-html :deep(ul),
.smartmigration-partner-detail__description-html :deep(ol) {
	margin: 0 0 12px;
	padding-inline-start: 24px;
}

.smartmigration-partner-detail__description-html :deep(a) {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.smartmigration-partner-detail__empty {
	place-self: center;
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

/* The partner SVG carries its own brand background, so no wrapper fill or padding
   here — that would paint a themed circle behind every logo. SVG scales, so the
   same file serves this thumbnail and the 128px avatar; no separate asset needed. */
.smartmigration-partners__logo {
	display: block;
	width: 34px;
	height: 34px;
	border-radius: 8px;
}

.smartmigration-partners th {
	color: var(--color-text-maxcontrast);
	font-weight: bold;
}

.smartmigration-partners thead {
	position: sticky;
	top: 0;
	z-index: 1;
	background: var(--color-main-background);
}

.smartmigration-partners__sort-button {
	padding: 0;
	border: 0;
	background: transparent;
	color: inherit;
	font: inherit;
	cursor: pointer;
}

.smartmigration-partners__sort-button:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
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

	.smartmigration-partner-detail__description--columns {
		display: block;
	}

	.smartmigration-partner-detail__description--spotlight .smartmigration-partner-detail__description-section:not(:first-child) {
		display: block;
		width: auto;
	}

	.smartmigration-partner-detail__description--columns .smartmigration-partner-detail__description-section + .smartmigration-partner-detail__description-section {
		margin-block-start: 16px;
	}

	.smartmigration-partner-detail__description--governance-grid {
		display: block;
	}

	.smartmigration-partner-detail__description--governance-grid :deep(h3) {
		margin-block-start: 12px;
	}

	.smartmigration-partner-detail__description--assurance-matrix {
		display: block;
	}

	.smartmigration-partner-detail__description--sections .smartmigration-partner-detail__description-section {
		padding-inline-start: 44px;
	}

	.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section {
		display: block;
	}

	.smartmigration-partner-detail__description--banner .smartmigration-partner-detail__description-section h4 {
		margin-block-end: 8px;
	}

}
</style>
