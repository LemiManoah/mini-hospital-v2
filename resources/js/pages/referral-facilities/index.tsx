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
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type ReferralFacilityIndexPageProps } from '@/types/referral-facility';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/administration/master-data' },
    { title: 'Referral Facilities', href: '/referral-facilities' },
];

export default function ReferralFacilitiesIndex({
    referralFacilities,
    filters,
}: ReferralFacilityIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows = referralFacilities.data ?? [];
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/referral-facilities',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['referralFacilities', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Referral Facilities" />

            <div className="m-4 flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="w-full md:max-w-sm">
                        <Input
                            placeholder="Search by facility, type, phone, or email..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    {hasPermission('referral_facilities.create') ? (
                        <Button asChild>
                            <Link href="/referral-facilities/create">
                                + Add Referral Facility
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Facility</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((facility) => (
                                    <TableRow key={facility.id}>
                                        <TableCell className="font-medium">
                                            <div>
                                                <p>{facility.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {facility.address ?? '-'}
                                                </p>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {facility.facility_type ?? '-'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            <div>
                                                <p>{facility.phone ?? '-'}</p>
                                                <p>{facility.email ?? '-'}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    facility.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {facility.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {hasPermission(
                                                    'referral_facilities.update',
                                                ) ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/referral-facilities/${facility.id}/edit`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {hasPermission(
                                                    'referral_facilities.delete',
                                                ) ? (
                                                    <DeleteConfirmationModal
                                                        title="Delete Referral Facility"
                                                        description={`Are you sure you want to delete "${facility.name}"?`}
                                                        action={{
                                                            action: `/referral-facilities/${facility.id}`,
                                                            method: 'delete',
                                                        }}
                                                        onSuccess={() =>
                                                            toast.success(
                                                                `Referral facility "${facility.name}" deleted successfully.`,
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
                                                ) : null}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No referral facilities found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {referralFacilities.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                referralFacilities.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {referralFacilities.links.map(
                                        (link, idx) => {
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
                                                            href={
                                                                link.url ??
                                                                undefined
                                                            }
                                                            isActive={
                                                                link.active
                                                            }
                                                        >
                                                            {label}
                                                        </PaginationLink>
                                                    </PaginationItem>
                                                );
                                            }

                                            return null;
                                        },
                                    )}
                                    <PaginationItem>
                                        <PaginationNext
                                            href={
                                                referralFacilities.next_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        </div>
                    ) : null}
                </div>
            </div>
        </AppLayout>
    );
}
