import { Head, Link, usePage } from '@inertiajs/react';
import {
    ChevronRight,
    MonitorSmartphone,
    Palette,
    Plus,
    Projector,
    Settings,
    Wifi,
    WifiOff,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { index as devicesIndex } from '@/routes/devices';
import { edit as profileEdit } from '@/routes/profile';

type Tone = 'primary' | 'success' | 'danger' | 'info' | 'warning';

type Stats = {
    total: number;
    online: number;
    offline: number;
    unknown: number;
    projectors: number;
    wakeable: number;
};

type ActivityLog = {
    id: number;
    device: string;
    action: string;
    result: string;
    detail: string | null;
    created_at: string | null;
};

type PageProps = {
    stats: Stats;
    recentLogs: ActivityLog[];
};

const toneTile: Record<Tone, string> = {
    primary: 'bg-primary/10 text-primary',
    success: 'bg-success/10 text-success',
    danger: 'bg-danger/10 text-danger',
    info: 'bg-info/10 text-info',
    warning: 'bg-warning/10 text-warning',
};

function greetingFor(hour: number): string {
    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
}

function formatTimestamp(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

const quickActions: {
    label: string;
    icon: LucideIcon;
    tone: Tone;
    href: string;
}[] = [
    {
        label: 'View devices',
        icon: MonitorSmartphone,
        tone: 'info',
        href: devicesIndex().url,
    },
    {
        label: 'Add device',
        icon: Plus,
        tone: 'primary',
        href: devicesIndex().url,
    },
    {
        label: 'Settings',
        icon: Settings,
        tone: 'success',
        href: profileEdit().url,
    },
    {
        label: 'Appearance',
        icon: Palette,
        tone: 'warning',
        href: appearanceEdit().url,
    },
];

function StatCard({
    icon: Icon,
    label,
    value,
    tone,
    sub,
}: {
    icon: LucideIcon;
    label: string;
    value: number;
    tone: Tone;
    sub: React.ReactNode;
}) {
    return (
        <Card className="transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
            <CardContent className="p-4">
                <div className="flex items-center gap-2.5">
                    <span
                        className={cn(
                            'flex size-8 items-center justify-center rounded-lg',
                            toneTile[tone],
                        )}
                    >
                        <Icon className="size-4" />
                    </span>
                    <p className="text-xs font-medium text-muted-foreground">
                        {label}
                    </p>
                </div>
                <h3 className="mt-2 text-2xl font-bold tracking-tight">
                    {value}
                </h3>
                <p className="mt-1 text-xs text-muted-foreground">{sub}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ stats, recentLogs }: PageProps) {
    const { auth } = usePage<PageProps & { auth: { user: { name: string } } }>()
        .props;
    const firstName = auth.user.name.split(' ')[0];
    const greeting = greetingFor(new Date().getHours());

    const pct = (n: number) =>
        stats.total > 0 ? Math.round((n / stats.total) * 100) : 0;

    const segments: {
        label: string;
        count: number;
        bar: string;
        dot: string;
    }[] = [
        {
            label: 'Online',
            count: stats.online,
            bar: 'bg-success',
            dot: 'bg-success',
        },
        {
            label: 'Offline',
            count: stats.offline,
            bar: 'bg-danger',
            dot: 'bg-danger',
        },
        {
            label: 'Unknown',
            count: stats.unknown,
            bar: 'bg-neutral-400',
            dot: 'bg-neutral-400',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-4 p-4">
                {/* Greeting */}
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight sm:text-3xl">
                            {greeting}, {firstName} <span aria-hidden>👋</span>
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Here’s the current state of your device fleet.
                        </p>
                    </div>
                </div>

                {/* Quick actions */}
                <Card>
                    <CardContent className="flex flex-wrap items-center gap-1.5 p-2 sm:gap-2 sm:p-3">
                        {quickActions.map((action, i) => (
                            <Link
                                key={action.label}
                                href={action.href}
                                prefetch
                                className={cn(
                                    'group flex flex-1 items-center justify-center gap-2.5 rounded-lg px-3 py-2 whitespace-nowrap transition-colors hover:bg-accent sm:justify-start',
                                    i > 0 &&
                                        'sm:border-s sm:border-border sm:ps-4',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                        toneTile[action.tone],
                                    )}
                                >
                                    <action.icon className="size-5" />
                                </span>
                                <span className="text-sm font-semibold">
                                    {action.label}
                                </span>
                                <ChevronRight className="ms-auto size-4 text-muted-foreground transition-colors group-hover:text-primary" />
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                {/* Stat cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={MonitorSmartphone}
                        label="Total devices"
                        value={stats.total}
                        tone="primary"
                        sub={`${stats.wakeable} Wake-on-LAN capable`}
                    />
                    <StatCard
                        icon={Wifi}
                        label="Online"
                        value={stats.online}
                        tone="success"
                        sub={
                            <Badge
                                variant="secondary"
                                className="bg-success/15 text-success"
                            >
                                {pct(stats.online)}% reachable
                            </Badge>
                        }
                    />
                    <StatCard
                        icon={WifiOff}
                        label="Offline"
                        value={stats.offline}
                        tone="danger"
                        sub={`${stats.unknown} not yet checked`}
                    />
                    <StatCard
                        icon={Projector}
                        label="Projectors"
                        value={stats.projectors}
                        tone="info"
                        sub="PJLink controllable"
                    />
                </div>

                {/* Lower grid */}
                <div className="grid grid-cols-12 gap-4">
                    {/* Status overview */}
                    <Card className="col-span-12 xl:col-span-8">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">
                                Status overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                                {segments.map((s) =>
                                    s.count > 0 ? (
                                        <div
                                            key={s.label}
                                            className={s.bar}
                                            style={{
                                                width: `${pct(s.count)}%`,
                                            }}
                                        />
                                    ) : null,
                                )}
                            </div>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {segments.map((s) => (
                                    <div
                                        key={s.label}
                                        className="flex items-center justify-between rounded-lg border border-border p-3"
                                    >
                                        <span className="flex items-center gap-2 text-sm">
                                            <span
                                                className={cn(
                                                    'size-2.5 rounded-full',
                                                    s.dot,
                                                )}
                                            />
                                            {s.label}
                                        </span>
                                        <span className="text-sm font-semibold">
                                            {s.count}
                                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                                {pct(s.count)}%
                                            </span>
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent activity */}
                    <Card className="col-span-12 xl:col-span-4">
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <CardTitle className="text-base">
                                Recent activity
                            </CardTitle>
                            <Link
                                href={devicesIndex()}
                                className="text-xs text-primary hover:underline"
                            >
                                View all
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {recentLogs.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No actions logged yet.
                                </p>
                            ) : (
                                <ul className="space-y-3 text-sm">
                                    {recentLogs.map((log) => (
                                        <li
                                            key={log.id}
                                            className="flex items-start gap-2"
                                        >
                                            <Badge
                                                variant={
                                                    log.result === 'failure' ||
                                                    log.result === 'offline'
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {log.action}
                                            </Badge>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {log.device}
                                                </p>
                                                {log.detail && (
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        {log.detail}
                                                    </p>
                                                )}
                                            </div>
                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                {formatTimestamp(
                                                    log.created_at,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
