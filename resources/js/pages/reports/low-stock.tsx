import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Download } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Low Stock Alerts', href: '/reports/low-stock' },
];

type StockStatus = 'out_of_stock' | 'critical' | 'low';

interface StockRow {
    item_id: string;
    item_name: string;
    dosage_info: string;
    unit: string | null;
    location_id: string;
    location_name: string;
    location_code: string;
    minimum_stock_level: number;
    reorder_level: number;
    quantity: number;
    status: StockStatus;
}

interface LocationOption {
    id: string;
    name: string;
    code: string | null;
}

interface ReportData {
    branch_name: string | null;
    total_alerts: number;
    critical_count: number;
    low_count: number;
    out_of_stock_count: number;
    selected_location_id: string | null;
    locations: LocationOption[];
    rows: StockRow[];
}

interface Props {
    report: ReportData | null;
    filters: {
        location_id: string | null;
    };
}

const STATUS_CONFIG: Record<StockStatus, { label: string; className: string }> =
    {
        out_of_stock: {
            label: 'Out of Stock',
            className: 'bg-red-100 text-red-800',
        },
        critical: {
            label: 'Critical',
            className: 'bg-orange-100 text-orange-800',
        },
        low: { label: 'Low', className: 'bg-yellow-100 text-yellow-800' },
    };

export default function LowStockAlertReport({ report, filters }: Props) {
    const [locationId, setLocationId] = useState(filters.location_id ?? 'all');

    function apply() {
        router.get(
            '/reports/low-stock',
            { location_id: locationId === 'all' ? undefined : locationId },
            { preserveScroll: true },
        );
    }

    function downloadPdf() {
        const query =
            locationId === 'all'
                ? ''
                : `?location_id=${encodeURIComponent(locationId)}`;

        window.location.href = `/reports/low-stock/download${query}`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Low Stock Alert Report" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="h-6 w-6 text-amber-600" />
                        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                            Low Stock Alert Report
                        </h1>
                    </div>
                </div>

                <div className="flex flex-wrap items-end gap-4 rounded-lg border bg-white p-4 dark:bg-gray-900">
                    <div className="space-y-1">
                        <Label htmlFor="location-filter">Location</Label>
                        <Select
                            value={locationId}
                            onValueChange={setLocationId}
                        >
                            <SelectTrigger
                                id="location-filter"
                                className="w-64"
                            >
                                <SelectValue placeholder="All locations" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All locations
                                </SelectItem>
                                {report?.locations.map((location) => (
                                    <SelectItem
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name}
                                        {location.code
                                            ? ` (${location.code})`
                                            : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button onClick={apply}>Apply</Button>
                    <Button
                        variant="outline"
                        onClick={downloadPdf}
                        className="ml-auto gap-2"
                    >
                        <Download className="h-4 w-4" />
                        Download PDF
                    </Button>
                </div>

                {report ? (
                    <>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Total Alerts
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-amber-600">
                                        {report.total_alerts}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Critical
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-orange-600">
                                        {report.critical_count}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Low
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {report.low_count}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Out of Stock
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-red-600">
                                        {report.out_of_stock_count}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="rounded-lg border bg-white dark:bg-gray-900">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-8">#</TableHead>
                                        <TableHead>Item Name</TableHead>
                                        <TableHead>Dosage / Form</TableHead>
                                        <TableHead>Location</TableHead>
                                        <TableHead className="text-right">
                                            Min. Level
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Reorder
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Current Qty
                                        </TableHead>
                                        <TableHead>Unit</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report.rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={9}
                                                className="py-10 text-center text-gray-400"
                                            >
                                                No low stock alerts found for
                                                the selected location.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        report.rows.map((row, index) => {
                                            const status =
                                                STATUS_CONFIG[row.status];

                                            return (
                                                <TableRow
                                                    key={`${row.item_id}-${row.location_id}`}
                                                >
                                                    <TableCell className="text-gray-400">
                                                        {index + 1}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {row.item_name}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-gray-500">
                                                        {row.dosage_info || '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {row.location_name}
                                                        <span className="ml-1 text-gray-400">
                                                            ({row.location_code}
                                                            )
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-right text-xs">
                                                        {
                                                            row.minimum_stock_level
                                                        }
                                                    </TableCell>
                                                    <TableCell className="text-right text-xs">
                                                        {row.reorder_level}
                                                    </TableCell>
                                                    <TableCell className="text-right font-bold">
                                                        {row.quantity.toFixed(
                                                            2,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {row.unit ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            className={`text-xs ${status.className} hover:${status.className}`}
                                                        >
                                                            {status.label}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                ) : (
                    <div className="rounded-lg border bg-white p-12 text-center text-gray-400 dark:bg-gray-900">
                        No active branch selected or no stock alerts available.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
