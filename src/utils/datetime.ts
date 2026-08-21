export function toDate(value: number | null): Date | null {
	return value ? new Date(value * 1000) : null
}

export function fromDatePicker(value: Date | null): number | null {
	return value ? Math.floor(value.getTime() / 1000) : null
}

/**
 * True when a stored date has been reached or passed, comparing calendar days so
 * a licence expiring today counts as expired regardless of the time of day.
 *
 * @param value - Unix seconds, or null when no date is stored
 */
export function isTodayOrPast(value: number | null): boolean {
	const date = toDate(value)
	if (date === null) {
		return false
	}

	const startOfToday = new Date()
	startOfToday.setHours(0, 0, 0, 0)
	date.setHours(0, 0, 0, 0)

	return date <= startOfToday
}
