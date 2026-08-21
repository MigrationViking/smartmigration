import { imagePath } from '@nextcloud/router'

const appLogo = imagePath('smartmigration', 'app.svg')

export type PartnerService = 'Files' | 'E-Mail' | 'Calendar'

export interface Partner {
	id: string
	name: string
	logo: string
	country: string
	services: PartnerService[]
	address: string
	email: string
	phone: string
	website: string
	description: string
}

export const partners: Partner[] = [
	{
		id: 'nordlys-consulting',
		name: 'Nordlys Consulting ApS',
		logo: appLogo,
		country: 'Denmark',
		services: ['Files', 'E-Mail'],
		address: 'Vesterbrogade 12, 1620 Copenhagen',
		email: 'partners@nordlysconsulting.example',
		phone: '+45 70 20 30 40',
		website: 'https://www.nordlysconsulting.example',
		description: 'Nordlys Consulting helps organisations plan and deliver structured content migrations, with a focus on discovery, governance, and reliable handover.',
	},
	{
		id: 'rheinland-digital',
		name: 'Rheinland Digital GmbH',
		logo: appLogo,
		country: 'Germany',
		services: ['Files', 'Calendar'],
		address: 'Domstrasse 4, 50668 Cologne',
		email: 'kontakt@rheinlanddigital.example',
		phone: '+49 221 555 0140',
		website: 'https://www.rheinlanddigital.example',
		description: 'Rheinland Digital supports Microsoft 365 and Nextcloud projects from assessment through migration, adoption, and operational support.',
	},
	{
		id: 'atelier-cloud',
		name: 'Atelier Cloud Solutions',
		logo: appLogo,
		country: 'France',
		services: ['Files', 'E-Mail', 'Calendar'],
		address: '18 Rue de la Republique, 69002 Lyon',
		email: 'contact@ateliercloud.example',
		phone: '+33 4 72 00 12 40',
		website: 'https://www.ateliercloud.example',
		description: 'Atelier Cloud Solutions combines migration engineering with practical change management for teams moving to secure, collaborative workspaces.',
	},
	{
		id: 'harborview-it',
		name: 'Harborview IT Services',
		logo: appLogo,
		country: 'United States',
		services: ['Files'],
		address: '200 Seaport Blvd, Boston, MA 02210',
		email: 'sales@harborviewit.example',
		phone: '+1 617 555 0188',
		website: 'https://www.harborviewit.example',
		description: 'Harborview IT Services delivers hands-on migration programmes for distributed organisations, from inventory and planning to final validation.',
	},
	{
		id: 'nova-terra',
		name: 'Nova Terra Systems',
		logo: appLogo,
		country: 'Netherlands',
		services: ['E-Mail', 'Calendar'],
		address: 'Prinsengracht 45, 1015 Amsterdam',
		email: 'info@novaterra.example',
		phone: '+31 20 555 0190',
		website: 'https://www.novaterra.example',
		description: 'Nova Terra Systems specialises in planning communication and collaboration migrations with clear reporting and measurable outcomes.',
	},
	{
		id: 'brightpath-migrations',
		name: 'BrightPath Migrations Ltd',
		logo: appLogo,
		country: 'United Kingdom',
		services: ['Files', 'E-Mail'],
		address: '22 Deansgate, Manchester M3 2BW',
		email: 'hello@brightpathmigrations.example',
		phone: '+44 161 555 0122',
		website: 'https://www.brightpathmigrations.example',
		description: 'BrightPath Migrations helps customers move content confidently, pairing technical execution with transparent communication throughout the project.',
	},
	{
		id: 'alpine-data',
		name: 'Alpine Data Partners AG',
		logo: appLogo,
		country: 'Switzerland',
		services: ['Files', 'Calendar'],
		address: 'Bahnhofstrasse 8, 8001 Zurich',
		email: 'office@alpinedatapartners.example',
		phone: '+41 44 555 0160',
		website: 'https://www.alpinedatapartners.example',
		description: 'Alpine Data Partners provides careful, compliant migration services for organisations that need strong control over data and access.',
	},
	{
		id: 'cedar-stone',
		name: 'Cedar & Stone Consulting',
		logo: appLogo,
		country: 'Canada',
		services: ['Files', 'E-Mail', 'Calendar'],
		address: '150 King Street W, Toronto, ON M5H 1J9',
		email: 'support@cedarstone.example',
		phone: '+1 416 555 0174',
		website: 'https://www.cedarstone.example',
		description: 'Cedar & Stone Consulting works with business and public-sector teams on secure migrations, governance, and long-term support.',
	},
	{
		id: 'fjord-technologies',
		name: 'Fjord Technologies AS',
		logo: appLogo,
		country: 'Norway',
		services: ['Files'],
		address: 'Karl Johans gate 10, 0154 Oslo',
		email: 'team@fjordtech.example',
		phone: '+47 22 555 018',
		website: 'https://www.fjordtech.example',
		description: 'Fjord Technologies brings migration, automation, and data quality expertise to organisations modernising their collaboration platforms.',
	},
	{
		id: 'sunrise-cloud',
		name: 'Sunrise Cloud Advisors',
		logo: appLogo,
		country: 'Australia',
		services: ['E-Mail', 'Calendar'],
		address: '55 Market Street, Sydney NSW 2000',
		email: 'contact@sunrisecloud.example',
		phone: '+61 2 5550 0195',
		website: 'https://www.sunrisecloud.example',
		description: 'Sunrise Cloud Advisors supports modern workplace programmes with practical migration planning, delivery, and post-project care.',
	},
]
