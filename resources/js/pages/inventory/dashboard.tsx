import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartConfig,
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Boxes, Building2, Pill, ShieldAlert, TrendingUp } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    XAxis,
} from 'recharts';

interface InventoryDashboardProps {
    stats: {
        out_of_stock: number;
        low_stock: number;
        expiring_soon: number;
        total_value: number;
        total_items: number;
        active_items: number;
        drug_items: number;
        expirable_items: number;
        total_locations: number;
        dispensing_locations: number;
        total_suppliers: number;
        distribution_by_type: Record<string, number>;
        distribution_by_category: Record<string, number>;
        recent_items: Array<{
            id: string;
            name: string;
            generic_name?: string;
            item_type: string;
            created_at: string;
        }>;
        po_stats: Record<string, number>;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
];

const COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export default function InventoryDashboard({ stats }: InventoryDashboardProps) {
    const { hasPermission } = usePermissions();

    const typeData = Object.entries(stats.distribution_by_type).map(
        ([name, value], index) => ({
            name: name.charAt(0).toUpperCase() + name.slice(1),
            value,
            fill: COLORS[index % COLORS.length],
        }),
    );

    const poData = Object.entries(stats.po_stats).map(([status, count]) => ({
        status: status.charAt(0).toUpperCase() + status.slice(1),
        count,
    }));

    const typeConfig = {
        value: {
            label: 'Items',
        },
        ...Object.fromEntries(
            typeData.map((d, i) => [
                d.name,
                { label: d.name, color: COLORS[i % COLORS.length] },
            ]),
        ),
    } satisfies ChartConfig;

    const poConfig = {
        count: {
            label: 'Orders',
            color: 'var(--chart-2)',
        },
    } satisfies ChartConfig;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory Dashboard" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-2">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                Inventory Analytics
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Critical business metrics and stock health
                                overview.
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        {hasPermission('inventory_items.create') ? (
                            <Button asChild>
                                <Link href="/inventory-items/create">
                                    Add Item
                                </Link>
                            </Button>
                        ) : null}
                        {hasPermission('purchase_orders.create') ? (
                            <Button asChild variant="outline">
                                <Link href="/purchase-orders/create">
                                    Raise PO
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Row 1 */}
                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-destructive uppercase">
                                Out of Stock
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-destructive">
                                {stats.out_of_stock}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <ShieldAlert className="h-3.5 w-3.5" />
                                <span>Zero balance items</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-amber-600 uppercase">
                                Low Stock
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-amber-600">
                                {stats.low_stock}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <TrendingUp className="h-3.5 w-3.5" />
                                <span>Below minimum level</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-orange-600 uppercase">
                                Expiring Soon
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-orange-600">
                                {stats.expiring_soon}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <ShieldAlert className="h-3.5 w-3.5" />
                                <span>Expiring within 30 days</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-primary uppercase">
                                Total Stock Value
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-primary">
                                {new Intl.NumberFormat('en-UG', {
                                    style: 'currency',
                                    currency: 'UGX',
                                    maximumFractionDigits: 0,
                                }).format(stats.total_value)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Boxes className="h-3.5 w-3.5" />
                                <span>Monetary value of inventory</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Row 2 */}
                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider uppercase">
                                Active Suppliers
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold">
                                {stats.total_suppliers}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Building2 className="h-3.5 w-3.5" />
                                <span>Registered vendors</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider uppercase">
                                Total Catalog
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold">
                                {stats.total_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Boxes className="h-3.5 w-3.5" />
                                <span>Unique SKU records</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider uppercase">
                                Pharmaceuticals
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold">
                                {stats.drug_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Pill className="h-3.5 w-3.5" />
                                <span>Medication items</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider uppercase">
                                Storage Nodes
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold">
                                {stats.total_locations}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Building2 className="h-3.5 w-3.5" />
                                <span>Active locations</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="flex flex-col border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Item Distribution</CardTitle>
                            <CardDescription>
                                Breakdown by inventory item type
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex-1 pb-0">
                            <ChartContainer
                                config={typeConfig}
                                className="mx-auto aspect-square max-h-[300px]"
                            >
                                <PieChart>
                                    <ChartTooltip
                                        cursor={false}
                                        content={
                                            <ChartTooltipContent hideLabel />
                                        }
                                    />
                                    <Pie
                                        data={typeData}
                                        dataKey="value"
                                        nameKey="name"
                                        innerRadius={60}
                                        strokeWidth={5}
                                    >
                                        {typeData.map((entry, index) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={entry.fill}
                                            />
                                        ))}
                                    </Pie>
                                    <ChartLegend
                                        content={
                                            <ChartLegendContent nameKey="name" />
                                        }
                                        className="-translate-y-2 flex-wrap gap-2 [&>*]:basis-1/4 [&>*]:justify-center"
                                    />
                                </PieChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>

                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Procurement Status</CardTitle>
                            <CardDescription>
                                Overview of purchase orders by lifecycle status
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={poConfig}
                                className="aspect-auto h-[300px] w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={poData}
                                    margin={{
                                        top: 20,
                                    }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="status"
                                        tickLine={false}
                                        tickMargin={10}
                                        axisLine={false}
                                    />
                                    <ChartTooltip
                                        cursor={false}
                                        content={
                                            <ChartTooltipContent hideLabel />
                                        }
                                    />
                                    <Bar
                                        dataKey="count"
                                        fill="var(--color-count)"
                                        radius={8}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="col-span-2 border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Recently Added Items</CardTitle>
                                <CardDescription>
                                    The latest catalog additions.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/inventory-items">View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="relative w-full overflow-auto">
                                <table className="w-full caption-bottom text-sm">
                                    <thead className="[&_tr]:border-b">
                                        <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                            <th className="h-10 px-2 text-left align-middle font-medium text-muted-foreground">
                                                Item Name
                                            </th>
                                            <th className="h-10 px-2 text-left align-middle font-medium text-muted-foreground">
                                                Type
                                            </th>
                                            <th className="h-10 px-2 text-right align-middle font-medium text-muted-foreground">
                                                Created
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="[&_tr:last-child]:border-0">
                                        {stats.recent_items.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted"
                                            >
                                                <td className="p-2 align-middle">
                                                    <div className="font-medium">
                                                        {item.name}
                                                    </div>
                                                    {item.generic_name && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.generic_name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-2 align-middle">
                                                    <span className="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none">
                                                        {item.item_type}
                                                    </span>
                                                </td>
                                                <td className="p-2 text-right align-middle text-muted-foreground">
                                                    {new Date(
                                                        item.created_at,
                                                    ).toLocaleDateString()}
                                                </td>
                                            </tr>
                                        ))}
                                        {stats.recent_items.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={3}
                                                    className="p-4 text-center text-muted-foreground"
                                                >
                                                    No items recently added.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg">
                                    Quick Management
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/inventory-items">
                                        <Pill className="mr-2 h-4 w-4" />
                                        Open Item Catalog
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/inventory-locations">
                                        <Building2 className="mr-2 h-4 w-4" />
                                        Manage Storage Nodes
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/suppliers">
                                        <Building2 className="mr-2 h-4 w-4" />
                                        Supplier Directory
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/purchase-orders">
                                        <Boxes className="mr-2 h-4 w-4" />
                                        Purchase History
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg text-primary">
                                    Stock Foundation
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Ensure your catalog is complete with correct
                                    unit types, reorder levels, and tax settings
                                    before initiating stock movements.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
