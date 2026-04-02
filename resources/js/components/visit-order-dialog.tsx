import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import {
    type DrugOption,
    type FacilityServiceOption,
    type FacilityServiceOrder,
    type PatientVisit,
} from '@/types/patient';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    type OrderTabValue,
    ORDER_TAB_LABELS,
    formatMoney,
    labelize,
} from './visit-ordering';

type PrescriptionDraftItem = {
    inventory_item_id: string;
    dosage: string;
    frequency: string;
    route: string;
    duration_days: string;
    quantity: string;
    instructions: string;
    is_prn: boolean;
    prn_reason: string;
    is_external_pharmacy: boolean;
};

const createPrescriptionItem = (): PrescriptionDraftItem => ({
    inventory_item_id: '',
    dosage: '',
    frequency: '',
    route: '',
    duration_days: '5',
    quantity: '1',
    instructions: '',
    is_prn: false,
    prn_reason: '',
    is_external_pharmacy: false,
});

export function VisitOrderDialog({
    open,
    onOpenChange,
    initialTab,
    redirectTo,
    visit,
    labTestOptions,
    drugOptions,
    labPriorities,
    imagingModalities,
    imagingPriorities,
    imagingLateralities,
    pregnancyStatuses,
    facilityServiceOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialTab: OrderTabValue;
    redirectTo: 'visit' | 'consultation';
    visit: Pick<
        PatientVisit,
        'id' | 'facilityServiceOrders' | 'facility_service_orders'
    > & {
        consultation?: unknown;
        triage?: unknown;
        facilityServiceOrders?: FacilityServiceOrder[] | null;
        facility_service_orders?: FacilityServiceOrder[] | null;
    };
    labTestOptions: Array<{
        id: string;
        test_code: string;
        test_name: string;
        category: string | null;
        base_price: number | null;
        quoted_price?: number | null;
        price_source?: string | null;
    }>;
    drugOptions: DrugOption[];
    labPriorities: { value: string; label: string }[];
    imagingModalities: { value: string; label: string }[];
    imagingPriorities: { value: string; label: string }[];
    imagingLateralities: { value: string; label: string }[];
    pregnancyStatuses: { value: string; label: string }[];
    facilityServiceOptions: FacilityServiceOption[];
}) {
    const [selectedTab, setSelectedTab] = useState<OrderTabValue>(initialTab);
    const consultation = (visit.consultation ?? null) as {
        history_of_present_illness?: string | null;
        primary_diagnosis?: string | null;
        primary_icd10_code?: string | null;
    } | null;
    const triage = (visit.triage ?? null) as {
        history_of_presenting_illness?: string | null;
    } | null;
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];

    useEffect(() => {
        if (open) {
            setSelectedTab(initialTab);
        }
    }, [initialTab, open]);

    const groupedLabTests = labTestOptions.reduce<
        Record<string, typeof labTestOptions>
    >((groups, option) => {
        const key = option.category || 'Other';
        groups[key] ??= [];
        groups[key].push(option);
        return groups;
    }, {});

    const labForm = useForm({
        test_ids: [] as string[],
        clinical_notes:
            consultation?.history_of_present_illness ??
            triage?.history_of_presenting_illness ??
            '',
        priority: labPriorities[0]?.value ?? 'routine',
        diagnosis_code: consultation?.primary_icd10_code ?? '',
        is_stat: false,
        redirect_to: redirectTo,
    });

    const imagingForm = useForm({
        modality: imagingModalities[0]?.value ?? 'xray',
        body_part: '',
        laterality: imagingLateralities[0]?.value ?? 'na',
        clinical_history:
            consultation?.history_of_present_illness ??
            triage?.history_of_presenting_illness ??
            '',
        indication: consultation?.primary_diagnosis ?? '',
        priority: imagingPriorities[0]?.value ?? 'routine',
        requires_contrast: false,
        contrast_allergy_status: '',
        pregnancy_status: pregnancyStatuses[0]?.value ?? 'unknown',
        redirect_to: redirectTo,
    });

    const prescriptionForm = useForm({
        primary_diagnosis: consultation?.primary_diagnosis ?? '',
        pharmacy_notes: '',
        is_discharge_medication: false,
        is_long_term: false,
        items: [createPrescriptionItem()],
        redirect_to: redirectTo,
    });

    const serviceForm = useForm({
        facility_service_id: '',
        redirect_to: redirectTo,
    });

    const toggleLabTest = (testId: string, checked: boolean) =>
        labForm.setData(
            'test_ids',
            checked
                ? [...labForm.data.test_ids, testId]
                : labForm.data.test_ids.filter((value) => value !== testId),
        );

    const updatePrescriptionItem = <K extends keyof PrescriptionDraftItem>(
        index: number,
        field: K,
        value: PrescriptionDraftItem[K],
    ) =>
        prescriptionForm.setData(
            'items',
            prescriptionForm.data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );

    const selectedDrugOptions = prescriptionForm.data.items.map((item) =>
        drugOptions.find((drug) => drug.id === item.inventory_item_id),
    );
    const selectedFacilityService = facilityServiceOptions.find(
        (option) => option.id === serviceForm.data.facility_service_id,
    );
    const pendingFacilityServiceIds = new Set(
        facilityServiceOrders
            .filter((order) => order.status === 'pending')
            .map((order) => order.facility_service_id),
    );
    const hasPendingSelectedFacilityService =
        serviceForm.data.facility_service_id !== '' &&
        pendingFacilityServiceIds.has(serviceForm.data.facility_service_id);

    const closeDialog = () => onOpenChange(false);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Visit Order Center</DialogTitle>
                    <DialogDescription>
                        Add lab tests, imaging, prescriptions, or facility
                        services from the visit and let the system link them to
                        the consultation automatically when one exists.
                    </DialogDescription>
                </DialogHeader>

                <Tabs
                    value={selectedTab}
                    onValueChange={(value) =>
                        setSelectedTab(value as OrderTabValue)
                    }
                    className="flex flex-col gap-4"
                >
                    <TabsList variant="line" className="w-full justify-start">
                        {(Object.keys(ORDER_TAB_LABELS) as OrderTabValue[]).map(
                            (tab) => (
                                <TabsTrigger key={tab} value={tab}>
                                    {ORDER_TAB_LABELS[tab]}
                                </TabsTrigger>
                            ),
                        )}
                    </TabsList>

                    <TabsContent value="lab" className="flex flex-col gap-4">
                        <form
                            className="flex flex-col gap-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                labForm.post(
                                    `/visits/${visit.id}/lab-requests`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            labForm.reset('test_ids');
                                            closeDialog();
                                        },
                                    },
                                );
                            }}
                        >
                            <div className="flex flex-col gap-3">
                                <div className="flex flex-col gap-1">
                                    <Label>Select Tests</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Choose one or more active laboratory
                                        tests for this patient.
                                    </p>
                                </div>
                                {Object.entries(groupedLabTests).map(
                                    ([category, tests]) => (
                                        <div
                                            key={category}
                                            className="rounded-lg border p-3"
                                        >
                                            <p className="mb-3 text-sm font-medium">
                                                {category}
                                            </p>
                                            <div className="grid gap-2 md:grid-cols-2">
                                                {tests.map((test) => (
                                                    <label
                                                        key={test.id}
                                                        className="flex items-start gap-3 rounded-md border px-3 py-2 text-sm"
                                                    >
                                                        <Checkbox
                                                            checked={labForm.data.test_ids.includes(
                                                                test.id,
                                                            )}
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                toggleLabTest(
                                                                    test.id,
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />
                                                        <span>
                                                            <span className="block font-medium">
                                                                {test.test_name}
                                                                {test.test_code
                                                                    ? ` (${test.test_code})`
                                                                    : ''}
                                                            </span>
                                                            <span className="block text-muted-foreground">
                                                                Quoted price:{' '}
                                                                {formatMoney(
                                                                    test.quoted_price ??
                                                                        test.base_price,
                                                                )}
                                                                {test.price_source ===
                                                                'insurance_package'
                                                                    ? ' (insurance package)'
                                                                    : ' (catalog)'}
                                                            </span>
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    ),
                                )}
                                <InputError message={labForm.errors.test_ids} />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label>Priority</Label>
                                    <Select
                                        value={labForm.data.priority}
                                        onValueChange={(value) =>
                                            labForm.setData('priority', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {labPriorities.map((priority) => (
                                                <SelectItem
                                                    key={priority.value}
                                                    value={priority.value}
                                                >
                                                    {priority.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="diagnosis_code">
                                        Diagnosis Code
                                    </Label>
                                    <Input
                                        id="diagnosis_code"
                                        value={labForm.data.diagnosis_code}
                                        onChange={(event) =>
                                            labForm.setData(
                                                'diagnosis_code',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={labForm.errors.diagnosis_code}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="lab_clinical_notes">
                                    Clinical Notes
                                </Label>
                                <Textarea
                                    id="lab_clinical_notes"
                                    rows={3}
                                    value={labForm.data.clinical_notes}
                                    onChange={(event) =>
                                        labForm.setData(
                                            'clinical_notes',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={labForm.errors.clinical_notes}
                                />
                            </div>

                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={labForm.data.is_stat}
                                    onCheckedChange={(checked) =>
                                        labForm.setData(
                                            'is_stat',
                                            checked === true,
                                        )
                                    }
                                />
                                Mark this request as STAT
                            </label>

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={labForm.processing}
                                >
                                    Request Lab Tests
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <TabsContent
                        value="prescriptions"
                        className="flex flex-col gap-4"
                    >
                        <form
                            className="flex flex-col gap-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                prescriptionForm.post(
                                    `/visits/${visit.id}/prescriptions`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            prescriptionForm.reset();
                                            prescriptionForm.setData('items', [
                                                createPrescriptionItem(),
                                            ]);
                                            closeDialog();
                                        },
                                    },
                                );
                            }}
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="primary_diagnosis">
                                        Primary Diagnosis
                                    </Label>
                                    <Input
                                        id="primary_diagnosis"
                                        value={
                                            prescriptionForm.data
                                                .primary_diagnosis
                                        }
                                        onChange={(event) =>
                                            prescriptionForm.setData(
                                                'primary_diagnosis',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            prescriptionForm.errors
                                                .primary_diagnosis
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="pharmacy_notes">
                                        Pharmacy Notes
                                    </Label>
                                    <Input
                                        id="pharmacy_notes"
                                        value={
                                            prescriptionForm.data.pharmacy_notes
                                        }
                                        onChange={(event) =>
                                            prescriptionForm.setData(
                                                'pharmacy_notes',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-3">
                                {prescriptionForm.data.items.map(
                                    (item, index) => (
                                        <div
                                            key={index}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="mb-4 flex items-center justify-between">
                                                <h3 className="font-medium">
                                                    Drug {index + 1}
                                                </h3>
                                                {prescriptionForm.data.items
                                                    .length > 1 ? (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            prescriptionForm.setData(
                                                                'items',
                                                                prescriptionForm.data.items.filter(
                                                                    (
                                                                        _,
                                                                        itemIndex,
                                                                    ) =>
                                                                        itemIndex !==
                                                                        index,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </Button>
                                                ) : null}
                                            </div>
                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label>Drug</Label>
                                                    <Select
                                                        value={
                                                            item.inventory_item_id
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'inventory_item_id',
                                                                value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select drug" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {drugOptions.map(
                                                                (drug) => (
                                                                    <SelectItem
                                                                        key={
                                                                            drug.id
                                                                        }
                                                                        value={
                                                                            drug.id
                                                                        }
                                                                    >
                                                                        {
                                                                            drug.generic_name
                                                                        }
                                                                        {drug.brand_name
                                                                            ? ` (${drug.brand_name})`
                                                                            : ''}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            prescriptionForm
                                                                .errors[
                                                                `items.${index}.inventory_item_id`
                                                            ]
                                                        }
                                                    />
                                                    {selectedDrugOptions[
                                                        index
                                                    ] ? (
                                                        <p className="text-xs text-muted-foreground">
                                                            Quoted price:{' '}
                                                            {formatMoney(
                                                                selectedDrugOptions[
                                                                    index
                                                                ]
                                                                    ?.quoted_price ??
                                                                    null,
                                                            )}
                                                        </p>
                                                    ) : null}
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Dosage</Label>
                                                    <Input
                                                        value={item.dosage}
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'dosage',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Frequency</Label>
                                                    <Input
                                                        value={item.frequency}
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'frequency',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Route</Label>
                                                    <Input
                                                        value={item.route}
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'route',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Duration Days</Label>
                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        max={365}
                                                        value={
                                                            item.duration_days
                                                        }
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'duration_days',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Quantity</Label>
                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        max={1000}
                                                        value={item.quantity}
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'quantity',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label>Instructions</Label>
                                                    <Textarea
                                                        rows={3}
                                                        value={
                                                            item.instructions
                                                        }
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'instructions',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="flex flex-col gap-3 rounded-lg border p-3">
                                                    <label className="flex items-center gap-3 text-sm">
                                                        <Checkbox
                                                            checked={
                                                                item.is_prn
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                updatePrescriptionItem(
                                                                    index,
                                                                    'is_prn',
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />
                                                        Prescribe as needed
                                                    </label>
                                                    <Input
                                                        placeholder="PRN reason"
                                                        value={item.prn_reason}
                                                        onChange={(event) =>
                                                            updatePrescriptionItem(
                                                                index,
                                                                'prn_reason',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <label className="flex items-center gap-3 text-sm">
                                                        <Checkbox
                                                            checked={
                                                                item.is_external_pharmacy
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                updatePrescriptionItem(
                                                                    index,
                                                                    'is_external_pharmacy',
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />
                                                        External pharmacy
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                            <div className="flex items-center justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        prescriptionForm.setData('items', [
                                            ...prescriptionForm.data.items,
                                            createPrescriptionItem(),
                                        ])
                                    }
                                >
                                    <Plus data-icon="inline-start" />
                                    Add Another Drug
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={prescriptionForm.processing}
                                >
                                    Save Prescription
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <TabsContent
                        value="imaging"
                        className="flex flex-col gap-4"
                    >
                        <form
                            className="flex flex-col gap-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                imagingForm.post(
                                    `/visits/${visit.id}/imaging-requests`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            imagingForm.reset();
                                            closeDialog();
                                        },
                                    },
                                );
                            }}
                        >
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label>Modality</Label>
                                    <Select
                                        value={imagingForm.data.modality}
                                        onValueChange={(value) =>
                                            imagingForm.setData(
                                                'modality',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {imagingModalities.map(
                                                (modality) => (
                                                    <SelectItem
                                                        key={modality.value}
                                                        value={modality.value}
                                                    >
                                                        {modality.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="imaging_body_part">
                                        Body Part
                                    </Label>
                                    <Input
                                        id="imaging_body_part"
                                        value={imagingForm.data.body_part}
                                        onChange={(event) =>
                                            imagingForm.setData(
                                                'body_part',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Laterality</Label>
                                    <Select
                                        value={imagingForm.data.laterality}
                                        onValueChange={(value) =>
                                            imagingForm.setData(
                                                'laterality',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {imagingLateralities.map(
                                                (laterality) => (
                                                    <SelectItem
                                                        key={laterality.value}
                                                        value={laterality.value}
                                                    >
                                                        {laterality.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="imaging_clinical_history">
                                        Clinical History
                                    </Label>
                                    <Textarea
                                        id="imaging_clinical_history"
                                        rows={3}
                                        value={
                                            imagingForm.data.clinical_history
                                        }
                                        onChange={(event) =>
                                            imagingForm.setData(
                                                'clinical_history',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="imaging_indication">
                                        Indication
                                    </Label>
                                    <Textarea
                                        id="imaging_indication"
                                        rows={3}
                                        value={imagingForm.data.indication}
                                        onChange={(event) =>
                                            imagingForm.setData(
                                                'indication',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label>Priority</Label>
                                    <Select
                                        value={imagingForm.data.priority}
                                        onValueChange={(value) =>
                                            imagingForm.setData(
                                                'priority',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {imagingPriorities.map(
                                                (priority) => (
                                                    <SelectItem
                                                        key={priority.value}
                                                        value={priority.value}
                                                    >
                                                        {priority.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Pregnancy Status</Label>
                                    <Select
                                        value={
                                            imagingForm.data.pregnancy_status
                                        }
                                        onValueChange={(value) =>
                                            imagingForm.setData(
                                                'pregnancy_status',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {pregnancyStatuses.map((status) => (
                                                <SelectItem
                                                    key={status.value}
                                                    value={status.value}
                                                >
                                                    {status.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contrast_allergy_status">
                                        Contrast Allergy Status
                                    </Label>
                                    <Input
                                        id="contrast_allergy_status"
                                        value={
                                            imagingForm.data
                                                .contrast_allergy_status
                                        }
                                        onChange={(event) =>
                                            imagingForm.setData(
                                                'contrast_allergy_status',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={imagingForm.data.requires_contrast}
                                    onCheckedChange={(checked) =>
                                        imagingForm.setData(
                                            'requires_contrast',
                                            checked === true,
                                        )
                                    }
                                />
                                This study requires contrast
                            </label>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={imagingForm.processing}
                                >
                                    Request Imaging
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <TabsContent
                        value="services"
                        className="flex flex-col gap-4"
                    >
                        {facilityServiceOptions.length === 0 ? (
                            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                No facility services are active in the catalog
                                yet.
                            </div>
                        ) : (
                            <form
                                className="flex flex-col gap-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    serviceForm.post(
                                        `/visits/${visit.id}/facility-service-orders`,
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                serviceForm.reset(
                                                    'facility_service_id',
                                                );
                                                closeDialog();
                                            },
                                        },
                                    );
                                }}
                            >
                                <div className="grid gap-4 lg:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Facility Service</Label>
                                        <Select
                                            value={
                                                serviceForm.data
                                                    .facility_service_id
                                            }
                                            onValueChange={(value) =>
                                                serviceForm.setData(
                                                    'facility_service_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select service" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {facilityServiceOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.id}
                                                            value={option.id}
                                                        >
                                                            {option.name}
                                                            {option.service_code
                                                                ? ` (${option.service_code})`
                                                                : ''}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                serviceForm.errors
                                                    .facility_service_id
                                            }
                                        />
                                    </div>
                                    <div className="rounded-lg border bg-muted/30 p-4">
                                        <p className="text-sm text-muted-foreground">
                                            Service preview
                                        </p>
                                        <p className="mt-1 font-medium">
                                            {selectedFacilityService?.name ??
                                                'Choose a service to preview its billing details.'}
                                        </p>
                                        {selectedFacilityService ? (
                                            <>
                                                <p className="mt-2 text-sm text-muted-foreground">
                                                    Quoted price:{' '}
                                                    {formatMoney(
                                                        selectedFacilityService.quoted_price ??
                                                            selectedFacilityService.selling_price,
                                                    )}
                                                </p>
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    <Badge variant="outline">
                                                        {labelize(
                                                            selectedFacilityService.category,
                                                        )}
                                                    </Badge>
                                                    <Badge variant="outline">
                                                        {selectedFacilityService.is_billable
                                                            ? 'Billable'
                                                            : 'Non-billable'}
                                                    </Badge>
                                                </div>
                                            </>
                                        ) : null}
                                        {hasPendingSelectedFacilityService ? (
                                            <p className="mt-2 text-sm text-amber-700">
                                                This service already has a
                                                pending order for the visit.
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={
                                            serviceForm.processing ||
                                            serviceForm.data
                                                .facility_service_id === '' ||
                                            hasPendingSelectedFacilityService
                                        }
                                    >
                                        Order Facility Service
                                    </Button>
                                </div>
                            </form>
                        )}
                    </TabsContent>
                </Tabs>
            </DialogContent>
        </Dialog>
    );
}
