import DataUploadController from '@/actions/App/Http/Controllers/DataUploadController';
import InputError from '@/components/input-error';
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
import { type BreadcrumbItem } from '@/types';
import { type DataUploadIndexPageProps } from '@/types/data-upload';
import { Head, useForm } from '@inertiajs/react';
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
                    submitLabel="Import Patients"
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
                        Rows with errors are skipped; the rest are imported.
                    </li>
                </UploadCard>

                <UploadCard
                    title="Drug Catalog Import"
                    description="Load pharmacy drug catalog items separately from stock quantities."
                    instructionsTitle="How to import drugs"
                    templateUrl={DataUploadController.drugTemplate.url()}
                    templateLabel="Download Drug Template"
                    importUrl={DataUploadController.importDrugs.url()}
                    submitLabel="Import Drugs"
                >
                    <li>Use this for medicines only.</li>
                    <li>
                        Required columns: generic_name, category, strength, and
                        dosage_form.
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
                        The import creates catalog items only. It does not
                        create stock balances.
                    </li>
                </UploadCard>

                <UploadCard
                    title="Consumable Catalog Import"
                    description="Load disposable and consumable inventory catalog items without mixing them with drugs."
                    instructionsTitle="How to import consumables"
                    templateUrl={DataUploadController.consumableTemplate.url()}
                    templateLabel="Download Consumable Template"
                    importUrl={DataUploadController.importConsumables.url()}
                    submitLabel="Import Consumables"
                >
                    <li>
                        Use this for consumables such as gloves and syringes.
                    </li>
                    <li>Required column: name.</li>
                    <li>
                        Unit must match an existing unit name or symbol such as
                        sachet, ml, g, or tab.
                    </li>
                    <li>
                        Leave selling price blank when the item is not directly
                        sold to patients.
                    </li>
                    <li>
                        The import creates catalog items only. Opening balances
                        should be handled through stock receiving or stock
                        setup.
                    </li>
                </UploadCard>

                {importResult && (
                    <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                            <h2 className="text-lg font-semibold">
                                Import Results
                            </h2>
                        </div>

                        <div className="space-y-4 p-6">
                            <div className="flex gap-6">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                                    <span className="text-sm font-medium">
                                        {importResult.imported} imported
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
                                    <h3 className="text-sm font-medium text-red-600 dark:text-red-400">
                                        Rows with errors
                                    </h3>
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
                                                                            msg,
                                                                            index,
                                                                        ) => (
                                                                            <li
                                                                                key={
                                                                                    index
                                                                                }
                                                                                className="text-sm text-red-600 dark:text-red-400"
                                                                            >
                                                                                {
                                                                                    msg
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
                                Importing...
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
