import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { type DispensingHistoryPageProps } from '@/types/pharmacy';
import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useState } from 'react';

const badgeTone = (value: string | null | undefined): string => {
    switch (value) {
        case 'posted':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'draft':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

export default function DispensingHistoryPage({
    navigation,
    records,
    filters,
}: DispensingHistoryPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                '/pharmacy/dispenses',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    from: from || undefined,
                    to: to || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timer);
    }, [search, status, from, to]);

    const exportUrl = () => {
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (status && status !== 'all') params.set('status', status);
        if (from) params.set('from', from);
        if (to) params.set('to', to);
        const qs = params.toString();
        return `/pharmacy/dispenses/export${qs ? `?${qs}` : ''}`;
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: 'Dispense History', href: '/pharmacy/dispenses' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dispense History" />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <h1 className="text-2xl font-semibold">Dispense History</h1>
                    <Button variant="outline" asChild>
                        <a href={exportUrl()} download>
                            <Download className="mr-2 h-4 w-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Search by number, patient, visit..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-60"
                    />
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="posted">Posted</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        type="date"
                        value={from}
                        onChange={(e) => setFrom(e.target.value)}
                        className="w-40"
                        title="From date"
                    />
                    <Input
                        type="date"
                        value={to}
                        onChange={(e) => setTo(e.target.value)}
                        className="w-40"
                        title="To date"
                    />
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Dispense #</TableHead>
                                <TableHead>Patient</TableHead>
                                <TableHead>Visit</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Dispensed By</TableHead>
                                <TableHead>Dispensed At</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {records.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="py-8 text-center text-sm text-muted-foreground"
                                    >
                                        No dispensing records found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                records.data.map((record) => (
                                    <TableRow key={record.id}>
                                        <TableCell className="font-medium">
                                            {record.dispense_number}
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                {record.patient_name ?? '-'}
                                            </div>
                                            {record.patient_number ? (
                                                <div className="text-xs text-muted-foreground">
                                                    {record.patient_number}
                                                </div>
                                            ) : null}
                                        </TableCell>
                                        <TableCell>
                                            {record.visit_number ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {record.inventory_location_name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {record.dispensed_by ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {record.dispensed_at
                                                ? new Date(
                                                      record.dispensed_at,
                                                  ).toLocaleString()
                                                : '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={badgeTone(
                                                    record.status,
                                                )}
                                            >
                                                {record.status_label ??
                                                    'Unknown'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/pharmacy/dispenses/${record.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {records.last_page > 1 ? (
                    <div className="flex justify-center gap-2">
                        {records.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={link.url === null}
                                onClick={() =>
                                    link.url && router.get(link.url)
                                }
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                ) : null}

                <p className="text-sm text-muted-foreground">
                    {records.total} record{records.total !== 1 ? 's' : ''}{' '}
                    total
                </p>
            </div>
        </AppLayout>
    );
}
