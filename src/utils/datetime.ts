export function toDate(value: number | null): Date | null {
	return value ? new Date(value * 1000) : null
}

export function fromDatePicker(value: Date | null): number | null {
	return value ? Math.floor(value.getTime() / 1000) : null
}
