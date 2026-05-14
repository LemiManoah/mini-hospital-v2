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
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import {
    type ChargeMaster,
    type ChargeMasterIndexPageProps,
} from '@/types/charge-master';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Charge Master', href: '/charge-masters' },
];

const formatMoney = (value: number | string): string =>
    Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const labelize = (value: string | null): string =>
    value === null
        ? 'Unclassified'
        : value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function ChargeMasterIndex({
    chargeMasters,
    filters,
    billableTypeOptions,
}: ChargeMasterIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: ChargeMaster[] = Array.isArray(chargeMasters)
        ? chargeMasters
        : (chargeMasters.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            type === (filters.type || 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/charge-masters',
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['chargeMasters', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, type, filters.search, filters.type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Charge Master" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 lg:flex-row lg:items-center">
                <div className="flex w-full flex-col gap-3 lg:max-w-3xl lg:flex-row">
                    <div className="flex-1">
                        <h2 className="text-2xl font-bold tracking-tight">
                            Charge Master
                        </h2>
                        <Input
                            placeholder="Search code or description..."
                            className="mt-2"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <div className="w-full lg:w-56">
                        <div className="mb-2 h-7" />
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger>
                                <SelectValue placeholder="All billable types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All billable types
                                </SelectItem>
                                {billableTypeOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <div className="m-2 overflow-x-auto rounded border bg-white p-4 dark:bg-zinc-900">
                <Table className="min-w-[980px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Code</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Current Price</TableHead>
                            <TableHead>Effective</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((chargeMaster) => (
                                <TableRow key={chargeMaster.id}>
                                    <TableCell className="font-mono">
                                        {chargeMaster.item_code}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {chargeMaster.description}
                                    </TableCell>
                                    <TableCell>
                                        {labelize(chargeMaster.billable_type)}
                                    </TableCell>
                                    <TableCell>
                                        {formatMoney(chargeMaster.unit_price)}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {chargeMaster.effective_from ?? 'Any'}
                                        {' to '}
                                        {chargeMaster.effective_to ?? 'Open'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {chargeMaster.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {hasPermission(
                                            'charge_masters.update',
                                        ) ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/charge-masters/${chargeMaster.id}/edit`}
                                                >
                                                    Edit Price
                                                </Link>
                                            </Button>
                                        ) : null}
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="py-12 text-center text-muted-foreground"
                                >
                                    No charge master rows found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(chargeMasters) &&
                chargeMasters.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            chargeMasters.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {chargeMasters.links.map((link, idx) => {
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

                                    return (
                                        <PaginationItem key={`${label}-${idx}`}>
                                            <PaginationLink
                                                href={link.url ?? '#'}
                                                isActive={link.active}
                                            >
                                                {label}
                                            </PaginationLink>
                                        </PaginationItem>
                                    );
                                })}
                                <PaginationItem>
                                    <PaginationNext
                                        href={
                                            chargeMasters.next_page_url ??
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
