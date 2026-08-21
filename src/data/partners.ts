import { buildPartner } from './partnerImport'

export type PartnerService = 'Files' | 'E-Mail' | 'Calendar'
export type PartnerDescriptionTone = 'primary' | 'success' | 'warning'
export type PartnerDescriptionLayout = 'sections' | 'spotlight' | 'columns' | 'banner' | 'timeline' | 'governance-grid' | 'assurance-matrix'

export interface PartnerDescriptionSection {
	heading: string
	text: string
	tone: PartnerDescriptionTone
}

export interface Partner {
	id: string
	/**
	 * Initial position in the partner list, taken from the numeric prefix of the
	 * partner's files ("01 nova-terra.xml"). Lower sorts first.
	 */
	sortOrder: number
	name: string
	logo: string
	country: string
	services: PartnerService[]
	address: string
	email: string
	phone: string
	website: string
	description: string
	descriptionSections?: PartnerDescriptionSection[]
	descriptionLayout?: PartnerDescriptionLayout
	descriptionHtml?: string
}

/**
 * Partners are supplied as files in the repository's `Partners` folder, three per
 * partner sharing one numbered stem:
 *
 *     01 harborview-it.xml    partner data
 *     01 harborview-it.svg    logo
 *     01 harborview-it.html   formatted presentation
 *
 * The leading number sets the initial order of the partner table. Vite inlines the
 * folder at build time, so adding a partner means dropping in three files and
 * rebuilding — there is no upload step and no request at runtime.
 */
const xmlFiles = import.meta.glob('/Partners/*.xml', { query: '?raw', import: 'default', eager: true }) as Record<string, string>
const htmlFiles = import.meta.glob('/Partners/*.html', { query: '?raw', import: 'default', eager: true }) as Record<string, string>
const svgFiles = import.meta.glob('/Partners/*.svg', { query: '?raw', import: 'default', eager: true }) as Record<string, string>

function baseName(path: string): string {
	return path.split('/').pop()?.toLowerCase() ?? ''
}

function stemOf(path: string): string {
	return baseName(path).replace(/\.[^.]+$/, '')
}

function byName(files: Record<string, string>): Map<string, string> {
	return new Map(Object.entries(files).map(([path, content]) => [baseName(path), content]))
}

function byStem(files: Record<string, string>): Map<string, string> {
	return new Map(Object.entries(files).map(([path, content]) => [stemOf(path), content]))
}

function loadPartners(): Partner[] {
	const htmlByName = byName(htmlFiles)
	const htmlByStem = byStem(htmlFiles)
	const svgByName = byName(svgFiles)
	const svgByStem = byStem(svgFiles)

	const loaded: Partner[] = []
	for (const [index, [path, xmlText]] of Object.entries(xmlFiles).sort().entries()) {
		try {
			loaded.push(buildPartner({
				fileName: baseName(path),
				xmlText,
				index,
				resolveHtml: (name, stem) => htmlByName.get(name) ?? htmlByStem.get(stem),
				resolveLogo: (name, stem) => {
					const svg = svgByName.get(name) ?? svgByStem.get(stem)
					// Inline rather than linking, so no extra request and no asset path.
					return svg === undefined ? undefined : `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`
				},
			}))
		} catch (error) {
			// One malformed partner file must not empty the whole tab.
			console.error(`Could not load partner file ${path}`, error)
		}
	}

	return loaded.sort((left, right) => left.sortOrder - right.sortOrder)
}

export const partners: Partner[] = loadPartners()
