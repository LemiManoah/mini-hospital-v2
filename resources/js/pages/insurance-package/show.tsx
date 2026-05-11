import InsurancePolicyImportController from '@/actions/App/Http/Controllers/InsurancePolicyImportController';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import {
    type BillableItemOption,
    type InsurancePackageShowPageProps,
    type InsurancePolicy,
    type InsurancePolicyItem,
    type InsurancePolicyType,
} from '@/types/insurance-package';
import {
    Head,
    type InertiaFormProps,
    Link,
    router,
    useForm,
} from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Download,
    LoaderCircle,
    Plus,
    Upload,
    XCircle,
} from 'lucide-react';
import {
    type ChangeEvent,
    type FormEvent,
    useMemo,
    useRef,
    useState,
} from 'react';
import { toast } from 'sonner';

const POLICY_TYPE_OPTIONS: { value: InsurancePolicyType; label: string }[] = [
    { value: 'pharmacy', label: 'Pharmacy' },
    { value: 'lab', label: 'Lab' },
    { value: 'services', label: 'Services' },
];

const POLICY_ITEM_TYPE: Record<
    InsurancePolicyType,
    'drug' | 'test' | 'service'
> = {
    pharmacy: 'drug',
    lab: 'test',
    services: 'service',
};

type PolicyFormData = {
    name: string;
    policy_type: InsurancePolicyType;
    effective_from: string;
    effective_to: string;
    status: 'active' | 'inactive';
    items: {
        item_id: string;
        price: string;
        effective_from: string;
        effective_to: string;
        status: 'active' | 'inactive';
    }[];
};

type PolicyItemFormData = {
    item_id: string;
    price: string;
    effective_from: string;
    effective_to: string;
    status: 'active' | 'inactive';
};

export default function InsurancePackageShow({
    insurancePackage,
    policies,
    activeBranch,
    billableItems,
    policyImports,
    importResult,
    importResultMode,
    queuedImportMessage,
    selectedPolicyId,
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

    const firstPolicyId = policies[0]?.id ?? 'new-policy';
    const [activePolicyId, setActivePolicyId] = useState(
        selectedPolicyId ?? firstPolicyId,
    );
    const activePolicy = policies.find(
        (policy) => policy.id === activePolicyId,
    );
    const [policyDialogOpen, setPolicyDialogOpen] = useState(false);
    const [editingPolicy, setEditingPolicy] = useState<InsurancePolicy | null>(
        null,
    );
    const [itemDialogOpen, setItemDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<InsurancePolicyItem | null>(
        null,
    );
    const [itemPolicy, setItemPolicy] = useState<InsurancePolicy | null>(null);
    const [includeInitialItem, setIncludeInitialItem] = useState(false);

    const policyForm = useForm<PolicyFormData>({
        name: '',
        policy_type: 'pharmacy',
        effective_from: '',
        effective_to: '',
        status: 'active',
        items: [
            {
                item_id: '',
                price: '',
                effective_from: '',
                effective_to: '',
                status: 'active',
            },
        ],
    });

    const itemForm = useForm<PolicyItemFormData>({
        item_id: '',
        price: '',
        effective_from: '',
        effective_to: '',
        status: 'active',
    });

    const initialItemOptions = useMemo(
        () => itemOptionsForPolicy(policyForm.data.policy_type, billableItems),
        [policyForm.data.policy_type, billableItems],
    );
    const activeItemOptions = itemPolicy
        ? itemOptionsForPolicy(itemPolicy.policyType, billableItems)
        : [];

    function openCreatePolicy() {
        policyForm.reset();
        policyForm.clearErrors();
        policyForm.setData({
            name: '',
            policy_type: 'pharmacy',
            effective_from: '',
            effective_to: '',
            status: 'active',
            items: [
                {
                    item_id: '',
                    price: '',
                    effective_from: '',
                    effective_to: '',
                    status: 'active',
                },
            ],
        });
        setIncludeInitialItem(false);
        setEditingPolicy(null);
        setPolicyDialogOpen(true);
    }

    function openEditPolicy(policy: InsurancePolicy) {
        policyForm.clearErrors();
        policyForm.setData({
            name: policy.name,
            policy_type: policy.policyType,
            effective_from: policy.effectiveFrom ?? '',
            effective_to: policy.effectiveTo ?? '',
            status: policy.status,
            items: [
                {
                    item_id: '',
                    price: '',
                    effective_from: '',
                    effective_to: '',
                    status: 'active',
                },
            ],
        });
        setIncludeInitialItem(false);
        setEditingPolicy(policy);
        setPolicyDialogOpen(true);
    }

    function closePolicyDialog() {
        setPolicyDialogOpen(false);
        setEditingPolicy(null);
        setIncludeInitialItem(false);
        policyForm.reset();
        policyForm.clearErrors();
    }

    function submitPolicy(event: FormEvent) {
        event.preventDefault();

        if (!activeBranch && !editingPolicy) {
            toast.error('Select an active branch before creating a policy.');

            return;
        }

        const submitOptions = {
            onSuccess: () => {
                toast.success(
                    editingPolicy
                        ? 'Policy updated successfully.'
                        : 'Policy created successfully.',
                );
                closePolicyDialog();
            },
        };

        if (editingPolicy) {
            policyForm.transform((data) => ({ ...data, items: [] }));
            policyForm.patch(
                `/insurance-packages/${insurancePackage.id}/policies/${editingPolicy.id}`,
                submitOptions,
            );

            return;
        }

        policyForm.transform((data) => ({
            ...data,
            items:
                includeInitialItem &&
                data.items[0]?.item_id &&
                data.items[0]?.price
                    ? data.items
                    : [],
        }));
        policyForm.post(
            `/insurance-packages/${insurancePackage.id}/policies`,
            submitOptions,
        );
    }

    function openCreateItem(policy: InsurancePolicy) {
        itemForm.reset();
        itemForm.clearErrors();
        itemForm.setData({
            item_id: '',
            price: '',
            effective_from: '',
            effective_to: '',
            status: 'active',
        });
        setEditingItem(null);
        setItemPolicy(policy);
        setItemDialogOpen(true);
    }

    function openEditItem(policy: InsurancePolicy, item: InsurancePolicyItem) {
        itemForm.clearErrors();
        itemForm.setData({
            item_id: item.itemId,
            price: item.price,
            effective_from: item.effectiveFrom ?? '',
            effective_to: item.effectiveTo ?? '',
            status: item.status,
        });
        setEditingItem(item);
        setItemPolicy(policy);
        setItemDialogOpen(true);
    }

    function closeItemDialog() {
        setItemDialogOpen(false);
        setEditingItem(null);
        setItemPolicy(null);
        itemForm.reset();
        itemForm.clearErrors();
    }

    function submitItem(event: FormEvent) {
        event.preventDefault();

        if (!itemPolicy) {
            return;
        }

        const submitOptions = {
            onSuccess: () => {
                toast.success(
                    editingItem
                        ? 'Policy item updated successfully.'
                        : 'Policy item added successfully.',
                );
                closeItemDialog();
            },
        };

        if (editingItem) {
            itemForm.patch(
                `/insurance-packages/${insurancePackage.id}/policies/${itemPolicy.id}/items/${editingItem.id}`,
                submitOptions,
            );

            return;
        }

        itemForm.post(
            `/insurance-packages/${insurancePackage.id}/policies/${itemPolicy.id}/items`,
            submitOptions,
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${insurancePackage.name} - Policies`} />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
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
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {insurancePackage.name}
                            </h2>
                            <Badge variant="secondary">
                                {insurancePackage.status}
                            </Badge>
                        </div>
                        <p className="ml-10 text-sm text-zinc-500 dark:text-zinc-400">
                            {insurancePackage.insurance_company?.name ?? '-'}
                        </p>
                    </div>

                    <div className="flex flex-col items-start gap-3 sm:items-end">
                        <div className="text-sm text-zinc-600 dark:text-zinc-300">
                            Active branch:{' '}
                            <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                {activeBranch
                                    ? `${activeBranch.name} (${activeBranch.branchCode})`
                                    : 'None selected'}
                            </span>
                        </div>
                        {canManage ? (
                            <Button
                                onClick={openCreatePolicy}
                                disabled={!activeBranch}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                New Policy
                            </Button>
                        ) : null}
                    </div>
                </div>

                {queuedImportMessage ? (
                    <div className="rounded border border-blue-200 bg-blue-50 px-6 py-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                        {queuedImportMessage}
                    </div>
                ) : null}

                {policies.length > 0 ? (
                    <Tabs
                        value={activePolicy?.id ?? firstPolicyId}
                        onValueChange={setActivePolicyId}
                        className="gap-4"
                    >
                        <TabsList className="w-full justify-start overflow-x-auto">
                            {policies.map((policy) => (
                                <TabsTrigger key={policy.id} value={policy.id}>
                                    {policy.name}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        {policies.map((policy) => (
                            <TabsContent
                                key={policy.id}
                                value={policy.id}
                                className="space-y-4"
                            >
                                <PolicyHeader
                                    policy={policy}
                                    canManage={canManage}
                                    insurancePackageId={insurancePackage.id}
                                    onEdit={() => openEditPolicy(policy)}
                                />

                                <PolicyImportPanel
                                    insurancePackageId={insurancePackage.id}
                                    policy={policy}
                                    canManage={canManage}
                                    activeBranchName={
                                        activeBranch?.name ?? null
                                    }
                                    imports={policyImports.filter(
                                        (dataImport) =>
                                            dataImport.policyId === policy.id,
                                    )}
                                    importResult={
                                        selectedPolicyId === policy.id
                                            ? importResult
                                            : null
                                    }
                                    importResultMode={importResultMode}
                                />

                                <PolicyItemsTable
                                    policy={policy}
                                    canManage={canManage}
                                    insurancePackageId={insurancePackage.id}
                                    onAdd={() => openCreateItem(policy)}
                                    onEdit={(item) =>
                                        openEditItem(policy, item)
                                    }
                                />
                            </TabsContent>
                        ))}
                    </Tabs>
                ) : (
                    <div className="rounded border border-zinc-200 bg-white px-6 py-12 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 className="text-lg font-semibold">
                            No policies yet
                        </h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a pharmacy, lab, or services policy for this
                            package and attach the items it covers.
                        </p>
                        {canManage ? (
                            <Button
                                className="mt-4"
                                onClick={openCreatePolicy}
                                disabled={!activeBranch}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                New Policy
                            </Button>
                        ) : null}
                    </div>
                )}
            </div>

            <Dialog
                open={policyDialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closePolicyDialog();
                    }
                }}
            >
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingPolicy ? 'Edit Policy' : 'Create Policy'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submitPolicy} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="policy_name">Policy Name</Label>
                            <Input
                                id="policy_name"
                                value={policyForm.data.name}
                                onChange={(event) =>
                                    policyForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={policyForm.errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="policy_type">Policy Type</Label>
                                <Select
                                    value={policyForm.data.policy_type}
                                    onValueChange={(value) => {
                                        policyForm.setData((data) => ({
                                            ...data,
                                            policy_type:
                                                value as InsurancePolicyType,
                                            items: data.items.map((item) => ({
                                                ...item,
                                                item_id: '',
                                            })),
                                        }));
                                    }}
                                    disabled={editingPolicy !== null}
                                >
                                    <SelectTrigger id="policy_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {POLICY_TYPE_OPTIONS.map((type) => (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={policyForm.errors.policy_type}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="policy_status">Status</Label>
                                <Select
                                    value={policyForm.data.status}
                                    onValueChange={(value) =>
                                        policyForm.setData(
                                            'status',
                                            value as 'active' | 'inactive',
                                        )
                                    }
                                >
                                    <SelectTrigger id="policy_status">
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
                                <InputError
                                    message={policyForm.errors.status}
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="policy_effective_from">
                                    Effective From
                                </Label>
                                <Input
                                    id="policy_effective_from"
                                    type="date"
                                    value={policyForm.data.effective_from}
                                    onChange={(event) =>
                                        policyForm.setData(
                                            'effective_from',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={policyForm.errors.effective_from}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="policy_effective_to">
                                    Effective To
                                </Label>
                                <Input
                                    id="policy_effective_to"
                                    type="date"
                                    value={policyForm.data.effective_to}
                                    onChange={(event) =>
                                        policyForm.setData(
                                            'effective_to',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={policyForm.errors.effective_to}
                                />
                            </div>
                        </div>

                        {!editingPolicy ? (
                            <div className="rounded border border-zinc-200 p-4 dark:border-zinc-800">
                                <label className="flex items-center gap-2 text-sm font-medium">
                                    <input
                                        type="checkbox"
                                        checked={includeInitialItem}
                                        onChange={(event) =>
                                            setIncludeInitialItem(
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Add a covered item now
                                </label>

                                {includeInitialItem ? (
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label>Item</Label>
                                            <Select
                                                value={
                                                    policyForm.data.items[0]
                                                        ?.item_id ?? ''
                                                }
                                                onValueChange={(value) =>
                                                    setInitialItemField(
                                                        policyForm,
                                                        'item_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select item" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {initialItemOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={nestedError(
                                                    policyForm.errors,
                                                    'items.0.item_id',
                                                )}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Price</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={
                                                    policyForm.data.items[0]
                                                        ?.price ?? ''
                                                }
                                                onChange={(event) =>
                                                    setInitialItemField(
                                                        policyForm,
                                                        'price',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={nestedError(
                                                    policyForm.errors,
                                                    'items.0.price',
                                                )}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Effective From</Label>
                                            <Input
                                                type="date"
                                                value={
                                                    policyForm.data.items[0]
                                                        ?.effective_from ?? ''
                                                }
                                                onChange={(event) =>
                                                    setInitialItemField(
                                                        policyForm,
                                                        'effective_from',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={nestedError(
                                                    policyForm.errors,
                                                    'items.0.effective_from',
                                                )}
                                            />
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}

                        <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={closePolicyDialog}
                                disabled={policyForm.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={policyForm.processing}
                            >
                                {policyForm.processing ? (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                ) : null}
                                {editingPolicy
                                    ? 'Save Changes'
                                    : 'Create Policy'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={itemDialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closeItemDialog();
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingItem
                                ? 'Edit Policy Item'
                                : 'Add Policy Item'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submitItem} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="item_id">Item</Label>
                            <Select
                                value={itemForm.data.item_id}
                                onValueChange={(value) =>
                                    itemForm.setData('item_id', value)
                                }
                                disabled={editingItem !== null}
                            >
                                <SelectTrigger id="item_id">
                                    <SelectValue placeholder="Select item" />
                                </SelectTrigger>
                                <SelectContent>
                                    {activeItemOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={itemForm.errors.item_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="item_price">Price</Label>
                            <Input
                                id="item_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={itemForm.data.price}
                                onChange={(event) =>
                                    itemForm.setData(
                                        'price',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={itemForm.errors.price} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="item_effective_from">
                                    Effective From
                                </Label>
                                <Input
                                    id="item_effective_from"
                                    type="date"
                                    value={itemForm.data.effective_from}
                                    onChange={(event) =>
                                        itemForm.setData(
                                            'effective_from',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={itemForm.errors.effective_from}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="item_effective_to">
                                    Effective To
                                </Label>
                                <Input
                                    id="item_effective_to"
                                    type="date"
                                    value={itemForm.data.effective_to}
                                    onChange={(event) =>
                                        itemForm.setData(
                                            'effective_to',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={itemForm.errors.effective_to}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="item_status">Status</Label>
                            <Select
                                value={itemForm.data.status}
                                onValueChange={(value) =>
                                    itemForm.setData(
                                        'status',
                                        value as 'active' | 'inactive',
                                    )
                                }
                            >
                                <SelectTrigger id="item_status">
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
                            <InputError message={itemForm.errors.status} />
                        </div>

                        <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={closeItemDialog}
                                disabled={itemForm.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={itemForm.processing}
                            >
                                {itemForm.processing ? (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                ) : null}
                                {editingItem ? 'Save Changes' : 'Add Item'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function PolicyHeader({
    policy,
    canManage,
    insurancePackageId,
    onEdit,
}: {
    policy: InsurancePolicy;
    canManage: boolean;
    insurancePackageId: string;
    onEdit: () => void;
}) {
    return (
        <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-semibold">{policy.name}</h3>
                        <Badge variant="secondary">
                            {policy.policyTypeLabel}
                        </Badge>
                        <Badge variant="outline">{policy.status}</Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {policy.branch?.name ?? 'Current branch'} ·{' '}
                        {policy.effectiveFrom ?? 'No start date'} to{' '}
                        {policy.effectiveTo ?? 'No end date'}
                    </p>
                </div>

                {canManage ? (
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={onEdit}>
                            Edit
                        </Button>
                        <DeleteConfirmationModal
                            title="Delete Policy"
                            description={`Delete "${policy.name}" and its attached prices? This cannot be undone.`}
                            action={{
                                action: `/insurance-packages/${insurancePackageId}/policies/${policy.id}`,
                                method: 'delete',
                            }}
                            onSuccess={() =>
                                toast.success('Policy deleted successfully.')
                            }
                            trigger={
                                <Button variant="destructive" size="sm">
                                    Delete
                                </Button>
                            }
                        />
                    </div>
                ) : null}
            </div>
        </div>
    );
}

function PolicyItemsTable({
    policy,
    canManage,
    insurancePackageId,
    onAdd,
    onEdit,
}: {
    policy: InsurancePolicy;
    canManage: boolean;
    insurancePackageId: string;
    onAdd: () => void;
    onEdit: (item: InsurancePolicyItem) => void;
}) {
    return (
        <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div className="flex flex-col gap-3 border-b border-zinc-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                <div>
                    <h3 className="text-lg font-semibold">Covered Items</h3>
                    <p className="text-sm text-muted-foreground">
                        Prices attached to this policy.
                    </p>
                </div>
                {canManage ? (
                    <Button onClick={onAdd}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Item
                    </Button>
                ) : null}
            </div>

            <div className="overflow-x-auto">
                <Table className="min-w-[850px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Item</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Effective From</TableHead>
                            <TableHead>Effective To</TableHead>
                            <TableHead>Status</TableHead>
                            {canManage ? (
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            ) : null}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {policy.items.length > 0 ? (
                            policy.items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">
                                        {item.itemName}
                                    </TableCell>
                                    <TableCell className="font-mono text-sm">
                                        {Number(item.price).toLocaleString(
                                            undefined,
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            },
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {item.effectiveFrom ?? '-'}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {item.effectiveTo ?? 'No end date'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {item.status}
                                        </Badge>
                                    </TableCell>
                                    {canManage ? (
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => onEdit(item)}
                                                >
                                                    Edit
                                                </Button>
                                                <DeleteConfirmationModal
                                                    title="Remove Item"
                                                    description={`Remove "${item.itemName}" from this policy? This cannot be undone.`}
                                                    action={{
                                                        action: `/insurance-packages/${insurancePackageId}/policies/${policy.id}/items/${item.id}`,
                                                        method: 'delete',
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            'Policy item removed successfully.',
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
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
                                    colSpan={canManage ? 6 : 5}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No items attached to this policy yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}

function PolicyImportPanel({
    insurancePackageId,
    policy,
    canManage,
    activeBranchName,
    imports,
    importResult,
    importResultMode,
}: {
    insurancePackageId: string;
    policy: InsurancePolicy;
    canManage: boolean;
    activeBranchName: string | null;
    imports: InsurancePackageShowPageProps['policyImports'];
    importResult: InsurancePackageShowPageProps['importResult'];
    importResultMode: InsurancePackageShowPageProps['importResultMode'];
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const uploadForm = useForm<{ file: File | null }>({ file: null });
    const routeArgs = {
        insurance_package: insurancePackageId,
        insurance_policy: policy.id,
    };
    const templateUrl = InsurancePolicyImportController.template.url(routeArgs);
    const importUrl = InsurancePolicyImportController.import.url(routeArgs);

    function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
        uploadForm.setData('file', event.target.files?.[0] ?? null);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        uploadForm.post(importUrl, {
            forceFormData: true,
            onSuccess: () => {
                uploadForm.reset();
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    }

    return (
        <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div className="grid gap-4 border-b border-zinc-200 px-4 py-4 lg:grid-cols-[1fr_auto] dark:border-zinc-800">
                <div>
                    <h3 className="text-lg font-semibold">Import Prices</h3>
                    <p className="text-sm text-muted-foreground">
                        Upload prices for {policy.name}.
                    </p>
                    <p className="mt-1 text-xs text-zinc-500">
                        Imports apply to{' '}
                        <span className="font-semibold">
                            {activeBranchName ?? 'the active branch'}
                        </span>
                        .
                    </p>
                </div>

                {canManage ? (
                    <form
                        onSubmit={handleSubmit}
                        className="grid gap-3 sm:grid-cols-[220px_auto_auto]"
                    >
                        <Input
                            ref={fileInput}
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            onChange={handleFileChange}
                            disabled={
                                !activeBranchName || uploadForm.processing
                            }
                        />
                        <Button variant="outline" asChild>
                            <a href={templateUrl}>
                                <Download className="mr-2 h-4 w-4" />
                                Template
                            </a>
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                !activeBranchName ||
                                !uploadForm.data.file ||
                                uploadForm.processing
                            }
                        >
                            {uploadForm.processing ? (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <Upload className="mr-2 h-4 w-4" />
                            )}
                            Preview
                        </Button>
                        <InputError
                            message={uploadForm.errors.file}
                            className="sm:col-span-3"
                        />
                    </form>
                ) : null}
            </div>

            {importResult ? (
                <div
                    className={cn(
                        'm-4 rounded border px-4 py-3 text-sm',
                        importResult.errors.length > 0
                            ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300'
                            : 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300',
                    )}
                >
                    <div className="flex items-center gap-2 font-medium">
                        {importResult.errors.length > 0 ? (
                            <XCircle className="h-4 w-4" />
                        ) : (
                            <CheckCircle2 className="h-4 w-4" />
                        )}
                        {importResultMode === 'preview'
                            ? 'Preview complete'
                            : 'Import result'}
                    </div>
                    <p className="mt-1">
                        Valid rows: {importResult.imported}. Skipped rows:{' '}
                        {importResult.skipped}.
                    </p>
                    {importResult.errors.length > 0 ? (
                        <ul className="mt-2 list-disc space-y-1 pl-4">
                            {importResult.errors.slice(0, 5).map((error) => (
                                <li key={`${error.row}-${error.name}`}>
                                    Row {error.row}, {error.name}:{' '}
                                    {error.messages.join('; ')}
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </div>
            ) : null}

            {imports.length > 0 ? (
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>File</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Preview</TableHead>
                                <TableHead>Imported</TableHead>
                                <TableHead>Skipped</TableHead>
                                <TableHead>Updated</TableHead>
                                {canManage ? (
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                ) : null}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {imports.map((dataImport) => (
                                <TableRow key={dataImport.id}>
                                    <TableCell className="font-medium">
                                        {dataImport.sourceFilename}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant="outline"
                                            className={statusBadgeClass(
                                                dataImport.status,
                                            )}
                                        >
                                            {dataImport.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {dataImport.previewCount}
                                    </TableCell>
                                    <TableCell>
                                        {dataImport.importedCount}
                                    </TableCell>
                                    <TableCell>
                                        {dataImport.skippedCount}
                                    </TableCell>
                                    <TableCell>
                                        {dataImport.completedAt ??
                                            dataImport.failedAt ??
                                            dataImport.startedAt ??
                                            dataImport.createdAt}
                                    </TableCell>
                                    {canManage ? (
                                        <TableCell className="text-right">
                                            {dataImport.status ===
                                            'previewed' ? (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            InsurancePolicyImportController.confirm.url(
                                                                dataImport.id,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    Confirm
                                                </Button>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    -
                                                </span>
                                            )}
                                        </TableCell>
                                    ) : null}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            ) : null}
        </div>
    );
}

function itemOptionsForPolicy(
    policyType: InsurancePolicyType,
    billableItems: InsurancePackageShowPageProps['billableItems'],
): BillableItemOption[] {
    return billableItems[POLICY_ITEM_TYPE[policyType]] ?? [];
}

function setInitialItemField(
    form: InertiaFormProps<PolicyFormData>,
    key: keyof PolicyFormData['items'][number],
    value: string,
) {
    form.setData((data) => ({
        ...data,
        items: [
            {
                ...(data.items[0] ?? {
                    item_id: '',
                    price: '',
                    effective_from: '',
                    effective_to: '',
                    status: 'active',
                }),
                [key]: value,
            },
        ],
    }));
}

function nestedError(
    errors: InertiaFormProps<PolicyFormData>['errors'],
    key: string,
): string | undefined {
    return (errors as Record<string, string | undefined>)[key];
}

function statusBadgeClass(status: string): string {
    if (status === 'completed') {
        return 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300';
    }

    if (status === 'failed') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300';
    }

    if (status === 'queued' || status === 'processing') {
        return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300';
    }

    if (status === 'previewed') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';
    }

    return 'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300';
}
