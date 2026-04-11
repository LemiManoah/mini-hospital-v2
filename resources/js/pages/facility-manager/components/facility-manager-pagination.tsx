import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';

import { type PaginationLinkItem } from '../types';

interface FacilityManagerPaginationProps {
    links: PaginationLinkItem[];
    prevPageUrl: string | null;
    nextPageUrl: string | null;
}

export function FacilityManagerPagination({
    links,
    prevPageUrl,
    nextPageUrl,
}: FacilityManagerPaginationProps) {
    return (
        <Pagination>
            <PaginationContent>
                <PaginationItem>
                    <PaginationPrevious href={prevPageUrl ?? undefined} />
                </PaginationItem>
                {links.map((link, idx) => {
                    const label = link.label
                        .replace('&laquo;', '')
                        .replace('&raquo;', '')
                        .trim();

                    if (label === '') {
                        return (
                            <PaginationItem key={`${idx}-${link.label}`}>
                                <PaginationEllipsis />
                            </PaginationItem>
                        );
                    }

                    return (
                        <PaginationItem key={`${idx}-${label}`}>
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
                    <PaginationNext href={nextPageUrl ?? undefined} />
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}
