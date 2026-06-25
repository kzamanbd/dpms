import { router } from '@inertiajs/react';
import { RefreshCw, Wifi } from 'lucide-react';
import { useState } from 'react';
import DeviceScanController from '@/actions/App/Http/Controllers/DeviceScanController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { ScanCandidate, ScanResult } from '@/types';

type Row = ScanCandidate & { selected: boolean };

export function NetworkScanModal({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [loading, setLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [result, setResult] = useState<ScanResult | null>(null);
    const [rows, setRows] = useState<Row[]>([]);

    const runScan = () => {
        setLoading(true);
        setResult(null);
        setRows([]);

        fetch(DeviceScanController.scan().url, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json() as Promise<ScanResult>)
            .then((data) => {
                setResult(data);
                setRows(
                    data.candidates.map((candidate) => ({
                        ...candidate,
                        // Pre-select fresh hosts; leave already-known ones off.
                        selected: !candidate.existing,
                    })),
                );
            })
            .catch(() => {
                setResult({
                    subnet: null,
                    interface_ip: null,
                    host_count: 0,
                    alive_count: 0,
                    candidates: [],
                    error: 'The scan request failed. Check the server logs.',
                });
            })
            .finally(() => setLoading(false));
    };

    const update = (ip: string, patch: Partial<Row>) => {
        setRows((current) =>
            current.map((row) => (row.ip === ip ? { ...row, ...patch } : row)),
        );
    };

    const selected = rows.filter((row) => row.selected);

    const importSelected = () => {
        setImporting(true);
        router.post(
            DeviceScanController.import().url,
            {
                devices: selected.map((row) => ({
                    name: row.name,
                    type: row.type,
                    ip: row.ip,
                    mac: row.mac,
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setImporting(false),
            },
        );
    };

    const close = (next: boolean) => {
        if (!next) {
            // Reset so the next open starts from a clean prompt.
            setResult(null);
            setRows([]);
        }

        onOpenChange(next);
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Wifi className="size-5" /> Scan network
                    </DialogTitle>
                    <DialogDescription>
                        {result?.subnet
                            ? `Found ${result.alive_count} reachable host${result.alive_count === 1 ? '' : 's'} on ${result.subnet} (server ${result.interface_ip}).`
                            : 'Discover devices on the server’s local subnet by ICMP ping sweep + ARP.'}
                    </DialogDescription>
                </DialogHeader>

                {!loading && !result && (
                    <div className="flex flex-col items-center gap-3 py-10 text-center">
                        <p className="text-sm text-muted-foreground">
                            The server auto-detects its subnet and looks for
                            reachable hosts. This can take a few seconds.
                        </p>
                        <Button onClick={runScan}>
                            <Wifi /> Scan now
                        </Button>
                    </div>
                )}

                {loading && (
                    <div className="flex items-center justify-center gap-2 py-12 text-sm text-muted-foreground">
                        <Spinner /> Sweeping the subnet — this can take a few
                        seconds.
                    </div>
                )}

                {!loading && result?.error && (
                    <p className="py-8 text-center text-sm text-destructive">
                        {result.error}
                    </p>
                )}

                {!loading && result && !result.error && rows.length === 0 && (
                    <p className="py-8 text-center text-sm text-muted-foreground">
                        No reachable hosts found on {result.subnet}.
                    </p>
                )}

                {!loading && rows.length > 0 && (
                    <div className="max-h-[50vh] divide-y divide-border overflow-y-auto">
                        {rows.map((row) => (
                            <div
                                key={row.ip}
                                className="flex items-center gap-3 py-2.5"
                            >
                                <Checkbox
                                    checked={row.selected}
                                    onCheckedChange={(checked) =>
                                        update(row.ip, {
                                            selected: checked === true,
                                        })
                                    }
                                    aria-label={`Select ${row.ip}`}
                                />
                                <Input
                                    value={row.name}
                                    onChange={(event) =>
                                        update(row.ip, {
                                            name: event.target.value,
                                        })
                                    }
                                    className="h-8 max-w-44"
                                />
                                <div className="min-w-0 flex-1 text-xs text-muted-foreground">
                                    <div className="font-mono text-foreground">
                                        {row.ip}
                                    </div>
                                    <div className="font-mono">
                                        {row.mac ?? 'MAC unknown'}
                                    </div>
                                    {row.vendor && (
                                        <div>{row.vendor}</div>
                                    )}
                                </div>
                                {row.supports_pjlink && (
                                    <Badge variant="secondary">PJLink</Badge>
                                )}
                                {row.existing && (
                                    <Badge variant="outline">known</Badge>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {result && (
                    <DialogFooter className="gap-2 sm:justify-between">
                        <Button
                            variant="outline"
                            onClick={runScan}
                            disabled={loading || importing}
                        >
                            <RefreshCw
                                className={loading ? 'animate-spin' : ''}
                            />{' '}
                            Rescan
                        </Button>
                        <Button
                            onClick={importSelected}
                            disabled={
                                loading || importing || selected.length === 0
                            }
                        >
                            {importing && <Spinner />}
                            Import {selected.length > 0 && `(${selected.length})`}
                        </Button>
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}
