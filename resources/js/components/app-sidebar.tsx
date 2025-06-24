import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, BookCheck, UserCog, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AppLogo from './app-logo';

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
    const {auth} =usePage().props;
    const user = auth.user as {id:number; name:string; role: 'SuperAdmin' | 'Admin' | 'Agent'} | null;

    const mainNavItems: NavItem[] = user?.role==='SuperAdmin'?[
    {
        title: ' Super Admin Dashboard',
        href: '/SuperAdminDashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Campaign',
        href: '/campaign',
        icon: BookCheck,
    },
    {
        title: 'User Management',
        href: '/users',
        icon: Users,
    },
]: user?.role === 'Admin'? [
    {
        title: 'Admin Dashboard',
        href: '/AdminDashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Campaign',
        href: '/campaign',
        icon: BookCheck,
    },
]: user?.role === 'Agent'? [
    {
        title: 'Agent Dashboard',
        href: '/Dashboard',
        icon: LayoutGrid,
    },
]: [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
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