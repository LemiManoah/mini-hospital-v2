import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as indexAddresses } from '@/routes/addresses';
import { index as indexAllergens } from '@/routes/allergens';
import { index as indexCurrencies } from '@/routes/currencies';
import { index as indexRoles } from '@/routes/roles';
import { index as indexSubscriptionPackages } from '@/routes/subscription-packages';

import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    Coins,
    FlaskConical,
    Folder,
    LayoutGrid,
    MapPin,
    Package,
    Shield,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Roles Management',
        href: indexRoles(),
        icon: Shield,
    },
    {
        title: 'Users',
        href: '/users',
        icon: UserCog,
    },
    {
        title: 'Staff',
        href: '/staff',
        icon: Users,
    },
    {
        title: 'Staff Positions',
        href: '/staff-positions',
        icon: Shield,
    },
    {
        title: 'Departments',
        href: '/departments',
        icon: Building2,
    },
    {
        title: 'Currencies',
        href: indexCurrencies(),
        icon: Coins,
    },
    {
        title: 'Subscription Packages',
        href: indexSubscriptionPackages(),
        icon: Package,
    },
    {
        title: 'Allergens',
        href: indexAllergens(),
        icon: FlaskConical,
    },
    {
        title: 'Addresses',
        href: indexAddresses(),
        icon: MapPin,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
