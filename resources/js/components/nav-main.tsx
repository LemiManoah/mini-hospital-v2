'use client';

import { Link, usePage, type InertiaLinkProps } from '@inertiajs/react';
import { ChevronRight, type LucideIcon } from 'lucide-react';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';

type NavHref = NonNullable<InertiaLinkProps['href']>;

export interface NavMainItem {
    title: string;
    url: NavHref;
    icon?: LucideIcon;
    isActive?: boolean;
    items?: NavMainItem[];
}

export function NavMain({ items }: { items: NavMainItem[] }) {
    const page = usePage();

    const normalizeHref = (href: NavHref): string => {
        if (typeof href === 'string') {
            return href;
        }

        if ('url' in href && typeof href.url === 'string') {
            return href.url;
        }

        return '';
    };

    const isActive = (href: NavHref): boolean => {
        const url = normalizeHref(href);
        if (url === '') {
            return false;
        }

        return page.url === url || page.url.startsWith(`${url}/`);
    };

    const isItemActive = (item: NavMainItem): boolean =>
        item.isActive ??
        (isActive(item.url) ||
            Boolean(item.items?.some((child) => isItemActive(child))));

    return (
        <SidebarGroup>
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const hasChildren = (item.items?.length ?? 0) > 0;
                    const active = isItemActive(item);

                    if (!hasChildren) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    tooltip={item.title}
                                    isActive={active}
                                >
                                    <Link href={item.url} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <Collapsible
                            key={item.title}
                            asChild
                            defaultOpen={active}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        tooltip={item.title}
                                        isActive={active}
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.items?.map((subItem) => (
                                            <NavSubItem
                                                key={subItem.title}
                                                item={subItem}
                                                isActive={isActive}
                                                isItemActive={isItemActive}
                                            />
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}

function NavSubItem({
    item,
    isActive,
    isItemActive,
}: {
    item: NavMainItem;
    isActive: (href: NavHref) => boolean;
    isItemActive: (item: NavMainItem) => boolean;
}) {
    const hasChildren = (item.items?.length ?? 0) > 0;

    if (!hasChildren) {
        return (
            <SidebarMenuSubItem>
                <SidebarMenuSubButton asChild isActive={isActive(item.url)}>
                    <Link href={item.url} prefetch className="truncate">
                        <span>{item.title}</span>
                    </Link>
                </SidebarMenuSubButton>
            </SidebarMenuSubItem>
        );
    }

    const active = isItemActive(item);

    return (
        <Collapsible
            asChild
            defaultOpen={active}
            className="group/sub-collapsible"
        >
            <SidebarMenuSubItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuSubButton isActive={active}>
                        <span className="truncate">{item.title}</span>
                        <ChevronRight className="ml-auto size-3 transition-transform duration-200 group-data-[state=open]/sub-collapsible:rotate-90" />
                    </SidebarMenuSubButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub className="mx-2.5 mt-1 px-2">
                        {item.items?.map((child) => (
                            <SidebarMenuSubItem key={child.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    size="sm"
                                    isActive={isActive(child.url)}
                                >
                                    <Link
                                        href={child.url}
                                        prefetch
                                        className="truncate"
                                    >
                                        <span>{child.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuSubItem>
        </Collapsible>
    );
}
