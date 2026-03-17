import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
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
import {
    type FacilityService,
    type FacilityServiceIndexPageProps,
} from '@/types/facility-service';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Services', href: '/facility-services' },
];

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function FacilityServiceIndex({
    facilityServices,
    filters,
}: FacilityServiceIndexPageProps) {
    const rows: FacilityService[] = Array.isArray(facilityServices)
        ? facilityServices
        : (facilityServices.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/facility-services',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['facilityServices', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Services" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Facility Services
                    </h2>
                    <Input
                        placeholder="Search services..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link href="/facility-services/create">
                        + Add Facility Service
                    </Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[1100px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Billing</TableHead>
                            <TableHead>Charge Master</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((facilityService) => (
                                <TableRow key={facilityService.id}>
                                    <TableCell>
                                        <div className="space-y-1">
                                            <p className="font-semibold">
                                                {facilityService.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {facilityService.description ||
                                                    'No description provided'}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-mono">
                                        {facilityService.service_code}
                                    </TableCell>
                                    <TableCell>
                                        {labelize(facilityService.category)}
                                    </TableCell>
                                    <TableCell>
                                        {facilityService.department_name || (
                                            <span className="italic opacity-50">
                                                Unassigned
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {facilityService.is_billable
                                                ? 'Billable'
                                                : 'Non-billable'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {facilityService.charge_master_id || (
                                            <span className="italic opacity-50">
                                                Not linked
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {facilityService.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/facility-services/${facilityService.id}/edit`}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <DeleteConfirmationModal
                                                title="Delete Facility Service"
                                                description={`Are you sure you want to delete "${facilityService.name}"? This action cannot be undone.`}
                                                action={{
                                                    method: 'delete',
                                                    action: `/facility-services/${facilityService.id}`,
                                                }}
                                                onSuccess={() =>
                                                    toast.success(
                                                        `Facility service "${facilityService.name}" deleted successfully.`,
                                                    )
                                                }
                                                trigger={
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                    >
                                                        Delete
                                                    </Button>
                                                }
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={8}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No facility services found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(facilityServices) &&
                facilityServices.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            facilityServices.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {facilityServices.links.map((link, idx) => {
                                    const label = link.label
                                        .replace(/<[^>]*>/g, '')
                                        .trim();
                                    if (label === '...') {
                                        return (
                                            <PaginationItem
                                                key={`ellipsis-${idx}`}
                                            >
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                        );
                                    }

                                    if (/^\d+$/.test(label)) {
                                        return (
                                            <PaginationItem key={label}>
                                                <PaginationLink
                                                    href={link.url ?? undefined}
                                                    isActive={link.active}
                                                >
                                                    {label}
                                                </PaginationLink>
                                            </PaginationItem>
                                        );
                                    }

                                    return null;
                                })}
                                <PaginationItem>
                                    <PaginationNext
                                        href={
                                            facilityServices.next_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
