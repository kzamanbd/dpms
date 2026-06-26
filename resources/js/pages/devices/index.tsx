import { Head, router, usePoll } from '@inertiajs/react';
import {
    Activity,
    Pencil,
    Plus,
    Power,
    PowerOff,
    Trash2,
    Wifi,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import DeviceActionController from '@/actions/App/Http/Controllers/DeviceActionController';
import DeviceController from '@/actions/App/Http/Controllers/DeviceController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DeviceFormModal } from '@/components/device-form-modal';
import Heading from '@/components/heading';
import { NetworkScanModal } from '@/components/network-scan-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as devicesIndex } from '@/routes/devices';
import type { ActivityLog, Device, DeviceStatus } from '@/types';

type PageProps = {
    devices: Device[];
    recentLogs: ActivityLog[];
};

const statusVariant: Record<
    DeviceStatus,
    'default' | 'destructive' | 'secondary'
> = {
    online: 'default',
    offline: 'destructive',
    unknown: 'secondary',
};

const statusDotClass: Record<DeviceStatus, string> = {
    online: 'bg-emerald-500',
    offline: 'bg-red-500',
    unknown: 'bg-neutral-400',
};

function formatTimestamp(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export default function DevicesIndex({ devices, recentLogs }: PageProps) {
    // Refresh status + activity on the same ~30s cadence as the monitoring loop.
    usePoll(30000, { only: ['devices', 'recentLogs'] });

    const [busy, setBusy] = useState<string | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [scanOpen, setScanOpen] = useState(false);
    const [editing, setEditing] = useState<Device | null>(null);
    const [pending, setPending] = useState<{
        kind: 'power-off' | 'remove';
        device: Device;
    } | null>(null);

    function openCreate() {
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(device: Device) {
        setEditing(device);
        setFormOpen(true);
    }

    function runAction(key: string, url: string) {
        setBusy(key);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setBusy(null),
            },
        );
    }

    function confirmPending() {
        if (!pending) {
            return;
        }

        const { kind, device } = pending;

        if (kind === 'power-off') {
            runAction(
                `${device.id}:off`,
                DeviceActionController.powerOff(device.id).url,
            );
            setPending(null);

            return;
        }

        setBusy(`${device.id}:delete`);
        router.delete(DeviceController.destroy(device.id).url, {
            preserveScroll: true,
            onFinish: () => setBusy(null),
        });
        setPending(null);
    }

    return (
        <>
            <Head title="Devices" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Devices"
                        description="Live reachability, projector control (PJLink), and Wake-on-LAN for the POC test set."
                    />
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setScanOpen(true)}
                        >
                            <Wifi /> Scan network
                        </Button>
                        <Button onClick={openCreate}>
                            <Plus /> Add device
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    {devices.map((device) => (
                        <Card key={device.id} className="flex flex-col">
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between gap-2">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <span
                                            className={`size-2.5 rounded-full ${statusDotClass[device.status]}`}
                                            aria-hidden
                                        />
                                        {device.name}
                                    </CardTitle>
                                    <Badge
                                        variant={statusVariant[device.status]}
                                    >
                                        {device.status}
                                    </Badge>
                                </div>
                                <div className="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                    <Badge variant="outline">
                                        {device.type_label}
                                    </Badge>
                                    <span>{device.ip}</span>
                                    {device.vlan && (
                                        <span>VLAN {device.vlan}</span>
                                    )}
                                    {device.is_cross_vlan && (
                                        <Badge variant="secondary">
                                            cross-VLAN
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>

                            <CardContent className="flex flex-1 flex-col justify-between gap-4">
                                <dl className="space-y-1 text-xs text-muted-foreground">
                                    <div className="flex justify-between gap-2">
                                        <dt>Last seen</dt>
                                        <dd className="text-foreground">
                                            {formatTimestamp(device.last_seen)}
                                        </dd>
                                    </div>
                                    {device.mac && (
                                        <div className="flex justify-between gap-2">
                                            <dt>MAC</dt>
                                            <dd className="font-mono text-foreground">
                                                {device.mac}
                                            </dd>
                                        </div>
                                    )}
                                    {device.last_action && (
                                        <div className="mt-2 rounded-md bg-muted p-2 text-foreground">
                                            <span className="font-medium">
                                                {device.last_action.action}
                                            </span>{' '}
                                            <span
                                                className={
                                                    device.last_action
                                                        .result === 'failure'
                                                        ? 'text-red-500'
                                                        : 'text-emerald-600 dark:text-emerald-400'
                                                }
                                            >
                                                ({device.last_action.result})
                                            </span>
                                            {device.last_action.detail && (
                                                <p className="mt-0.5 text-muted-foreground">
                                                    {device.last_action.detail}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </dl>

                                <div className="flex flex-wrap gap-2">
                                    {device.supports_pjlink && (
                                        <>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                disabled={
                                                    busy === `${device.id}:on`
                                                }
                                                onClick={() =>
                                                    runAction(
                                                        `${device.id}:on`,
                                                        DeviceActionController.powerOn(
                                                            device.id,
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Power /> On
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                disabled={
                                                    busy === `${device.id}:off`
                                                }
                                                onClick={() =>
                                                    setPending({
                                                        kind: 'power-off',
                                                        device,
                                                    })
                                                }
                                            >
                                                <PowerOff /> Off
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                disabled={
                                                    busy ===
                                                    `${device.id}:status`
                                                }
                                                onClick={() =>
                                                    runAction(
                                                        `${device.id}:status`,
                                                        DeviceActionController.status(
                                                            device.id,
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Activity /> Status
                                            </Button>
                                        </>
                                    )}
                                    {device.can_wake && (
                                        <Button
                                            size="sm"
                                            disabled={
                                                busy === `${device.id}:wake`
                                            }
                                            onClick={() =>
                                                runAction(
                                                    `${device.id}:wake`,
                                                    DeviceActionController.wake(
                                                        device.id,
                                                    ).url,
                                                )
                                            }
                                        >
                                            <Zap /> Wake
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="ml-auto"
                                        onClick={() => openEdit(device)}
                                    >
                                        <Pencil /> Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="text-destructive hover:text-destructive"
                                        disabled={
                                            busy === `${device.id}:delete`
                                        }
                                        onClick={() =>
                                            setPending({
                                                kind: 'remove',
                                                device,
                                            })
                                        }
                                    >
                                        <Trash2 /> Remove
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Recent activity
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentLogs.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No actions logged yet.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border text-sm">
                                {recentLogs.map((log) => (
                                    <li
                                        key={log.id}
                                        className="flex flex-wrap items-baseline justify-between gap-2 py-2"
                                    >
                                        <span className="flex items-center gap-2">
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
                                            <span className="font-medium">
                                                {log.device}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {log.detail}
                                            </span>
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {formatTimestamp(log.created_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <DeviceFormModal
                open={formOpen}
                onOpenChange={setFormOpen}
                device={editing}
            />

            <NetworkScanModal open={scanOpen} onOpenChange={setScanOpen} />

            <ConfirmDialog
                open={pending !== null}
                onOpenChange={(open) => !open && setPending(null)}
                title={
                    pending?.kind === 'power-off'
                        ? `Power off ${pending.device.name}?`
                        : `Remove ${pending?.device.name}?`
                }
                description={
                    pending?.kind === 'power-off'
                        ? 'The device will be sent a power-off command and go offline.'
                        : 'This permanently removes the device and its action history from DPMS.'
                }
                confirmLabel={
                    pending?.kind === 'power-off' ? 'Power off' : 'Remove'
                }
                onConfirm={confirmPending}
            />
        </>
    );
}

DevicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Devices',
            href: devicesIndex(),
        },
    ],
};
