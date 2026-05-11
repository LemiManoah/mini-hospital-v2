import DataUploadController from '@/actions/App/Http/Controllers/DataUploadController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import {
    type DataImportSummary,
    type DataUploadIndexPageProps,
} from '@/types/data-upload';
import { Head, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Download,
    LoaderCircle,
    Upload,
    XCircle,
} from 'lucide-react';
import {
    type ChangeEvent,
    type FormEvent,
    type ReactNode,
    useRef,
} from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administration',
        href: '/administration/general-settings',
    },
    { title: 'Data Upload', href: '/data-upload' },
];

export default function DataUploadIndex({
    importResult,
    importResultMode,
    hasErrorReport,
    queuedImportMessage,
    dataImports,
}: DataUploadIndexPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Upload" />

            <div className="m-4 max-w-5xl space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">Data Upload</h1>
                    <p className="text-sm text-muted-foreground">
                        Import records in bulk from a spreadsheet.
                    </p>
                </div>

                <UploadCard
                    title="Patient Import"
                    description="Download the template, fill in patient data, then upload it here."
                    instructionsTitle="How to import patients"
                    templateUrl={DataUploadController.patientTemplate.url()}
                    templateLabel="Download Patient Template"
                    importUrl={DataUploadController.importPatients.url()}
                    submitLabel="Preview Patients"
                    extraErrorKeys={['branch']}
                >
                    <li>Download the template file below.</li>
                    <li>
                        Fill in patient data following the example row. Do not
                        change the column headings.
                    </li>
                    <li>
                        Accepted values:
                        <ul className="mt-1 list-disc pl-4">
                            <li>
                                <strong>gender:</strong> male, female
                            </li>
                            <li>
                                <strong>date_of_birth:</strong> YYYY-MM-DD
                            </li>
                            <li>
                                <strong>marital_status:</strong> single,
                                married, divorced, widowed, separated
                            </li>
                            <li>
                                <strong>blood_group:</strong> A+, A-, B+, B-,
                                AB+, AB-, O+, O-, unknown
                            </li>
                            <li>
                                <strong>religion:</strong> christian, muslim,
                                hindu, buddhist, other, unknown
                            </li>
                            <li>
                                <strong>next_of_kin_relationship:</strong>{' '}
                                spouse, parent, child, sibling, other, unknown
                            </li>
                        </ul>
                    </li>
                    <li>Save as .csv or .xlsx and upload the file.</li>
                    <li>
                        Valid rows are previewed first. Confirm the preview to
                        queue the actual import.
                    </li>
                </UploadCard>

                <UploadCard
                    title="Drug Opening Stock Import"
                    description="Load medicines, batches, expiry dates, and opening stock quantities."
                    instructionsTitle="How to import drugs"
                    templateUrl={DataUploadController.drugTemplate.url()}
                    templateLabel="Download Drug Template"
                    importUrl={DataUploadController.importDrugs.url()}
                    submitLabel="Preview Drugs"
                    extraErrorKeys={['branch']}
                >
                    <li>Use this for medicines only.</li>
                    <li>
                        Required columns include generic_name, category,
                        strength, dosage_form, inventory_location,
                        quantity_on_hand, and unit_cost.
                    </li>
                    <li>
                        Accepted categories include analgesic, antibiotic,
                        antipyretic, anti_malarial, antihypertensive,
                        gastrointestinal, respiratory, and other.
                    </li>
                    <li>
                        Unit must match an existing unit name or symbol such as
                        tab, cap, ml, g, or sachet.
                    </li>
                    <li>
                        Expiring drugs require both batch_number and
                        expiry_date. The same drug can appear again when the
                        batch is different.
                    </li>
                </UploadCard>

                <UploadCard
                    title="Consumable Opening Stock Import"
                    description="Load consumables, batch references, and opening stock quantities."
                    instructionsTitle="How to import consumables"
                    templateUrl={DataUploadController.consumableTemplate.url()}
                    templateLabel="Download Consumable Template"
                    importUrl={DataUploadController.importConsumables.url()}
                    submitLabel="Preview Consumables"
                    extraErrorKeys={['branch']}
                >
                    <li>
                        Use this for consumables such as gloves and syringes.
                    </li>
                    <li>
                        Required columns include name, inventory_location,
                        quantity_on_hand, and unit_cost.
                    </li>
                    <li>
                        Unit must match an existing unit name or symbol such as
                        sachet, ml, g, or tab.
                    </li>
                    <li>
                        Leave selling price blank when the item is not directly
                        sold to patients.
                    </li>
                    <li>
                        Expiry dates are optional. If an expiry date is
                        supplied, batch_number is required too.
                    </li>
                </UploadCard>

                <UploadCard
                    title="Facility Service Import"
                    description="Load billable services such as consultations, procedures, ward fees, and nursing services."
                    instructionsTitle="How to import facility services"
                    templateUrl={DataUploadController.facilityServiceTemplate.url()}
                    templateLabel="Download Service Template"
                    importUrl={DataUploadController.importFacilityServices.url()}
                    submitLabel="Preview Services"
                    extraErrorKeys={['branch']}
                >
                    <li>Use this for the facility service catalog.</li>
                    <li>
                        Required columns are service_code, name, category,
                        is_billable, and is_active.
                    </li>
                    <li>
                        Supported categories include dressing, physiotherapy,
                        procedure, dental, nursing, transport, and other.
                    </li>
                    <li>
                        Billable services must have a selling_price. Cost price
                        can be left blank.
                    </li>
                    <li>
                        Existing service codes are skipped and reported; this
                        import does not update existing services.
                    </li>
                </UploadCard>

                {queuedImportMessage && (
                    <div className="rounded border border-blue-200 bg-blue-50 px-6 py-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                        {queuedImportMessage}
                    </div>
                )}

                {dataImports.length > 0 && (
                    <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                            <h2 className="text-lg font-semibold">
                                Recent Imports
                            </h2>
                        </div>

                        <div className="overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>File</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Preview</TableHead>
                                        <TableHead>Imported</TableHead>
                                        <TableHead>Skipped</TableHead>
                                        <TableHead>Updated</TableHead>
                                        <TableHead className="text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {dataImports.map((dataImport) => (
                                        <TableRow key={dataImport.id}>
                                            <TableCell className="font-medium">
                                                {formatImportType(
                                                    dataImport.importType,
                                                )}
                                            </TableCell>
                                            <TableCell className="max-w-64 truncate">
                                                {dataImport.sourceFilename}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={cn(
                                                        'capitalize',
                                                        statusBadgeClass(
                                                            dataImport.status,
                                                        ),
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
                                            <TableCell className="text-sm text-muted-foreground">
                                                {dataImport.completedAt ??
                                                    dataImport.failedAt ??
                                                    dataImport.startedAt ??
                                                    dataImport.createdAt ??
                                                    'Pending'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {dataImport.status ===
                                                    'previewed' &&
                                                dataImport.previewCount > 0 ? (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                confirmImportUrl(
                                                                    dataImport,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Confirm
                                                    </Button>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                )}

                {importResult && (
                    <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                            <h2 className="text-lg font-semibold">
                                {importResultMode === 'preview'
                                    ? 'Preview Results'
                                    : 'Import Results'}
                            </h2>
                        </div>

                        <div className="space-y-4 p-6">
                            <div className="flex gap-6">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                                    <span className="text-sm font-medium">
                                        {importResult.imported}{' '}
                                        {importResultMode === 'preview'
                                            ? 'valid rows'
                                            : 'imported'}
                                    </span>
                                </div>
                                {importResult.skipped > 0 && (
                                    <div className="flex items-center gap-2">
                                        <XCircle className="h-5 w-5 text-red-500" />
                                        <span className="text-sm font-medium">
                                            {importResult.skipped} skipped
                                        </span>
                                    </div>
                                )}
                            </div>

                            {importResult.errors.length > 0 && (
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <h3 className="text-sm font-medium text-red-600 dark:text-red-400">
                                            Rows with errors
                                        </h3>
                                        {hasErrorReport && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={DataUploadController.downloadErrorReport.url()}
                                                >
                                                    <Download className="mr-2 h-4 w-4" />
                                                    Download Error Report
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                    <div className="overflow-hidden rounded border border-zinc-200 dark:border-zinc-800">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-16">
                                                        Row
                                                    </TableHead>
                                                    <TableHead>Name</TableHead>
                                                    <TableHead>
                                                        Errors
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {importResult.errors.map(
                                                    (error) => (
                                                        <TableRow
                                                            key={error.row}
                                                        >
                                                            <TableCell className="font-mono text-sm">
                                                                {error.row}
                                                            </TableCell>
                                                            <TableCell>
                                                                {error.name}
                                                            </TableCell>
                                                            <TableCell>
                                                                <ul className="list-disc space-y-0.5 pl-4">
                                                                    {error.messages.map(
                                                                        (
                                                                            message,
                                                                            index,
                                                                        ) => (
                                                                            <li
                                                                                key={
                                                                                    index
                                                                                }
                                                                                className="text-sm text-red-600 dark:text-red-400"
                                                                            >
                                                                                {
                                                                                    message
                                                                                }
                                                                            </li>
                                                                        ),
                                                                    )}
                                                                </ul>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function confirmImportUrl(dataImport: DataImportSummary): string {
    if (dataImport.importType === 'patients') {
        return DataUploadController.confirmPatientImport.url({
            dataImport: dataImport.id,
        });
    }

    if (dataImport.importType === 'facility_services') {
        return DataUploadController.confirmFacilityServiceImport.url({
            dataImport: dataImport.id,
        });
    }

    return DataUploadController.confirmInventoryImport.url({
        dataImport: dataImport.id,
    });
}

function formatImportType(importType: string): string {
    return importType
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function statusBadgeClass(status: DataImportSummary['status']): string {
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

interface UploadCardProps {
    title: string;
    description: string;
    instructionsTitle: string;
    templateUrl: string;
    templateLabel: string;
    importUrl: string;
    submitLabel: string;
    children: ReactNode;
    extraErrorKeys?: string[];
}

function UploadCard({
    title,
    description,
    instructionsTitle,
    templateUrl,
    templateLabel,
    importUrl,
    submitLabel,
    children,
    extraErrorKeys = [],
}: UploadCardProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        setData('file', event.target.files?.[0] ?? null);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(importUrl, {
            forceFormData: true,
            onSuccess: () => {
                reset();

                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    return (
        <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                <h2 className="text-lg font-semibold">{title}</h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>

            <div className="space-y-6 p-6">
                <div className="rounded-md bg-blue-50 p-4 dark:bg-blue-950/30">
                    <h3 className="mb-2 text-sm font-medium text-blue-800 dark:text-blue-300">
                        {instructionsTitle}
                    </h3>
                    <ol className="list-decimal space-y-1 pl-4 text-sm text-blue-700 dark:text-blue-400">
                        {children}
                    </ol>
                </div>

                <div>
                    <Button variant="outline" asChild>
                        <a href={templateUrl} download>
                            <Download className="mr-2 h-4 w-4" />
                            {templateLabel}
                        </a>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    {extraErrorKeys.map((key) => (
                        <InputError
                            key={key}
                            message={
                                (errors as Partial<Record<string, string>>)[key]
                            }
                        />
                    ))}

                    <div className="grid gap-2">
                        <Label htmlFor={`${title}-file`}>
                            Upload File (CSV or Excel)
                        </Label>
                        <Input
                            id={`${title}-file`}
                            ref={fileInputRef}
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            onChange={handleFileChange}
                            className="cursor-pointer"
                        />
                        <InputError message={errors.file} />
                    </div>

                    <Button type="submit" disabled={processing || !data.file}>
                        {processing ? (
                            <>
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                Processing...
                            </>
                        ) : (
                            <>
                                <Upload className="mr-2 h-4 w-4" />
                                {submitLabel}
                            </>
                        )}
                    </Button>
                </form>
            </div>
        </div>
    );
}
