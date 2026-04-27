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
import { index as reportsIndex } from '@/routes/reports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    Download,
    Eye,
    FileSpreadsheet,
    FileText,
    SlidersHorizontal,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/reports' }];

type FilterOption = {
    value: string;
    label: string;
};

type FilterDefinition = {
    name: 'date' | 'location_id' | 'doctor_id';
    label: string;
    type: 'date' | 'select';
    placeholder?: string;
    options?: FilterOption[];
};

type ReportDefinition = {
    key: string;
    label: string;
    description: string;
    filters: FilterDefinition[];
};

type SummaryCard = {
    label: string;
    value: string;
    tone: 'primary' | 'success' | 'warning' | 'danger' | 'muted';
};

type PreviewColumn = {
    key: string;
    label: string;
    align: 'left' | 'right';
};

type PreviewRow = Record<string, string | number | null>;

type PreviewData = {
    title: string;
    description: string;
    summary: SummaryCard[];
    columns: PreviewColumn[];
    rows: PreviewRow[];
    empty_message: string;
    pdf_url: string;
    csv_url: string;
    legacy_url: string;
};

type Props = {
    reports: ReportDefinition[];
    selectedReport: string;
    filters: {
        date: string | null;
        location_id: string | null;
        doctor_id: string | null;
    };
    preview: PreviewData;
};

const toneClasses: Record<SummaryCard['tone'], string> = {
    primary: 'border-blue-200 bg-blue-50 text-blue-900',
    success: 'border-green-200 bg-green-50 text-green-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    danger: 'border-red-200 bg-red-50 text-red-900',
    muted: 'border-slate-200 bg-slate-50 text-slate-900',
};

export default function ReportsIndex({
    reports,
    selectedReport,
    filters,
    preview,
}: Props) {
    const [selectedKey, setSelectedKey] = useState(selectedReport);
    const [date, setDate] = useState(
        filters.date ?? new Date().toISOString().slice(0, 10),
    );
    const [locationId, setLocationId] = useState(filters.location_id ?? 'all');
    const [doctorId, setDoctorId] = useState(filters.doctor_id ?? 'all');

    useEffect(() => {
        setSelectedKey(selectedReport);
        setDate(filters.date ?? new Date().toISOString().slice(0, 10));
        setLocationId(filters.location_id ?? 'all');
        setDoctorId(filters.doctor_id ?? 'all');
    }, [filters.date, filters.doctor_id, filters.location_id, selectedReport]);

    const selectedDefinition = useMemo(
        () =>
            reports.find((report) => report.key === selectedKey) ?? reports[0],
        [reports, selectedKey],
    );

    function queryForSelectedReport(
        report: ReportDefinition,
    ): Record<string, string> {
        const query: Record<string, string> = { report: report.key };

        for (const filter of report.filters) {
            if (filter.name === 'date' && date !== '') {
                query.date = date;
            }

            if (filter.name === 'location_id' && locationId !== 'all') {
                query.location_id = locationId;
            }

            if (filter.name === 'doctor_id' && doctorId !== 'all') {
                query.doctor_id = doctorId;
            }
        }

        return query;
    }

    function generatePreview(): void {
        router.get(
            reportsIndex.url({
                query: queryForSelectedReport(selectedDefinition),
            }),
        );
    }

    function onReportChange(nextKey: string): void {
        const nextDefinition = reports.find((report) => report.key === nextKey);

        setSelectedKey(nextKey);
        setLocationId('all');
        setDoctorId('all');

        if (nextDefinition) {
            router.get(
                reportsIndex.url({
                    query: {
                        report: nextDefinition.key,
                        ...(nextDefinition.filters.some(
                            (filter) => filter.name === 'date',
                        )
                            ? { date }
                            : {}),
                    },
                }),
                {},
                { preserveScroll: true },
            );
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="space-y-6 p-6">
                <div className="space-y-2">
                    <div className="flex items-center gap-2">
                        <SlidersHorizontal className="h-6 w-6 text-blue-600" />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Report Generator
                        </h1>
                    </div>
                    <p className="max-w-3xl text-sm text-muted-foreground">
                        Choose a report, apply the relevant filters, preview the
                        output, and export it from one place. The individual
                        report pages still exist, but this is now the main
                        workspace for generating reports.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <Card className="border-slate-200">
                        <CardHeader>
                            <CardTitle>Configure Report</CardTitle>
                            <CardDescription>
                                Select the report type and adjust only the
                                filters that apply to it.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="report-type">Report Type</Label>
                                <Select
                                    value={selectedKey}
                                    onValueChange={onReportChange}
                                >
                                    <SelectTrigger
                                        id="report-type"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select report" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reports.map((report) => (
                                            <SelectItem
                                                key={report.key}
                                                value={report.key}
                                            >
                                                {report.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                                <p className="text-sm font-medium text-slate-900">
                                    {selectedDefinition.label}
                                </p>
                                <p className="mt-1 text-xs leading-5 text-slate-600">
                                    {selectedDefinition.description}
                                </p>
                            </div>

                            <div className="space-y-4">
                                {selectedDefinition.filters.map((filter) => (
                                    <div
                                        key={filter.name}
                                        className="space-y-2"
                                    >
                                        <Label htmlFor={filter.name}>
                                            {filter.label}
                                        </Label>

                                        {filter.type === 'date' ? (
                                            <Input
                                                id={filter.name}
                                                type="date"
                                                value={date}
                                                onChange={(event) =>
                                                    setDate(event.target.value)
                                                }
                                            />
                                        ) : (
                                            <Select
                                                value={
                                                    filter.name ===
                                                    'location_id'
                                                        ? locationId
                                                        : doctorId
                                                }
                                                onValueChange={(value) => {
                                                    if (
                                                        filter.name ===
                                                        'location_id'
                                                    ) {
                                                        setLocationId(value);
                                                    }

                                                    if (
                                                        filter.name ===
                                                        'doctor_id'
                                                    ) {
                                                        setDoctorId(value);
                                                    }
                                                }}
                                            >
                                                <SelectTrigger
                                                    id={filter.name}
                                                    className="w-full"
                                                >
                                                    <SelectValue
                                                        placeholder={
                                                            filter.placeholder
                                                        }
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        {filter.placeholder ??
                                                            'All'}
                                                    </SelectItem>
                                                    {filter.options?.map(
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
                                        )}
                                    </div>
                                ))}
                            </div>

                            <Button
                                onClick={generatePreview}
                                className="w-full gap-2"
                            >
                                <Eye className="h-4 w-4" />
                                Generate Preview
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="border-slate-200">
                            <CardHeader className="gap-4">
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="space-y-1">
                                        <CardTitle>{preview.title}</CardTitle>
                                        <CardDescription>
                                            {preview.description}
                                        </CardDescription>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="gap-2"
                                        >
                                            <a href={preview.csv_url}>
                                                <FileSpreadsheet className="h-4 w-4" />
                                                Export CSV
                                            </a>
                                        </Button>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="gap-2"
                                        >
                                            <a href={preview.pdf_url}>
                                                <Download className="h-4 w-4" />
                                                Download PDF
                                            </a>
                                        </Button>
                                        <Button asChild className="gap-2">
                                            <Link href={preview.legacy_url}>
                                                <FileText className="h-4 w-4" />
                                                Open Dedicated Page
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                        </Card>

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {preview.summary.map((item) => (
                                <Card
                                    key={item.label}
                                    className={toneClasses[item.tone]}
                                >
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-xs font-medium tracking-wide uppercase">
                                            {item.label}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-2xl font-bold">
                                            {item.value}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Card className="border-slate-200">
                            <CardHeader>
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <CardTitle>Preview Table</CardTitle>
                                        <CardDescription>
                                            A browser preview of the same data
                                            you can export.
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline">
                                        {preview.rows.length} row
                                        {preview.rows.length === 1 ? '' : 's'}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-lg border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                {preview.columns.map(
                                                    (column) => (
                                                        <TableHead
                                                            key={column.key}
                                                            className={
                                                                column.align ===
                                                                'right'
                                                                    ? 'text-right'
                                                                    : 'text-left'
                                                            }
                                                        >
                                                            {column.label}
                                                        </TableHead>
                                                    ),
                                                )}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {preview.rows.length === 0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={
                                                            preview.columns
                                                                .length
                                                        }
                                                        className="py-12 text-center text-muted-foreground"
                                                    >
                                                        {preview.empty_message}
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                preview.rows.map(
                                                    (row, index) => (
                                                        <TableRow
                                                            key={`preview-row-${index}`}
                                                        >
                                                            {preview.columns.map(
                                                                (column) => (
                                                                    <TableCell
                                                                        key={`${index}-${column.key}`}
                                                                        className={
                                                                            column.align ===
                                                                            'right'
                                                                                ? 'text-right'
                                                                                : 'text-left'
                                                                        }
                                                                    >
                                                                        {row[
                                                                            column
                                                                                .key
                                                                        ] ??
                                                                            '—'}
                                                                    </TableCell>
                                                                ),
                                                            )}
                                                        </TableRow>
                                                    ),
                                                )
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
