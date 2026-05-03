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
import { useRef } from 'react';

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
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData('file', e.target.files?.[0] ?? null);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(DataUploadController.importPatients.url(), {
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Upload" />

            <div className="m-4 max-w-5xl space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">Data Upload</h1>
                    <p className="text-sm text-muted-foreground">
                        Import records in bulk from a spreadsheet.
                    </p>
                </div>

                <div className="rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                        <h2 className="text-lg font-semibold">
                            Patient Import
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Download the template, fill in your patient data,
                            then upload it here.
                        </p>
                    </div>

                    <div className="space-y-6 p-6">
                        <div className="rounded-md bg-blue-50 p-4 dark:bg-blue-950/30">
                            <h3 className="mb-2 text-sm font-medium text-blue-800 dark:text-blue-300">
                                How to import patients
                            </h3>
                            <ol className="list-decimal space-y-1 pl-4 text-sm text-blue-700 dark:text-blue-400">
                                <li>Download the template file below.</li>
                                <li>
                                    Fill in patient data following the example
                                    row. Do not change the column headings.
                                </li>
                                <li>
                                    Accepted values:
                                    <ul className="mt-1 list-disc pl-4">
                                        <li>
                                            <strong>gender:</strong> male,
                                            female
                                        </li>
                                        <li>
                                            <strong>date_of_birth:</strong>{' '}
                                            YYYY-MM-DD (e.g. 1990-05-15)
                                        </li>
                                        <li>
                                            <strong>marital_status:</strong>{' '}
                                            single, married, divorced, widowed,
                                            separated
                                        </li>
                                        <li>
                                            <strong>blood_group:</strong> A+,
                                            A-, B+, B-, AB+, AB-, O+, O-,
                                            unknown
                                        </li>
                                        <li>
                                            <strong>religion:</strong>{' '}
                                            christian, muslim, hindu, buddhist,
                                            other, unknown
                                        </li>
                                        <li>
                                            <strong>
                                                next_of_kin_relationship:
                                            </strong>{' '}
                                            spouse, parent, child, sibling,
                                            other, unknown
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    Save as .csv or .xlsx and upload the file.
                                </li>
                                <li>
                                    Rows with errors are skipped — the rest are
                                    imported.
                                </li>
                            </ol>
                        </div>

                        <div>
                            <Button variant="outline" asChild>
                                <a
                                    href={DataUploadController.patientTemplate.url()}
                                    download
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Download Patient Template
                                </a>
                            </Button>
                        </div>

                        <form onSubmit={submit} className="space-y-4">
                            <InputError
                                message={
                                    (
                                        errors as Partial<
                                            Record<'branch' | 'file', string>
                                        >
                                    ).branch
                                }
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="file">
                                    Upload File (CSV or Excel)
                                </Label>
                                <Input
                                    id="file"
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    onChange={handleFileChange}
                                    className="cursor-pointer"
                                />
                                <InputError message={errors.file} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing || !data.file}
                            >
                                {processing ? (
                                    <>
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        Importing...
                                    </>
                                ) : (
                                    <>
                                        <Upload className="mr-2 h-4 w-4" />
                                        Import Patients
                                    </>
                                )}
                            </Button>
                        </form>
                    </div>
                </div>

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
                                                    <TableHead>Errors</TableHead>
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
                                                                            i,
                                                                        ) => (
                                                                            <li
                                                                                key={
                                                                                    i
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
