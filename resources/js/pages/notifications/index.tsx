import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import { Button } from '@/components/ui/button';
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
    type Notification,
    type NotificationIndexPageProps,
} from '@/types/notification';
import { Head, Link, router } from '@inertiajs/react';
import { Bell, Check, CheckCheck, ExternalLink, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: NotificationController.index.url() },
];

const formatDateTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

function typeLabel(type: string | null): string {
    switch (type) {
        case 'lab_result_released':
            return 'Laboratory';
        case 'prescription_created':
            return 'Pharmacy';
        case 'inventory_requisition_submitted':
            return 'Inventory';
        default:
            return 'System';
    }
}

function typeBadgeClass(type: string | null): string {
    switch (type) {
        case 'lab_result_released':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'prescription_created':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'inventory_requisition_submitted':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        default:
            return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
    }
}

function markAsRead(notification: Notification): void {
    router.post(
        NotificationController.markAsRead.url(notification.id),
        {},
        { preserveScroll: true, only: ['notifications'] },
    );
}

function markAllAsRead(): void {
    router.post(
        NotificationController.markAllAsRead.url(),
        {},
        { preserveScroll: true, only: ['notifications'] },
    );
}

function deleteNotification(notification: Notification): void {
    router.delete(NotificationController.destroy.url(notification.id), {
        preserveScroll: true,
        only: ['notifications'],
    });
}

export default function NotificationsIndex({
    notifications,
}: NotificationIndexPageProps) {
    const rows: Notification[] = notifications.data ?? [];
    const hasUnread = rows.some((n) => n.read_at === null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex items-center gap-3">
                    <Bell className="size-6 text-zinc-500" />
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Notifications
                    </h2>
                </div>

                {hasUnread && (
                    <Button
                        variant="outline"
                        size="sm"
                        className="shrink-0 gap-2 border-zinc-200 shadow-sm dark:border-zinc-800"
                        onClick={markAllAsRead}
                    >
                        <CheckCheck className="size-4" />
                        Mark all as read
                    </Button>
                )}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[700px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Type
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Notification
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Received
                            </TableHead>
                            <TableHead className="text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((notification) => (
                                <TableRow
                                    key={notification.id}
                                    className={
                                        notification.read_at === null
                                            ? 'bg-blue-50/50 dark:bg-blue-950/10'
                                            : ''
                                    }
                                >
                                    <TableCell>
                                        <span
                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${typeBadgeClass(notification.type)}`}
                                        >
                                            {typeLabel(notification.type)}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-0.5">
                                            <span
                                                className={`text-sm font-semibold ${notification.read_at === null ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400'}`}
                                            >
                                                {notification.title ?? '—'}
                                            </span>
                                            <span className="text-xs text-zinc-500 dark:text-zinc-500">
                                                {notification.message ?? '—'}
                                            </span>
                                            {notification.action_url && (
                                                <Link
                                                    href={
                                                        notification.action_url
                                                    }
                                                    className="mt-0.5 inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                                >
                                                    <ExternalLink className="size-3" />
                                                    View
                                                </Link>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-xs text-zinc-500 dark:text-zinc-500">
                                        {formatDateTime(
                                            notification.created_at,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {notification.read_at === null && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 gap-1 border-zinc-200 px-2 text-xs shadow-sm hover:border-emerald-500 hover:text-emerald-600 dark:border-zinc-800"
                                                    onClick={() =>
                                                        markAsRead(notification)
                                                    }
                                                >
                                                    <Check className="size-3" />
                                                    Mark read
                                                </Button>
                                            )}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 gap-1 border-zinc-200 px-2 text-xs shadow-sm hover:border-red-400 hover:text-red-600 dark:border-zinc-800"
                                                onClick={() =>
                                                    deleteNotification(
                                                        notification,
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-3" />
                                                Delete
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-12 text-center text-zinc-400"
                                >
                                    <Bell className="mx-auto mb-2 size-8 opacity-30" />
                                    <p className="text-sm">
                                        You have no notifications.
                                    </p>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {notifications.links && notifications.links.length > 3 && (
                <div className="mt-4 flex justify-center pb-6">
                    <Pagination>
                        <PaginationContent>
                            {notifications.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            notifications.prev_page_url ?? '#'
                                        }
                                    />
                                </PaginationItem>
                            )}
                            {notifications.links
                                .filter(
                                    (l) =>
                                        ![
                                            '&laquo; Previous',
                                            'Next &raquo;',
                                        ].includes(l.label),
                                )
                                .map((link, i) =>
                                    link.label === '...' ? (
                                        <PaginationItem key={i}>
                                            <PaginationEllipsis />
                                        </PaginationItem>
                                    ) : (
                                        <PaginationItem key={i}>
                                            <PaginationLink
                                                href={link.url ?? '#'}
                                                isActive={link.active}
                                            >
                                                {link.label}
                                            </PaginationLink>
                                        </PaginationItem>
                                    ),
                                )}
                            {notifications.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext
                                        href={
                                            notifications.next_page_url ?? '#'
                                        }
                                    />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                </div>
            )}
        </AppLayout>
    );
}
