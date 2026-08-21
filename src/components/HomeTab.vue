<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { imagePath } from '@nextcloud/router'
import { type Job, fetchJobs } from '../api/jobs'
import { type License } from '../api/settings'

const appLogo = imagePath('smartmigration', 'app.svg')

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
	<section v-if="!hasLicenseKey" class="smartmigration-home smartmigration-home--welcome">
		<div class="smartmigration-home__welcome-graphic">
			<img :src="appLogo" alt="" aria-hidden="true">
		</div>
		<div class="smartmigration-home__welcome-copy">
			<p class="smartmigration-home__eyebrow">
				{{ t('smartmigration', 'SMART Migration') }}
			</p>
			<h2>{{ t('smartmigration', 'About SMART Migration') }}</h2>
			<p>
				{{ t('smartmigration', 'Provides a Nextcloud interface for defining and monitoring MigrateDMS SMART Migration jobs.') }}
			</p>
			<p>
				{{ t('smartmigration', 'Create a job for each SharePoint, OneDrive or Teams document library, or file share, and run deep file discovery before you move anything. Interactive business intelligence shows volume, age, structure, and the content that needs attention first. You plan the migration on a complete picture of your data.') }}
			</p>
			<p>
				{{ t('smartmigration', 'The same reporting tracks progress while the migration runs and becomes your documentation of what was moved once it is done. Discovery, migration, and run history stay visible in Nextcloud, with reports delivered to Nextcloud Files.') }}
			</p>
			<p>
				{{ t('smartmigration', 'A SMART Migration partner supplies the server licence, helps you plan the work, and supports you through the migration. Start by choosing a partner.') }}
			</p>
			<NcButton variant="primary" @click="emit('showPartners')">
				{{ t('smartmigration', 'Choose a partner') }}
			</NcButton>
		</div>
	</section>

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
	max-width: 960px;
	margin: 0 auto;
}

.smartmigration-home--welcome {
	display: grid;
	grid-template-columns: minmax(180px, 0.7fr) minmax(0, 1.3fr);
	gap: 48px;
	align-items: center;
	padding: 48px 24px;
}

.smartmigration-home__welcome-graphic {
	display: grid;
	place-items: center;
	min-height: 220px;
	background: var(--color-primary-element-light);
	border-radius: 8px;
}

.smartmigration-home__welcome-graphic img {
	width: 128px;
	height: 128px;
	padding: 28px;
	background: var(--color-primary-element);
	border-radius: 50%;
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

.smartmigration-home__welcome-copy p:not(.smartmigration-home__eyebrow) {
	max-width: 680px;
	line-height: 1.6;
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
	.smartmigration-home--welcome {
		grid-template-columns: 1fr;
		gap: 24px;
		padding: 24px 0;
	}

	.smartmigration-home__welcome-graphic {
		min-height: 160px;
	}

	.smartmigration-home__summary {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
</style>
