import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import {
    type BillableItemOption,
    type BillableItemType,
    type InsurancePackagePrice,
    type InsurancePackageShowPageProps,
} from '@/types/insurance-package';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const BILLABLE_TYPE_LABELS: Record<BillableItemType, string> = {
    service: 'Service',
    drug: 'Drug',
    test: 'Lab Test',
    imaging: 'Imaging',
    procedure: 'Procedure',
    bed_day: 'Bed Day',
    other: 'Other',
};

const SUPPORTED_TYPES: BillableItemType[] = ['service', 'drug', 'test'];

type FormData = {
    insurance_package_id: string;
    facility_branch_id: string;
    billable_type: BillableItemType | '';
    billable_id: string;
    price: string;
    effective_from: string;
    effective_to: string;
    status: 'active' | 'inactive';
};

export default function InsurancePackageShow({
    insurancePackage,
    prices,
    billableItems,
    branches,
}: InsurancePackageShowPageProps) {
    const { hasPermission } = usePermissions();
    const canManage = hasPermission('insurance_packages.update');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Insurance Packages', href: '/insurance-packages' },
        {
            title: insurancePackage.name,
            href: `/insurance-packages/${insurancePackage.id}`,
        },
    ];

    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingPrice, setEditingPrice] =
        useState<InsurancePackagePrice | null>(null);

    const form = useForm<FormData>({
        insurance_package_id: insurancePackage.id,
        facility_branch_id: '',
        billable_type: '',
        billable_id: '',
        price: '',
        effective_from: '',
        effective_to: '',
        status: 'active',
    });

    const itemOptions: BillableItemOption[] =
        form.data.billable_type && form.data.billable_type in billableItems
            ? billableItems[
                  form.data.billable_type as keyof typeof billableItems
              ]
            : [];

    function openCreate() {
        form.reset();
        form.setData('insurance_package_id', insurancePackage.id);
        setEditingPrice(null);
        setDialogOpen(true);
    }

    function openEdit(price: InsurancePackagePrice) {
        form.setData({
            insurance_package_id: insurancePackage.id,
            facility_branch_id: price.facility_branch_id,
            billable_type: price.billable_type,
            billable_id: price.billable_id,
            price: price.price,
            effective_from: price.effective_from ?? '',
            effective_to: price.effective_to ?? '',
            status: price.status,
        });
        setEditingPrice(price);
        setDialogOpen(true);
    }

    function closeDialog() {
        setDialogOpen(false);
        setEditingPrice(null);
        form.reset();
        form.setData('insurance_package_id', insurancePackage.id);
    }

    function handleBillableTypeChange(value: string) {
        form.setData((prev) => ({
            ...prev,
            billable_type: value as BillableItemType,
            billable_id: '',
        }));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (editingPrice) {
            form.patch(
                `/insurance-packages/${insurancePackage.id}/prices/${editingPrice.id}`,
                {
                    onSuccess: () => {
                        toast.success('Price updated successfully.');
                        closeDialog();
                    },
                },
            );
        } else {
            form.post(`/insurance-packages/${insurancePackage.id}/prices`, {
                onSuccess: () => {
                    toast.success('Price added successfully.');
                    closeDialog();
                },
            });
        }
    }

    // Reset billable_id when type changes during edit
    useEffect(() => {
        if (!editingPrice) {
            return;
        }
        if (form.data.billable_type !== editingPrice.billable_type) {
            form.setData('billable_id', '');
        }
    }, [form.data.billable_type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${insurancePackage.name} — Prices`} />

            {/* Header */}
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            asChild
                            className="h-8 px-2"
                        >
                            <Link href="/insurance-packages">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {insurancePackage.name}
                        </h2>
                        <span className="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {insurancePackage.status}
                        </span>
                    </div>
                    <p className="ml-10 text-sm text-zinc-500 dark:text-zinc-400">
                        {insurancePackage.insurance_company?.name ?? '—'}
                    </p>
                </div>

                {canManage ? (
                    <Button
                        onClick={openCreate}
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Add Price
                    </Button>
                ) : null}
            </div>

            {/* Prices table */}
            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Type
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Item
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Branch
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Price
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Effective From
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Effective To
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            {canManage ? (
                                <TableHead className="w-[120px] text-right text-xs font-semibold tracking-wider uppercase">
                                    Actions
                                </TableHead>
                            ) : null}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {prices.length > 0 ? (
                            prices.map((price) => (
                                <TableRow
                                    key={price.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="text-sm text-zinc-600 dark:text-zinc-300">
                                        {BILLABLE_TYPE_LABELS[
                                            price.billable_type
                                        ] ?? price.billable_type}
                                    </TableCell>
                                    <TableCell className="font-medium text-zinc-900 dark:text-zinc-100">
                                        {price.billable_name}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {price.branch?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="font-mono text-sm text-zinc-900 dark:text-zinc-100">
                                        {Number(price.price).toLocaleString(
                                            undefined,
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            },
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {price.effective_from ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {price.effective_to ?? 'No expiry'}
                                    </TableCell>
                                    <TableCell>
                                        <span className="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {price.status}
                                        </span>
                                    </TableCell>
                                    {canManage ? (
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(price)
                                                    }
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    Edit
                                                </Button>
                                                <DeleteConfirmationModal
                                                    title="Remove Price"
                                                    description={`Remove the price for "${price.billable_name}" from this package? This cannot be undone.`}
                                                    action={{
                                                        action: `/insurance-packages/${insurancePackage.id}/prices/${price.id}`,
                                                        method: 'delete',
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            'Price removed successfully.',
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="h-8 cursor-pointer px-3 text-xs shadow-sm"
                                                        >
                                                            Remove
                                                        </Button>
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    ) : null}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={canManage ? 8 : 7}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No prices configured for this package yet.
                                    {canManage ? (
                                        <>
                                            {' '}
                                            Click <strong>Add Price</strong> to
                                            get started.
                                        </>
                                    ) : null}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Add / Edit dialog */}
            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closeDialog();
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingPrice ? 'Edit Price' : 'Add Price'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <input
                            type="hidden"
                            name="insurance_package_id"
                            value={form.data.insurance_package_id}
                        />

                        {/* Branch */}
                        <div className="grid gap-2">
                            <Label
                                htmlFor="facility_branch_id"
                                className="text-sm font-semibold"
                            >
                                Branch
                            </Label>
                            <Select
                                value={form.data.facility_branch_id}
                                onValueChange={(v) =>
                                    form.setData('facility_branch_id', v)
                                }
                            >
                                <SelectTrigger id="facility_branch_id">
                                    <SelectValue placeholder="Select branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    {branches.map((b) => (
                                        <SelectItem
                                            key={b.value}
                                            value={b.value}
                                        >
                                            {b.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={form.errors.facility_branch_id}
                            />
                        </div>

                        {/* Billable type */}
                        <div className="grid gap-2">
                            <Label
                                htmlFor="billable_type"
                                className="text-sm font-semibold"
                            >
                                Item Type
                            </Label>
                            <Select
                                value={form.data.billable_type}
                                onValueChange={handleBillableTypeChange}
                            >
                                <SelectTrigger id="billable_type">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {SUPPORTED_TYPES.map((t) => (
                                        <SelectItem key={t} value={t}>
                                            {BILLABLE_TYPE_LABELS[t]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.billable_type} />
                        </div>

                        {/* Billable item */}
                        <div className="grid gap-2">
                            <Label
                                htmlFor="billable_id"
                                className="text-sm font-semibold"
                            >
                                Item
                            </Label>
                            <Select
                                value={form.data.billable_id}
                                onValueChange={(v) =>
                                    form.setData('billable_id', v)
                                }
                                disabled={
                                    !form.data.billable_type ||
                                    itemOptions.length === 0
                                }
                            >
                                <SelectTrigger id="billable_id">
                                    <SelectValue
                                        placeholder={
                                            !form.data.billable_type
                                                ? 'Select a type first'
                                                : 'Select item'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {itemOptions.map((opt) => (
                                        <SelectItem
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.billable_id} />
                        </div>

                        {/* Price */}
                        <div className="grid gap-2">
                            <Label
                                htmlFor="price"
                                className="text-sm font-semibold"
                            >
                                Negotiated Price
                            </Label>
                            <Input
                                id="price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.price}
                                onChange={(e) =>
                                    form.setData('price', e.target.value)
                                }
                                placeholder="0.00"
                            />
                            <InputError message={form.errors.price} />
                        </div>

                        {/* Effective dates */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="effective_from"
                                    className="text-sm font-semibold"
                                >
                                    Effective From
                                </Label>
                                <Input
                                    id="effective_from"
                                    type="date"
                                    value={form.data.effective_from}
                                    onChange={(e) =>
                                        form.setData(
                                            'effective_from',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.effective_from}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="effective_to"
                                    className="text-sm font-semibold"
                                >
                                    Effective To{' '}
                                    <span className="font-normal text-zinc-400">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="effective_to"
                                    type="date"
                                    value={form.data.effective_to}
                                    onChange={(e) =>
                                        form.setData(
                                            'effective_to',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.effective_to}
                                />
                            </div>
                        </div>

                        {/* Status */}
                        <div className="grid gap-2">
                            <Label
                                htmlFor="status"
                                className="text-sm font-semibold"
                            >
                                Status
                            </Label>
                            <Select
                                value={form.data.status}
                                onValueChange={(v) =>
                                    form.setData(
                                        'status',
                                        v as 'active' | 'inactive',
                                    )
                                }
                            >
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.status} />
                        </div>

                        <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={closeDialog}
                                disabled={form.processing}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                ) : null}
                                {editingPrice ? 'Save Changes' : 'Add Price'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
