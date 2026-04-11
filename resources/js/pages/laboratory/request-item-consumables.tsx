import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type LaboratoryConsumableOption,
    type LaboratoryRequestItemConsumablesPageProps,
} from '@/types/laboratory';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const formatMoney = (amount: number | null | undefined): string =>
    new Intl.NumberFormat('en-UG', {
        style: 'currency',
        currency: 'UGX',
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const formatDateTime = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleString() : 'Not yet recorded';

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const itemTypeLabel = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Other';

const actorName = (
    actor?: { first_name: string; last_name: string } | null,
): string =>
    actor ? `${actor.first_name} ${actor.last_name}` : 'Not recorded';

const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed') return 'secondary';
    if (workflowStage === 'cancelled' || workflowStage === 'rejected') {
        return 'destructive';
    }

    return 'outline';
};

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-muted-foreground">{label}</p>
            <p className="font-medium">{value}</p>
        </div>
    );
}

export default function LaboratoryRequestItemConsumablesPage({
    labRequestItem,
    consumableOptions,
}: LaboratoryRequestItemConsumablesPageProps) {
    const patient = labRequestItem.request?.visit?.patient ?? null;
    const variance =
        (labRequestItem.price ?? 0) - (labRequestItem.actual_cost ?? 0);
    const pageTitle = 'Consumables';
    const isResultReleased = labRequestItem.approved_at !== null;
    const queueLabel = isResultReleased ? 'View Results' : 'Enter Results';
    const queueHref = isResultReleased
        ? '/laboratory/view-results'
        : '/laboratory/enter-results';
    const [selectedConsumableId, setSelectedConsumableId] = useState('');
    const groupedConsumableOptions = consumableOptions.reduce<
        Record<string, LaboratoryConsumableOption[]>
    >((groups, option) => {
        const key = itemTypeLabel(option.item_type);
        groups[key] = [...(groups[key] ?? []), option];

        return groups;
    }, {});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        { title: queueLabel, href: queueHref },
        {
            title: pageTitle,
            href: `/laboratory/request-items/${labRequestItem.id}/consumables`,
        },
    ];

    const consumableForm = useForm({
        inventory_item_id: '',
        consumable_name: '',
        unit_label: '',
        quantity: '1',
        unit_cost: '0',
        used_at: '',
        notes: '',
    });

    const applyConsumableDefaults = (consumableId: string) => {
        setSelectedConsumableId(consumableId);

        const selectedConsumable = consumableOptions.find(
            (option: LaboratoryConsumableOption) => option.id === consumableId,
        );

        if (!selectedConsumable) {
            return;
        }

        consumableForm.setData('inventory_item_id', selectedConsumable.id);
        consumableForm.setData('consumable_name', selectedConsumable.name);
        consumableForm.setData(
            'unit_label',
            selectedConsumable.unit_label ?? '',
        );

        if (selectedConsumable.default_unit_cost !== null) {
            consumableForm.setData(
                'unit_cost',
                `${selectedConsumable.default_unit_cost}`,
            );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${pageTitle} ${labRequestItem.test?.test_name ?? ''}`}
            />

            <div className="m-4 flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="flex flex-col gap-2">
                                <p className="text-sm font-medium text-muted-foreground">
                                    {pageTitle}
                                </p>
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle>
                                        {labRequestItem.test?.test_name ??
                                            'Lab test'}
                                    </CardTitle>
                                    <Badge
                                        variant={workflowVariant(
                                            labRequestItem.workflow_stage,
                                        )}
                                    >
                                        {labelize(
                                            labRequestItem.workflow_stage,
                                        )}
                                    </Badge>
                                    <Badge variant="outline">
                                        {labelize(
                                            labRequestItem.request?.priority,
                                        )}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {patient
                                        ? `${patient.first_name} ${patient.last_name}`
                                        : 'Unknown patient'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/laboratory/request-items/${labRequestItem.id}`}
                                    >
                                        Result Correction
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={queueHref}>
                                        Back to {queueLabel}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Record Consumables</CardTitle>
                                <CardDescription>
                                    Pick a consumable from inventory to prefill
                                    its name, unit, and unit cost. You can still
                                    edit the values before saving.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        consumableForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/consumables`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="grid gap-4 rounded-lg border p-4 md:grid-cols-2"
                                >
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="consumable_picker">
                                            Inventory Consumable
                                        </Label>
                                        <Select
                                            value={selectedConsumableId}
                                            onValueChange={
                                                applyConsumableDefaults
                                            }
                                            disabled={
                                                consumableForm.processing ||
                                                consumableOptions.length === 0
                                            }
                                        >
                                            <SelectTrigger id="consumable_picker">
                                                <SelectValue
                                                    placeholder={
                                                        consumableOptions.length
                                                            ? 'Choose a consumable to prefill this usage entry'
                                                            : 'No active consumables are configured in inventory yet'
                                                    }
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(
                                                    groupedConsumableOptions,
                                                ).map(([group, options]) => (
                                                    <SelectGroup key={group}>
                                                        <div className="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                                                            {group}
                                                        </div>
                                                        {options.map(
                                                            (option) => (
                                                            <SelectItem
                                                                key={option.id}
                                                                value={
                                                                    option.id
                                                                }
                                                            >
                                                                {option.label}
                                                                {option.unit_label
                                                                    ? ` (${option.unit_label})`
                                                                    : ''}
                                                            </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            Inventory defaults help you start
                                            faster, but the saved usage entry
                                            will use whatever name, unit, and
                                            cost you confirm below. The dropdown
                                            shows current lab-store quantity.
                                        </p>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="consumable_name">
                                            Consumable Name
                                        </Label>
                                        <Input
                                            id="consumable_name"
                                            placeholder="e.g. EDTA Tube"
                                            value={
                                                consumableForm.data
                                                    .consumable_name
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'consumable_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors
                                                    .consumable_name
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_label">Unit</Label>
                                        <Input
                                            id="unit_label"
                                            placeholder="e.g. pcs"
                                            value={
                                                consumableForm.data.unit_label
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'unit_label',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.unit_label
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity">
                                            Quantity
                                        </Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="1"
                                            value={consumableForm.data.quantity}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'quantity',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.quantity
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_cost">
                                            Unit Cost
                                        </Label>
                                        <Input
                                            id="unit_cost"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            value={
                                                consumableForm.data.unit_cost
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'unit_cost',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.unit_cost
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Line cost preview:{' '}
                                            {formatMoney(
                                                Number(
                                                    consumableForm.data
                                                        .quantity || 0,
                                                ) *
                                                    Number(
                                                        consumableForm.data
                                                            .unit_cost || 0,
                                                    ),
                                            )}
                                        </p>
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="used_at">Used At</Label>
                                        <Input
                                            id="used_at"
                                            type="datetime-local"
                                            value={consumableForm.data.used_at}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'used_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.used_at
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            rows={4}
                                            placeholder="Optional note about why this consumable was used or adjusted."
                                            value={consumableForm.data.notes}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.notes
                                            }
                                        />
                                    </div>
                                    <div className="flex justify-end md:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={consumableForm.processing}
                                        >
                                            Add Consumable Usage
                                        </Button>
                                    </div>
                                </form>

                                {(labRequestItem.consumables ?? []).length ? (
                                    <div className="flex flex-col gap-3">
                                        {labRequestItem.consumables?.map(
                                            (usage) => (
                                                <div
                                                    key={usage.id}
                                                    className="rounded-lg border p-4"
                                                >
                                                    <div className="flex flex-col gap-3 lg:flex-row lg:justify-between">
                                                        <div className="flex flex-col gap-1">
                                                            <p className="font-medium">
                                                                {
                                                                    usage.consumable_name
                                                                }
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {usage.quantity}{' '}
                                                                {usage.unit_label ??
                                                                    ''}{' '}
                                                                |{' '}
                                                                {formatMoney(
                                                                    usage.line_cost,
                                                                )}
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {actorName(
                                                                    usage.recordedBy,
                                                                )}{' '}
                                                                |{' '}
                                                                {formatDateTime(
                                                                    usage.used_at,
                                                                )}
                                                            </p>
                                                            {usage.notes ? (
                                                                <p className="text-sm">
                                                                    {
                                                                        usage.notes
                                                                    }
                                                                </p>
                                                            ) : null}
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.delete(
                                                                    `/laboratory/request-items/${labRequestItem.id}/consumables/${usage.id}`,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </Button>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        No consumables recorded yet.
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cost Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3 text-sm">
                                <SummaryRow
                                    label="Billed Price"
                                    value={formatMoney(labRequestItem.price)}
                                />
                                <SummaryRow
                                    label="Actual Cost"
                                    value={formatMoney(
                                        labRequestItem.actual_cost,
                                    )}
                                />
                                <SummaryRow
                                    label="Variance"
                                    value={formatMoney(variance)}
                                />
                                <SummaryRow
                                    label="Costed At"
                                    value={formatDateTime(
                                        labRequestItem.costed_at,
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
