import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
} from './types';

interface BranchRow {
    id: string;
    name: string;
    branch_code: string | null;
    status: string | null;
    is_main_branch: boolean;
    has_store: boolean;
    staff_count: number;
    currency: string | null;
    address: string;
}

interface FacilityManagerBranchesProps {
    tenant: FacilityManagerTenantSummary;
    metrics: FacilityManagerMetric[];
    branches: BranchRow[];
}

export default function FacilityManagerBranches({
    tenant,
    metrics,
    branches,
}: FacilityManagerBranchesProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Branches',
            href: `/facility-manager/facilities/${tenant.id}/branches`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Branches`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link
                            href={`/facility-manager/facilities/${tenant.id}`}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Overview
                        </Link>
                    </Button>
                </div>

                <FacilityManagerTenantHeader tenant={tenant} />

                <FacilityManagerNav tenantId={tenant.id} current="branches" />

                <FacilityManagerMetrics metrics={metrics} />

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Branch List</CardTitle>
                        <CardDescription>
                            All facility branches configured for this tenant.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Branch</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Staff</TableHead>
                                    <TableHead>Store</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Address</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {branches.length > 0 ? (
                                    branches.map((branch) => (
                                        <TableRow key={branch.id}>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <span className="font-medium">
                                                        {branch.name}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {branch.branch_code ??
                                                            'No code'}
                                                        {branch.is_main_branch
                                                            ? ' | Main branch'
                                                            : ''}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {branch.status ?? 'unknown'}
                                            </TableCell>
                                            <TableCell>
                                                {branch.staff_count}
                                            </TableCell>
                                            <TableCell>
                                                {branch.has_store
                                                    ? 'Enabled'
                                                    : 'Not enabled'}
                                            </TableCell>
                                            <TableCell>
                                                {branch.currency ?? 'Not set'}
                                            </TableCell>
                                            <TableCell>
                                                {branch.address || 'Not set'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-12 text-center text-sm text-muted-foreground"
                                        >
                                            No branches are configured for this
                                            tenant yet.
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
