import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Boxes, Building2, Pill, ShieldAlert } from 'lucide-react';

interface InventoryDashboardProps {
    stats: {
        total_items: number;
        active_items: number;
        drug_items: number;
        expirable_items: number;
        total_locations: number;
        dispensing_locations: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
];

export default function InventoryDashboard({
    stats,
}: InventoryDashboardProps) {
    const { hasPermission } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory Dashboard" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Inventory Dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Track the stock catalog foundations before
                            procurement, dispensing, and movement workflows
                            land.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        {hasPermission('inventory_items.create') ? (
                            <Button asChild>
                                <Link href="/inventory-items/create">
                                    Add inventory item
                                </Link>
                            </Button>
                        ) : null}
                        {hasPermission('inventory_locations.create') ? (
                            <Button asChild variant="outline">
                                <Link href="/inventory-locations/create">
                                    Add location
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Items</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.total_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Boxes className="h-4 w-4" />
                            Catalog records created so far
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Active Items</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.active_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldAlert className="h-4 w-4" />
                            Available for future stock workflows
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Drug Items</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.drug_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Pill className="h-4 w-4" />
                            Medication records live directly in inventory
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Expirable Items</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.expirable_items}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldAlert className="h-4 w-4" />
                            Items that need expiry tracking
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Locations</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.total_locations}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Building2 className="h-4 w-4" />
                            Branch-scoped stores and stock points
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Dispensing Points</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.dispensing_locations}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Pill className="h-4 w-4" />
                            Locations marked for pharmacy fulfillment
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Inventory Items</CardTitle>
                            <CardDescription>
                                Create stockable items for drugs, consumables,
                                reagents, and general supplies.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex gap-3">
                            <Button asChild>
                                <Link href="/inventory-items">
                                    Open item catalog
                                </Link>
                            </Button>
                            {hasPermission('inventory_items.create') ? (
                                <Button asChild variant="outline">
                                    <Link href="/inventory-items/create">
                                        Create item
                                    </Link>
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Inventory Locations</CardTitle>
                            <CardDescription>
                                Define where stock will be held inside the
                                active branch before transfers, receipts, and
                                dispensing.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex gap-3">
                            <Button asChild>
                                <Link href="/inventory-locations">
                                    Open locations
                                </Link>
                            </Button>
                            {hasPermission('inventory_locations.create') ? (
                                <Button asChild variant="outline">
                                    <Link href="/inventory-locations/create">
                                        Create location
                                    </Link>
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
