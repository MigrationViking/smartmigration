<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { type Job, fetchJobs } from '../api/jobs'
import { type License } from '../api/settings'
import WelcomeInfo from './WelcomeInfo.vue'

const props = defineProps<{
	license: License | null
}>()

const emit = defineEmits<{
	showPartners: []
	showJobs: []
}>()

const hasLicenseKey = computed(() => Boolean(props.license?.licenseKey?.trim()))
const jobs = ref<Job[]>([])
const loading = ref(false)
const loadError = ref(false)

const runningJobs = computed(() => jobs.value.filter((job) => job.status === 'Running' || job.status === 'Reporting').length)

onMounted(async () => {
	if (!hasLicenseKey.value) {
		return
	}

	loading.value = true
	try {
		jobs.value = await fetchJobs()
	} catch (error) {
		loadError.value = true
		console.error('Failed to load dashboard jobs', error)
	} finally {
		loading.value = false
	}
})
</script>

<template>
	<WelcomeInfo v-if="!hasLicenseKey" @show-partners="emit('showPartners')" />

	<section v-else class="smartmigration-home">
		<header class="smartmigration-home__header">
			<div>
				<p class="smartmigration-home__eyebrow">
					{{ t('smartmigration', 'Dashboard') }}
				</p>
				<h2>{{ t('smartmigration', 'Migration overview') }}</h2>
			</div>
			<NcButton variant="primary" @click="emit('showJobs')">
				{{ t('smartmigration', 'Open jobs') }}
			</NcButton>
		</header>

		<div v-if="loading" class="smartmigration-home__loading">
			<NcLoadingIcon :size="32" />
		</div>
		<NcEmptyContent v-else-if="loadError"
			:name="t('smartmigration', 'Dashboard unavailable')"
			:description="t('smartmigration', 'The job overview could not be loaded. Open Jobs to try again.')" />
		<div v-else class="smartmigration-home__summary">
			<div class="smartmigration-home__metric">
				<strong>{{ jobs.length }}</strong>
				<span>{{ t('smartmigration', 'Total jobs') }}</span>
			</div>
			<div class="smartmigration-home__metric smartmigration-home__metric--active">
				<strong>{{ runningJobs }}</strong>
				<span>{{ t('smartmigration', 'Running jobs') }}</span>
			</div>
			<div class="smartmigration-home__metric">
				<strong>{{ jobs.filter((job) => job.status === 'Ready').length }}</strong>
				<span>{{ t('smartmigration', 'Ready jobs') }}</span>
			</div>
			<div class="smartmigration-home__metric">
				<strong>{{ jobs.filter((job) => job.status === 'Finished').length }}</strong>
				<span>{{ t('smartmigration', 'Finished jobs') }}</span>
			</div>
		</div>
	</section>
</template>

<style scoped>
.smartmigration-home {
	max-width: 1200px;
	margin: 0 auto;
}





.smartmigration-home__eyebrow {
	margin: 0 0 8px;
	color: var(--color-primary-element);
	font-size: 0.85rem;
	font-weight: bold;
	text-transform: uppercase;
}

.smartmigration-home h2 {
	margin: 0 0 16px;
}

.smartmigration-home__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	padding: 24px 0;
	border-bottom: 1px solid var(--color-border);
}

.smartmigration-home__header h2 {
	margin-bottom: 0;
}

.smartmigration-home__loading {
	display: grid;
	place-items: center;
	min-height: 220px;
}

.smartmigration-home__summary {
	display: grid;
	grid-template-columns: repeat(4, minmax(0, 1fr));
	gap: 16px;
	padding-top: 32px;
}

.smartmigration-home__metric {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 24px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
}

.smartmigration-home__metric strong {
	font-size: 2rem;
}

.smartmigration-home__metric span {
	color: var(--color-text-maxcontrast);
}

.smartmigration-home__metric--active strong {
	color: var(--color-element-success);
}

@media (max-width: 700px) {

	.smartmigration-home__summary {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
</style>
