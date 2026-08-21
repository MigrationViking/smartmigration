import { imagePath } from '@nextcloud/router'
import type { Partner, PartnerService } from './partners'

const fallbackLogo = imagePath('smartmigration', 'app.svg')
const allowedServices: PartnerService[] = ['Files', 'E-Mail', 'Calendar']
const allowedTags = new Set([
	'a', 'b', 'blockquote', 'br', 'div', 'em', 'h1', 'h2', 'h3', 'h4', 'hr', 'i', 'li',
	'ol', 'p', 'small', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
])
const voidTags = new Set(['br', 'hr'])

/**
 * Presentation properties a partner may set on their own markup. Everything that
 * can load a resource, escape its box, or cover the page (position, background
 * shorthand, transform, and friends) is deliberately absent.
 */
const allowedStyles = new Set([
	'background-color', 'border', 'border-bottom', 'border-collapse', 'border-left',
	'border-radius', 'border-right', 'border-top', 'color', 'display', 'font-size',
	'font-style', 'font-weight', 'letter-spacing', 'line-height', 'list-style',
	'margin', 'margin-bottom', 'margin-left', 'margin-right', 'margin-top', 'max-width',
	'opacity', 'padding', 'padding-bottom', 'padding-left', 'padding-right',
	'padding-top', 'text-align', 'text-decoration', 'text-transform', 'width',
])

function escapeHtml(value: string): string {
	return value
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#39;')
}

function safeStyle(value: string): string {
	return value
		.split(';')
		.map((declaration) => declaration.trim())
		.filter((declaration) => {
			const separator = declaration.indexOf(':')
			if (separator < 0) {
				return false
			}
			const property = declaration.slice(0, separator).trim().toLowerCase()
			const styleValue = declaration.slice(separator + 1).trim()
			// No functional values: url() would fetch, and the rest are attack surface.
			if (/url\(|expression|@import|\/\*/i.test(styleValue)) {
				return false
			}
			return allowedStyles.has(property)
				&& /^[#a-zA-Z0-9,.%\s-]+$/.test(styleValue)
		})
		.join('; ')
}

function sanitizeNode(node: Node): string {
	if (node.nodeType === Node.TEXT_NODE) {
		return escapeHtml(node.textContent ?? '')
	}
	if (node.nodeType !== Node.ELEMENT_NODE) {
		return ''
	}

	const element = node as Element
	const tagName = element.tagName.toLowerCase()
	const children = Array.from(element.childNodes).map(sanitizeNode).join('')
	if (!allowedTags.has(tagName)) {
		return children
	}
	if (voidTags.has(tagName)) {
		return `<${tagName}>`
	}

	const attributes: string[] = []
	if (tagName === 'a') {
		const href = element.getAttribute('href') ?? ''
		if (/^(https?:|mailto:|tel:)/i.test(href)) {
			attributes.push(` href="${escapeHtml(href)}"`)
		}
	}
	const style = safeStyle(element.getAttribute('style') ?? '')
	if (style) {
		attributes.push(` style="${escapeHtml(style)}"`)
	}

	return `<${tagName}${attributes.join('')}>${children}</${tagName}>`
}

export function sanitizeDescriptionHtml(html: string): string {
	const document = new DOMParser().parseFromString(html, 'text/html')
	return Array.from(document.body.childNodes).map(sanitizeNode).join('').trim()
}

function childText(node: Element, name: string): string {
	return Array.from(node.children)
		.find((child) => child.tagName.toLowerCase() === name.toLowerCase())
		?.textContent?.trim() ?? ''
}

function childServices(node: Element): PartnerService[] {
	const servicesText = childText(node, 'services')
	const serviceNodes = Array.from(node.children)
		.find((child) => child.tagName.toLowerCase() === 'services')
		?.children
	const values = serviceNodes?.length
		? Array.from(serviceNodes).map((service) => service.textContent?.trim() ?? '')
		: servicesText.split(',').map((service) => service.trim())
	return values.filter((service): service is PartnerService => allowedServices.includes(service as PartnerService))
}

function fileName(value: string): string {
	return value.split('/').pop()?.toLowerCase() ?? ''
}

function fileStem(value: string): string {
	return fileName(value).replace(/\.[^.]+$/, '')
}

/**
 * Reads the numeric prefix a partner's files carry ("01 nova-terra.png") and uses
 * it as the partner's initial position in the list. Files without a prefix fall
 * back to the order the XML files were read in.
 *
 * @param value - A file name or path from the uploaded folder
 */
function filePrefix(value: string): number | null {
	const match = /^\s*(\d+)\s*[-_. ]/.exec(fileName(value))
	return match ? Number.parseInt(match[1], 10) : null
}

export interface PartnerSource {
	/** The XML file's own name, e.g. "01 harborview-it.xml". */
	fileName: string
	xmlText: string
	/** Resolves the partner's HTML description by file name or by shared stem. */
	resolveHtml: (fileName: string, stem: string) => string | undefined
	/** Resolves the partner's logo to something usable as an img src. */
	resolveLogo: (fileName: string, stem: string) => string | undefined
	/** Position fallback when the files carry no numeric prefix. */
	index: number
}

/**
 * Turns one partner XML document plus its sibling files into a Partner.
 *
 * @param source - The XML text and resolvers for its logo and description
 */
export function buildPartner(source: PartnerSource): Partner {
	const { fileName: xmlFileName, xmlText, resolveHtml, resolveLogo, index } = source

	const xmlDocument = new DOMParser().parseFromString(xmlText, 'application/xml')
	if (xmlDocument.querySelector('parsererror')) {
		throw new Error(`The XML file ${xmlFileName} could not be read.`)
	}
	const node = xmlDocument.documentElement.tagName.toLowerCase() === 'partner'
		? xmlDocument.documentElement
		: xmlDocument.getElementsByTagName('partner')[0]
	if (!node) {
		throw new Error(`The XML file ${xmlFileName} does not contain one partner element.`)
	}

	const name = childText(node, 'name')
	if (!name) {
		throw new Error(`The XML file ${xmlFileName} is missing a partner name.`)
	}

	const stem = fileStem(xmlFileName)
	const descriptionFile = childText(node, 'descriptionFile') || `${stem}.html`
	const htmlText = resolveHtml(fileName(descriptionFile), stem)
	if (htmlText === undefined) {
		throw new Error(`The HTML description for ${name} is missing (${descriptionFile}).`)
	}

	const logoValue = childText(node, 'logo')
	const logoFileName = childText(node, 'logoFile') || logoValue
	const logo = logoValue.startsWith('data:image/')
		? logoValue
		: resolveLogo(fileName(logoFileName), stem) ?? fallbackLogo

	const declaredOrder = Number.parseInt(childText(node, 'sortOrder'), 10)
	const sortOrder = Number.isFinite(declaredOrder)
		? declaredOrder
		: filePrefix(xmlFileName) ?? filePrefix(logoFileName) ?? index + 1

	const layout = childText(node, 'descriptionLayout')
	const validLayout = ['sections', 'spotlight', 'columns', 'banner', 'timeline', 'governance-grid', 'assurance-matrix'].includes(layout)
	const descriptionHtml = sanitizeDescriptionHtml(htmlText)

	return {
		id: childText(node, 'id') || `${name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}-${index + 1}`,
		sortOrder,
		name,
		logo,
		country: childText(node, 'country'),
		services: childServices(node),
		address: childText(node, 'address'),
		email: childText(node, 'email'),
		phone: childText(node, 'phone'),
		website: childText(node, 'website'),
		description: childText(node, 'description'),
		descriptionHtml: descriptionHtml || undefined,
		descriptionLayout: validLayout ? layout as Partner['descriptionLayout'] : 'sections',
	}
}
