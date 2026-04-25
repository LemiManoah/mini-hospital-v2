export const facilitySupportStatusOptions = [
    { value: 'stable', label: 'Stable' },
    { value: 'follow_up', label: 'Needs Follow-Up' },
    { value: 'awaiting_facility', label: 'Awaiting Facility' },
    { value: 'escalated', label: 'Escalated' },
    { value: 'resolved', label: 'Resolved' },
] as const;

export const facilitySupportPriorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'normal', label: 'Normal' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
] as const;

export const toDateTimeLocalValue = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const timezoneOffset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - timezoneOffset).toISOString().slice(0, 16);
};
