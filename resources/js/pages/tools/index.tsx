import { Head, router } from '@inertiajs/react';
import { CheckCircle2, RefreshCw, TriangleAlert, XCircle } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { index as toolsIndex } from '@/routes/tools';

type CheckStatus = 'ok' | 'warn' | 'error';

type Check = {
    group: string;
    label: string;
    status: CheckStatus;
    required: boolean;
    value: string;
    hint: string;
};

type PageProps = {
    checks: Check[];
    summary: { ok: number; warn: number; error: number };
};

const statusMeta: Record<
    CheckStatus,
    { icon: typeof CheckCircle2; className: string; label: string }
> = {
    ok: { icon: CheckCircle2, className: 'text-success', label: 'Pass' },
    warn: { icon: TriangleAlert, className: 'text-warning', label: 'Warning' },
    error: { icon: XCircle, className: 'text-danger', label: 'Fail' },
};

function StatusIcon({ status }: { status: CheckStatus }) {
    const { icon: Icon, className } = statusMeta[status];

    return <Icon className={cn('size-5 shrink-0', className)} />;
}

export default function ToolsIndex({ checks, summary }: PageProps) {
    const [refreshing, setRefreshing] = useState(false);

    const groups = checks.reduce<Record<string, Check[]>>((acc, check) => {
        (acc[check.group] ??= []).push(check);

        return acc;
    }, {});

    const rerun = () => {
        setRefreshing(true);
        router.reload({
            only: ['checks', 'summary'],
            onFinish: () => setRefreshing(false),
        });
    };

    const allPass = summary.error === 0;

    return (
        <>
            <Head title="System tools" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="System tools"
                        description="Verify this host meets the requirements to run DPMS and reach devices."
                    />
                    <Button
                        variant="outline"
                        onClick={rerun}
                        disabled={refreshing}
                    >
                        <RefreshCw
                            className={cn(refreshing && 'animate-spin')}
                        />{' '}
                        Re-run checks
                    </Button>
                </div>

                {/* Summary */}
                <Card
                    className={cn(
                        'border-l-4',
                        allPass ? 'border-l-success' : 'border-l-danger',
                    )}
                >
                    <CardContent className="flex flex-wrap items-center gap-4 p-4">
                        <p className="text-sm font-medium">
                            {allPass
                                ? 'All required checks passed — the host is ready to run DPMS.'
                                : 'Some required checks failed — resolve them before running DPMS.'}
                        </p>
                        <div className="ms-auto flex items-center gap-2 text-sm">
                            <Badge
                                variant="secondary"
                                className="bg-success/15 text-success"
                            >
                                {summary.ok} pass
                            </Badge>
                            <Badge
                                variant="secondary"
                                className="bg-warning/15 text-warning"
                            >
                                {summary.warn} warning
                            </Badge>
                            <Badge
                                variant="secondary"
                                className="bg-danger/15 text-danger"
                            >
                                {summary.error} fail
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                {/* Grouped checks */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {Object.entries(groups).map(([group, items]) => (
                        <Card key={group}>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">
                                    {group}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y divide-border">
                                    {items.map((check) => (
                                        <li
                                            key={check.label}
                                            className="flex items-start gap-3 py-3 first:pt-0 last:pb-0"
                                        >
                                            <StatusIcon status={check.status} />
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium">
                                                        {check.label}
                                                    </span>
                                                    {!check.required && (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-[10px]"
                                                        >
                                                            optional
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {check.hint}
                                                </p>
                                            </div>
                                            <span
                                                className={cn(
                                                    'shrink-0 font-mono text-xs',
                                                    statusMeta[check.status]
                                                        .className,
                                                )}
                                            >
                                                {check.value}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

ToolsIndex.layout = {
    breadcrumbs: [
        {
            title: 'System tools',
            href: toolsIndex(),
        },
    ],
};
