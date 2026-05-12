# Sidebar Nested Children Implementation Plan

## Goal

Allow sidebar child links to optionally have their own children so related pages can sit under the workflow they belong to instead of being listed flatly or hidden behind low-value landing pages.

Examples:

- `Administration -> Insurance Setup -> Companies / Packages`
- `Administration -> Master Data -> Units / Currencies / Services`
- `Inventory -> Procurement -> Suppliers / Purchase Orders / Goods Receipts`
- `Appointments -> Configuration -> Categories / Modes / Schedule Exceptions`

## Current Shape

- Main sidebar items are assembled in `resources/js/components/app-sidebar.tsx`.
- Rendering happens in `resources/js/components/nav-main.tsx`.
- The current renderer supports:
    - top-level sidebar item
    - one child level through `item.items`
- The shared `NavItem` type in `resources/js/types/index.d.ts` is currently flat and does not model children.
- Permission filtering is done before rendering through `filterItems()` in `app-sidebar.tsx`.

## Recommended Scope

Support two visible levels below a top-level sidebar section:

```txt
Top Level
  Child
    Grandchild
```

Do not introduce unlimited visual nesting yet. A recursive data model is fine, but the UI should render only one extra level at first because sidebars become hard to scan when they go deeper.

## Data Model

Update the nav item shape so children can be nested:

```ts
type SidebarNavItem = {
    title: string;
    url: NavHref;
    icon?: LucideIcon;
    isActive?: boolean;
    permission?: string;
    permissions?: string[];
    items?: SidebarNavItem[];
};
```

Implementation notes:

- Keep `url` required for now so every expandable row still has a sensible active-route anchor.
- Keep `permission` and `permissions` on every level.
- Filter recursively so a parent remains visible when it has any visible child.
- Strip permission fields before passing to the pure renderer if desired, but this is optional.

## Rendering Plan

1. Update `NavMainItem` in `resources/js/components/nav-main.tsx` to allow `items?: NavMainItem[]`.
2. Add helpers:
    - `normalizeHref(href)`
    - `isActive(href)`
    - `isItemActive(item)` that checks the item and descendants.
3. Keep top-level rendering mostly as-is.
4. Inside each top-level collapsible, render child rows.
5. For children with grandchildren:
    - render the child as a nested collapsible row inside `SidebarMenuSub`
    - show a small `ChevronRight`
    - render grandchildren as a second indented list
6. For children without grandchildren:
    - render the current `SidebarMenuSubButton` link.

## Permission Filtering Plan

Replace the current `filterItems()` with a recursive version:

```ts
function filterNavItems(
    items: SidebarNavItem[],
    hasPermission: (permission: string) => boolean,
): SidebarNavItem[] {
    return items
        .map((item) => ({
            ...item,
            items: item.items
                ? filterNavItems(item.items, hasPermission)
                : undefined,
        }))
        .filter((item) => {
            const canSeeSelf = item.permission
                ? hasPermission(item.permission)
                : item.permissions
                  ? item.permissions.some(hasPermission)
                  : true;

            return canSeeSelf || (item.items?.length ?? 0) > 0;
        });
}
```

This keeps a grouping item visible when the user can access at least one child, even if the grouping row itself has no direct permission.

## Suggested First Navigation Restructure

Start conservatively with areas that are already conceptually grouped.

### Administration

```txt
Administration
  General Settings
  Insurance Setup
    Companies
    Packages
  Master Data
    Branches
    Clinics
    Departments
    Facility Services
    Units
    Currencies
  Reports
  Data Upload
  Platform
```

### Appointments

```txt
Appointments
  Bookings
  Queue
  My Appointments
  Schedules
  Configuration
    Categories
    Modes
    Schedule Exceptions
```

### Inventory

```txt
Inventory
  Dashboard
  Stock
    Items
    Locations
    Stock By Location
    Stock Movements
  Procurement
    Suppliers
    Purchase Orders
    Goods Receipts
  Requisitions
    Incoming Requisitions
  Reconciliations
```

### Finance & Accounting

```txt
Finance & Accounting
  Incoming OPD Payments
  Insurance
    Insurance Invoices
  Accounts
    Deposits
    Debtors
  Reports
    Billing Summary
    Daily Revenue
```

## UX Details

- Automatically open a parent branch when the current route matches any descendant.
- Keep click targets predictable:
    - rows with children toggle open/closed
    - leaf rows navigate
- Use the existing `ChevronRight` rotation pattern for nested collapsibles.
- Avoid adding a landing page only for grouping unless it contains useful dashboard content.
- When the sidebar is collapsed to icon mode, rely on the top-level tooltip and hide deeper nested content as the current sidebar primitives already do.

## Testing Plan

Run at minimum:

```powershell
& 'C:\Users\Manoah\AppData\Local\OpenAI\Codex\bin\node.exe' node_modules\typescript\bin\tsc --noEmit
```

If the nav restructure changes route imports or permission behavior, also run focused feature tests for pages whose route helpers were touched.

## Implementation Steps

1. Introduce a recursive sidebar nav type near `app-sidebar.tsx` or in `resources/js/types/index.d.ts`.
2. Replace `filterItems()` with recursive `filterNavItems()`.
3. Update `NavMain` to support children on child rows.
4. Move one low-risk section first, preferably `Appointments -> Configuration`.
5. Run TypeScript.
6. Restructure larger sections after the renderer is proven.

## Risks

- Over-nesting can hide frequent workflows, so keep the initial pass shallow.
- Parent active state must include descendants or users will lose orientation.
- Permission filtering must not show empty parent groups.
- Very long labels should remain truncated so the sidebar width stays stable.
