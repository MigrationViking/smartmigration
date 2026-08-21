<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { imagePath } from '@nextcloud/router'
import { type Job, fetchJobs } from '../api/jobs'
import { type License } from '../api/settings'

const appLogo = imagePath('smartmigration', 'smart.png')

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
		<div class="smartmigration-home__welcome-header">
			<img class="smartmigration-home__welcome-logo"
				:src="appLogo"
				alt=""
				aria-hidden="true">
			<div>
				<h2>{{ t('smartmigration', 'SMART Migration from MigrateDMS') }}</h2>
				<p class="smartmigration-home__lead">
					{{ t('smartmigration', 'Manage file migrations from SharePoint, OneDrive, Teams, and file shares to Nextcloud, with deep discovery, interactive business intelligence, progress tracking, and complete run history.') }}
				</p>
			</div>
		</div>

		<div class="smartmigration-home__welcome-copy">

			<div class="smartmigration-home__sections">
				<section class="smartmigration-home__section smartmigration-home__section--discover">
					<h3>{{ t('smartmigration', 'Discover before you move') }}</h3>
					<p>
						{{ t('smartmigration', 'Create a job for each SharePoint, OneDrive or Teams document library, or file share. Deep discovery reads the source before anything moves: volume, age profile, folder depth, permissions, version history, and the file types that will need a decision.') }}
					</p>
					<p>
						{{ t('smartmigration', 'You find out what is really there — abandoned content, oversized files, broken permissions — while there is still time to act. Discovery on its own is a complete result, not an unfinished migration.') }}
					</p>
					<strong>{{ t('smartmigration', 'Volume. Age. Structure. Attention points.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--migrate">
					<h3>{{ t('smartmigration', 'Migrate with confidence') }}</h3>
					<p>
						{{ t('smartmigration', 'Interactive business intelligence turns discovery into a plan you can defend: what moves first, what needs cleaning up, what can be left behind, and roughly how long each wave is going to take.') }}
					</p>
					<p>
						{{ t('smartmigration', 'The same reporting follows the migration itself, so throughput, progress, and exceptions stay visible while the work runs — instead of arriving as a surprise in a final report nobody can act on.') }}
					</p>
					<strong>{{ t('smartmigration', 'One clear view from first discovery to final handover.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--report">
					<h3>{{ t('smartmigration', 'Keep the record') }}</h3>
					<p>
						{{ t('smartmigration', 'Discovery results, migration outcomes, and the full run history stay visible inside Nextcloud. Every run records what it touched, when it ran, which settings applied, and what needed attention.') }}
					</p>
					<p>
						{{ t('smartmigration', 'Reports are delivered to Nextcloud Files and become the documentation of the move — the record somebody opens years later to answer where a file came from and why it looks the way it does.') }}
					</p>
					<strong>{{ t('smartmigration', 'A living record of your migration.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--groupware">
					<h3>{{ t('smartmigration', 'Groupware') }}</h3>
					<p>
						{{ t('smartmigration', 'This app moves files. E-mail and calendar are handled by an external service your SMART Migration partner delivers alongside them: mailboxes, shared calendars, room resources, and delegate permissions, planned in the same waves as the files.') }}
					</p>
					<p>
						{{ t('smartmigration', 'Not every partner offers it. The Partners tab lists the services each one covers, so you can choose a partner able to take on Files, E-Mail, and Calendar as a single engagement.') }}
					</p>
					<strong>{{ t('smartmigration', 'One project. Files, mail, and calendar.') }}</strong>
				</section>
			</div>

			<div class="smartmigration-home__partner-callout">
				<div>
					<h3>{{ t('smartmigration', 'Your partner gets you started') }}</h3>
					<p>
						{{ t('smartmigration', 'A SMART Migration partner supplies the server licence, helps you plan the work, and supports you through the migration.') }}
					</p>
				</div>
				<NcButton variant="primary" @click="emit('showPartners')">
					{{ t('smartmigration', 'Choose a partner') }}
				</NcButton>
			</div>
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
	max-width: 1200px;
	margin: 0 auto;
}

.smartmigration-home--welcome {
	padding: 40px 24px 48px;
}

.smartmigration-home__welcome-header {
	display: flex;
	align-items: center;
	gap: 28px;
	margin-block-end: 8px;
}

.smartmigration-home__welcome-logo {
	flex-shrink: 0;
	width: 170px;
	height: auto;
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
	line-height: 1.6;
}

.smartmigration-home__welcome-header .smartmigration-home__lead {
	max-width: 780px;
	line-height: 1.6;
}

.smartmigration-home__lead {
	font-size: 1.1rem;
	font-weight: 600;
}

.smartmigration-home__sections {
	display: grid;
	/* Four pillars side by side where there is room, wrapping before they get too
	   narrow to read. */
	grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
	gap: 20px;
	margin: 32px 0;
}

.smartmigration-home__section {
	display: flex;
	flex-direction: column;
	padding: 4px 0 4px 18px;
	border-inline-start: 4px solid var(--color-primary-element);
}

/* Pins the closing line to the bottom, so uneven copy still ends level. */
.smartmigration-home__section strong {
	margin-block-start: auto;
	padding-block-start: 8px;
}

.smartmigration-home__section--discover {
	border-color: var(--color-element-success);
}

.smartmigration-home__section--migrate {
	border-color: var(--color-primary-element);
}

.smartmigration-home__section--report {
	border-color: var(--color-element-error);
}

/*
 * Groupware is the odd one out: it is delivered by a partner rather than by this
 * app, so it gets a heavier rule than the three core pillars.
 */
.smartmigration-home__section--groupware {
	padding-inline-start: 22px;
	border-inline-start-width: 9px;
	border-color: var(--color-element-warning);
}

.smartmigration-home__section h3,
.smartmigration-home__partner-callout h3 {
	margin: 0 0 8px;
	font-size: 1.05rem;
}

.smartmigration-home__section p,
.smartmigration-home__partner-callout p {
	margin: 0 0 12px;
	line-height: 1.55;
}

.smartmigration-home__section strong {
	font-size: 0.9rem;
}

.smartmigration-home__partner-callout {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 24px;
	padding: 20px 24px;
	background: var(--color-background-hover);
	border-top: 3px solid var(--color-primary-element);
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
		width: 100%;
	}

	.smartmigration-home__sections {
		grid-template-columns: 1fr;
		gap: 24px;
	}

	.smartmigration-home__partner-callout {
		align-items: flex-start;
		flex-direction: column;
	}

	.smartmigration-home__summary {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
</style>
