"use client"

import { Link, usePage, type InertiaLinkProps } from "@inertiajs/react"
import { ChevronRight, type LucideIcon } from "lucide-react"

import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"

type NavHref = NonNullable<InertiaLinkProps["href"]>

interface NavMainItem {
  title: string
  url: NavHref
  icon?: LucideIcon
  isActive?: boolean
  items?: {
    title: string
    url: NavHref
  }[]
}

export function NavMain({ items }: { items: NavMainItem[] }) {
  const page = usePage()

  const normalizeHref = (href: NavHref): string => {
    if (typeof href === "string") {
      return href
    }

    if ("url" in href && typeof href.url === "string") {
      return href.url
    }

    return ""
  }

  const isActive = (href: NavHref): boolean => {
    const url = normalizeHref(href)
    if (url === "") {
      return false
    }

    return page.url === url || page.url.startsWith(`${url}/`)
  }

  return (
    <SidebarGroup>
      <SidebarGroupLabel>Platform</SidebarGroupLabel>
      <SidebarMenu>
        {items.map((item) => {
          const hasChildren = (item.items?.length ?? 0) > 0
          const active =
            item.isActive ??
            (isActive(item.url) || Boolean(item.items?.some((subItem) => isActive(subItem.url))))

          if (!hasChildren) {
            return (
              <SidebarMenuItem key={item.title}>
                <SidebarMenuButton asChild tooltip={item.title} isActive={active}>
                  <Link href={item.url} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            )
          }

          return (
            <Collapsible key={item.title} asChild defaultOpen={active} className="group/collapsible">
              <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                  <SidebarMenuButton tooltip={item.title} isActive={active}>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                    <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    {item.items?.map((subItem) => (
                      <SidebarMenuSubItem key={subItem.title}>
                        <SidebarMenuSubButton asChild isActive={isActive(subItem.url)}>
                          <Link href={subItem.url} prefetch className="truncate">
                            <span>{subItem.title}</span>
                          </Link>
                        </SidebarMenuSubButton>
                      </SidebarMenuSubItem>
                    ))}
                  </SidebarMenuSub>
                </CollapsibleContent>
              </SidebarMenuItem>
            </Collapsible>
          )
        })}
      </SidebarMenu>
    </SidebarGroup>
  )
}
