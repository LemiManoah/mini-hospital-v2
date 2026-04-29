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
import {
    type ConsultationTariff,
    type ConsultationTariffIndexPageProps,
} from '@/types/consultation-tariff';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Consultation Tariffs', href: '/consultation-tariffs' },
];

const labelize = (value: string | null): string =>
    value === null
        ? 'All Visit Types'
        : value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function ConsultationTariffIndex({
    consultationTariffs,
    filters,
}: ConsultationTariffIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: ConsultationTariff[] = Array.isArray(consultationTariffs)
        ? consultationTariffs
        : (consultationTariffs.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/consultation-tariffs',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['consultationTariffs', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Consultation Tariffs" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight">
                        Consultation Tariffs
                    </h2>
                    <Input
                        placeholder="Search tariff services..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('consultation_tariffs.create') ? (
                    <Button asChild className="shrink-0">
                        <Link href="/consultation-tariffs/create">
                            + Add Consultation Tariff
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border bg-white p-4 dark:bg-zinc-900">
                <Table className="min-w-[960px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Consultation Type</TableHead>
                            <TableHead>Visit Type Scope</TableHead>
                            <TableHead>Billing Tariff</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((tariff) => (
                                <TableRow key={tariff.id}>
                                    <TableCell className="font-medium">
                                        {labelize(tariff.consultation_type)}
                                    </TableCell>
                                    <TableCell>
                                        {labelize(tariff.visit_type)}
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1">
                                            <p className="font-medium">
                                                {tariff.facility_service
                                                    ?.name ?? 'Unknown service'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {tariff.facility_service
                                                    ?.service_code ?? 'No code'}
                                                {tariff.facility_service
                                                    ?.selling_price !== null &&
                                                tariff.facility_service
                                                    ?.selling_price !==
                                                    undefined
                                                    ? ` - ${tariff.facility_service.selling_price}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {tariff.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'consultation_tariffs.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/consultation-tariffs/${tariff.id}/edit`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {hasPermission(
                                                'consultation_tariffs.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Consultation Tariff"
                                                    description={`Are you sure you want to delete the ${labelize(tariff.consultation_type)} mapping?`}
                                                    action={{
                                                        method: 'delete',
                                                        action: `/consultation-tariffs/${tariff.id}`,
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            'Consultation tariff deleted successfully.',
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
                                    className="py-12 text-center text-muted-foreground"
                                >
                                    No consultation tariffs found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(consultationTariffs) &&
                consultationTariffs.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            consultationTariffs.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {consultationTariffs.links.map((link, idx) => {
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
                                            consultationTariffs.next_page_url ??
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
