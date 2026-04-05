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
import { type InventoryItemShowPageProps } from '@/types/inventory-item';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';

export default function InventoryItemShow({
    inventoryItem,
}: InventoryItemShowPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Items', href: '/inventory-items' },
        { title: inventoryItem.name, href: `/inventory-items/${inventoryItem.id}` },
    ];

    const labelize = (value: string | null): string =>
        value
            ? value
                  .replaceAll('_', ' ')
                  .replace(/\b\w/g, (letter) => letter.toUpperCase())
            : 'Not set';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Inventory Item: ${inventoryItem.name}`} />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {inventoryItem.generic_name ?? inventoryItem.name}
                        </h1>
                        {inventoryItem.brand_name && (
                            <p className="text-muted-foreground">
                                Brand: {inventoryItem.brand_name}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/inventory-items">Back to List</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/inventory-items/${inventoryItem.id}/edit`}>
                                Edit Item
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Item Type
                                </span>
                                <span>{labelize(inventoryItem.item_type)}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Unit
                                </span>
                                <span>
                                    {inventoryItem.unit
                                        ? `${inventoryItem.unit.name} (${inventoryItem.unit.symbol})`
                                        : 'Not set'}
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Manufacturer
                                </span>
                                <span>{inventoryItem.manufacturer ?? 'N/A'}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Status
                                </span>
                                <div>
                                    <Badge variant={inventoryItem.is_active ? 'default' : 'secondary'}>
                                        {inventoryItem.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-1">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Expiring
                                </span>
                                <div>
                                    <Badge variant={inventoryItem.expires ? 'default' : 'secondary'}>
                                        {inventoryItem.expires ? 'Yes' : 'No'}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Stock Thresholds</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Minimum Stock Level
                                </span>
                                <span>{inventoryItem.minimum_stock_level}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Reorder Level
                                </span>
                                <span>{inventoryItem.reorder_level}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Default Purchase Price
                                </span>
                                <span>{inventoryItem.default_purchase_price ?? 'N/A'}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Default Selling Price
                                </span>
                                <span>{inventoryItem.default_selling_price ?? 'N/A'}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {inventoryItem.item_type === 'drug' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Drug Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Category
                                </span>
                                <span>{labelize(inventoryItem.category)}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1 border-b pb-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Dosage Form
                                </span>
                                <span>{labelize(inventoryItem.dosage_form)}</span>
                            </div>
                            <div className="grid grid-cols-2 gap-1">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Strength
                                </span>
                                <span>{inventoryItem.strength ?? 'N/A'}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Inventory Batches</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Batch Number</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Expiry Date</TableHead>
                                    <TableHead className="text-right">Unit Cost</TableHead>
                                    <TableHead className="text-right">Received</TableHead>
                                    <TableHead>Received At</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {inventoryItem.batches && inventoryItem.batches.length > 0 ? (
                                    inventoryItem.batches.map((batch) => (
                                        <TableRow key={batch.id}>
                                            <TableCell className="font-medium">
                                                {batch.batch_number ?? 'N/A'}
                                            </TableCell>
                                            <TableCell>{batch.location?.name ?? 'N/A'}</TableCell>
                                            <TableCell>
                                                {batch.expiry_date
                                                    ? format(new Date(batch.expiry_date), 'MMM d, yyyy')
                                                    : 'Non-expiring'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {batch.unit_cost}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {batch.quantity_received}
                                            </TableCell>
                                            <TableCell>
                                                {format(new Date(batch.received_at), 'MMM d, yyyy HH:mm')}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center text-muted-foreground">
                                            No active batches found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Stock Movements</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead className="text-right">Quantity</TableHead>
                                    <TableHead className="text-right">Unit Cost</TableHead>
                                    <TableHead>Performed By</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {inventoryItem.stock_movements && inventoryItem.stock_movements.length > 0 ? (
                                    inventoryItem.stock_movements.map((movement) => (
                                        <TableRow key={movement.id}>
                                            <TableCell>
                                                {format(new Date(movement.occurred_at), 'MMM d, yyyy HH:mm')}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {labelize(movement.movement_type)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{movement.location?.name ?? 'N/A'}</TableCell>
                                            <TableCell className="text-right font-medium">
                                                {parseFloat(movement.quantity) > 0 ? '+' : ''}
                                                {movement.quantity}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {movement.unit_cost ?? 'N/A'}
                                            </TableCell>
                                            <TableCell>{movement.user?.name ?? 'System'}</TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center text-muted-foreground">
                                            No stock movements found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
