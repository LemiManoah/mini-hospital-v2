import {
    type LaboratoryQueuePageProps,
    type LaboratoryQueueRequest,
    type LaboratoryRequestItem,
    type LaboratoryRequestSummary,
} from '@/types/laboratory';

type ResultActor = { first_name: string; last_name: string } | null | undefined;

type ResultEntryActorShape = {
    enteredBy?: ResultActor;
    reviewedBy?: ResultActor;
    approvedBy?: ResultActor;
    correctedBy?: ResultActor;
    entered_by?: ResultActor;
    reviewed_by?: ResultActor;
    approved_by?: ResultActor;
    corrected_by?: ResultActor;
};

type ResultValueShape = {
    display_value?: string | null;
    value_text: string | null;
    value_numeric: number | null;
};

export const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

export const formatDateTime = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleString() : 'Not yet recorded';

export const actorName = (
    actor?: { first_name: string; last_name: string } | null,
): string =>
    actor ? `${actor.first_name} ${actor.last_name}` : 'Not recorded';

export const actorFromResultEntry = (
    resultEntry: ResultEntryActorShape | null | undefined,
    field: 'enteredBy' | 'reviewedBy' | 'approvedBy' | 'correctedBy',
    legacyField: 'entered_by' | 'reviewed_by' | 'approved_by' | 'corrected_by',
): string =>
    actorName(resultEntry?.[field] ?? resultEntry?.[legacyField] ?? null);

export const formatPatientAge = (
    patient?: {
        age?: number | null;
        age_units?: string | null;
        display_age?: number | null;
        display_age_units?: string | null;
    } | null,
): string => {
    const age = patient?.display_age ?? patient?.age;

    if (age === null || age === undefined) {
        return 'N/A';
    }

    const units = patient?.display_age_units ?? patient?.age_units;

    const normalizedUnits = units ? units.replaceAll('_', ' ') : 'years';

    return `${age} ${normalizedUnits}`;
};

export const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed' || workflowStage === 'result_entered') {
        return 'secondary';
    }
    if (workflowStage === 'cancelled' || workflowStage === 'rejected') {
        return 'destructive';
    }

    return 'outline';
};

export const priorityVariant = (
    priority: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (priority === 'critical' || priority === 'stat') return 'destructive';
    if (priority === 'urgent') return 'secondary';

    return 'outline';
};

export const resultValueDisplay = (value: ResultValueShape): string =>
    value.display_value ?? value.value_text ?? `${value.value_numeric ?? ''}`;

export type ModalMode = 'collect' | 'enter' | 'review' | 'view';

export type ActiveModal = {
    mode: ModalMode;
    item: LaboratoryRequestItem;
    request: LaboratoryRequestSummary | null;
} | null;

export type QueueCardRequest = LaboratoryQueueRequest & {
    request_count: number;
};

const priorityWeight = (priority: string): number =>
    ({
        critical: 0,
        stat: 1,
        urgent: 2,
        routine: 3,
    })[priority] ?? 4;

const toRequestSummary = (
    request: LaboratoryQueueRequest,
): LaboratoryRequestSummary => ({
    id: request.id,
    request_date: request.request_date,
    priority: request.priority,
    status: request.status,
    clinical_notes: request.clinical_notes,
    requestedBy: request.requestedBy,
    visit: request.visit,
});

export const withRequestSummary = (
    request: LaboratoryQueueRequest,
): QueueCardRequest => {
    const summary = toRequestSummary(request);

    return {
        ...request,
        request_count: 1,
        items: request.items.map((item) => ({
            ...item,
            request: item.request ?? summary,
        })),
    };
};

export const groupIncomingRequests = (
    requests: LaboratoryQueueRequest[],
): QueueCardRequest[] => {
    const groupedRequests = new Map<string, QueueCardRequest>();

    requests.forEach((request) => {
        const visitId = request.visit?.id ?? `request:${request.id}`;
        const patientId = request.visit?.patient?.id ?? 'unknown-patient';
        const groupKey = `${visitId}:${patientId}`;
        const normalizedRequest = withRequestSummary(request);
        const existing = groupedRequests.get(groupKey);

        if (!existing) {
            groupedRequests.set(groupKey, {
                ...normalizedRequest,
                id: groupKey,
            });
            return;
        }

        groupedRequests.set(groupKey, {
            ...existing,
            request_date:
                new Date(normalizedRequest.request_date).getTime() >
                new Date(existing.request_date).getTime()
                    ? normalizedRequest.request_date
                    : existing.request_date,
            priority:
                priorityWeight(normalizedRequest.priority) <
                priorityWeight(existing.priority)
                    ? normalizedRequest.priority
                    : existing.priority,
            items: [...existing.items, ...normalizedRequest.items].sort(
                (left, right) =>
                    new Date(
                        right.request?.request_date ??
                            normalizedRequest.request_date,
                    ).getTime() -
                    new Date(
                        left.request?.request_date ??
                            normalizedRequest.request_date,
                    ).getTime(),
            ),
            request_count: existing.request_count + 1,
        });
    });

    return [...groupedRequests.values()].sort(
        (left, right) =>
            new Date(right.request_date).getTime() -
            new Date(left.request_date).getTime(),
    );
};

export function modalModeForStage(
    stage: LaboratoryQueuePageProps['page']['stage'],
): ModalMode {
    if (stage === 'incoming') return 'collect';
    if (stage === 'enter_results') return 'enter';
    if (stage === 'review_results') return 'review';

    return 'view';
}
