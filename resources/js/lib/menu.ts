import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    BookOpen,
    Gauge,
    LayoutDashboard,
    MonitorSmartphone,
    Palette,
    Settings,
    ShieldCheck,
    User,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { index as devicesIndex } from '@/routes/devices';
import { index as docsIndex } from '@/routes/docs';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';
import { index as statusIndex } from '@/routes/status';

export type MenuBadge = {
    text: string;
    className?: string;
};

export type MenuNode = {
    label: string;
    heading?: boolean;
    icon?: LucideIcon;
    href?: string;
    /** Render as a plain anchor (non-Inertia route, e.g. Horizon) opened in a new tab. */
    external?: boolean;
    badge?: MenuBadge;
    children?: MenuNode[];
};

/**
 * Sidebar / horizontal-menu navigation, ported to the Vantyx structure
 * (sections, collapsible groups, badges). Links resolve through Wayfinder so
 * only real routes are referenced.
 */
export const menu: MenuNode[] = [
    { label: 'Platform', heading: true },
    {
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: dashboard().url,
    },
    {
        label: 'Devices',
        icon: MonitorSmartphone,
        href: devicesIndex().url,
        badge: { text: 'Live', className: 'bg-success/15 text-success' },
    },
    { label: 'System', heading: true },
    {
        label: 'Status',
        icon: Activity,
        href: statusIndex().url,
    },
    {
        label: 'User guide',
        icon: BookOpen,
        href: docsIndex().url,
    },
    {
        label: 'Horizon',
        icon: Gauge,
        href: '/horizon',
        external: true,
    },
    { label: 'Account', heading: true },
    {
        label: 'Settings',
        icon: Settings,
        children: [
            { label: 'Profile', icon: User, href: profileEdit().url },
            { label: 'Security', icon: ShieldCheck, href: securityEdit().url },
            { label: 'Appearance', icon: Palette, href: appearanceEdit().url },
        ],
    },
];
