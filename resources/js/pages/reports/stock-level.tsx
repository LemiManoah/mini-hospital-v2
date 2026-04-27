import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Download, Package } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Stock Level', href: '/reports/stock-level' },
];

type StockStatus = 'out_of_stock' | 'critical' | 'low' | 'ok';

interface StockRow {
    item_id: string;
    item_name: string;
    dosage_info: string;
    unit: string | null;
    location_name: string;
    location_code: string;
    minimum_stock_level: number;
    reorder_level: number;
    quantity: number;
    status: StockStatus;
}

interface ReportData {
    branch_name: string | null;
    total_items: number;
    low_stock_count: number;
    out_of_stock_count: number;
    locations: { id: string; name: string; code: string }[];
    rows: StockRow[];
}

interface Props {
    report: ReportData | null;
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
        ok: { label: 'OK', className: 'bg-green-100 text-green-800' },
    };

export default function StockLevelReport({ report }: Props) {
    function refresh() {
        router.get('/reports/stock-level', {}, { preserveScroll: true });
    }

    function downloadPdf() {
        window.location.href = '/reports/stock-level/download';
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stock Level Report" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Package className="h-6 w-6 text-blue-600" />
                        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                            Stock Level Report
                        </h1>
                    </div>
                </div>

                {/* Action bar */}
                <div className="flex flex-wrap items-center gap-3 rounded-lg border bg-white p-4 dark:bg-gray-900">
                    <Button onClick={refresh} variant="outline">
                        Refresh
                    </Button>
                    <Button onClick={downloadPdf} className="ml-auto gap-2">
                        <Download className="h-4 w-4" />
                        Download PDF (Landscape)
                    </Button>
                </div>

                {report ? (
                    <>
                        {/* KPI cards */}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Total Items
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {report.total_items}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Low / Critical
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {report.low_stock_count}
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
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Adequate
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-green-600">
                                        {report.total_items -
                                            report.low_stock_count -
                                            report.out_of_stock_count}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Stock table */}
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
                                                No stock data found for the
                                                active branch.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        report.rows.map((row, i) => {
                                            const statusCfg =
                                                STATUS_CONFIG[row.status];
                                            return (
                                                <TableRow
                                                    key={`${row.item_id}-${i}`}
                                                >
                                                    <TableCell className="text-gray-400">
                                                        {i + 1}
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
                                                            className={`text-xs ${statusCfg.className} hover:${statusCfg.className}`}
                                                        >
                                                            {statusCfg.label}
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
                        No active branch selected or no stock data available.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
