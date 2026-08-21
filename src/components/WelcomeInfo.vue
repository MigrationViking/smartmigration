<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import { imagePath } from '@nextcloud/router'

const appLogo = imagePath('smartmigration', 'smart.png')

const emit = defineEmits<{
	showPartners: []
}>()
</script>

<template>
	<section class="smartmigration-home smartmigration-home--welcome">
		<div class="smartmigration-home__welcome-header">
			<img class="smartmigration-home__welcome-logo"
				:src="appLogo"
				alt=""
				aria-hidden="true">
			<div>
				<h2>{{ t('smartmigration', 'SMART Migration from MigrateDMS') }}</h2>
				<p class="smartmigration-home__lead">
					{{ t('smartmigration', 'Manage file migrations from SharePoint, OneDrive, Teams, and file shares to Nextcloud, with deep discovery, interactive business intelligence, progress tracking, and complete run history. The same jobs serve digital operational resilience.') }}
				</p>
				<p class="smartmigration-home__welcome-note">
					{{ t('smartmigration', 'This app is where the work is defined and followed. The work itself runs on the SMART Migration server, installed on the machine that performs the BI data discoveries and migrations and connected to this Nextcloud — that is where all the logic lives. A partner supplies the SMART Migration server licence, helps you plan the work, and supports you.') }}
				</p>
				<NcButton class="smartmigration-home__welcome-action"
					variant="primary"
					@click="emit('showPartners')">
					{{ t('smartmigration', 'Choose a partner') }}
				</NcButton>
			</div>
		</div>

		<div class="smartmigration-home__welcome-copy">

			<div class="smartmigration-home__sections">
				<section class="smartmigration-home__section smartmigration-home__section--discover">
					<h3>{{ t('smartmigration', 'Discover before you move') }}</h3>
					<p>
						{{ t('smartmigration', 'Create a job for each SharePoint, OneDrive or Teams library, or file share. Deep discovery reads the source before anything moves: volume, age profile, folder depth, permissions, version history, and the file types that need a decision.') }}
					</p>
					<p>
						{{ t('smartmigration', 'You see what is really there, and which content matters enough to keep replicated rather than migrated once.') }}
					</p>
					<strong>{{ t('smartmigration', 'Volume. Age. Structure. Attention points.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--migrate">
					<h3>{{ t('smartmigration', 'Migrate with confidence') }}</h3>
					<p>
						{{ t('smartmigration', 'Interactive business intelligence turns discovery into a plan you can defend: what moves first, what needs cleaning up, what can be left behind, and how long each wave will take.') }}
					</p>
					<p>
						{{ t('smartmigration', 'Give a job a recurrence and the same selection refreshes on a schedule, keeping a standing copy current. Microsoft 365 limits mean this suits a focused set, not an entire estate.') }}
					</p>
					<strong>{{ t('smartmigration', 'One clear view from first discovery to final handover.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--report">
					<h3>{{ t('smartmigration', 'Keep the record') }}</h3>
					<p>
						{{ t('smartmigration', 'Discovery results, migration outcomes, and the full run history stay visible inside Nextcloud. Every run records what it touched, when it ran, and what needed attention.') }}
					</p>
					<p>
						{{ t('smartmigration', 'Reports land in Nextcloud Files as the documentation of the move — and, for a standing copy, the evidence of when each protected set was last refreshed.') }}
					</p>
					<strong>{{ t('smartmigration', 'A living record of your migration.') }}</strong>
				</section>

				<section class="smartmigration-home__section smartmigration-home__section--groupware">
					<h3>{{ t('smartmigration', 'Groupware') }}</h3>
					<p>
						{{ t('smartmigration', 'This app moves files. E-mail and calendar are handled by an external service your SMART Migration partner delivers alongside them: mailboxes, shared calendars, room resources, and delegate permissions.') }}
					</p>
					<p>
						{{ t('smartmigration', 'Not every partner offers it. The Partners tab lists the services each one covers, so you can choose a partner able to take on Files, E-Mail, and Calendar together.') }}
					</p>
					<strong>{{ t('smartmigration', 'One project. Files, mail, and calendar.') }}</strong>
				</section>
			</div>


			<section class="smartmigration-home__resilience">
				<h3>{{ t('smartmigration', 'Digital operational resilience and DORA') }}</h3>
				<p class="smartmigration-home__resilience-lead">
					{{ t('smartmigration', 'Migration is not the only reason to copy content into Nextcloud. The same jobs can maintain an independent, always-current copy of the documents your critical business functions depend on.') }}
				</p>

				<div class="smartmigration-home__resilience-grid">
					<div>
						<h4>{{ t('smartmigration', 'What DORA asks for') }}</h4>
						<p>
							{{ t('smartmigration', 'The Digital Operational Resilience Act, Regulation (EU) 2022/2554, has applied to EU financial entities since January 2025. It requires them to keep critical functions running through ICT disruption, to manage their dependence on third-party ICT providers, and to be able to demonstrate how.') }}
						</p>
					</div>
					<div>
						<h4>{{ t('smartmigration', 'Where this app fits') }}</h4>
						<p>
							{{ t('smartmigration', 'Concentration risk is one of the concerns: if the documents a critical function needs exist only in Microsoft 365, that function stops when Microsoft 365 does. A recurring job keeps those documents in Nextcloud — a different provider, under your own control — and the run history shows when the copy was last refreshed.') }}
						</p>
					</div>
					<div>
						<h4>{{ t('smartmigration', 'What it does not do') }}</h4>
						<p>
							{{ t('smartmigration', 'This app is not a compliance product and certifies nothing. It supports one control among many. Deciding the scope, testing that the copy is usable, and presenting the evidence remain your responsibility, and your auditor\'s judgement.') }}
						</p>
					</div>
				</div>

				<div class="smartmigration-home__resilience-limits">
					<h4>{{ t('smartmigration', 'Scope it deliberately — Microsoft 365 sets the ceiling') }}</h4>
					<p>
						{{ t('smartmigration', 'Microsoft 365 throttles heavy or frequent reads, so an entire tenant cannot be kept continuously in sync. A resilience scope has to be chosen, not assumed. We advise:') }}
					</p>
					<ul>
						<li>{{ t('smartmigration', 'Fewer than 20 libraries in the recurring scope.') }}</li>
						<li>{{ t('smartmigration', 'Under 50,000 files in each of them.') }}</li>
						<li>{{ t('smartmigration', 'A recurrence matched to how fast the content changes — daily for active document sets, weekly for reference material.') }}</li>
						<li>{{ t('smartmigration', 'Everything else migrated once, or left where it is.') }}</li>
					</ul>
					<p>
						{{ t('smartmigration', 'Discovery is how you find out whether a candidate library fits inside those numbers before you commit to protecting it.') }}
					</p>
				</div>
			</section>
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

.smartmigration-home__welcome-note {
	max-width: 780px;
	margin-block-start: 10px;
	line-height: 1.6;
}

.smartmigration-home__welcome-action {
	margin-block-start: 16px;
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

.smartmigration-home__section h3 {
	margin: 0 0 8px;
	font-size: 1.05rem;
}

.smartmigration-home__section p {
	margin: 0 0 12px;
	line-height: 1.55;
}

.smartmigration-home__section strong {
	font-size: 0.9rem;
}

/*
 * The resilience explainer is the long-form counterpart to the pillars, so it is
 * boxed off rather than competing with them for attention.
 */
.smartmigration-home__resilience {
	margin-block-start: 32px;
	padding: 28px;
	border: 1px solid var(--color-border);
	border-radius: 12px;
}

.smartmigration-home__resilience h3 {
	margin: 0 0 8px;
	color: var(--color-element-warning);
	font-size: 1.15rem;
}

.smartmigration-home__resilience-lead {
	margin: 0 0 20px;
	font-weight: 600;
	line-height: 1.6;
}

.smartmigration-home__resilience-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
	gap: 24px;
}

.smartmigration-home__resilience h4 {
	margin: 0 0 6px;
	font-size: 0.95rem;
}

.smartmigration-home__resilience p {
	margin: 0;
	line-height: 1.55;
}

.smartmigration-home__resilience-limits {
	margin-block-start: 24px;
	padding: 18px 22px;
	background: var(--color-background-hover);
	border-inline-start: 9px solid var(--color-element-warning);
	border-radius: 0 8px 8px 0;
}

.smartmigration-home__resilience-limits ul {
	margin: 10px 0;
	padding-inline-start: 20px;
	line-height: 1.7;
}

@media (max-width: 700px) {
	.smartmigration-home--welcome {
		padding: 24px 0;
	}

	.smartmigration-home__sections {
		grid-template-columns: 1fr;
		gap: 24px;
	}
}
</style>
